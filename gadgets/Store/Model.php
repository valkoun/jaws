<?php
/**
 * Store Gadget
 *
 * @category   GadgetModel
 * @package    Store
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreModel extends Jaws_Model
{
    var $_Name = 'Store';
    
	/**
     * Has the Atom pointer to create the RSS/XML files
     *
     * @var    AtomFeed
     * @access private
     */
    var $_Atom;

    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetProductParent($id)
    {
        $params = array();
		$sql = '
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code], [productparentchecksum]
            FROM [[productparent]]';

        if (is_numeric($id)) {
            $sql .= ' WHERE [productparentid] = {id}';
			 $params['id']       = (int)$id;
       } else {
            $sql .= ' WHERE [productparentfast_url] = {id}';
			 $params['id']       = $id;
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['productparentid'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
    }
    
    /**
     * Gets a user productparent by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the product and false on error
     */
    function GetSingleProductParentByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code], [productparentchecksum]
			FROM [[productparent]]
            WHERE ([productparentownerid] = {id} AND [productparentid] = {cid})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }


		return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single page by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetProductParentByChecksum($checksum)
    {
        $params 			= array();
		$params['checksum']	= $checksum;
		$sql = '
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code], [productparentchecksum]
            FROM [[productparent]] WHERE [productparentchecksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['productparentid'])) {
            return $row;
        }


        return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
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
    function GetProductParents($limit = null, $sortColumn = 'productparentsort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('productparentsort_order', 'productparentownerid', 'productparentcategory_name', 
		'productparentcreated', 'productparentupdated', 'productparentactive');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'productparentsort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code], [productparentchecksum]
            FROM [[productparent]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [productparentownerid] = {owner_id}";
		} else {
			$sql .= " WHERE [productparentownerid] = 0";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
			$result = $GLOBALS['db']->setLimit(10, $offSet);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENTS_NOT_RETRIEVED'), _t('STORE_NAME'));
			}
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENTS_NOT_RETRIEVED'), _t('STORE_NAME'));
			}
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENTS_NOT_RETRIEVED'), _t('STORE_NAME'));
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
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code], [productparentchecksum]
			FROM [[productparent]] WHERE [productparentparent] = {id}
			ORDER BY [productparentsort_order] ASC, [productparentcategory_name] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result) || !isset($result[0]['productparentid']) || empty($result[0]['productparentid'])) {
            //add language word for this
            //return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
            return array();
        }
		
        return $result;
    }

    /**
     * Returns recursive propertyparents with the given parent ID
     *
     * @access  public
     * @return  array  Array with all the propertyparent IDs or Jaws_Error on error
     */
    function GetRecursiveSubCategoriesOfParent($id)
    {
		$categories = array();
		
		$result = $this->GetAllSubCategoriesOfParent($id);
		$categories = array_merge($categories, $result);
		if (isset($result[0]['productparentid']) && !empty($result[0]['productparentid'])) {
			foreach ($result as $res) {
				$categories = array_merge($categories, $this->GetRecursiveSubCategoriesOfParent($res['productparentid']));
			}
		}
		return $categories;
    }

    /**
     * Gets the users property parents by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetProductParentsByUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code], [productparentchecksum]
			FROM [[productparent]]
            WHERE ([productparentownerid] = {id})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENTS_NOT_RETRIEVED'), _t('STORE_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single product by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetProduct($id)
    {
        $params             = array();
        //$params['language'] = $language;
		$sql = '
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
            FROM [[product]]';

        if (is_numeric($id)) {
            $sql .= ' WHERE [id] = {id}';
			$params['id']       = (int)$id;
        } else {
            $sql .= ' WHERE [fast_url] = {id}';
			$params['id']       = $id;
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);


        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND').' [ID: '.$id.']', _t('STORE_NAME'));
    }

    /**
     * Gets a user product by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the product and false on error
     */
    function GetSingleProductByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
			FROM [[product]]
            WHERE ([ownerid] = {id} AND [id] = {cid})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

		return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a product by checksum
     *
     * @access  public
     * @param   int     $checksum  The checksum
     * @return  mixed   Returns an array with the product and false on error
     */
    function GetProductByChecksum($checksum)
    {
		$params       		= array();
        $params['checksum'] = $checksum;
		
		$sql = '
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
			FROM [[product]]
            WHERE ([checksum] = {checksum})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }


		//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
        return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND').' [checksum: '.$checksum.']', _t('STORE_NAME'));
    }

    /**
     * Gets an index of all the products.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetProducts(
		$limit = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null, 
		$active = null, $return = null, $search = '', $featured = null, $brandid = '', $sales = '', $category = '', 
		$attributes = '', $random_seed = ''
	) {
        $GLOBALS['db']->dbc->loadModule('Function', null, true);
		$fields = array('sort_order', 'premium', 'price', 'brandid', 'featured', 'ownerid', 'title', 'created', 'updated', 'active', 'attribute');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
			$sort_ascending = false;
        } else {
			$sort_ascending = true;
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
            FROM [[product]]
			WHERE ([title] != '')
		";

		$params = array();
		if (!is_null($OwnerID)) {
			$sql .=  " AND ([ownerid] = {OwnerID})";
			$params['OwnerID'] = (int)$OwnerID;
		}
		if (!is_null($featured)) {
			$sql .=  " AND ([featured] = {featured})";
			$params['featured'] = $featured;
		}
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		if (trim($sales) != '') {
			$sales = explode(',', $sales);
			$sql .= " AND (";
			$i = 0;
			foreach ($sales as $sale) {
				$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
				$params['sale_'.$i] = $sale;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($attributes) != '') {
			$attributes = explode(',', $attributes);
			$sql .= " AND (";
			$i = 0;
			foreach ($attributes as $attribute) {
				$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
				$params['attribute_'.$i] = $attribute;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($brandid) != '') {
			$sql .= " AND ([brandid] = {brandid})";
			$params['brandid'] = (int)$brandid;
		}
		if (trim($category) != '') {
			$sqlcategory = $GLOBALS['db']->dbc->function->lower('[category]');
			$sql .= " AND ($sqlcategory = {category}";
			$params['category'] = strtolower($category);
			/*
			$parentID = $category;
			$parent = $this->GetProductParent($category);
			if (!Jaws_Error::IsError($parent) && isset($parent['productparentparent'])) {
				$parentID = $parent['productparentparent'];
			}
			*/
		}
		if (!is_null($return) && trim($search) != '') {
			$return = strtolower($return);
			$sql .=  " AND (([$return] LIKE {search}) OR ([title] LIKE {titlesearch}))";
			$params['search'] = $search.'%';
			$params['titlesearch'] = '%'.$search.'%';
		} else if (trim($search) != '') {
			$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
				([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
				([company] LIKE {s}) OR ([company_email] LIKE {s}))";
			$params['s'] = '%'.$search.'%';
		}
		
		if (trim($random_seed) != '') {
			$sql .= " ORDER BY rand(".(int)$random_seed.")";
		} else {
			$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'sort_order' ? ", [image] DESC, [premium] DESC" : '');
		}
		
		if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit, $offSet);
                if (Jaws_Error::IsError($result)) {
                    //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
					return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
                }
            }
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				//return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
				return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
			}
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
            return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
        }
       	   
		return $result;
    }

    /**
     * Gets an index of all the products.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetTotalOfProducts(
		$sortColumn = 'sort_order', $sortDir = 'ASC', $OwnerID = null, $active = null, 
		$return = null, $search = '', $featured = null, $brandid = '', $sales = '', $category = '', 
		$attributes = ''
	) {
        $GLOBALS['db']->dbc->loadModule('Function', null, true);

        $sql = "
            SELECT COUNT([id])
			FROM [[product]]
			WHERE ([title] != '')
		";

		$params = array();
		if (!is_null($OwnerID)) {
			$sql .=  " AND ([ownerid] = {OwnerID})";
			$params['OwnerID'] = (int)$OwnerID;
		}
		if (!is_null($featured)) {
			$sql .=  " AND ([featured] = {featured})";
			$params['featured'] = $featured;
		}
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		if (trim($sales) != '') {
			$sales = explode(',', $sales);
			$sql .= " AND (";
			$i = 0;
			foreach ($sales as $sale) {
				$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
				$params['sale_'.$i] = $sale;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($attributes) != '') {
			$attributes = explode(',', $attributes);
			$sql .= " AND (";
			$i = 0;
			foreach ($attributes as $attribute) {
				$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
				$params['attribute_'.$i] = $attribute;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($brandid) != '') {
			$sql .= " AND ([brandid] = {brandid})";
			$params['brandid'] = (int)$brandid;
		}
		if (trim($category) != '' && ($pid === null && $recursive === false)) {
			$parent_category = $GLOBALS['db']->dbc->function->lower('[category]');
			$sql .= " AND ($parent_category = {category})";
			$params['category'] = strtolower($category);
		}
		if (!is_null($return) && trim($search) != '') {
			$return = strtolower($return);
			$sql .=  " AND (([$return] LIKE {search}) OR ([title] LIKE {titlesearch}))";
			$params['search'] = $search.'%';
			$params['titlesearch'] = '%'.$search.'%';
		} else if (trim($search) != '') {
			$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
				([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
				([company] LIKE {s}) OR ([company_email] LIKE {s}))";
			$params['s'] = '%'.$search.'%';
		}
		$res = $GLOBALS['db']->queryOne($sql, $params);
		$total = (Jaws_Error::IsError($res) ? 0 : (int)$res);
        			
		return $total;
    }

    /**
     * Returns all products that belongs to a parent
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllProductsOfParent(
		$id, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $OwnerID = null, 
		$search = '', $brandid = '', $sales = '', $category = '', $attributes = '', 
		$limit = null, $offSet = null, $random_seed = '', $recursive = false
	) {
		$parents = array();
        if (!is_numeric($id)) {
			$parent = $this->GetProductParent($id);
			if (Jaws_Error::IsError($parent)) {
				return new Jaws_Error($parent->GetMessage(), _t('STORE_NAME'));
			} else if (!isset($parent['productparentid']) || empty($parent['productparentid'])) {
				return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
			} else {
				$id = $parent['productparentid'];
			}
		}
		$parents[] = $id;
		if ($recursive === true) {
			$productparents = $this->GetRecursiveSubCategoriesOfParent($id);
			if (!Jaws_Error::IsError($productparents) && isset($productparents[0]['productparentid']) && !empty($productparents[0]['productparentid'])) {
				foreach ($productparents as $productparent) {
					$parents[] = $productparent['productparentid'];
				}
			}
		}
        $GLOBALS['db']->dbc->loadModule('Function', null, true);
        $fields = array('sort_order', 'premium', 'price', 'brandid', 'featured', 'ownerid', 'title', 'created', 'updated', 'active', 'attribute');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);

		$sql  = "
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [[product]].[created], [[product]].[updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
			FROM [[products_parents]]
            INNER JOIN [[product]] ON [[products_parents]].[prod_id] = [[product]].[id]
			WHERE ([title] != '')
		";

		$params = array();
		if ($recursive === true) {
			$p = 0;
			$sql .= " AND (";
			foreach ($parents as $par) {
				$sql .= ($p > 0 ? " OR " : '')."([[products_parents]].[parent_id] = {id".$p."})";
				$params['id'.$p] = (int)$par;
				$p++;
			}
			$sql .=  ")";
		} else {
			$params['id'] = (int)$id;
			$sql .=  " AND ([[products_parents]].[parent_id] = {id})";
		}
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		if (!is_null($OwnerID)) {
			$sql .=  " AND ([ownerid] = {OwnerID})";
			$params['OwnerID'] = (int)$OwnerID;
		}
		if (trim($sales) != '') {
			$sales = explode(',', $sales);
			$sql .= " AND (";
			$i = 0;
			foreach ($sales as $sale) {
				$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
				$params['sale_'.$i] = $sale;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($attributes) != '') {
			$attributes = explode(',', $attributes);
			$sql .= " AND (";
			$i = 0;
			foreach ($attributes as $attribute) {
				$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
				$params['attribute_'.$i] = $attribute;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($brandid) != '') {
			$sql .= " AND ([brandid] = {brandid})";
			$params['brandid'] = (int)$brandid;
		}
		if (trim($category) != '') {
			$category = $GLOBALS['db']->dbc->function->lower('[category]');
			$sql .= " AND ($category = {category})";
			$params['category'] = strtolower($category);
		}
		if (trim($search) != '') {
			$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
				([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
				([company] LIKE {s}) OR ([company_email] LIKE {s}))";
			$params['s'] = '%'.$search.'%';
		}
		if (trim($random_seed) != '') {
			$sql .= " ORDER BY rand(".(int)$random_seed.")";
		} else {
			$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'sort_order' ? ", [image] DESC, [premium] DESC" : '');
		}
		
		if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit, $offSet);
                if (Jaws_Error::IsError($result)) {
                    //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
					return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
                }
            }
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				//return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
				return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
			}
        }
        
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
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
     * Returns all products that belongs to a parent
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetTotalOfProductsOfParent(
		$id, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $OwnerID = null, 
		$search = '', $brandid = '', $sales = '', $category = '', $attributes = '', $recursive = false
	) {
		$parents = array();
		$productparent = $this->GetProductParent($id);
		if (Jaws_Error::IsError($productparent)) {
			return new Jaws_Error($productparent->GetMessage(), _t('STORE_NAME'));
		} else if (!isset($productparent['productparentid']) || empty($productparent['productparentid'])) {
			return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
		} else {
			$GLOBALS['db']->dbc->loadModule('Function', null, true);
			$parentID = $productparent['productparentid'];
			$parents[] = $parentID;
			$productparents = $this->GetRecursiveSubCategoriesOfParent($parentID);
			if (!Jaws_Error::IsError($productparents) && isset($productparents[0]['productparentid']) && !empty($productparents[0]['productparentid'])) {
				foreach ($productparents as $parent) {
					$parentID = $parent['productparentid'];
					$parents[] = $parentID;
				}
			}

			$sql  = '
				SELECT COUNT([id])
				FROM [[products_parents]]
				INNER JOIN [[product]] ON [[products_parents]].[prod_id] = [[product]].[id]
				WHERE ([[product]].[title] != "")
			';

			$params = array();
			if ($recursive === true) {
				$p = 0;
				$sql .=  " AND (";
				foreach ($parents as $parent) {
					$sql .= ($p > 0 ? " OR " : '')."([[products_parents]].[parent_id] = {id".$p."})";
					$params['id'.$p] = (int)$parent;
					$p++;
				}
				$sql .=  ")";
			} else {
				$params['id'] = $productparent['productparentid'];
				$sql .= " AND ([[products_parents]].[parent_id] = {id})";
			}
			if (!is_null($active)) {
				$sql .=  " AND ([active] = {active})";
				$params['active'] = $active;
			}
			if (!is_null($OwnerID)) {
				$sql .=  " AND ([ownerid] = {OwnerID})";
				$params['OwnerID'] = (int)$OwnerID;
			}
			if (trim($sales) != '') {
				$sales = explode(',', $sales);
				$sql .= " AND (";
				$i = 0;
				foreach ($sales as $sale) {
					$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
					$params['sale_'.$i] = $sale;
					$i++;
				}
				$sql .= ")";
			}
			if (trim($attributes) != '') {
				$attributes = explode(',', $attributes);
				$sql .= " AND (";
				$i = 0;
				foreach ($attributes as $attribute) {
					$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
					$params['attribute_'.$i] = $attribute;
					$i++;
				}
				$sql .= ")";
			}
			if (trim($brandid) != '') {
				$sql .= " AND ([brandid] = {brandid})";
				$params['brandid'] = (int)$brandid;
			}
			if (trim($category) != '') {
				$product_category = $GLOBALS['db']->dbc->function->lower('[category]');
				$sql .= " AND ($product_category = {category})";
				$params['category'] = strtolower($category);
			}
			if (trim($search) != '') {
				$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
					([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
					([company] LIKE {s}) OR ([company_email] LIKE {s}))";
				$params['s'] = '%'.$search.'%';
			}
			$res = $GLOBALS['db']->queryOne($sql, $params);
			$total = (Jaws_Error::IsError($res) ? 0 : (int)$res);
						
			return $total;
 		}
   }
    
    /**
     * Returns all products that belongs to a parent
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetRecursiveTotalOfProductsOfParent(
		$id, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $OwnerID = null, 
		$search = '', $brandid = '', $sales = '', $category = '', $attributes = ''
	) {
		$parents = array();
		$productparent = $this->GetProductParent($id);
		if (Jaws_Error::IsError($productparent)) {
			return new Jaws_Error($productparent->GetMessage(), _t('STORE_NAME'));
		} else if (!isset($productparent['productparentid']) || empty($productparent['productparentid'])) {
			return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
		} else {
			$parentID = $productparent['productparentid'];
			$parents[] = $parentID;
			$productparents = $this->GetRecursiveSubCategoriesOfParent($parentID);
			if (!Jaws_Error::IsError($productparents) && isset($productparents[0]['productparentid']) && !empty($productparents[0]['productparentid'])) {
				foreach ($productparents as $parent) {
					$parentID = $parent['productparentid'];
					$parents[] = $parentID;
				}
			}
		
			$GLOBALS['db']->dbc->loadModule('Function', null, true);

			$sql  = '
				SELECT COUNT([[product]].[id])
				FROM [[products_parents]]
				INNER JOIN [[product]] ON [[products_parents]].[prod_id] = [[product]].[id]
				WHERE ([[product]].[title] != "")';

			$params = array();
			$p = 0;
			$sql .=  " AND (";
			foreach ($parents as $parent) {
				$sql .= ($p > 0 ? " OR " : '')."([[products_parents]].[parent_id] = {id".$p."})";
				$params['id'.$p] = (int)$parent;
				$p++;
			}
			$sql .=  ")";
			if (!is_null($active)) {
				$sql .=  " AND ([active] = {active})";
				$params['active'] = $active;
			}
			if (!is_null($OwnerID)) {
				$sql .=  " AND ([ownerid] = {OwnerID})";
				$params['OwnerID'] = (int)$OwnerID;
			}
			if (trim($sales) != '') {
				$sales = explode(',', $sales);
				$sql .= " AND (";
				$i = 0;
				foreach ($sales as $sale) {
					$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
					$params['sale_'.$i] = $sale;
					$i++;
				}
				$sql .= ")";
			}
			if (trim($attributes) != '') {
				$attributes = explode(',', $attributes);
				$sql .= " AND (";
				$i = 0;
				foreach ($attributes as $attribute) {
					$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
					$params['attribute_'.$i] = $attribute;
					$i++;
				}
				$sql .= ")";
			}
			if (trim($brandid) != '') {
				$sql .= " AND ([brandid] = {brandid})";
				$params['brandid'] = (int)$brandid;
			}
			if (trim($category) != '') {
				$product_category = $GLOBALS['db']->dbc->function->lower('[category]');
				$sql .= " AND ($product_category = {category})";
				$params['category'] = strtolower($category);
			}
			if (trim($search) != '') {
				$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
					([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
					([company] LIKE {s}) OR ([company_email] LIKE {s}))";
				$params['s'] = '%'.$search.'%';
			}
			$res = $GLOBALS['db']->queryOne($sql, $params);
			$total = (Jaws_Error::IsError($res) ? 0 : (int)$res);
						
			return $total;
		}
    }
    
	/**
     * Returns all product owners of given product parent
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetStoreOwnersOfParent($pid)
    {
        if (!is_numeric($pid)) {
			$parent = $this->GetProductParent($pid);
			if (Jaws_Error::IsError($parent)) {
				return new Jaws_Error($parent->GetMessage(), _t('STORE_NAME'));
			} else if (!isset($parent['productparentid']) || empty($parent['productparentid'])) {
				return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
			} else {
				$pid = $parent['productparentid'];
			}
		}

		$sql  = '
            SELECT DISTINCT [ownerid]
			FROM [[products_parents]]
            INNER JOIN [[product]] ON [[products_parents]].[prod_id] = [[product]].[id]
            INNER JOIN [[users]] ON [[users]].[id] = [[product]].[ownerid]
			WHERE ([[products_parents]].[parent_id] = {id})
		';

		$params = array();
		$params['id'] = (int)$pid;
        
        $types = array(
			'integer'
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
     * Returns true/false if a user owns a product in given parent
     *
     * @access  public
     * @return  boolean  true/false
     */
    function UserOwnsStoreInParent($pid, $uid)
    {
        if (!is_numeric($pid)) {
			$parent = $this->GetProductParent($pid);
			if (!Jaws_Error::IsError($parent) && isset($parent['productparentid']) && !empty($parent['productparentid'])) {
				$pid = $parent['productparentid'];
			} else {
				return false;
			}
		}

		$sql  = '
            SELECT COUNT([[product]].[id])
			FROM [[product]]
            INNER JOIN [[products_parents]] ON [[product]].[id] = [[products_parents]].[prod_id] 
            INNER JOIN [[users]] ON [[product]].[ownerid] = [[users]].[id]
			WHERE ([[products_parents]].[parent_id] = {id}) AND ([[product]].[ownerid] = {uid})
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
     * Gets the users products by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the products and false on error
     */
    function GetStoreOfUserID($id, $active = null, $sortColumn = 'created', $sortDir = 'ASC', $limit = null)
    {
        $fields = array('sort_order', 'premium', 'price', 'brandid', 'featured', 'ownerid', 'title', 'created', 'updated', 'active', 'attribute');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
		
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
			FROM [[product]]
            WHERE ([ownerid] = {id})';
		if (!is_null($active)) {
			$sql .=  "AND ([active] = {active})";
			$params['active'] = $active;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'sort_order' ? ", [image] DESC, [premium] DESC" : '');
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
			}
		}
		
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
        }

        return $result;
    }

    /**
     * Returns all products that belongs to users of given Group ID
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetStoreOfGroup(
		$gid, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $OwnerID = null,  
		$search = '', $brandid = '', $sales = '', $category = '', $attributes = '', 
		$limit = null, $offSet = null, $random_seed = ''
	) {
        $GLOBALS['db']->dbc->loadModule('Function', null, true);
        $fields = array('sort_order', 'premium', 'price', 'brandid', 'featured', 'ownerid', 'title', 'created', 'updated', 'active', 'attribute');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);

		$sql  = "
            SELECT [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [[product]].[created], [[product]].[updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
			FROM [[users_groups]]
            INNER JOIN [[product]] ON [[users_groups]].[user_id] = [[product]].[ownerid]
			WHERE ([[users_groups]].[group_id] = {id}) AND ([[users_groups]].[status] = 'active' OR [[users_groups]].[status] = 'admin' OR [[users_groups]].[status] = 'founder')
		";

		$params = array();
		$params['id'] = $gid;
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		if (!is_null($OwnerID)) {
			$sql .=  " AND ([ownerid] = {OwnerID})";
			$params['OwnerID'] = (int)$OwnerID;
		}
		if (trim($sales) != '') {
			$sales = explode(',', $sales);
			$sql .= " AND (";
			$i = 0;
			foreach ($sales as $sale) {
				$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
				$params['sale_'.$i] = $sale;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($attributes) != '') {
			$attributes = explode(',', $attributes);
			$sql .= " AND (";
			$i = 0;
			foreach ($attributes as $attribute) {
				$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
				$params['attribute_'.$i] = $attribute;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($brandid) != '') {
			$sql .= " AND ([brandid] = {brandid})";
			$params['brandid'] = (int)$brandid;
		}
		if (trim($category) != '') {
			$category = $GLOBALS['db']->dbc->function->lower('[category]');
			$sql .= " AND ($category = {category})";
			$params['category'] = strtolower($category);
		}
		if (trim($search) != '') {
			$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
				([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
				([company] LIKE {s}) OR ([company_email] LIKE {s}))";
			$params['s'] = '%'.$search.'%';
		}
		if (trim($random_seed) != '') {
			$sql .= " ORDER BY rand(".(int)$random_seed.")";
		} else {
			$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'sort_order' ? ", [image] DESC, [premium] DESC" : '');
		}
		
		if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit, $offSet);
                if (Jaws_Error::IsError($result)) {
                    //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
					return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
                }
            }
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				//return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
				return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
			}
        }
        
        
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
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
     * Returns all products that belongs to users of given Group ID
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetTotalOfStoreOfGroup(
		$gid, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $OwnerID = null,  
		$search = '', $brandid = '', $sales = '', $category = '', $attributes = ''
	) {
        $GLOBALS['db']->dbc->loadModule('Function', null, true);
        $sql = "
			SELECT COUNT([id]) 
			FROM [[users_groups]]
            INNER JOIN [[product]] ON [[users_groups]].[user_id] = [[product]].[ownerid]
			WHERE ([[users_groups]].[group_id] = {id}) AND ([[users_groups]].[status] = 'active' OR [[users_groups]].[status] = 'admin' OR [[users_groups]].[status] = 'founder')
		";

		$params = array();
		$params['id'] = $gid;
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		if (!is_null($OwnerID)) {
			$sql .=  " AND ([ownerid] = {OwnerID})";
			$params['OwnerID'] = (int)$OwnerID;
		}
		if (trim($sales) != '') {
			$sales = explode(',', $sales);
			$sql .= " AND (";
			$i = 0;
			foreach ($sales as $sale) {
				$sql .= ($i > 0 ? "AND " : '')."{sale_".$i."} IN ([sales])";
				$params['sale_'.$i] = $sale;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($attributes) != '') {
			$attributes = explode(',', $attributes);
			$sql .= " AND (";
			$i = 0;
			foreach ($attributes as $attribute) {
				$sql .= ($i > 0 ? "AND " : '')."{attribute_".$i."} IN ([attribute])";
				$params['attribute_'.$i] = $attribute;
				$i++;
			}
			$sql .= ")";
		}
		if (trim($brandid) != '') {
			$sql .= " AND ([brandid] = {brandid})";
			$params['brandid'] = (int)$brandid;
		}
		if (trim($category) != '') {
			$category = $GLOBALS['db']->dbc->function->lower('[category]');
			$sql .= " AND ($category = {category})";
			$params['category'] = strtolower($category);
		}
		if (trim($search) != '') {
			$sql .=  " AND (([title] LIKE {s}) OR ([product_code] LIKE {s}) OR ([sm_description] LIKE {s}) OR 
				([description] LIKE {s}) OR ([fast_url] LIKE {s}) OR ([contact] LIKE {s}) OR ([contact_email] LIKE {s}) OR 
				([company] LIKE {s}) OR ([company_email] LIKE {s}))";
			$params['s'] = '%'.$search.'%';
		}
		$res = $GLOBALS['db']->queryOne($sql, $params);
        $total = (Jaws_Error::IsError($res) ? 0 : (int)$res);
        			
		return $total;
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
			FROM [[product_posts]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Returns all posts that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllPostsOfProduct($id)
    {
	    $sql  = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [checksum]
			FROM [[product_posts]] WHERE [linkid] = {id}
			ORDER BY [sort_order] ASC, [title] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single post by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetPostByChecksum($checksum)
    {		
		$sql = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [checksum]
			FROM [[product_posts]] WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }


        return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single attribute by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAttribute($id)
    {
		$sql = '
            SELECT [id], [sort_order], [feature], [typeid], [description], [add_amount], 
				[add_percent], [newprice], [ownerid], [active], [created], [updated], [checksum]
            FROM [[productattribute]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 'decimal', 
			'integer', 'decimal', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }


        return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single attribute by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAttributeByChecksum($checksum)
    {
		$sql = '
            SELECT [id], [sort_order], [feature], [typeid], [description], [add_amount], 
				[add_percent], [newprice], [ownerid], [active], [created], [updated], [checksum]
            FROM [[productattribute]] WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 'decimal', 
			'integer', 'decimal', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }


        return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets an index of all the attributes.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetProductAttributes($limit = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('sort_order', 'ownerid', 'feature', 'typeid', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [id], [sort_order], [feature], [typeid], [description], [add_amount], 
				[add_percent], [newprice], [ownerid], [active], [created], [updated], [checksum]
            FROM [[productattribute]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTES_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTES_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 'decimal', 
			'integer', 'decimal', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTES_NOT_RETRIEVED'), _t('STORE_NAME'));
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
    function GetAttributesOfType($id)
    {
        $sql = "
            SELECT [id], [sort_order], [feature], [typeid], [description], [add_amount], 
				[add_percent], [newprice], [ownerid], [active], [created], [updated], [checksum]
            FROM [[productattribute]]
			WHERE [typeid] = {id}
			ORDER BY [sort_order] ASC
			";
		
		$params             = array();
		$params['id'] 		= $id;

        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 'decimal', 
			'integer', 'decimal', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTES_NOT_RETRIEVED'), _t('STORE_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single attribute type by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAttributeType($id)
    {
		$sql = '
            SELECT [id], [title], [description], [itype], [required], 
				[ownerid], [active], [created], [updated], [checksum]
            FROM [[attribute_types]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single attribute type by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAttributeTypeByChecksum($checksum)
    {
		$sql = '
            SELECT [id], [title], [description], [itype], [required], 
				[ownerid], [active], [created], [updated], [checksum]
            FROM [[attribute_types]] WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets an index of all the attribute types.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetAttributeTypes($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'title', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'title';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [id], [title], [description], [itype], [required], 
				[ownerid], [active], [created], [updated], [checksum]
            FROM [[attribute_types]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPES_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPES_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPES_NOT_RETRIEVED'), _t('STORE_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single sale by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetSale($id)
    {		
		$sql = '
            SELECT [id], [title], [startdate], [enddate], [description], [min_qty], [discount_amount], 
				[discount_percent], [discount_newprice], [coupon_code], 
				[featured], [ownerid], [active], [created], [updated], [checksum]
			FROM [[sales]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'integer', 'decimal',
			'integer', 'decimal', 'text', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single sale by code.
     *
     * @access  public
     * @param   string     $code     The code of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetSaleByCode($code)
    {		
        $params             = array();
        $params['code'] 	= strtolower(trim($code));
        //$GLOBALS['db']->dbc->loadModule('Function', null, true);
        //$coupon_code = $GLOBALS['db']->dbc->function->lower('[coupon_code]');

		$sql = "
            SELECT [id], [title], [startdate], [enddate], [description], [min_qty], [discount_amount], 
				[discount_percent], [discount_newprice], [coupon_code], 
				[featured], [ownerid], [active], [created], [updated], [checksum]
			FROM [[sales]] WHERE [coupon_code] = {code}";

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'integer', 'decimal',
			'integer', 'decimal', 'text', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);
		
        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single sale by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetSaleByChecksum($checksum)
    {		
		$sql = '
            SELECT [id], [title], [startdate], [enddate], [description], [min_qty], [discount_amount], 
				[discount_percent], [discount_newprice], [coupon_code], 
				[featured], [ownerid], [active], [created], [updated], [checksum]
			FROM [[sales]] WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'integer', 'decimal',
			'integer', 'decimal', 'text', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_FOUND'), _t('STORE_NAME'));
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
    function GetSales($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields = array('ownerid', 'title', 'startdate', 'enddate', 'min_qty', 'discount_amount', 
			'discount_percent', 'discount_newprice', 'coupon_code', 'featured', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'title';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [id], [title], [startdate], [enddate], [description], [min_qty], [discount_amount], 
				[discount_percent], [discount_newprice], [coupon_code], 
				[featured], [ownerid], [active], [created], [updated], [checksum]
            FROM [[sales]]
			";
		$params              	= array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [discount_amount] DESC, [discount_percent] DESC, [discount_newprice] DESC, [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_SALES_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_SALES_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'integer', 'decimal',
			'integer', 'decimal', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_SALES_NOT_RETRIEVED'), _t('STORE_NAME'));
        }

        return $result;
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
            SELECT [id], [title], [description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum] 
			FROM [[productbrand]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_BRAND_NOT_FOUND'), _t('STORE_NAME'));
    }

    /**
     * Gets a single brand by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetBrandByChecksum($checksum)
    {		
		$sql = '
            SELECT [id], [title], [description], [image], [image_width], 
				[image_height], [layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum] 
			FROM [[productbrand]] WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (Jaws_Error::IsError($row)) {
			$error = new Jaws_Error($row->GetMessage(), _t('STORE_NAME'));
		} else if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('STORE_ERROR_BRAND_NOT_FOUND'), _t('STORE_NAME'));
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
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
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
            FROM [[productbrand]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_BRANDS_NOT_RETRIEVED'), _t('STORE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('STORE_ERROR_BRANDS_NOT_RETRIEVED'), _t('STORE_NAME'));
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
            return new Jaws_Error(_t('STORE_ERROR_BRANDS_NOT_RETRIEVED'), _t('STORE_NAME'));
        }

        return $result;
    }
    
    /**
     * Returns all rss items that should be hidden
     *
     * @access  public
     * @return  array  Array with all the rss info or Jaws_Error on error
     */
    function GetHiddenRssOfProductParent($id)
    {
	    $sql  = 'SELECT [id], [linkid], [title], [published], [url]
			FROM [[product_rss_hide]] WHERE [linkid] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'text'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
        }

        return $result;
    }
	
    /**
     * Create RSS of the Store
     *
     * @access  public
     * @param   boolean  $write  Flag that determinates if it should returns the RSS
     * @return  mixed    Returns the RSS(string) if it was required, or true
     */
    function MakeRSS($write = false, $gid = null, $OwnerID = null, $pid = null)
    {
        $atom = $this->GetAtomStruct($gid, $OwnerID, $pid);
        if (Jaws_Error::IsError($atom)) {
            return $atom;
        }

        if ($write) {
            if (!Jaws_Utils::is_writable(JAWS_DATA . 'xml')) {
                return new Jaws_Error(_t('STORE_ERROR_WRITING_RSSFILE'), _t('STORE_NAME'));
            }

            $atom->SetLink($GLOBALS['app']->getDataURL('', true) . 'xml/store.rss');

            ///FIXME we need to do more error checking over here
            @file_put_contents(JAWS_DATA . 'xml/store.rss', $atom->ToRSS2());
            Jaws_Utils::chmod(JAWS_DATA . 'xml/store.rss');
        }

        return $atom->ToRSS2WithCustom();
    }


    /**
     * Create ATOM struct of a given category
     *
     * @access  public
     * @param   int $category Category ID
     * @return  object  Can return the Atom Object
     */
    function GetAtomStruct($gid = null, $OwnerID = null, $category = null)
    {
		if (!is_null($gid)) {
			$result = $this->GetStoreOfGroup((int)$gid);
		} else if (!is_null($OwnerID)) {
			$result = $this->GetStoreOfUserID((int)$OwnerID);
		} else if (!is_null($category)) {
			$result = $this->GetAllProductsOfParent((int)$category, 'sort_order', 'ASC', 'Y', 0);
			$catInfo = $this->GetProductParent((int)$category);
			if (Jaws_Error::IsError($catInfo)) {
				return new Jaws_Error(_t('STORE_ERROR_GETTING_CATEGORIES_ATOMSTRUCT'), _t('STORE_NAME'));
			}
		} else {
			$result = $this->GetProducts(null, 'sort_order', 'ASC', false, 0, 'Y');
		}		

        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_GETTING_CATEGORIES_ATOMSTRUCT'), _t('STORE_NAME'));
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $siteURL = $GLOBALS['app']->GetSiteURL() . '/';

		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
        require_once JAWS_PATH . 'include/Jaws/AtomFeed.php';
        $categoryAtom = new Jaws_AtomFeed();

        $categoryAtom->SetTitle($GLOBALS['app']->Registry->Get('/config/site_name'));
        $url = $xss->filter($GLOBALS['app']->GetFullURL());

        //$categoryAtom->SetLink($siteURL.'index.php?gadget=Store&action=RSS'.(!is_null($gid) ? '&gid='.$gid : '').(!is_null($category) ? '&pid='.$category : '').(!is_null($OwnerID) ? '&OwnerID='.$OwnerID : ''));
        $categoryAtom->SetLink($url);
        $categoryAtom->SetSiteURL($siteURL);
        /// FIXME: Get an IRI from the URL or something...
        $categoryAtom->SetId($siteURL);
        $categoryAtom->SetAuthor($GLOBALS['app']->Registry->Get('/config/site_author'),
                                 $siteURL,
                                 $GLOBALS['app']->Registry->Get('/network/site_email'));
        $categoryAtom->SetGenerator('Jaws'.$GLOBALS['app']->Registry->Get('/version'));
        $categoryAtom->SetCopyright($GLOBALS['app']->Registry->Get('/config/copyright'));
        //$categoryAtom->SetStyle($categoryAtom->Link->HRef.'/gadgets/Store/templates/atom.xsl', 'text/xsl');
        $tagLine = isset($catInfo['productparentcategory_name']) && !empty($catInfo['productparentcategory_name']) ?
                   _t('STORE_XML_TAGLINE') :
                   $xss->filter($catInfo['productparentcategory_name']);
        $categoryAtom->SetTagLine($tagLine);

        $date = $GLOBALS['app']->loadDate();
        foreach ($result as $r) {
			$entry = new AtomEntry();
			$entry->SetTitle($r['title']);
			$post_id = empty($r['fast_url']) ? $r['id'] : $r['fast_url'];
			$url = $siteURL.$GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $post_id));
			$entry->SetLink($url);
			$entry->SetId($url);
			$content = Jaws_Gadget::ParseText($r['description'], $this->_Name);
			$entry->SetSummary($content, 'html');
			$entry->SetContent($content, 'html');
			if (!empty($r['image'])) {
				$enclosure = JAWS_DATA . 'files'.$xss->parse(strip_tags($r['image']));
				if (file_exists($enclosure)) {
					/*
					$size = filesize($enclosure);
					$img_info = Jaws_Utils::image_info($enclosure);
					$entry->AddEnclosure($GLOBALS['app']->GetSiteURL() . '/data/files'.$xss->parse(strip_tags($r['image'])),
										 $size, $img_info['mime']);
					*/
					$entry->Categories[] = new AtomContentConstruct('g:image_link', $entry->ToCDATA($GLOBALS['app']->GetDataURL('', true) . 'files'.$xss->parse(strip_tags($r['image']))), 'html');
				}
				// Product posts
				$posts = $this->GetAllPostsOfProduct($r['id']);
				if (!Jaws_Error::IsError($posts)) {
					foreach($posts as $post) {		            
						if (isset($post['image']) && !empty($post['image'])) {
							$post_enclosure = JAWS_DATA . 'files'.$xss->parse(strip_tags($post['image']));
							if (file_exists($post_enclosure)) {
								/*
								$post_size = filesize($post_enclosure);
								$post_info = Jaws_Utils::image_info($post_enclosure);
								$entry->AddEnclosure($GLOBALS['app']->GetSiteURL() . '/data/files'.$xss->parse(strip_tags($post['image'])),
													 $post_size, $post_info['mime']);
								*/
								$entry->Categories[] = new AtomContentConstruct('g:image_link', $entry->ToCDATA($GLOBALS['app']->GetDataURL('', true) . 'files'.$xss->parse(strip_tags($post['image']))), 'html');
							}
						}
					}
				}
			}
			if ((int)$r['ownerid'] > 0) {
				$userInfo = $jUser->GetUserInfoById((int)$r['ownerid'], true, true, true, true);
				$entry->SetAuthor($userInfo['nickname'], $categoryAtom->Link->HRef, $userInfo['email']);
			} else {
				$entry->SetAuthor($GLOBALS['app']->Registry->Get('/config/site_author'), $categoryAtom->Link->HRef, $GLOBALS['app']->Registry->Get('/network/site_email'));
			}
			$entry->Categories[] = new AtomContentConstruct('g:user_id', $r['ownerid'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:id', $r['id'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:brandid', $r['brandid'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:sort_order', $r['sort_order'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:category', $r['category'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:product_code', $entry->ToCDATA($r['product_code']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:sm_description', $entry->ToCDATA($r['sm_description']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:weight', $r['weight'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:retail', $r['retail'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:price', $r['price'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:cost', $r['cost'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:setup_fee', $r['setup_fee'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:unit', $entry->ToCDATA($r['unit']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:recurring', $r['recurring'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:inventory', $r['inventory'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:instock', $r['instock'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:lowstock', $r['lowstock'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:outstockmsg', $entry->ToCDATA($r['outstockmsg']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:outstockbuy', $r['outstockbuy'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:attribute', $r['attribute'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:premium', $r['premium'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:featured', $r['featured'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:active', $r['active'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:fast_url', $entry->ToCDATA($r['fast_url']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:internal_productno', $entry->ToCDATA($r['internal_productno']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alink', $entry->ToCDATA($r['alink']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alinktitle', $entry->ToCDATA($r['alinktitle']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alinktype', $r['alinktype'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:alink2', $entry->ToCDATA($r['alink2']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alink2title', $entry->ToCDATA($r['alink2title']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alink2type', $r['alink2type'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:alink3', $entry->ToCDATA($r['alink3']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alink3title', $entry->ToCDATA($r['alink3title']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:alink3type', $r['alink3type'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:rss_url', $entry->ToCDATA($r['rss_url']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:contact', $entry->ToCDATA($r['contact']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:contact_email', $entry->ToCDATA($r['contact_email']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:contact_phone', $entry->ToCDATA($r['contact_phone']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:contact_website', $entry->ToCDATA($r['contact_website']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:contact_photo', $entry->ToCDATA($r['contact_photo']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:company', $entry->ToCDATA($r['company']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:company_email', $entry->ToCDATA($r['company_email']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:company_phone', $entry->ToCDATA($r['company_phone']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:company_website', $entry->ToCDATA($r['company_website']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:company_logo', $entry->ToCDATA($r['company_logo']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:subscribe_method', $entry->ToCDATA($r['subscribe_method']), 'html');
			$entry->Categories[] = new AtomContentConstruct('g:sales', $r['sales'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:min_qty', $r['min_qty'], 'text');
			$entry->Categories[] = new AtomContentConstruct('g:checksum', $r['checksum'], 'text');
			$entry->SetPublished($date->ToISO($r['created']));
			$entry->SetUpdated($date->ToISO($r['updated']));

			$categoryAtom->AddEntry($entry);

			if (!isset($last_modified)) {
				$last_modified = $r['updated'];
			}
        }

        if (isset($last_modified)) {
            $categoryAtom->SetUpdated($date->ToISO($last_modified));
        } else {
            $categoryAtom->SetUpdated($date->ToISO(date('Y-m-d H:i:s')));
        }

        return $categoryAtom;
    }
	
	/**
     * Displays product template icons
     *
     * @access       public
     * @param        integer $id ID of product
     * @return       string with template icons content
     */
    function ShowProductTemplateIcons($id) {
		$page = $this->GetProduct($id);
		if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
			$tpl = new Jaws_Template('gadgets/Store/templates/');
			$tpl->Load('ShowProductTemplateIcons.html');
			if (file_exists(JAWS_DATA . 'templates/Store/product/'.$page['template'].'/'.$page['template'].'.html')) {
				$stpl = new Jaws_Template(JAWS_DATA . 'templates/Store/product/'.$page['template']);
				$stpl->Load($page['template'].'.html', false, false);
			} else {
				return new Jaws_Error('Template file "'.JAWS_DATA . 'templates/Store/product/'.$page['template'].'/'.$page['template'].'.html" doesn\'t exist', _t('STORE_NAME'));
			}
			return $tpl->Get();
		} else {
			return $page;
		}
		return new Jaws_Error(_t('STORE_ERROR_GETTING_PRODUCT'), _t('STORE_NAME'));
	}
	
	/**
     * Displays attribute_types template icons of given product
     *
     * @access       public
     * @param        integer $id ID of product
     * @return       string with template icons content
     */
    function ShowProductAttributeTemplateIcons($id) {
		$page = $this->GetProduct($id);
		if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
			$tpl = new Jaws_Template('gadgets/Store/templates/');
			$tpl->Load('ShowProductAttributeTemplateIcons.html');
			return $tpl->Get();
		} else {
			return $page;
		}
		return new Jaws_Error(_t('STORE_ERROR_GETTING_PRODUCT'), _t('STORE_NAME'));
	}
	
	/**
     * Creates a PDF from HTML template
     *
     * @access       public
     * @param        string $template filename to build from
     * @param        string $html HTML string to build from
     * @param        string $mode mPDF mode
     * @param        mixed $format predefined (A4, C2, etc) or array(width, height) in mm
     * @param        mixed $destination location to save or false to download
     * @param        array $replacements array of template variables to replace ({key} => value) 
     * @return       string with template icons content
     */
    function CreatePDF($template = null, $html = '', $mode = '', $format = 'A4', $destination = false, $replacements = array()) {
		if (empty($html)) {
			// Build HTML with replacements
			$html = '<p>Test</p>';
		}
		
		$result = Jaws_Utils::CreatePDF('', $html, $destination, $mode, $format);
		if ($result !== true) {
            return new Jaws_Error(_t('GLOBAL_ERROR_CREATING_PDF'), _t('STORE_NAME'));
		}
		return true;
	}
		
}
