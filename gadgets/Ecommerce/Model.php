<?php
/**
 * Ecommerce Gadget
 *
 * @category   GadgetModel
 * @package    Ecommerce
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceModel extends Jaws_Model
{
    var $_Name = 'Ecommerce';
	
    /**
     * Gets a single order by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the order to get.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetOrder($id)
    {
        $sql = '
		SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [checksum], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
            FROM [[order]]
			WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
    }

    /**
     * Gets a single order by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the order to get.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetOrderByChecksum($checksum)
    {
        $sql = '
		SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [checksum], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
            FROM [[order]]
			WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
    }

    /**
     * Gets an index of all the orders.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the GALLERIES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either GALLERIES_ASC or GALLERIES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetOrders($limit = null, $sortColumn = 'orderno', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'prod_id', 'qty', 'orderno', 'total', 'backorder', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ECOMMERCE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'orderno';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
		SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [checksum], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
		FROM [[order]]
			";
		$params = array();
		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		} else if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
			$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
			$sql .= " WHERE [ownerid] = {owner_id}";
		} 
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDERS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDERS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDERS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }

        return $result;
    }
    		
    /**
     * Gets a single order by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @param   int     $gid  The order ID
     * @return  mixed   Returns an array with the order info and false on error
     */
    function GetSingleOrderByUserID($id, $gid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['gid'] = $gid;
		
		$sql = '
		SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [checksum], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
		FROM [[order]]
		WHERE ([id] = {gid} AND [ownerid] = {id})
		ORDER BY [updated] DESC';
		
        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }

        return $result;
    }

    /**
     * Gets all orders by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the order info and false on error
     */
    function GetEcommerceOfUserID($id = null, $customer = null, $status = null, $sortColumn = 'updated', $sortDir = 'ASC', $limit = null)
    {
        $fields = array('orderno', 'prod_id', 'price', 'qty', 'total', 'ownerid', 'customer_id', 'sales_id', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ECOMMERCE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'updated';
        }

        $sortDir = strtoupper($sortDir);
		$params       	= array();
		
		$sql = '
		SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [checksum], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
		from [[order]]';
		if (!is_null($id)) {
			$params['id']	= (int)$id;
			$sql .= " WHERE ([ownerid] = {id})";
		} else if (!is_null($customer)) {
			$params['customer_id']	= (int)$customer;
			$sql .= " WHERE ([customer_id] = {customer_id})";
		} else {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
		}
		
		
		if (!is_null($status)) {
			$params['status'] = $status;
			$sql .= " AND ([active] = {status})";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";
		
        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
			}
		}
		
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }

        return $result;
    }
    
    /**
     * Returns all orders that belongs to users of given Group ID
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetEcommerceOfGroup($gid, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null)
    {
        $fields = array('ownerid', 'prod_id', 'qty', 'orderno', 'total', 'backorder', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ECOMMERCE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'updated';
        }

        $sortDir = strtoupper($sortDir);

		$sql  = "
			SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
				[attribute], [total], [backorder], [description], [recurring], 
				[gadget_table], [gadget_id], [ownerid], [active], [[order]].[created], [[order]].[updated],
				[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
				[customer_city], [customer_region], [customer_postal], [customer_country], 
				[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
				[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
				[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [[order]].[checksum], [sales_id], 
				[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
			FROM [[users_groups]]
            INNER JOIN [[order]] ON [[users_groups]].[user_id] = [[order]].[ownerid]
			WHERE ([[users_groups]].[group_id] = {id}) AND ([[users_groups]].[status] = 'active' OR [[users_groups]].[status] = 'admin')
		";

		$params = array();
		$params['id'] = $gid;
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir";
        
        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

		$properties = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($properties)) {
            //add language word for this
            //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
            return new Jaws_Error($properties->GetMessage(), _t('ECOMMERCE_NAME'));
        }	
		if (!is_array($properties) || count($properties) <= 0) {
			return array();
		}
		
		return $properties;
    }

    /**
     * Gets all items of orderno
     *
     * @access  public
     * @param   int     $id  The orderno
     * @return  mixed   Returns an array with the order info and false on error
     */
    function GetAllItemsOfOrderNo($id)
    {
		$params       = array();
        $params['id'] = (int)$id;
		
		$sql = '
		SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [customer_id], [checksum], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv]
		FROM [[order]]
		WHERE ([orderno] = {id})
		ORDER BY [updated] DESC';
		
        $types = array(
			'integer', 'integer', 'text', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text', 'integer', 
			'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //var_dump($result);
			return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }

        return $result;
    }
	
    /**
     * Gets a single shipping by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the order to get.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetShipping($id)
    {
        $sql = '
		SELECT [id], [sort_order], [type], [title], [minfactor], [maxfactor], 
			[price], [description], [ownerid], [active], [created], [updated], [checksum]
        FROM [[shipping]]
		WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'decimal', 'decimal', 'decimal', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_FOUND'), _t('ECOMMERCE_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_FOUND'), _t('ECOMMERCE_NAME'));
    }

    /**
     * Gets a single shipping by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the order to get.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetShippingByChecksum($checksum)
    {
        $sql = '
		SELECT [id], [sort_order], [type], [title], [minfactor], [maxfactor], 
			[price], [description], [ownerid], [active], [created], [updated], [checksum]
        FROM [[shipping]]
		WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'decimal', 'decimal', 'decimal', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_FOUND'), _t('ECOMMERCE_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_FOUND'), _t('ECOMMERCE_NAME'));
    }

    /**
     * Gets an index of all the orders.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the GALLERIES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either GALLERIES_ASC or GALLERIES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetShippings($limit = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null, $active = null)
    {
        $fields     = array('ownerid', 'sort_order', 'type', 'title', 'price', 'minfactor', 'maxfactor', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ECOMMERCE_ERROR_UNKNOWN_COLUMN'));
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
		SELECT [id], [sort_order], [type], [title], [minfactor], [maxfactor], 
			[price], [description], [ownerid], [active], [created], [updated], [checksum]
		FROM [[shipping]]
		";
		
		$params  = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE ([ownerid] = {owner_id})";
		} else if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
			$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
			$sql .= " WHERE ([ownerid] = {owner_id})";
		} 
		if (!is_null($active)) {
			$params['active'] = $active;
			$sql .= " AND ([active] = {active})";
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'text', 'decimal', 'decimal', 'decimal', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }

        return $result;
    }

    
	/**
     * Gets shipping methods available to an Order.
     *
     * @access  public
     * @param   int     $id     The ID of the order to get.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetShippingsOfOrder($weight = 1, $price = 0, $qty = 1)
    {
		// Get shipping methods for each type (default, by weight, price and qty)
		$sql = "
		SELECT [id], [sort_order], [type], [title], [minfactor], [maxfactor], 
			[price], [description], [ownerid], [active], [created], [updated], [checksum]
		FROM [[shipping]] WHERE [active] = {Active} AND 
			([type] = {default}) OR
			([type] = {weight} AND ([minfactor] <= {factorweight}) AND ([maxfactor] >= {factorweight})) OR
			([type] = {price} AND ([minfactor] <= {factorprice}) AND ([maxfactor] >= {factorprice})) OR
			([type] = {qty} AND ([minfactor] <= {factorqty}) AND ([maxfactor] >= {factorqty}))
		";
		
		$params = array();
		$params['Active'] = 'Y';
		$params['default'] = 'default';
		$params['weight'] = 'weight';
		$params['factorweight'] = (int)$weight;
		$params['price'] = 'price';
		$params['factorprice'] = (int)$price;
		$params['qty'] = 'products';
		$params['factorqty'] = (int)$qty;
		
		$result = $GLOBALS['db']->queryAll($sql, $params);
		if (Jaws_Error::IsError($result)) {
			return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
		}
		
		return $result;
	}
	
	/**
     * Gets shipping methods of user.
     *
     * @access  public
     * @param   int     $id     The ID of the user.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetShippingsOfUserID($id, $active = null)
    {
		$params = array();
		$params['OwnerID'] = (int)$id;
		
		$sql = "
		SELECT [id], [sort_order], [type], [title], [minfactor], [maxfactor], 
			[price], [description], [ownerid], [active], [created], [updated], [checksum]
		FROM [[shipping]] WHERE [ownerid] = {OwnerID}
		";
		
		if (!is_null($active)) {
			$sql = " AND ([active] = {active})";
			$params['active'] = $active;
		}
		
		$types = array(
			'integer', 'integer', 'text', 'text', 'decimal', 'decimal', 'decimal', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
		if (Jaws_Error::IsError($result)) {
			return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
		}
		
		return $result;
	}
	
    /**
     * Gets an index of all the orders.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the GALLERIES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either GALLERIES_ASC or GALLERIES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetTaxes($limit = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'sort_order', 'title', 'locations', 'taxpercent', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ECOMMERCE_ERROR_UNKNOWN_COLUMN'));
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
		SELECT 
			[id], [sort_order], [title], [locations], [taxpercent], [ownerid], 
			[always], [active], [created], [updated], [checksum]
        FROM [[taxes]]
		";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		} else {
			$sql .= " WHERE [ownerid] = 0";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ECOMMERCE_ERROR_TAXES_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ECOMMERCE_ERROR_TAXES_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
                }
            }
        }
        
		$types = array(
			'integer', 'integer', 'text', 'text', 'decimal',
			'integer', 'text', 'text', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_TAXES_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }

        return $result;
    }

	/**
     * Gets taxes of user.
     *
     * @access  public
     * @param   int     $id     The ID of the user.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetTaxesOfUserID($id, $active = null)
    {
		return array();
	}
		
	/**
     * Gets taxes of a (U.S.) dollar amount.
     *
     * @access  public
     * @param   int     $id     The ID of the order to get.
     * @return  array   An array containing the order information, or false if no order could be loaded.
     */
    function GetTaxOfAmount($price = 0, $fromstate = '', $tostate = '')
    {
		if (!is_numeric($price) || trim($fromstate) == '' || trim($tostate) == '') {
			return 0.00;
		}
		$fromstate = strtoupper(trim(str_replace(' ', '', $fromstate))); 
		$tostate = strtoupper(trim(str_replace(' ', '', $tostate))); 
		
		$tax_rules = array();
		$tax_rules[0]["CA"] = 0.0825;
		$tax_rules[0]["CALIFORNIA"] = 0.0825;
		$tax_rules[1]["NY"] = 0.0825;
		$tax_rules[1]["NEWYORK"] = 0.0825;
		$tax_rules[2]["GA"] = 0.07;
		$tax_rules[2]["GEORGIA"] = 0.07;
		$tax_rules[3]["OH"] = 0.07;
		$tax_rules[3]["OHIO"] = 0.07;

		foreach ($tax_rules as $tax_rule) {
			if (isset($tax_rule[$fromstate]) && isset($tax_rule[$tostate])) {
				$result = number_format(($tax_rule[$tostate]*$price), 2, '.', '');
				return $result;
			}
		}
		return 0.00;
	}
	

    /**
     * Gets a product that is tied to a User Account Pane Method.
     *
     * @access  public
     * @param   int     $method     The pane method.
     * @return  array   An array containing the product information, or false if no product could be loaded.
     */
    function GetProductByPaneMethod($method = '')
    {
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
			WHERE [subscribe_method] = {method}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'integer', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);

        $params             = array();
        $params['method']   = $method;
        //$params['language'] = $language;

		$row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            //return new Jaws_Error($row->GetMessage(), _t('ECOMMERCE_NAME'));
            return new Jaws_Error(_t('ECOMMERCE_ERROR_PRODUCTPANEMETHOD_NOT_FOUND'), _t('ECOMMERCE_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return array();
    }
	
	/**
     * Payment gateway support including Authorize.NET, PayPal, GoogleCheckout, and offline (manual) gateways.
     *
     * @category 	feature
     * @param 	array 	$items 	Google Checkout JS representation of items in cart (see: https://sites.google.com/site/checkoutshoppingcart/reference)
     * @param 	decimal 	$total_weight 	Decimal value of total weight of all items in cart
     * @param 	string 	$paymentmethod 	Payment method the customer has specified
     * @param 	string 	$redirect_to 	Where to redirect on unsuccessful checkout
     * @param 	string 	$customer_shipfirstname 	Customer's shipping first name
     * @param 	string 	$customer_shiplastname 	Customer's shipping last name
     * @param 	string 	$customer_shipaddress 	Customer's shipping address
     * @param 	string 	$customer_shipcity 	Customer's shipping city
     * @param 	string 	$customer_shipregion 	Customer's shipping state/province/region
     * @param 	string 	$customer_shippostal 	Customer's shipping postal code
     * @param 	string 	$customer_shipcountry 	Customer's shipping country
     * @param 	string 	$shipfreight 	Shipping freight total
     * @param 	string 	$customer_shipaddress2 	Customer's shipping address cont'd
     * @param 	string 	$customer_firstname 	Customer's first name
     * @param 	string 	$customer_middlename 	Customer's middle name
     * @param 	string 	$customer_lastname 	Customer's last name
     * @param 	string 	$customer_suffix 	Customer's suffix
     * @param 	string 	$customer_address 	Customer's address
     * @param 	string 	$customer_address2 	Customer's address cont'd
     * @param 	string 	$customer_city 	Customer's city
     * @param 	string 	$customer_region 	Customer's region
     * @param 	string 	$customer_postal 	Customer's postal
     * @param 	string 	$customer_country 	Customer's country
     * @param 	string 	$cc_creditcardtype 	Credit card type
     * @param 	string 	$cc_acct 	Credit card number
     * @param 	string 	$cc_expdate_month 	Credit card expiration month
     * @param 	string 	$cc_expdate_year 	Credit card expiration year
     * @param 	string 	$cc_cvv2 	Credit card security code
     * @param 	array 	$customcheckoutfields 	Custom checkout fields (key => value)
     * @param 	string 	$customer_phone 	Customer's telephone
     * @param 	string 	$sales_code 	Sales code specified by customer
     * @param 	string 	$usecase 	Cart use case
     * @access 	public
     * @return 	void
     */
    function PostCart(
		$items, $total_weight = 0.00, $paymentmethod = '', $redirect_to = '', $customer_shipfirstname = '', $customer_shiplastname = '', 
		$customer_shipaddress = '', $customer_shipcity = '', $customer_shipregion = '', $customer_shippostal = '', $customer_shipcountry = '', 
		$shipfreight = '', $customer_shipaddress2 = '', $customer_firstname = '', $customer_middlename = '', $customer_lastname = '', 
		$customer_suffix = '', $customer_address = '', $customer_address2 = '', $customer_city = '', $customer_region = '', 
		$customer_postal = '', $customer_country = '', $cc_creditcardtype = '', $cc_acct = '', $cc_expdate_month = '', $cc_expdate_year = '', 
		$cc_cvv2 = '', $customcheckoutfields = array(), $customer_phone = '', $sales_code = '', $usecase = 'DigitalUsecase'
	) {
		$submit_vars = array();

		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/Mail.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$merchant_signature = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_signature');  // Your Merchant Signature
		$shipfrom_city = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city');  // City Shipping From
		$shipfrom_state = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');  // State Shipping From
		$shipfrom_zip = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip');  // Zip Shipping From
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');  // Use Carrier Calculated Shipping
		$transaction_percent = 0;
		$transaction_amount = 0;
		$transaction_mode = 'add';
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');  // Use Carrier Calculated Shipping
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$domain = strtolower(str_replace(array('http://', 'https://'), '', $GLOBALS['app']->getSiteURL()));
		$admin_email = $GLOBALS['app']->Registry->Get('/network/site_email');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$error = '';
		$has_weight = false;	
		$no_merchants = '';
		$session_id = $GLOBALS['app']->Session->GetAttribute("session_id");
		
		$customer_id = 0;
		$customer_email = '';
		if ($GLOBALS['app']->Session->Logged()) {
			$logged = true;
			$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
			if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				$customer_id = $userInfo['id'];
				$customer_email = $userInfo['email'];
				$customer_phone = (!empty($customer_phone) ? $customer_phone : (!empty($userInfo['office']) ? $userInfo['office'] : (!empty($userInfo['phone']) ? $userInfo['phone'] : '')));
			}
		}
		
		$sales_id = '0';
		$now = $GLOBALS['db']->Date();
		if (!empty($sales_code)) {
			$saleInfo = $model->GetSaleByCode($sales_code);
			if (
				!Jaws_Error::IsError($saleInfo) && isset($saleInfo['id']) && !empty($saleInfo['id']) && 
				$saleInfo['active'] == 'Y' && ($now > $saleInfo['startdate'] && $now < $saleInfo['enddate'])
			) {
				$sales_id = $saleInfo['id'];
			}
		}
		
		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
		
		$customfields = array();
		foreach ($customcheckoutfields as $ck => $cv) {
			$cv = $JCrypt->rsa->encrypt($cv, $JCrypt->pub_key);
			$ck = str_replace('customcheckoutfields_', '', $ck);
			$ck = str_replace('_', " ", $ck);
			$customfields[$ck] = $cv;
		}
										
		if ($payment_gateway == 'GoogleCheckout') {
			$server_type = "";  // change this to go live
			$currency = 'USD';  // set to GBP if in the UK
		
			require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googlecart.php');
			require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googleitem.php');
			require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googleshipping.php');
			require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googletax.php');

			define('RESPONSE_HANDLER_ERROR_LOG_FILE', JAWS_DATA . 'logs/googleerror.log');
			define('RESPONSE_HANDLER_LOG_FILE', JAWS_DATA . 'logs/googlemessage.log');

			/**
			 * Copyright (C) 2007 Google Inc.
			 * 
			 * Licensed under the Apache License, Version 2.0 (the "License");
			 * you may not use this file except in compliance with the License.
			 * You may obtain a copy of the License at
			 * 
			 *      http://www.apache.org/licenses/LICENSE-2.0
			 * 
			 * Unless required by applicable law or agreed to in writing, software
			 * distributed under the License is distributed on an "AS IS" BASIS,
			 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
			 * See the License for the specific language governing permissions and
			 * limitations under the License.
			 */

			/*
			// The idea of this usecase is to show how to implement Server2Server
			// Checkout API Requests
			// http://code.google.com/apis/checkout/developer/index.html#alternate_technique
			*/
				
			$cart = new GoogleCart($merchant_id, $merchant_key, $server_type, $currency);
			//  Check this URL for more info about the two types of digital Delivery
			//  http://code.google.com/apis/checkout/developer/Google_Checkout_Digital_Delivery.html
		} else if ($payment_gateway == 'AuthorizeNet') {
			$submit_url = 'https://secure.authorize.net/gateway/transact.dll';
		} else if ($payment_gateway == 'PayPal') {
			// Included required files.
			require_once(JAWS_PATH . 'libraries/PayPal/paypal.nvp.class.php');

			// Sandbox (Test) Mode Trigger
			$sandbox = false;

			// PayPal API Credentials
			$api_username = ($sandbox ? 'PAYPAL_API_USERNAME' : $merchant_id);
			$api_password = ($sandbox ? 'PAYPAL_API_PASSWORD' : $merchant_key);
			$api_signature = ($sandbox ? 'PAYPAL_API_SIGNATURE' : $merchant_signature);
		}
		$OwnerPayments = array();
				
		// TODO: For each order entry (which can be more than one per order - i.e. parallel payments), 
		// we need to create a "NOTPROCESSED" order entry in DB, then update it to "NEW" if order is
		// approved. So, the earliest when each order is known, we should create the DB entries
		
		//$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
		$string = uniqid('', true);
		$string = preg_replace('#[^\d]+#', '', $string);
		while (strlen($string) > 9) {
			$string = substr($string, 0, (strlen($string)-1));
		}
		$order_number = (int)$string;
		/*
		// send highest order number
		$sql = "SELECT MAX([orderno]) FROM [[order]] ORDER BY [orderno] DESC";
		$max = $GLOBALS['db']->queryOne($sql);
		if (Jaws_Error::IsError($max)) {
		   return $max;
		}
		if (is_numeric($max)) {
			$order_number = $max+1;
		} else {
			$order_number = 1;
		}
		
		$ecommerceAdminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$ecommerceAdminModel->AddOrder(
			$order_number, 0, 0, 1, '', 1, 
			'', 0, '', 'N', 
			'', '', 0, 'NOTPROCESSED', '', '', 
			'', '', '', 
			'', '', '', '', 
			'', '', '', '', 
			'', '', '', 
			'', '', 0, ''
		);
		*/
				
		$cart_data = array(
			'items' => $items, 
			'paymentmethod' => $paymentmethod, 
			'customer_shipfirstname' => $customer_shipfirstname, 
			'customer_shiplastname' => $customer_shiplastname, 
			'customer_shipaddress' => $customer_shipaddress, 
			'customer_shipcity' => $customer_shipcity, 
			'customer_shipregion' => $customer_shipregion, 
			'customer_shippostal' => $customer_shippostal, 
			'customer_shipcountry' => $customer_shipcountry, 
			'shipfreight' => $shipfreight, 
			'customer_shipaddress2' => $customer_shipaddress2, 
			'customer_firstname' => $customer_firstname, 
			'customer_middlename' => $customer_middlename, 
			'customer_lastname' => $customer_lastname, 
			'customer_suffix' => $customer_suffix, 
			'customer_address' => $customer_address, 
			'customer_address2' => $customer_address2, 
			'customer_city' => $customer_city, 
			'customer_region' => $customer_region,
			'customer_postal' => $customer_postal, 
			'customer_country' => $customer_country,
			'customer_phone' => $customer_phone,
			'cc_creditcardtype' => $cc_creditcardtype,
			'cc_acct' => $cc_acct, 
			'cc_expdate_month' => $cc_expdate_month, 
			'cc_expdate_year' => $cc_expdate_year, 
			'cc_cvv2' => $cc_cvv2, 
			'total_weight' => $total_weight,
			'redirect_to' => $redirect_to,
			'sales_id' => $sales_id,
			'sales_code' => $sales_code,
			'customcheckoutfields' => $customfields,
			'usecase' => $usecase,
			'session_id' => $session_id,
			'orderno' => $order_number
		);
					
		$GLOBALS['app']->Session->PushSimpleResponse($cart_data, 'Ecommerce.Cart.Data');	
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onBeforeCheckout', 
			$cart_data
		);

		if (Jaws_Error::IsError($res) || !$res) {
			$submit_vars['message'] = 'Error: '.(Jaws_Error::IsError($res) ? $res->GetMessage() : "Could not complete checkout. Please try again later.");
			return $submit_vars;
		} else if (isset($res['message']) && !empty($res['message'])) {
			$submit_vars['message'] = $res['message'];
			if (isset($res['url'])) {
				$submit_vars['url'] = $res['url'];
			}
			if (isset($res['body'])) {
				$submit_vars['body'] = $res['body'];
			}
			if (isset($res['form_submit'])) {
				$submit_vars['form_submit'] = $res['form_submit'];
			}
			if (isset($res['form'])) {
				$submit_vars['form'] = $res['form'];
			}
			return $submit_vars;
		}
		
		if (!empty($site_ssl_url) && ($payment_gateway == 'ManualCreditCard' || (!empty($merchant_id) && !empty($merchant_key)))) {			
			$request =& Jaws_Request::getInstance();
			$get = $request->getRaw(array('usecase', 'redirect'));
			$output = '';
			$total = 0.00;
			$total_weight = 0.00;
			$total_quantity = 0;
			$i = 0;
			$attribute_nums = '';
			$prod_ids = '';
			
			$normalized_items = array();
			foreach ($items as $itmkey => $itmval) {
				if (substr($itmkey, 0, 5) == "item_") {
					$num = (int)end(explode('_', $itmkey));
					if (!isset($normalized_items[$num])) {
						$normalized_items[$num] = array();
					}
					if (substr($itmkey, ((-1)*(strlen($num)+1))) == '_'.$num) {
						if (substr($itmkey, 0, 13) == "item_quantity") {
							$normalized_items[$num]['qa'] = $itmval;
						} else if (substr($itmkey, 0, 10) == "item_price") {
							$normalized_items[$num]['properties']['price'] = $itmval;
						} else if (substr($itmkey, 0, 12) == "item_options") {
							foreach (explode(',', $itmval) as $itmoption) {
								$optionkey = trim(substr($itmoption, 0, strpos($itmoption, ": ")));
								$optionval = trim(substr($itmoption, (strpos($itmoption, ": ")+1), strlen($itmoption)));
								switch ($optionkey) {
									case 'producturl': 
										$normalized_items[$num]['properties']['url'] = $optionval;
										break;
									case 'weight': 
										$normalized_items[$num]['properties']['weight'] = $optionval;
										break;
									default: 
										$normalized_items[$num]['customAttributes'][$optionkey] = $optionval;
										break;
								}								
							}
						}
					}
				}
			}

			foreach ($normalized_items as $item) {
				//var_dump($item);
				//exit;
				$product_description = '';
				$product_quantity = $item['qa'];
				if (isset($item['customAttributes']['productid']) && !empty($item['customAttributes']['productid'])) {
					$prod_ids .= (!empty($prod_ids) ? ',' : '').$item['customAttributes']['productid'];
					$product_id = $item['customAttributes']['productid'];
					$page = $model->GetProduct((int)$product_id);
					if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
						
						$product_image = '';
						if (isset($page['image']) && !empty($page['image'])) {
							if (substr($page['image'],0,7) == "GADGET:") {
								$product_image = $xss->filter(strip_tags($page['image']));
							} else {
								$product_image = $xss->filter(strip_tags($page['image']));
								if (substr(strtolower($product_image), 0, 4) == "http") {
									if (substr(strtolower($product_image), 0, 7) == "http://") {
										$product_image = explode('http://', $product_image);
										foreach ($product_image as $img_src) {
											if (!empty($img_src)) {
												$product_image = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$product_image = explode('https://', $product_image);
										foreach ($product_image as $img_src) {
											if (!empty($img_src)) {
												$product_image = 'https://'.$img_src;
												break;
											}
										}
									}
									if (strpos(strtolower($product_image), 'data/files/') !== false) {
										$product_image = 'image_thumb.php?uri='.urlencode($product_image);
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($product_image);
									$medium = Jaws_Image::GetMediumPath($product_image);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$product_image = $GLOBALS['app']->getDataURL('', true) . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$product_image = $GLOBALS['app']->getDataURL('', true) . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$product_image)) {
										$product_image = $GLOBALS['app']->getDataURL('', true) . 'files'.$product_image;
									}
								}
							}
						}
						/*
						$item_image = '';
						if (isset($item['properties']['image']) && !empty($item['properties']['image'])) {
							$item_image = $xss->parse($item['properties']['image']);
						}	
						if ($product_image != $item_image) {
							$error .= '::: Images not the same. Expected: '.$product_image.', received: '.$item_image;
						}
						*/
						
						$product_title = '';
						if (isset($page['title']) && !empty($page['title'])) {
							$product_title = $xss->parse(strip_tags($page['title']));
						}
						/*
						$item_title = '';
						if (isset($item['properties']['title']) && !empty($item['properties']['title'])) {
							$item_title = $xss->parse(strip_tags($item['properties']['title']));
						}
						if (strtolower(trim($product_title)) != strtolower(trim($item_title))) {
							$error .= '::: Titles not the same. Expected: '.$product_title.', received: '.$item_title;
						}
						*/
						
						$price = '';
						$orig_product_price = 0;
						if (!empty($page['price']) && ($page['price'] > 0)) {
							$price = number_format($page['price'], 2, '.', '');
							$orig_product_price = number_format($page['price'], 2, '.', '');
						}
						// TODO: Add AJAX coupon code verifier. On coupon code input,
						// disable Add to Cart button, verify code, show result, add
						// product-attr-sale that updates price if necessary and re-enable button
						// sales
						$now = $GLOBALS['db']->Date();
						$sale_price = $price;
						$sale_string = number_format($price, 2, '.', '');
						if (isset($saleInfo['id']) && !empty($saleInfo['id'])) {
							if ($saleInfo['discount_amount'] > 0) {
								$sale_price = number_format($sale_price - number_format($saleInfo['discount_amount'], 2, '.', ''), 2, '.', '');
							} else if ($saleInfo['discount_percent'] > 0) {
								$sale_price = number_format($sale_price - ($sale_price * ($saleInfo['discount_percent'] * .01)), 2, '.', '');
							} else if ($saleInfo['discount_newprice'] > 0) {
								$sale_price = number_format($saleInfo['discount_newprice'], 2, '.', '');
							}
						} else if (isset($page['sales']) && !empty($page['sales'])) {
							$propSales = explode(',', $page['sales']);
							foreach($propSales as $propSale) {		            
								$saleParent = $model->GetSale((int)$propSale);
								if (!Jaws_Error::IsError($saleParent)) {
									if (
										empty($sales_code) && empty($saleParent['coupon_code']) && 
										$saleParent['active'] == 'Y' && ($now > $saleParent['startdate'] && $now < $saleParent['enddate'])
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
							}
						}
						$sale_string = ($sale_price > 0 ? $sale_price : 0.00);
						$price_string = number_format($price, 2, '.', '');
						$product_price = ($sale_string != $price_string ? $sale_string : $price_string);
																														
						$numeric_weight = 0;
						$product_weight = 0;
						if (isset($page['weight']) && !empty($page['weight']) && $page['weight'] > 0) {
							$product_weight = number_format($page['weight'], 2, '.', '');
						}
						$item_weight = 0;
						if (isset($item['properties']['weight']) && !empty($item['properties']['weight'])) {
							$item_weight = $item['properties']['weight'];
							// Format weight
							$newstring = "";
							$array = str_split($item_weight);
							foreach($array as $char) {
								if (($char >= '0' && $char <= '9') || $char == '.') {
									$newstring .= $char;
								}
							}
							$item_weight = number_format($newstring, 2, '.', '');
						}
						if ((number_format($product_weight, 2, '.', ',') !== number_format($item_weight, 2, '.', ','))) {
							$error .= '::: Weights not the same. Expected: '.$product_weight.', received: '.$item_weight;
						} else {
							$numeric_weight = number_format(($product_quantity*$item_weight), 2, '.', '');
						}
						if ($numeric_weight > 0) {
							$has_weight = true;
						}
						
						$product_redirect = $GLOBALS['app']->getSiteURL() . "/index.php?gadget=Store&action=Product&id=".$page['id'];
						$item_redirect = '';
						if (isset($item['properties']['url']) && !empty($item['properties']['url'])) {
							$item_redirect = $item['properties']['url'];
						}
						/*
						if ($product_redirect != $item_redirect) {
							$error .= '::: URLs not the same. Expected: '.$product_redirect.', received: '.$item_redirect;
						}
						*/
						
						$custom_attributes = '';
						$product_brand = '';
						$item_brand = '';
						$product_setup_price = 0;
						$item_setup_price = 0;
						$product_sm_description = '';
						$item_sm_description = '';
						$product_code = '';
						$item_code = '';
						$product_recurring = 'N';
						$item_recurring = 'N';
						$product_unit = '';
						$item_unit = '';
						$product_retail_price = 0;
						$item_retail_price = 0;
						$privateItemData = array();
						if (isset($item['customAttributes']) && is_array($item['customAttributes'])) {
							foreach($item['customAttributes'] as $attribute_key => $attibute_value) {
								if ($attribute_key == 'retail') {
									if (isset($page['retail']) && !empty($page['retail']) && $page['retail'] > 0) {
										$product_retail_price = number_format($page['retail'], 2, '.', '');
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_retail_price = $attibute_value;
										// Format setup price
										$newstring = "";
										$array = str_split($item_retail_price);
										foreach($array as $char) {
											if (($char >= '0' && $char <= '9') || $char == '.') {
												$newstring .= $char;
											}
										}
										$item_retail_price = number_format($newstring, 2, '.', '');
									}
									if ($product_retail_price !== $item_retail_price) {
										$error .= '::: Retail prices not the same. Expected: '.$product_retail_price.', received: '.$item_retail_price;
									} else {
										$privateItemData[$attribute_key] = $attibute_value;
									}
								} else if ($attribute_key == 'unit') {
									if (isset($page['unit']) && !empty($page['unit'])) {
										$product_unit = $xss->parse(strip_tags($page['unit']));
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_unit = $xss->parse(strip_tags($attibute_value));
									}
									if ($product_unit != $item_unit) {
										$error .= '::: Units not the same. Expected: '.$product_unit.', received: '.$item_unit;
									} else {
										$privateItemData[$attribute_key] = $attibute_value;
									}
								} else if ($attribute_key == 'recurring') {
									if (isset($page['recurring']) && !empty($page['recurring'])) {
										$product_recurring = 'Is subscription: '.$page['recurring'];
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_recurring = $attibute_value;
									}
									if ($product_recurring != $item_recurring) {
										$error .= '::: Recurring not the same. Expected: '.$product_recurring.', received: '.$item_recurring;
									} else {
										$privateItemData[$attribute_key] = $attibute_value;
									}
								} else if ($attribute_key == 'productcode') {
									if (isset($page['product_code']) && !empty($page['product_code'])) {
										$product_code = 'Product Code: '.$xss->parse(strip_tags($page['product_code']));
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_code = $xss->parse(strip_tags($attibute_value));
									}
									/*
									if (strtolower(trim($product_code)) != strtolower(trim($item_code))) {
										$error .= '::: Product Codes not the same. Expected: '.$product_code.', received: '.$item_code;
									} else {
									*/
										$privateItemData[$attribute_key] = $attibute_value;
									//}	
								} else if ($attribute_key == 'summary') {
									if (isset($page['sm_description']) && !empty($page['sm_description'])) {
										$product_sm_description = $xss->parse(strip_tags($page['sm_description']));
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_sm_description = $xss->parse(strip_tags($attibute_value));
									}
									/*
									if ($product_sm_description != $item_sm_description) {
										$error .= '::: Summaries not the same. Expected: '.$product_sm_description.', received: '.$item_sm_description;
									} else {
									*/
										$privateItemData[$attribute_key] = $attibute_value;
									//}								
								} else if ($attribute_key == 'brand') {
									if (!empty($page['brandid']) && ($page['brandid'] > 0)) {
										$brandParent = $model->GetBrand($page['brandid']);
										if (!Jaws_Error::IsError($brandParent)) {
											$product_brand = $xss->parse(strip_tags($brandParent['title']));
											$product_brand = 'Brand: '.$product_brand;
										}
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_brand = $attibute_value;
									}
									/*
									if ($product_brand != $item_brand) {
										$error .= '::: Brands not the same. Expected: '.$product_brand.', received: '.$item_brand;
									} else {
									*/
										$privateItemData[$attribute_key] = $attibute_value;
									//}
									
								} else if ($attribute_key == 'setupfee') {
									if (isset($page['setup_fee']) && !empty($page['setup_fee']) && $page['setup_fee'] > 0) {
										$product_setup_price = number_format($page['setup_fee'], 2, '.', '');
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_setup_price = $attibute_value;
										// Format setup price
										$newstring = "";
										$array = str_split($item_setup_price);
										foreach($array as $char) {
											if (($char >= '0' && $char <= '9') || $char == '.') {
												$newstring .= $char;
											}
										}
										$item_setup_price = number_format($newstring, 2, '.', '');
									}
									if ($product_setup_price !== $item_setup_price) {
										$error .= '::: Setup Fee prices not the same. Expected: '.$product_setup_price.', received: '.$item_setup_price;
									} else {
										$privateItemData[$attribute_key] = $attibute_value;
									}
								} else if ($attribute_key == 'description') {
									// description
									if (isset($page['description']) && !empty($page['description'])) {
										$product_description = $xss->parse(strip_tags($page['description'], 'Store'));
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_description = $xss->parse(strip_tags($attibute_value, 'Store'));
									}
									/*
									if ($product_description != $item_description) {
										$error .= '::: Descriptions not the same. Expected: '.$product_description.', received: '.$item_description;
									} else {
									*/
										$privateItemData[$attribute_key] = $attibute_value;
									//}
								} else if ($attribute_key == 'contact' || $attribute_key == 'company' || $attribute_key == 'contactwebsite' || $attribute_key == 'contactphone' || $attribute_key == 'contactemail') {
									// contact information
									if (((isset($page['contact']) && !empty($page['contact'])) || (isset($page['company']) && !empty($page['company']))) && ((isset($item['customAttributes']['contact']) && !empty($item['customAttributes']['contact'])) || (isset($item['customAttributes']['company']) && !empty($item['customAttributes']['company'])))) {
										
										if ($attribute_key == 'contact') {
											$agent_html = '';
											if (isset($page['contact']) && !empty($page['contact'])) {
												$agent_html .= 'Listed by: '.$xss->parse(strip_tags($page['contact']));
											}
											$product_contact = $agent_html;
											if (isset($attibute_value) && !empty($attibute_value)) {
												$item_contact = $attibute_value;
											}
											/*
											if (strtolower(trim($product_contact)) != strtolower(trim($item_contact))) {
												$error .= '::: Contacts not the same. Expected: '.$product_contact.', received: '.$item_contact;
											} else {
											*/
												$privateItemData[$attribute_key] = $attibute_value;
											//}
										} else if ($attribute_key == 'contactwebsite') {
											$agent_website = '';
											$agent_website_html = '';
											if (isset($page['contact_website']) && !empty($page['contact_website'])) {
												$agent_website = $xss->parse(strip_tags($page['contact_website']));
												$agent_website_html .= $xss->parse(strip_tags($page['contact_website']));
											} else if (isset($page['company_website']) && !empty($page['company_website'])) {
												$agent_website = $xss->parse(strip_tags($page['company_website']));
												$agent_website_html .= $xss->parse(strip_tags($page['company_website']));
											}
											$product_contact_website = $agent_website_html;
											if (isset($attibute_value) && !empty($attibute_value)) {
												$item_contact_website = $attibute_value;
											}
											/*
											if (strtolower(trim($product_contact_website)) != strtolower(trim($item_contact_website))) {
												$error .= '::: Contact websites not the same. Expected: '.$product_contact_website.', received: '.$item_contact_website;
											} else {
											*/
												$privateItemData[$attribute_key] = $attibute_value;
											//}
										
										} else if ($attribute_key == 'company') {
											$broker_html = '';
											if (isset($page['company']) && !empty($page['company'])) {
												$broker_html .= ($agent_html != '' ? 'of ' : '').$xss->parse(strip_tags(str_replace('&nbsp;', ' ', $page['company'])));
											}
											$product_company = $broker_html;
											if (isset($attibute_value) && !empty($attibute_value)) {
												$item_company = $attibute_value;
											}
											/*
											if (strtolower(trim($product_company)) != strtolower(trim($item_company))) {
												$error .= '::: Companies not the same. Expected: '.$product_company.', received: '.$item_company;
											} else {
											*/
												$privateItemData[$attribute_key] = $attibute_value;
											//}
										} else if ($attribute_key == 'contactphone') {
											$agent_phone_html = '';
											if (isset($page['agent_phone']) && !empty($page['contact_phone']) && strpos($page['contact_phone'], "://") === false) {
												$agent_phone_html .= 'Phone: '.$xss->parse(strip_tags($page['contact_phone']));
											} else if (isset($page['company_phone']) && !empty($page['company_phone']) && strpos($page['company_phone'], "://") === false) {
												$agent_phone_html .= 'Phone: '.$xss->parse(strip_tags($page['company_phone']));
											}
											$product_contact_phone = $agent_phone_html;
											if (isset($attibute_value) && !empty($attibute_value)) {
												$item_contact_phone = $attibute_value;
											}
											/*
											if (strtolower(trim($product_contact_phone)) != strtolower(trim($item_contact_phone))) {
												$error .= '::: Contact Phone Numbers not the same. Expected: '.$product_contact_phone.', received: '.$item_contact_phone;
											} else {
											*/
												$privateItemData[$attribute_key] = $attibute_value;
											//}
										} else if ($attribute_key == 'contactemail') {
											$agent_email_html = '';
											if (isset($page['contact_email']) && !empty($page['contact_email'])) {
												$agent_email_html .= 'E-mail: '.$xss->parse(strip_tags($page['contact_email']));
											} else if (isset($page['company_email']) && !empty($page['company_email'])) {
												$agent_email_html .= 'E-mail: '.$xss->parse(strip_tags($page['company_email']));
											}
											$product_contact_email = $agent_email_html;
											if (isset($attibute_value) && !empty($attibute_value)) {
												$item_contact_email = $attibute_value;
											}
											/*
											if (strtolower(trim($product_contact_email)) != strtolower(trim($item_contact_email))) {
												$error .= '::: Contact E-mail Addresses not the same. Expected: '.$product_contact_email.', received: '.$item_contact_email;
											} else {
											*/
												$privateItemData[$attribute_key] = $attibute_value;
											//}
										}
									}
								} else if ($attribute_key == 'additionalinfo') {
									// external links
									$product_alink = '';
									if (!empty($page['alink'])) {
										if (!empty($page['alink']) && !empty($page['alinktype'])) {
											$product_alink = ($page['alinktype'] == 'M' ? 'mailto:' : 'http://').$xss->parse(strip_tags($page['alink']));
										}
									}
									if (isset($attibute_value) && !empty($attibute_value)) {
										$item_alink = $attibute_value;
									}
									/*
									if (strtolower(trim($product_alink)) != strtolower(trim($item_alink))) {
										$error .= '::: Additional Info Links not the same. Expected: '.$product_alink.', received: '.$item_alink;
									} else {
									*/
										$privateItemData[$attribute_key] = $attibute_value;
									//}
									
								} else if ($attribute_key == 'productid') {
									$privateItemData[$attribute_key] = $attibute_value;
								} else if ($attribute_key == 'ownerid') {
									$privateItemData[$attribute_key] = $attibute_value;
							} else if ($attribute_key == 'gadgettable') {
									if (isset($attibute_value) && !empty($attibute_value)) {
										$privateItemData[$attribute_key] = $attibute_value;
										if ($payment_gateway == 'AuthorizeNet') {
											$submit_vars['x_gadget_table'] = $attibute_value;
										}
									}
								} else if ($attribute_key == 'gadgetid') {
									if (isset($attibute_value) && !empty($attibute_value)) {
										$privateItemData[$attribute_key] = $attibute_value;
										if ($payment_gateway == 'AuthorizeNet') {
											$submit_vars['x_gadget_id'] = $attibute_value;
										}
									}
								} else {	
									// attributes
									if (isset($page['attribute']) && !empty($page['attribute'])) {
										$amenityTypes = $model->GetAttributeTypes();
										if (!Jaws_Error::IsError($amenityTypes)) {
											$propAmenities = explode(',', $page['attribute']);
											foreach($amenityTypes as $amenityType) {		            
												// Find received item attributes with key "$amenityType['title']"
												if ($attribute_key == str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))) {
													$item_attribute = '';
													//$comma = '';
													// Loop through all of the product's possible attributes
													foreach($propAmenities as $propAmenity) {		            
														$amenity = '';
														$amenityParent = $model->GetAttribute((int)$propAmenity);
														// Is this attribute is a child of the current parent?
														if (!Jaws_Error::IsError($amenityParent) && $amenityType['id'] == $amenityParent['typeid']) {
															// Ok, then get the expected value
															if ($amenityType['itype'] == 'Normal') {
																$amenity = $amenityParent['feature'];
															} else {
																$amenity = $xss->parse($amenityType['title']).': '.$xss->parse($amenityParent['feature']);
															}
															if (isset($attibute_value) && !empty($attibute_value)) {
																// Explode the received item attributes
																$item_attributes = explode(',', $attibute_value);
																foreach ($item_attributes as $item_value) {
																	// Is the expected value the same as the received value? 
																	if (trim($amenity) == trim($item_value) || (($amenityType['itype'] == 'TextBox' || $amenityType['itype'] == 'TextArea' || $amenityType['itype'] == 'HiddenField') && trim($item_value) != '')) {
																		// Ok, then set the attribute and update the price
																		$item_attribute .= ','.((($amenityType['itype'] == 'TextBox' || $amenityType['itype'] == 'TextArea' || $amenityType['itype'] == 'HiddenField') && trim($item_value) != '') ? $xss->parse($amenityType['title']).': ' : '').$item_value;
																		$attribute_nums .= ','.$amenityParent['id'];
																		if ($amenityParent['add_amount'] > 0) {
																			$attr_price = ((number_format($amenityParent['add_amount'], 2, '.', ''))+$product_price);
																			$attr_price = number_format($attr_price, 2, '.', '');
																			$product_price = $attr_price;
																		} else if ($amenityParent['add_percent'] > 0) {
																			$attr_price = ((($amenityParent['add_percent'] * .01) * ($orig_product_price))+$product_price);
																			$attr_price = number_format($attr_price, 2, '.', '');
																			$product_price = $attr_price;
																		} else if ($amenityParent['newprice'] > 0) {
																			$attr_price = number_format($amenityParent['newprice'], 2, '.', '');
																			if ($attr_price > $orig_product_price) {
																				$add_amount = number_format(($attr_price - $orig_product_price), 2, '.', '');
																			} else {
																				$add_amount = number_format(($orig_product_price - $attr_price)*(-1), 2, '.', '');
																			}
																			$product_price =  number_format(($product_price+$add_amount), 2, '.', '');
																		}
																		//$comma = ',';
																	}
																}
															}
														}
													}
													$custom_attributes .= $item_attribute;
													$privateItemData[$attribute_key] = $item_attribute;
												}
											}
										}
									}
								}
							}
						}

						if ($item_setup_price === $product_setup_price) {
							$product_price = (($product_setup_price/$product_quantity)+$product_price);
						}								
						
						if (isset($item['properties']['price']) && !empty($item['properties']['price'])) {
							$item_price = number_format($item['properties']['price'], 2, '.', '');
							if (isset($saleInfo['id']) && !empty($saleInfo['id'])) {
								if ($saleInfo['discount_amount'] > 0) {
									$item_price = number_format($item_price - number_format($saleInfo['discount_amount'], 2, '.', ''), 2, '.', '');
								} else if ($saleInfo['discount_percent'] > 0) {
									$item_price = number_format($item_price - ($item_price * ($saleInfo['discount_percent'] * .01)), 2, '.', '');
								} else if ($saleInfo['discount_newprice'] > 0) {
									$item_price = number_format($saleInfo['discount_newprice'], 2, '.', '');
								}
							}
						}
						if ((number_format($product_price, 2, '.', ',') !== number_format($item_price, 2, '.', ','))) {
							$error .= '::: Prices not the same. Expected: '.number_format($product_price, 2, '.', ',').', received: '.number_format($item_price, 2, '.', ',');
						}
						
						$item_desc = '';
						if (!empty($custom_attributes)) {
							$item_desc .= $custom_attributes;
						} else {
							foreach($privateItemData as $privateItemKey => $privateItemVal) {
								if ($privateItemKey != 'ownerid' && $privateItemKey != 'gadgettable' && $privateItemKey != 'gadgetid') {
									$item_desc .= $privateItemVal.', ';
								}
							}
						}

						$product_desc = $item_desc.($numeric_weight > 0 ? ', Weight: '.$numeric_weight.',' : '');
						if ($payment_gateway == 'GoogleCheckout') {
							//function GoogleItem($name, $desc, $qty, $price, $item_weight='', $numeric_weight='')
							$google_item = new GoogleItem(
								$product_title,      // Item name
								$product_desc, // Item description
								$product_quantity, // Quantity
								number_format($product_price, 2, '.', ''), // Unit price
								'LB', 
								$numeric_weight // Numeric weight
							);					
							if (isset($item['customAttributes']) && is_array($item['customAttributes']) && count($privateItemData) > 0) {
								reset($privateItemData);
								$google_item->SetMerchantPrivateItemData(
									new MerchantPrivateItemData($privateItemData)
								);
							}
							if (isset($product_code) && !empty($product_code)) {
								$google_item->SetMerchantItemId($product_code);
							}
							
							/*
							$google_item->SetURLDigitalContent($GLOBALS['app']->GetSiteURL().'/index.php?user/login.html',
														'S/N: 123.123123-3213',
														"Download Item1");
							*/
							if ($numeric_weight <= 0) {
								$google_item->SetEmailDigitalDelivery('true');
							}

							if ($error == '') {
								$cart->AddItem($google_item);
							}
						} else if ($payment_gateway == 'AuthorizeNet') {
							//$line_items[] = $product_id.'<|>'.$product_title.'<|>'.$item_desc.'<|>'.$product_quantity.'<|>'.$product_price.'<|>Y';
							$submit_vars['x_line_item['.$i.']'] = $product_id.'<|>'.$xss->parse(ereg_replace("[^A-Za-z0-9\:\ \,]", '', $product_title)).'<|>'.$xss->parse(ereg_replace("[^A-Za-z0-9\:\ \,]", '', $product_desc)).'<|>'.$product_quantity.'<|>'.$product_price.'<|>Y';
						}
						$OwnerID = (int)$privateItemData['ownerid'];
						if ($OwnerID > 0) {
							$uInfo = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
							if (isset($uInfo['id'])) {
								if (isset($uInfo['merchant_id']) && empty($uInfo['merchant_id'])) {
									$no_merchants .= '::: '.((isset($uInfo['username']) && !empty($uInfo['username'])) || (isset($uInfo['company']) && !empty($uInfo['company'])) ? (!empty($uInfo['company']) ? $uInfo['company'] : 'Merchant: '.$uInfo['username']) : 'Merchant #'.$OwnerID);
									$no_merchants .= ' has not supplied a Merchant ID in their profile.';
								}
							}
						} else {
							$OwnerID = 0;
							// TODO: Get site owner's shipfrom state
						}
						if (!isset($OwnerPayments[$OwnerID])) {
							$OwnerPayments[$OwnerID] = array();
						}							
						$Item = array(
							'name' => $product_title, 															// Item name. 127 char max.
							'desc' => substr($product_desc, 0, 127), 											// Item description. 127 char max.
							'amt' => number_format($product_price, 2, '.', ''), 								// Cost of item.
							'qty' => $product_quantity, 														// Item qty on order.  Any positive integer.
							'taxamt' => 0.00, 																	// Item sales tax
							'itemurl' => $product_redirect, 													// URL for the item.
							'itemweightvalue' => $numeric_weight, 												// The weight value of the item.
							'itemweightunit' => 'lbs', 															// The weight unit of the item.
							'itemheightvalue' => '10', 															// The height value of the item.
							'itemheightunit' => 'inches', 														// The height unit of the item.
							'itemwidthvalue' => '10', 															// The width value of the item.
							'itemwidthunit' => 'inches', 														// The width unit of the item.
							'itemlengthvalue' => '10', 															// The length value of the item.
							'itemlengthunit' => 'inches',  														// The length unit of the item.
							'number' => $product_id 															// Item number.  127 char max.
						);
						array_push($OwnerPayments[$OwnerID], $Item);
						$total = number_format(($total+($product_quantity*$product_price)), 2, '.', '');
						$total_quantity = $total_quantity+$product_quantity;
						$total_weight = number_format(($total_weight+$numeric_weight), 2, '.', '');
						$i++;
					}
				}
			}
			
			$total = number_format($total, 2, '.', '');
			$total_weight = number_format($total_weight, 2, '.', '');
			
			if (
				$total_weight > 0 && (empty($customer_shiplastname) && 
				empty($customer_shipfirstname) && empty($customer_shipaddress) && 
				empty($customer_shipcity) && empty($customer_shipregion) && 
				empty($customer_shippostal) && empty($customer_shipcountry))
			) {
				$submit_vars['message'] = 'javascript';
				$submit_vars['body'] = 'showShipping';
				return $submit_vars;
			}

			if ($payment_gateway == 'GoogleCheckout' && $total > 0) {
				if (!empty($error)) {
					$submit_vars['message'] = $error;
					return $submit_vars;
					//$GLOBALS['app']->Session->PushLastResponse($error, RESPONSE_ERROR);
					//return false;
				} else {
					// Add tax rules
					$tax_rule = new GoogleDefaultTaxRule(0.07);
					$tax_rule->SetStateAreas(array("GA"));
					$cart->AddDefaultTaxRules($tax_rule);

					/*
					// Add US tax rules
					$tax_rule_1 = new GoogleDefaultTaxRule(0.0825);
					$tax_rule_1->SetStateAreas(array("CA", "NY"));
					$cart->AddDefaultTaxRules($tax_rule_1);

					// Add International tax rules
					$tax_rule_2 = new GoogleDefaultTaxRule(0.15);
					$tax_rule_2->AddPostalArea("GB");
					$tax_rule_2->AddPostalArea("FR");
					$tax_rule_2->AddPostalArea("DE");
					$cart->AddDefaultTaxRules($tax_rule_2);
					*/
					
					// Specify <edit-cart-url>
					$cart->SetEditCartUrl($GLOBALS['app']->getSiteURL()."/index.php?products/all.html");

					// Specify "Return to xyz" link
					$cart->SetContinueShoppingUrl($GLOBALS['app']->getSiteURL()."/index.php?products/all.html");

					// Request buyer's phone number
					$cart->SetRequestBuyerPhone(true);

					/*
					if ($usecase == 'CarrierCalcUsecase' || $has_weight === true) {
						// TODO: Get default "ship from" stuff from somewhere
						$google_shipfrom_city = 'Dawsonville';
						$google_shipfrom_state = 'GA';
						$google_shipfrom_zip = '30534';
						
						if (isset($shipfrom_city) && !empty($shipfrom_city)) {
							$google_shipfrom_city = $shipfrom_city;
						}
						if (isset($shipfrom_state) && !empty($shipfrom_state)) {
							$google_shipfrom_state = strtoupper($shipfrom_state);
						}
						if (isset($shipfrom_zip) && !empty($shipfrom_zip)) {
							$google_shipfrom_zip = $shipfrom_zip+'';
						}
						
						$ship_from = new GoogleShipFrom('Store_origin',
														$google_shipfrom_city,
														'US',
														$google_shipfrom_zip,
														$google_shipfrom_state);
						
						$GSPackage = new GoogleShippingPackage($ship_from,36,36,36,'IN');
						$Gshipping = new GoogleCarrierCalculatedShipping('Carrier_shipping');
						$Gshipping->addShippingPackage($GSPackage);

						$CCSoption = new GoogleCarrierCalculatedShippingOption("10.99", "FedEx", "Ground", "0.99");
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						$CCSoption = new GoogleCarrierCalculatedShippingOption("22.99", "FedEx", "Express Saver");
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						$CCSoption = new GoogleCarrierCalculatedShippingOption("24.99", "FedEx", "2Day", "0", "10", 'REGULAR_PICKUP');
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						
						$CCSoption = new GoogleCarrierCalculatedShippingOption("11.99", "UPS", "Ground", "0.99", "5", 'REGULAR_PICKUP');
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						$CCSoption = new GoogleCarrierCalculatedShippingOption("18.99", "UPS", "3 Day Select");
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						$CCSoption = new GoogleCarrierCalculatedShippingOption("20.99", "UPS", "Next Day Air", "0", "10", 'REGULAR_PICKUP');
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						
						$CCSoption = new GoogleCarrierCalculatedShippingOption("9.99", "USPS", "Media Mail", "0", "2", 'REGULAR_PICKUP');
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						$CCSoption = new GoogleCarrierCalculatedShippingOption("15.99", "USPS", "Parcel Post");
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
						$CCSoption = new GoogleCarrierCalculatedShippingOption("18.99", "USPS", "Express Mail", "2.99", "10", 'REGULAR_PICKUP');
						$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
					
						$cart->AddShipping($Gshipping);

						$ship_1 = new GoogleFlatRateShipping("Flat Rate", 5.0);
						$restriction_1 = new GoogleShippingFilters();
						$restriction_1->SetAllowedCountryArea("CONTINENTAL_48");
						$ship_1->AddShippingRestrictions($restriction_1);
						$cart->AddShipping($ship_1);
					}
						
					// Add merchant calculations options
					$cart->SetMerchantCalculations(
						"http://200.69.205.154/~brovagnati/tools/unitTest/demo/responsehandlerdemo.php", // merchant-calculations-url
						"false", // merchant-calculated tax
						"true", // accept-merchant-coupons
						"true" // accept-merchant-gift-certificates
					);
					// Add merchant-calculated-shipping option
					$ship = new GoogleMerchantCalculatedShipping(
						"2nd Day Air", // Shippping method
						10.00 // Default, fallback price
					);
					$restriction = new GoogleShippingFilters();
					//$restriction->SetAllowedCountryArea('CONTINENTAL_48');
					$restriction->AddAllowedPostalArea("GB");
					$restriction->AddAllowedPostalArea("US");
					$restriction->SetAllowUsPoBox(false);
					$ship->AddShippingRestrictions($restriction);

					$address_filter = new GoogleShippingFilters();
					$address_filter->AddAllowedPostalArea("GB");
					$address_filter->AddAllowedPostalArea("US");
					$address_filter->SetAllowUsPoBox(false);
					$ship->AddAddressFilters($address_filter);
					
					$cart->AddShipping($ship);
					*/
					
					// Define rounding policy
					$cart->AddRoundingPolicy("HALF_UP", "PER_LINE");

					// Add analytics data to the cart if its set
					if(isset($_POST['analyticsdata']) && !empty($_POST['analyticsdata'])){
						$cart->SetAnalyticsData($_POST['analyticsdata']);
					}
					// This will do a server-2-server cart post and send an HTTP 302 redirect status
					// This is the best way to do it if implementing digital delivery
					// More info http://code.google.com/apis/checkout/developer/index.html#alternate_technique
					$result = $cart->CheckoutServer2Server(array(), '', false);
					if (is_array($result)) {
						// if i reach this point, something was wrong
						$output = "Error: An error has ocurred: HTTP Status: " . $result[0]. " :::: ";
						$output .= "Error message: ";
						$output .= $result[1];
						$submit_vars['message'] = $output;
						//$GLOBALS['app']->Session->PushLastResponse($output, RESPONSE_ERROR);
						//return false;
					} else {
						$submit_vars['form_submit'] = 'true';
						$submit_vars['url'] = $result;
						$submit_vars['message'] = 'true';
						//$GLOBALS['app']->Session->PushLastResponse($result, RESPONSE_NOTICE);
						//return true;
					}
					$order_details = $cart;
				}
				//
			} else if ($payment_gateway == 'AuthorizeNet' && $total > 0) {
				if (!empty($error)) {
					$submit_vars['message'] = $error;
					return $submit_vars;
					//$GLOBALS['app']->Session->PushLastResponse($error, RESPONSE_ERROR);
					//return false;
				} else {
					$shipping_total = 0;
					// Validate shipping cost
					if (!empty($shipfreight) || $shipfreight == '0') {
						if (strpos($shipfreight, " [$") === false) {
							$output = 0;
						} else {
							$shiptype = substr($shipfreight, 0, strpos($shipfreight, " [$"));
							$inputStr = $shipfreight;
							$delimeterLeft = "[$";
							$delimeterRight = "]";
							$posLeft=strpos($inputStr, $delimeterLeft);
							$posLeft+=strlen($delimeterLeft);
							$posRight=strpos($inputStr, $delimeterRight, $posLeft);
							$output = substr($inputStr, $posLeft, $posRight-$posLeft);
						}
						//var_dump($output);
						if ((int)$output > 0) {
							$shipping_ok = false;
							$ship_select = $this->GetShippingSelect($total_weight, $total, 1, $customer_shippostal, $customer_shipregion, 'US');
							if (strpos(strtolower($ship_select), $output) !== false) {
								$shipping_ok = true;
							}
							if ($shipping_ok === true) {
								$shipping_total = number_format(($shipping_total+$output), 2, '.', '');
								$total = number_format(($total+$shipping_total), 2, '.', '');
							} else {
								$submit_vars['message'] = 'Shipping method could not be validated.';
								return $submit_vars;
							}
						}
					} else if ($total_weight > 0) {
						$submit_vars['message'] = 'Shipping Method must be selected.';
						return $submit_vars;
					}

					// an invoice is generated using the date and time
					$invoice	= date('YmdHis');
					// a sequence number is randomly generated
					$sequence	= rand(1, 1000);
					// a timestamp is generated
					$timeStamp	= time();
					if (phpversion() >= '5.1.2') {	
						$fingerprint = hash_hmac("md5", $merchant_id . "^" . $sequence . "^" . $timeStamp . "^" . $total . "^", $merchant_key); 
					} else { 
						$fingerprint = bin2hex(mhash(MHASH_MD5, $merchant_id . "^" . $sequence . "^" . $timeStamp . "^" . $total . "^", $merchant_key)); 
					}
					
					$submit_vars['x_version'] = '3.1';
					$submit_vars['x_method'] = 'CC';
					$submit_vars['x_login'] = $merchant_id;
					$submit_vars['x_description'] = 'Purchase from '.$_SERVER['SERVER_NAME'];
					$submit_vars['x_owner_id'] = $OwnerID;
					$submit_vars['x_invoice_num'] = $order_number;
					$submit_vars['x_fp_sequence'] = $sequence;
					$submit_vars['x_fp_timestamp'] = $timeStamp;
					$submit_vars['x_fp_hash'] = $fingerprint;
					$submit_vars['x_show_form'] = 'PAYMENT_FORM';
					$submit_vars['x_relay_response'] = 'TRUE';
					$submit_vars['x_relay_url'] = "https://".$site_ssl_url."/index.php?gadget=Ecommerce&action=AuthorizeNetResponse&url=".urlencode(str_replace(array('http://', 'https://'), '', $GLOBALS['app']->getSiteURL()));
					$submit_vars['x_attribute'] = $attribute_nums;
					$submit_vars['x_amount'] = $total;
					$submit_vars['x_total_weight'] = $total_weight;
					
					if (!empty($customer_shipfirstname)) {
						$submit_vars['x_first_name'] = $customer_shipfirstname;
						$submit_vars['x_ship_to_first_name'] = $customer_shipfirstname;
					}
					if (!empty($customer_shiplastname)) {
						$submit_vars['x_last_name'] = $customer_shiplastname;
						$submit_vars['x_ship_to_last_name'] = $customer_shiplastname;
					}
					if (!empty($customer_shipaddress)) {
						$submit_vars['x_address'] = $customer_shipaddress;
						$submit_vars['x_ship_to_address'] = $customer_shipaddress;
					}
					if (!empty($customer_shipcity)) {
						$submit_vars['x_city'] = $customer_shipcity;
						$submit_vars['x_ship_to_city'] = $customer_shipcity;
					}
					if (!empty($customer_shipregion)) {
						$submit_vars['x_state'] = $customer_shipregion;
						$submit_vars['x_ship_to_state'] = $customer_shipregion;
					} else if ($total_weight > 0) {
						$submit_vars['message'] = 'Ship to State must be selected.';
						return $submit_vars;
					}
					if (!empty($customer_shippostal)) {
						$submit_vars['x_zip'] = $customer_shippostal;
						$submit_vars['x_ship_to_zip'] = $customer_shippostal;
					} else if ($total_weight > 0) {
						$submit_vars['message'] = 'Ship to Zip Code must be supplied.';
						return $submit_vars;
					}
					if (!empty($customer_shipcountry)) {
						$submit_vars['x_country'] = $customer_shipcountry;
						$submit_vars['x_ship_to_country'] = $customer_shipcountry;
					} else if ($total_weight > 0) {
						$submit_vars['message'] = 'Ship to Country must be supplied.';
						return $submit_vars;
					}
					if (!empty($customer_id) && $customer_id > 0) {
						$submit_vars['x_cust_id'] = $customer_id;
					}
					
					// Insert temporary order
					$params = array();
					$params['Active'] = 'TEMP';
					$params['orderno'] = $order_number;
					$params['prod_id'] = $prod_ids;
					$params['total'] = $total;
					$params['tax'] = '';
					$params['freight'] = $shipping_total;
					$params['qty'] = 1;
					$params['unit'] = '';
					$params['weight'] = $total_weight;
					$params['attribute'] = '';
					$params['shiptype'] = $shiptype;
					$params['gadget_table'] = '';
					$params['gadget_id'] = '';
					$params['OwnerID'] = $OwnerID;
					$params['customer_id'] = $customer_id;
					$orderDescription = array(
						'description' => 'Order from '.$site_name,
						'items' => $OwnerPayments[$OwnerID],
						'customcheckoutfields' => $customfields
					);
					$params['description'] = serialize($orderDescription);
					$params['customer_email'] = $customer_email;
					$params['customer_name'] = (!empty($customer_firstname) ? $customer_firstname.' '.$customer_lastname : $customer_shipfirstname.' '.$customer_shiplastname);
					$params['customer_company'] = '';
					$params['customer_address'] = (!empty($customer_address) ? $customer_address : $customer_shipaddress);
					$params['customer_address2'] = (!empty($customer_address2) ? $customer_address2 : $customer_shipaddress2);
					$params['customer_city'] = (!empty($customer_city) ? $customer_city : $customer_shipcity);
					$params['customer_region'] = (!empty($customer_region) ? $customer_region : $customer_shipregion);
					$params['customer_postal'] = (!empty($customer_postal) ? $customer_postal : $customer_shippostal);
					$params['customer_country'] = (!empty($customer_country) ? $customer_country : $customer_shipcountry);
					$params['customer_phone'] = $customer_phone;
					$params['customer_fax'] = '';
					$params['customer_shipname'] = $customer_shipfirstname.' '.$customer_shiplastname;
					$params['customer_shipaddress'] = $customer_shipaddress;
					$params['customer_shipaddress2'] = $customer_shipaddress2;
					$params['customer_shipcity'] = $customer_shipcity;
					$params['customer_shipregion'] = $customer_shipregion;
					$params['customer_shippostal'] = $customer_shippostal;
					$params['customer_shipcountry'] = $customer_shipcountry;
					$params['sales_id'] = $sales_id;
					$params['customer_cc_type'] = '';
					$params['customer_cc_number'] = '';
					$params['customer_cc_exp_month'] = '';
					$params['customer_cc_exp_year'] = '';
					$params['customer_cc_cvv'] = '';
					// TODO: Implement recurring, backorder
					$params['backorder'] = '0';
					$params['recurring'] = 'N'; 
											
					$adminHTML = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
					$result = $adminHTML->form_post(true, 'AddOrder', $params, md5('AuthorizeNet'));				
					if (!is_numeric($result)) {
						$submit_vars['message'] = 'Error: Order could not be added [for product IDs: '.$prod_ids.']';
						return $submit_vars;
					}
					
					
					//$snoopy = new Snoopy('Ecommerce');
					//if($snoopy->submit($submit_url, $submit_vars)) {
					$form = '<form method="POST" action="'.$submit_url.'" name="'.md5($fingerprint).'">'."\n";
					foreach($submit_vars as $sk => $sv) {
						$form .= '<input type="hidden" name="'.$sk.'" value="'.$sv.'" />'."\n";
					}
					/*
					if (Jaws_Utils::is_writable(JAWS_DATA . 'logs/')) {
						$result = file_put_contents(JAWS_DATA . 'logs/'.md5($fingerprint).'.log', $form);
						if ($result === false) {
							return new Jaws_Error("Couldn't create order file", _t('ECOMMERCE_NAME'));
							//return false;
						}
					}
					*/
					
					$form .= '</form>'."\n";
					$submit_vars['message'] = 'body';
					$submit_vars['body'] = $form;
					$submit_vars['form_submit'] = 'true';
					$submit_vars['form'] = md5($fingerprint);
					$order_details = array('ORDER' => $items, 'PAYMENTS' => $OwnerPayments, 'RESULT' => $result);
				}
			} else if ($payment_gateway == 'PayPal' && $total > 0) {	
				if (!empty($error)) {
					$submit_vars['message'] = $error;
					return $submit_vars;
					//$GLOBALS['app']->Session->PushLastResponse($error, RESPONSE_ERROR);
					//return false;
				} else {
					// Setup PayPal object
					$PayPalConfig = array('Sandbox' => $sandbox, 'APIUsername' => $api_username, 'APIPassword' => $api_password, 'APISignature' => $api_signature);
					$PayPal = new PayPal($PayPalConfig);
					$total = 0;
					$fee_total = 0;
					$shipping_total = 0;
					$tax_total = 0;
					// Basic array of survey choices.  Nothing but the values should go in here.  
					//$SurveyChoices = array('Choice 1', 'Choice2', 'Choice3', 'etc');
					$SurveyChoices = array();

					if ($total_weight > 0 && empty($customer_shiplastname)) {
						$submit_vars['message'] = 'Ship to Last Name must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipfirstname)) {
						$submit_vars['message'] = 'Ship to First Name must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipaddress)) {
						$submit_vars['message'] = 'Ship to Address must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipcity)) {
						$submit_vars['message'] = 'Ship to City must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipregion)) {
						$submit_vars['message'] = 'Ship to State must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shippostal)) {
						$submit_vars['message'] = 'Ship to Zip Code must be supplied.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipcountry)) {
						$submit_vars['message'] = 'Ship to Country must be supplied.';
						return $submit_vars;
					}
					$parallel = false;
					if (is_array($OwnerPayments) && !count($OwnerPayments) <= 0) {
						$parallel = true;
					}
					
					// Count number of Payments
					$numPayments = 0;
					foreach ($OwnerPayments as $OwnerID => $OwnerItems) {
						$numPayments++;
					}
					
					/*
					// DoDirectPayments??
					if (!empty($cc_acct)) {
						$DPFields = array(
							'paymentaction' => 'Authorization',		// How you want to obtain payment.  Authorization indidicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
							'ipaddress' => $_SERVER['REMOTE_ADDR'],	// Required.  IP address of the payer's browser.
							'returnfmfdetails' => '0' 				// Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
						);
										
						$CCDetails = array(
							'creditcardtype' => $cc_creditcardtype,	// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
							'acct' => $cc_acct, 					// Required.  Credit card number.  No spaces or punctuation.  
							'expdate' => $cc_expdate_month.$cc_expdate_year,	// Required.  Credit card expiration date.  Format is MMYYYY
							'cvv2' => $cc_cvv2						// Requirements determined by your PayPal account settings.  Security digits for credit card.
							//'startdate' => '', 						// Month and year that Maestro or Solo card was issued.  MMYYYY
							//'issuenumber' => ''						// Issue number of Maestro or Solo card.  Two numeric digits max.
						);
										
						$PayerInfo = array(
							'email' => $customer_email,				// Email address of payer.
							'payerid' => '', 						// Unique PayPal customer ID for payer.
							'payerstatus' => 'unverified',			// Status of payer.  Values are verified or unverified
							'business' => ''						// Payer's business name.
						);
										
						$PayerName = array(
							'salutation' => '', 					// Payer's salutation.  20 char max.
							'firstname' => $customer_firstname,		// Payer's first name.  25 char max.
							'middlename' => $customer_middlename,	// Payer's middle name.  25 char max.
							'lastname' => $customer_lastname,		// Payer's last name.  25 char max.
							'suffix' => $customer_suffix,			// Payer's suffix.  12 char max.
						);
										
						$BillingAddress = array(
							'street' => $customer_address, 			// Required.  First street address.
							'street2' => $customer_address2, 		// Second street address.
							'city' => $customer_city, 				// Required.  Name of City.
							'state' => $customer_region, 			// Required. Name of State or Province.
							'countrycode' => 'US', 					// Required.  Country code.
							'zip' => $customer_postal, 				// Required.  Postal code of payer.
							'phonenum' => $customer_phone 			// Phone Number of payer.  20 char max.
						);
											
						$ShippingAddress = array(
							'shiptoname' => $customer_shipfirstname.' '.$customer_shiplastname,	// Required if shipping is included.  Person's name associated with this address.  32 char max.
							'shiptostreet' => $customer_shipaddress, 		// Required if shipping is included.  First street address.  100 char max.
							'shiptostreet2' => $customer_shipaddress2, 		// Second street address.  100 char max.
							'shiptocity' => $customer_shipcity, 			// Required if shipping is included.  Name of city.  40 char max.
							'shiptostate' => $customer_shipregion, 			// Required if shipping is included.  Name of state or province.  40 char max.
							'shiptozip' => $customer_shippostal, 			// Required if shipping is included.  Postal code of shipping address.  20 char max.
							'shiptocountry' => $customer_shipcountry,		// Required if shipping is included.  Country code of shipping address.  2 char max.
							'shiptophonenum' => $customer_phone,  			// Phone number for shipping address.  20 char max.
						);
											
						$OrderItems = array();		
						foreach ($OwnerPayments as $OwnerID => $OwnerItems) {
							$OwnerTotal = 0;
							$OwnerItemsTotal = 0;
							$OwnerItemsWeight = 0;
							$OwnerItemsShipping = 0;
							$OwnerItemsTax = 0;
							$OwnerFeeTotal = 0;
							
							foreach ($OwnerItems as $Item) {
								if (!empty($customer_shipregion) && (!empty($sellerregion))) {
									$Item['taxamt'] = $this->GetTaxOfAmount($Item['amt'], $customer_shipregion, $sellerregion);
								}
								$OwnerItemsTotal = number_format(($OwnerItemsTotal+($Item['qty']*$Item['amt'])), 2, '.', '');
								$OwnerItemsWeight = number_format(($OwnerItemsWeight+($Item['qty']*$Item['itemweightvalue'])), 2, '.', '');
								$OwnerItemsTax = number_format(($OwnerItemsTax+($Item['qty']*$Item['taxamt'])), 2, '.', '');
								
								$OItem = array(
									'l_name' => $Item['name'], 															// Item name. 127 char max.
									'l_desc' => $Item['desc'], 												// Item description. 127 char max.
									'l_amt' => $Item['amt'],								 								// Cost of item.
									'l_qty' => $Item['qty'], 																		// Item qty on order.  Any positive integer.
									'l_taxamt' => $Item['taxamt'], 																	// Item sales tax
									'l_number' => $Item['number'])		 																// Item number.  127 char max.
									//'l_ebayitemnumber' => '', 				// eBay auction number of item.
									//'l_ebayitemauctiontxnid' => '', 		// eBay transaction ID of purchased item.
									//'l_ebayitemorderid' => '' 				// eBay order ID for the item.
								);
								array_push($OrderItems, $OItem);
							}
							
							$OwnerTotal = number_format(($OwnerTotal+($OwnerItemsTotal + $OwnerItemsTax)), 2, '.', '');
							$shipping_total = number_format(($shipping_total+$OwnerItemsShipping), 2, '.', '');
							$tax_total = number_format(($tax_total+$OwnerItemsTax), 2, '.', '');
							$OwnerItemsTotal = number_format($OwnerItemsTotal, 2, '.', ',');
							$OwnerItemsWeight = number_format($OwnerItemsWeight, 2, '.', ',');
							$OwnerItemsTax = number_format($OwnerItemsTax, 2, '.', ',');
							$OwnerItemsShipping = number_format($OwnerItemsShipping, 2, '.', ',');
							$total = number_format(($total+$OwnerTotal), 2, '.', '');
							$OwnerFeeTotal = number_format(($OwnerTotal * ($transaction_percent * (.01)))+$transaction_amount, 2, '.', '');
							if ($transaction_mode == 'subtract') {
								$OwnerTotal = number_format(($OwnerTotal - $OwnerFeeTotal), 2, '.', '');
							} else {
								$OwnerTotal = number_format($OwnerTotal, 2, '.', '');
							}
							$fee_total = number_format(($fee_total+$OwnerFeeTotal), 2, '.', '');
							$OwnerTotal = number_format($OwnerTotal, 2, '.', ',');

							// Validate shipping cost
							if (!empty($shipfreight)) {
								$shipping_ok = false;
								$ship_select = $this->GetShippingSelect($OwnerItemsWeight, $OwnerItemsTotal, 1, $customer_shippostal, $customer_shipregion, 'US');
								$submit_vars['x_freight_type'] = substr($shipfreight, 0, strpos($shipfreight, " [$"));
								$inputStr = $shipfreight;
								$delimeterLeft = "[$";
								$delimeterRight = "]";
								$posLeft=strpos($inputStr, $delimeterLeft);
								$posLeft+=strlen($delimeterLeft);
								$posRight=strpos($inputStr, $delimeterRight, $posLeft);
								$output = substr($inputStr, $posLeft, $posRight-$posLeft);
								if (strpos(strtolower($ship_select), $output) !== false) {
									$shipping_ok = true;
								}
								if ($shipping_ok === true) {
									$shipping_total = number_format($output, 2, '.', ',');
									$total = number_format(($total+$shipping_total), 2, '.', ',');
								} else {
									$submit_vars['message'] = 'Shipping method could not be validated.';
									return $submit_vars;
								}
							} else if ($total_weight > 0) {
								$submit_vars['message'] = 'Shipping Method must be selected.';
								return $submit_vars;
							}

						}
						
						if ($fee_total > 0) {
							$fee_total = number_format($fee_total, 2, '.', ',');
							$Item = array(
								'l_name' => 'Handling fee', 															// Item name. 127 char max.
								'l_desc' => $site_name.' handling fee', 												// Item description. 127 char max.
								'l_amt' => $fee_total,								 								// Cost of item.
								'l_qty' => 1, 																		// Item qty on order.  Any positive integer.
								'l_taxamt' => 0.00, 																	// Item sales tax
								'l_number' => '')		 																// Item number.  127 char max.
								//'l_ebayitemnumber' => '', 				// eBay auction number of item.
								//'l_ebayitemauctiontxnid' => '', 		// eBay transaction ID of purchased item.
								//'l_ebayitemorderid' => '' 				// eBay order ID for the item.
							);
							//var_dump($Payment);
							//$Payment['order_items'] = $OwnerPayments[$OwnerID];
							array_push($OrderItems, $Item);
						}
												
						$PaymentDetails = array(
							'amt' => $total, 							// Required.  Total amount of order, including shipping, handling, and tax.  
							'currencycode' => 'USD', 					// Required.  Three-letter currency code.  Default is USD.
							'itemamt' => $total, 						// Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
							'shippingamt' => $shipping_total, 			// Total shipping costs for the order.  If you specify shippingamt, you must also specify itemamt.
							'handlingamt' => $fee_total, 				// Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
							'taxamt' => $tax_total, 					// Required if you specify itemized cart tax details. Sum of tax for all items on the order.  Total sales tax. 
							'desc' => "Order from ".$site_name, 		// Description of the order the customer is purchasing.  127 char max.
							'custom' => '', 							// Free-form field for your own use.  256 char max.
							'invnum' => $order_number.'-0-'.$customer_id, 	// Your own invoice or tracking number
							'buttonsource' => 'Syntacts_PHP_Class_DDP', 					// An ID code for use by 3rd party apps to identify transactions.
							'notifyurl' => $GLOBALS['app']->getSiteURL()."/index.php?gadget=Ecommerce&action=PayPalResponse"						// URL for receiving Instant Payment Notifications.  This overrides what your profile is set to use.
						);
												
						$Secure3D = array();
						
						// Wrap all data arrays into a single, "master" array which will be passed into the class function.
						$RequestData = array(
							'DPFields' => $DPFields, 
							'CCDetails' => $CCDetails, 
							'PayerInfo' => $PayerInfo, 
							'PayerName' => $PayerName, 
							'BillingAddress' => $BillingAddress, 
							'ShippingAddress' => $ShippingAddress,
							'PaymentDetails' => $PaymentDetails,
							'OrderItems' => $OrderItems
						);
						// Pass the master array into the PayPal class function
						$PayPalResult = $PayPal->DoDirectPayment($RequestData);
					} else {
					*/
						$Payments = array();
						foreach ($OwnerPayments as $OwnerID => $OwnerItems) {
							// TODO: Add Ecommerce registry key for gateway_email, so site owner can receive payments
							$paymentaction = '';
							$paymentrequestid = '';
							$selleraccountid = '';
							$sellerregion = '';
							//if ($parallel === true) {
								$paymentaction = 'Order';
								$paymentrequestid = $order_number.'-PAYMENT'.$OwnerID;
								if ($OwnerID > 0) {
									$uInfo = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
									if (isset($uInfo['id'])) {
										if (isset($uInfo['merchant_id']) && !empty($uInfo['merchant_id'])) {
											$selleraccountid = $uInfo['merchant_id'];
										}
										if (isset($uInfo['region']) && !empty($uInfo['region'])) {
											$sellerregion = $uInfo['region'];
										}
									}
								} else {
									// TODO: Get site owner's shipfrom state
									$selleraccountid = $admin_email;
								}
								if (empty($selleraccountid)) {
									continue;
								}
							//}
							$OwnerTotal = 0;
							$OwnerItemsTotal = 0;
							$OwnerItemsWeight = 0;
							$OwnerItemsShipping = 0;
							$OwnerItemsTax = 0;
							$OwnerFeeTotal = 0;
							
							$o = 0;
							foreach ($OwnerItems as $Item) {
								if (!empty($customer_shipregion) && (!empty($sellerregion))) {
									$OwnerPayments[$OwnerID][$o]['taxamt'] = $this->GetTaxOfAmount($Item['amt'], $customer_shipregion, $sellerregion);
								}
								$OwnerItemsTotal = number_format(($OwnerItemsTotal+($Item['qty']*$Item['amt'])), 2, '.', '');
								$OwnerItemsWeight = number_format(($OwnerItemsWeight+($Item['qty']*$Item['itemweightvalue'])), 2, '.', '');
								$OwnerItemsTax = number_format(($OwnerItemsTax+($Item['qty']*$OwnerPayments[$OwnerID][$o]['taxamt'])), 2, '.', '');
								$o++;
							}
							
							$OwnerTotal = number_format(($OwnerTotal+($OwnerItemsTotal + $OwnerItemsTax)), 2, '.', '');
							$OwnerItemsWeight = number_format($OwnerItemsWeight, 2, '.', ',');
							$OwnerItemsTax = number_format($OwnerItemsTax, 2, '.', ',');
							$OwnerItemsShipping = number_format($OwnerItemsShipping, 2, '.', ',');
							$total = number_format(($total+$OwnerTotal), 2, '.', '');
							if ($total > 0 ) {
								$OwnerFeeTotal = number_format(($OwnerTotal * ($transaction_percent * (.01))), 2, '.', '');
								if ($OwnerFeeTotal < ($transaction_amount/$numPayments)) {
									$OwnerFeeTotal = number_format(($transaction_amount/$numPayments), 2, '.', '');
								}
								if ($transaction_mode == 'subtract') {
									$o2 = 0;
									foreach ($OwnerItems as $Item) {
										$OwnerPayments[$OwnerID][$o2]['amt'] = number_format(($Item['amt'] - $OwnerFeeTotal), 2, '.', ',');
										$o2++;
									}
									$OwnerTotal = number_format(($OwnerTotal - $OwnerFeeTotal), 2, '.', '');
									$OwnerItemsTotal = number_format(($OwnerItemsTotal - $OwnerFeeTotal), 2, '.', '');
								} else {
									$OwnerTotal = number_format($OwnerTotal, 2, '.', '');
								}
								$fee_total = number_format(($fee_total+$OwnerFeeTotal), 2, '.', '');
							}
							$OwnerTotal = number_format($OwnerTotal, 2, '.', ',');
							$OwnerItemsTotal = number_format($OwnerItemsTotal, 2, '.', ',');
							
							/*
							// Validate shipping cost
							//var_dump($shipfreight);
							if (!empty($shipfreight)) {
								$shiptype = substr($shipfreight, 0, strpos($shipfreight, " [$"));
								$inputStr = $shipfreight;
								$delimeterLeft = "[$";
								$delimeterRight = "]";
								$posLeft=strpos($inputStr, $delimeterLeft);
								$posLeft+=strlen($delimeterLeft);
								$posRight=strpos($inputStr, $delimeterRight, $posLeft);
								$output = substr($inputStr, $posLeft, $posRight-$posLeft);
								//var_dump($output);
								if ((int)$output > 0) {
									$shipping_ok = false;
									$ship_select = $this->GetShippingSelect($OwnerItemsWeight, $OwnerItemsTotal, 1, $customer_shippostal, $customer_shipregion, 'US');
									//var_dump($ship_select);
									if (strpos(strtolower($ship_select), $output) !== false) {
										$shipping_ok = true;
									}
									if ($shipping_ok === true) {
										$OwnerItemsShipping = number_format(($OwnerItemsShipping+$output), 2, '.', '');
										$shipping_total = number_format(($shipping_total+$output), 2, '.', '');
										$OwnerItemsTotal = number_format(($OwnerItemsTotal+$shipping_total), 2, '.', '');
										$total = number_format(($total+$shipping_total), 2, '.', '');
									} else {
										$submit_vars['message'] = 'Shipping method could not be validated.';
										return $submit_vars;
									}
								}
							} else if ($total_weight > 0) {
								$submit_vars['message'] = 'Shipping Method must be selected.';
								return $submit_vars;
							}
							*/
							
							$Payment = array(
								'amt' => $OwnerTotal, 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
								'currencycode' => 'USD', 						// A three-character currency code.  Default is USD.
								'itemamt' => $OwnerItemsTotal, 					// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
								'taxamt' => $OwnerItemsTax, 					// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
								'insuranceoptionoffered' => 'false', 			// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
								'desc' => 'Order from '.$site_name, 			// Description of items on the order.  127 char max.
								'invnum' => $order_number.'-'.$OwnerID.'-'.$customer_id, 		// Your own invoice or tracking number.  127 char max.
								'paymentaction' => $paymentaction, 				// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order. 
								'paymentrequestid' => $paymentrequestid,		// A unique identifier of the specific payment request, which is required for parallel payments. 
								'sellerpaypalaccountid' => $selleraccountid,	// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
								'order_items' => $OwnerPayments[$OwnerID]
								/*
								'custom' => '', 								// Free-form field for your own use.  256 char max.
								'notifyurl' => '', 								// URL for receiving Instant Payment Notifications
								'notetext' => '', 								// Note to the merchant.  255 char max.  
								'allowedpaymentmethod' => 'Any',				// The payment method type.  Specify the value InstantPaymentOnly.
								*/
							);
							if ($total_weight > 0 || $OwnerItemsShipping > 0) {
								$Payment['shippingamt'] = $OwnerItemsShipping; 			// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
								$Payment['shipdiscamt'] = '0.00'; 						// Total shipping discount amount for this order.
								$Payment['handlingamt'] = '0.00'; 						// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
								$Payment['shiptoname'] = $customer_shipfirstname.' '.$customer_shiplastname;	// Required if shipping is included.  Person's name associated with this address.  32 char max.
								$Payment['shiptostreet'] = $customer_shipaddress; 		// Required if shipping is included.  First street address.  100 char max.
								$Payment['shiptostreet2'] = $customer_shipaddress2; 		// Second street address.  100 char max.
								$Payment['shiptocity'] = $customer_shipcity; 			// Required if shipping is included.  Name of city.  40 char max.
								$Payment['shiptostate'] = $customer_shipregion; 			// Required if shipping is included.  Name of state or province.  40 char max.
								$Payment['shiptozip'] = $customer_shippostal; 			// Required if shipping is included.  Postal code of shipping address.  20 char max.
								$Payment['shiptocountry'] = $customer_shipcountry;		// Required if shipping is included.  Country code of shipping address.  2 char max.
								$Payment['shiptophonenum'] = $customer_phone;  			// Phone number for shipping address.  20 char max.
							}
							
							//var_dump($Payment);
							//$Payment['order_items'] = $OwnerPayments[$OwnerID];
							array_push($Payments, $Payment);
							
							// Insert temp order 
							$params = array();
							$params['Active'] = 'TEMP';
							$params['orderno'] = $order_number;
							$params['prod_id'] = $prod_ids;
							$params['total'] = $Payment['amt'];
							$params['tax'] = $Payment['taxamt'];
							$params['freight'] = $Payment['shippingamt'];
							$params['qty'] = 1;
							$params['unit'] = '';
							$params['weight'] = 1;
							$params['attribute'] = '';
							$params['shiptype'] = '';
							$params['gadget_table'] = '';
							$params['gadget_id'] = '';
							$params['OwnerID'] = $OwnerID;
							$params['customer_id'] = $customer_id;
							$orderDescription = array(
								'description' => 'Order from '.$site_name,
								'items' => $Payment['order_items'], 
								'customcheckoutfields' => $customfields
							);
							$params['description'] = serialize($orderDescription);
							$params['customer_email'] = $customer_email;
							$params['customer_name'] = (!empty($customer_firstname) ? $customer_firstname.' '.$customer_lastname : $customer_shipfirstname.' '.$customer_shiplastname);
							$params['customer_company'] = '';
							$params['customer_address'] = (!empty($customer_address) ? $customer_address : $customer_shipaddress);
							$params['customer_address2'] = (!empty($customer_address2) ? $customer_address2 : $customer_shipaddress2);
							$params['customer_city'] = (!empty($customer_city) ? $customer_city : $customer_shipcity);
							$params['customer_region'] = (!empty($customer_region) ? $customer_region : $customer_shipregion);
							$params['customer_postal'] = (!empty($customer_postal) ? $customer_postal : $customer_shippostal);
							$params['customer_country'] = (!empty($customer_country) ? $customer_country : $customer_shipcountry);
							$params['customer_phone'] = $customer_phone;
							$params['customer_fax'] = '';
							$params['customer_shipname'] = $customer_shipfirstname.' '.$customer_shiplastname;
							$params['customer_shipaddress'] = $customer_shipaddress;
							$params['customer_shipaddress2'] = $customer_shipaddress2;
							$params['customer_shipcity'] = $customer_shipcity;
							$params['customer_shipregion'] = $customer_shipregion;
							$params['customer_shippostal'] = $customer_shippostal;
							$params['customer_shipcountry'] = $customer_shipcountry;
							$params['sales_id'] = $sales_id;
							$params['customer_cc_type'] = '';
							$params['customer_cc_number'] = '';
							$params['customer_cc_exp_month'] = '';
							$params['customer_cc_exp_year'] = '';
							$params['customer_cc_cvv'] = '';
							// TODO: Implement recurring, backorder
							$params['backorder'] = '0';
							$params['recurring'] = 'N'; 
													
							$adminHTML = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
							$result = $adminHTML->form_post(true, 'AddOrder', $params, md5('PayPal'));				
							if (!is_numeric($result)) {
								$submit_vars['message'] = 'Error: Order could not be added [for product IDs: '.$prod_ids.']';
								return $submit_vars;
							}
						}
						
						if (!is_array($Payments) || count($Payments) <= 0) {
							$submit_vars['message'] = 'Error: '._t('ECOMMERCE_ERROR_NO_MERCHANT_ACCOUNTS').' '.$no_merchants;
						}
						if ($transaction_mode == 'add') {
							$total = number_format(($fee_total+$total), 2, '.', ',');
						} else {
							$total = number_format($total, 2, '.', ',');
						}
						if ($fee_total > 0) {
							$fee_total = number_format($fee_total, 2, '.', ',');
							$Payment = array(
								'amt' => $fee_total, 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
								'currencycode' => 'USD', 						// A three-character currency code.  Default is USD.
								'itemamt' => $fee_total, 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
								'shippingamt' => '0.00', 						// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
								'shipdiscamt' => '0.00', 						// Total shipping discount amount for this order.
								'handlingamt' => '0.00', 						// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
								'taxamt' => '0.00', 							// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
								'insuranceoptionoffered' => 'false', 			// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
								'desc' => 'Handling fee', 						// Description of items on the order.  127 char max.
								'invnum' => $order_number.'-0-'.$customer_id.'-fee',	// Your own invoice or tracking number.  127 char max.
								'shiptoname' => $customer_shipfirstname.' '.$customer_shiplastname,	// Required if shipping is included.  Person's name associated with this address.  32 char max.
								'shiptostreet' => $customer_shipaddress, 		// Required if shipping is included.  First street address.  100 char max.
								'shiptostreet2' => $customer_shipaddress2, 		// Second street address.  100 char max.
								'shiptocity' => $customer_shipcity, 			// Required if shipping is included.  Name of city.  40 char max.
								'shiptostate' => $customer_shipregion, 			// Required if shipping is included.  Name of state or province.  40 char max.
								'shiptozip' => $customer_shippostal, 			// Required if shipping is included.  Postal code of shipping address.  20 char max.
								'shiptocountry' => $customer_shipcountry,		// Required if shipping is included.  Country code of shipping address.  2 char max.
								'shiptophonenum' => $customer_phone,			// Phone number for shipping address.  20 char max.
								'paymentaction' => $paymentaction, 				// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order. 
								'paymentrequestid' => $order_number.'-PAYMENT0-fee',	// A unique identifier of the specific payment request, which is required for parallel payments. 
								'sellerpaypalaccountid' => ($sandbox ? '' : ''),		// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
								'order_items' => array(
									array(
										'name' => 'Handling fee', 															// Item name. 127 char max.
										'desc' => $site_name.' handling fee', 												// Item description. 127 char max.
										'amt' => $fee_total,								 								// Cost of item.
										'qty' => 1, 																		// Item qty on order.  Any positive integer.
										'taxamt' => 0.00, 																	// Item sales tax
										'itemurl' => '',				 													// URL for the item.
										'itemweightvalue' => 0.00,			 												// The weight value of the item.
										'itemweightunit' => 'lbs', 															// The weight unit of the item.
										'itemheightvalue' => '0', 															// The height value of the item.
										'itemheightunit' => 'inches', 														// The height unit of the item.
										'itemwidthvalue' => '0', 															// The width value of the item.
										'itemwidthunit' => 'inches', 														// The width unit of the item.
										'itemlengthvalue' => '0', 															// The length value of the item.
										'itemlengthunit' => 'inches',  														// The length unit of the item.
										'number' => ''		 																// Item number.  127 char max.
									)
								)
								/*
								'custom' => '', 								// Free-form field for your own use.  256 char max.
								'notifyurl' => '', 								// URL for receiving Instant Payment Notifications
								'notetext' => '', 								// Note to the merchant.  255 char max.  
								'allowedpaymentmethod' => 'Any',				// The payment method type.  Specify the value InstantPaymentOnly.
								*/
							);
							//var_dump($Payment);
							//$Payment['order_items'] = $OwnerPayments[$OwnerID];
							array_push($Payments, $Payment);
							
							// Insert temp order 
							$params = array();
							$params['Active'] = 'TEMP';
							$params['orderno'] = $order_number;
							$params['prod_id'] = $prod_ids;
							$params['total'] = $Payment['amt'];
							$params['tax'] = $Payment['taxamt'];
							$params['freight'] = $Payment['shippingamt'];
							$params['qty'] = 1;
							$params['unit'] = '';
							$params['weight'] = 1;
							$params['attribute'] = '';
							$params['shiptype'] = '';
							$params['gadget_table'] = '';
							$params['gadget_id'] = '';
							$params['OwnerID'] = 0;
							$params['customer_id'] = $customer_id;
							$orderDescription = array(
								'description' => 'Handling fee',
								'items' => $Payment['order_items'], 
								'customcheckoutfields' => $customfields
							);
							$params['description'] = serialize($orderDescription);
							$params['customer_email'] = $customer_email;
							$params['customer_name'] = (!empty($customer_firstname) ? $customer_firstname.' '.$customer_lastname : $customer_shipfirstname.' '.$customer_shiplastname);
							$params['customer_company'] = '';
							$params['customer_address'] = (!empty($customer_address) ? $customer_address : $customer_shipaddress);
							$params['customer_address2'] = (!empty($customer_address2) ? $customer_address2 : $customer_shipaddress2);
							$params['customer_city'] = (!empty($customer_city) ? $customer_city : $customer_shipcity);
							$params['customer_region'] = (!empty($customer_region) ? $customer_region : $customer_shipregion);
							$params['customer_postal'] = (!empty($customer_postal) ? $customer_postal : $customer_shippostal);
							$params['customer_country'] = (!empty($customer_country) ? $customer_country : $customer_shipcountry);
							$params['customer_phone'] = $customer_phone;
							$params['customer_fax'] = '';
							$params['customer_shipname'] = $customer_shipfirstname.' '.$customer_shiplastname;
							$params['customer_shipaddress'] = $customer_shipaddress;
							$params['customer_shipaddress2'] = $customer_shipaddress2;
							$params['customer_shipcity'] = $customer_shipcity;
							$params['customer_shipregion'] = $customer_shipregion;
							$params['customer_shippostal'] = $customer_shippostal;
							$params['customer_shipcountry'] = $customer_shipcountry;
							$params['sales_id'] = $sales_id;
							$params['customer_cc_type'] = '';
							$params['customer_cc_number'] = '';
							$params['customer_cc_exp_month'] = '';
							$params['customer_cc_exp_year'] = '';
							$params['customer_cc_cvv'] = '';
							// TODO: Implement recurring, backorder
							$params['backorder'] = '0';
							$params['recurring'] = 'N'; 
													
							$adminHTML = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
							$result = $adminHTML->form_post(true, 'AddOrder', $params, md5('PayPal'));				
							if (!is_numeric($result)) {
								$submit_vars['message'] = 'Error: Order could not be added [for product IDs: '.$prod_ids.']';
								return $submit_vars;
							}
						}
						
						$SECFields = array(
							/*
							'token' => '', 								// A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
							*/
							'maxamt' => $total, 						// The expected maximum total amount the order will be, including S&H and sales tax.
							'returnurl' => $GLOBALS['app']->getSiteURL()."/index.php?gadget=Ecommerce&action=PayPalResponse",	// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
							'cancelurl' => $GLOBALS['app']->getSiteURL()."/index.php?products/all.html",						// Required.  URL to which the customer will be returned if they cancel payment on PayPal's site.
							'reqconfirmshipping' => '0', 				// The value 1 indicates that you require that the customer's shipping address is Confirmed with PayPal.  This overrides anything in the account profile.  Possible values are 1 or 0.
							'noshipping' => '0', 						// The value 1 indiciates that on the PayPal pages, no shipping address fields should be displayed.  Maybe 1 or 0.
							'allownote' => '0', 						// The value 1 indiciates that the customer may enter a note to the merchant on the PayPal page during checkout.  The note is returned in the GetExpresscheckoutDetails response and the DoExpressCheckoutPayment response.  Must be 1 or 0.
							'addroverride' => '1', 						// The value 1 indiciates that the PayPal pages should display the shipping address set by you in the SetExpressCheckout request, not the shipping address on file with PayPal.  This does not allow the customer to edit the address here.  Must be 1 or 0.
							'solutiontype' => 'Mark', 					// Type of checkout flow.  Must be Sole (express checkout for auctions) or Mark (normal express checkout)
							'landingpage' => 'Billing',					// Type of PayPal page to display.  Can be Billing or Login.  If billing it shows a full credit card form.  If Login it just shows the login screen.
							'channeltype' => 'Merchant', 				// Type of channel.  Must be Merchant (non-auction seller) or eBayItem (eBay auction)
							'brandname' => $site_name, 					// A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.  127 char max.
							'email' => $customer_email, 				// Email address of the buyer as entered during checkout.  PayPal uses this value to pre-fill the PayPal sign-in page.  127 char max.
							/*
							'callback' => '', 							// URL to which the callback request from PayPal is sent.  Must start with https:// for production.
							'callbacktimeout' => '', 					// An override for you to request more or less time to be able to process the callback request and response.  Acceptable range for override is 1-6 seconds.  If you specify greater than 6 PayPal will use default value of 3 seconds.
							'callbackversion' => '', 					// The version of the Instant Update API you're using.  The default is the current version.							
							'localecode' => '', 						// Locale of pages displayed by PayPal during checkout.  Should be a 2 character country code.  You can retrive the country code by passing the country name into the class' GetCountryCode() function.
							'pagestyle' => '', 							// Sets the Custom Payment Page Style for payment pages associated with this button/link.  
							'hdrimg' => '', 							// URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
							'hdrbordercolor' => '', 					// Sets the border color around the header of the payment page.  The border is a 2-pixel permiter around the header space.  Default is black.  
							'hdrbackcolor' => '', 						// Sets the background color for the header of the payment page.  Default is white.  
							'payflowcolor' => '', 						// Sets the background color for the payment page.  Default is white.
							'skipdetails' => '', 						// This is a custom field not included in the PayPal documentation.  It's used to specify whether you want to skip the GetExpressCheckoutDetails part of checkout or not.  See PayPal docs for more info.
							'giropaysuccessurl' => '', 					// The URL on the merchant site to redirect to after a successful giropay payment.  Only use this field if you are using giropay or bank transfer payment methods in Germany.
							'giropaycancelurl' => '', 					// The URL on the merchant site to redirect to after a canceled giropay payment.  Only use this field if you are using giropay or bank transfer methods in Germany.
							'banktxnpendingurl' => '',  				// The URL on the merchant site to transfer to after a bank transfter payment.  Use this field only if you are using giropay or bank transfer methods in Germany.
							'customerservicenumber' => '', 				// Merchant Customer Service number displayed on the PayPal Review page. 16 char max.
							'giftmessageenable' => '', 					// Enable gift message widget on the PayPal Review page. Allowable values are 0 and 1
							'giftreceiptenable' => '', 					// Enable gift receipt widget on the PayPal Review page. Allowable values are 0 and 1
							'giftwrapenable' => '', 					// Enable gift wrap widget on the PayPal Review page.  Allowable values are 0 and 1.
							'giftwrapname' => '', 						// Label for the gift wrap option such as "Box with ribbon".  25 char max.
							'giftwrapamount' => '', 					// Amount charged for gift-wrap service.
							'buyeremailoptionenable' => '', 			// Enable buyer email opt-in on the PayPal Review page. Allowable values are 0 and 1
							'surveyquestion' => '', 					// Text for the survey question on the PayPal Review page. If the survey question is present, at least 2 survey answer options need to be present.  50 char max.
							'surveyenable' => '', 						// Enable survey functionality. Allowable values are 0 and 1
							'buyerid' => '', 							// The unique identifier provided by eBay for this buyer. The value may or may not be the same as the username. In the case of eBay, it is different. 255 char max.
							'buyerusername' => '', 						// The user name of the user at the marketplaces site.
							'buyerregistrationdate' => '',  			// Date when the user registered with the marketplace.
							'allowpushfunding' => ''					// Whether the merchant can accept push funding.  0 = Merchant can accept push funding : 1 = Merchant cannot accept push funding.			
							*/
						);

						$BuyerDetails = array(
							'buyerid' => '', 				// The unique identifier provided by eBay for this buyer.  The value may or may not be the same as the username.  In the case of eBay, it is different.  Char max 255.
							'buyerusername' => '', 			// The username of the marketplace site.
							'buyerregistrationdate' => ''	// The registration of the buyer with the marketplace.
						);
												
						// For shipping options we create an array of all shipping choices similar to how order items works.
						$ShippingOptions = array();
						/*
						$Option = array(
							'l_shippingoptionisdefault' => '', 				// Shipping option.  Required if specifying the Callback URL.  true or false.  Must be only 1 default!
							'l_shippingoptionname' => '', 					// Shipping option name.  Required if specifying the Callback URL.  50 character max.
							'l_shippingoptionlabel' => '', 					// Shipping option label.  Required if specifying the Callback URL.  50 character max.
							'l_shippingoptionamount' => '' 					// Shipping option amount.  Required if specifying the Callback URL.  
						);
						array_push($ShippingOptions, $Option);
						*/
								
						$BillingAgreements = array();
						/*
						$Item = array(
						  'l_billingtype' => '', 							// Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
						  'l_billingagreementdescription' => '', 			// Required for recurring payments.  Description of goods or services associated with the billing agreement.  
						  'l_paymenttype' => '', 							// Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
						  'l_billingagreementcustom' => ''					// Custom annotation field for your own use.  256 char max.
						);
						array_push($BillingAgreements, $Item);
						*/

						// Wrap all data arrays into a single, "master" array which will be passed into the class function.
						$RequestData = array(
							'SECFields' => $SECFields, 
							'SurveyChoices' => $SurveyChoices, 
							'Payments' => $Payments
							/* 
							'BuyerDetails' => $BuyerDetails, 
							'ShippingOptions' => $ShippingOptions, 
							'BillingAgreements' => $BillingAgreements
							*/
						);
						
						// Pass the master array into the PayPal class function
						$PayPalResult = $PayPal->SetExpressCheckout($RequestData);
					//}
					// Display results
					if (isset($PayPalResult['TOKEN']) && !empty($PayPalResult['TOKEN']) && isset($PayPalResult['REDIRECTURL']) && !empty($PayPalResult['REDIRECTURL'])) {
						$submit_vars['form_submit'] = 'true';
						$submit_vars['url'] = $PayPalResult['REDIRECTURL'].'&useraction=commit';
						$submit_vars['message'] = 'true';
						//$GLOBALS['app']->Session->PushLastResponse($result, RESPONSE_NOTICE);
						//return true;
					} else {
						if (is_array($PayPalResult['ERRORS']) && (isset($PayPalResult['ERRORS'][0]['L_LONGMESSAGE']) && !empty($PayPalResult['ERRORS'][0]['L_LONGMESSAGE']))) {
							foreach ($PayPalResult['ERRORS'] as $errmsg) {
								if ($errmsg['L_SEVERITYCODE'] == 'Error') {
									if (substr($submit_vars['message'], 0, 6) != 'Error:') {
										$submit_vars['message'] = 'Error: '.$submit_vars['message'];
									}
									if (isset($errmsg['L_LONGMESSAGE']) && !empty($errmsg['L_LONGMESSAGE'])) {
										$submit_vars['message'] .= '::: '.$errmsg['L_LONGMESSAGE'];
									}
								}
							}
							if (substr($submit_vars['message'], 0, 6) == 'Error:') {
								// Try to pin down error for each payment
								foreach ($Payments as $epayment) {
									$merchant_found = false;
									// Wrap all data arrays into a single, "master" array which will be passed into the class function.
									$eRequestData = array(
										'SECFields' => $SECFields, 
										'SurveyChoices' => $SurveyChoices, 
										'Payments' => array($epayment)/*, 
										'BuyerDetails' => $BuyerDetails, 
										'ShippingOptions' => $ShippingOptions, 
										'BillingAgreements' => $BillingAgreements*/
									);
									
									// Pass the master array into the PayPal class function
									$ePayPalResult = $PayPal->SetExpressCheckout($eRequestData);
									//$submit_vars['message'] .= '::: '.var_export($ePayPalResult, true);
									if (is_array($ePayPalResult['ERRORS']) && (isset($ePayPalResult['ERRORS'][0]['L_LONGMESSAGE']) && !empty($ePayPalResult['ERRORS'][0]['L_LONGMESSAGE']))) {
										foreach ($ePayPalResult['ERRORS'] as $errmsg) {
											if ($errmsg['L_SEVERITYCODE'] == 'Error') {
												$submit_vars['message'] .= '::: Merchant: '.$epayment['sellerpaypalaccountid'];
												break;
											}
										}
									}
								}
							}
						} else {
							$submit_vars['message'] = 'Error: '._t('ECOMMERCE_ERROR_SUBMITTING_ORDER');
						}
					}
					$order_details = array('ORDER' => $items, 'PAYMENTS' => $OwnerPayments, 'RESULT' => $PayPalResult);
				}
			} else if ($payment_gateway == 'ManualCreditCard' || $total == 0) {	
				if (!empty($error)) {
					$submit_vars['message'] = $error;
					return $submit_vars;
					//$GLOBALS['app']->Session->PushLastResponse($error, RESPONSE_ERROR);
					//return false;
				} else {
					if (
						$payment_gateway == 'ManualCreditCard' && 
						$total > 0 && (empty($cc_creditcardtype) ||  
						empty($customer_firstname) || empty($cc_acct) || 
						empty($cc_expdate_month) || empty($cc_expdate_year) || 
						empty($cc_cvv2))
					) {
						$submit_vars['message'] = 'javascript';
						$submit_vars['body'] = 'showCreditCard';
						return $submit_vars;
					}
					
					$total = 0;
					$fee_total = 0;
					$shipping_total = 0;
					$tax_total = 0;
			
					if ($total_weight > 0 && empty($customer_shiplastname)) {
						$submit_vars['message'] = 'Ship to Last Name must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipfirstname)) {
						$submit_vars['message'] = 'Ship to First Name must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipaddress)) {
						$submit_vars['message'] = 'Ship to Address must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipcity)) {
						$submit_vars['message'] = 'Ship to City must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipregion)) {
						$submit_vars['message'] = 'Ship to State must be selected.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shippostal)) {
						$submit_vars['message'] = 'Ship to Zip Code must be supplied.';
						return $submit_vars;
					}
					if ($total_weight > 0 && empty($customer_shipcountry)) {
						$submit_vars['message'] = 'Ship to Country must be supplied.';
						return $submit_vars;
					}
					$parallel = false;
					if (is_array($OwnerPayments) && !count($OwnerPayments) <= 0) {
						$parallel = true;
					}
					
					// Count number of Payments
					$numPayments = 0;
					foreach ($OwnerPayments as $OwnerID => $OwnerItems) {
						$numPayments++;
					}
					
					foreach ($OwnerPayments as $OwnerID => $OwnerItems) {
						// TODO: Add Ecommerce registry key for gateway_email, so site owner can receive payments
						$sellerregion = '';
						if ($OwnerID > 0) {
							$uInfo = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
							if (isset($uInfo['id'])) {
								/*
								if (isset($uInfo['merchant_id']) && !empty($uInfo['merchant_id'])) {
									$selleraccountid = $uInfo['merchant_id'];
								}
								*/
								if (isset($uInfo['region']) && !empty($uInfo['region'])) {
									$sellerregion = $uInfo['region'];
								}
							}
						} else {
							// TODO: Get site owner's shipfrom state
						}
						$OwnerTotal = 0;
						$OwnerItemsTotal = 0;
						$OwnerItemsWeight = 0;
						$OwnerItemsShipping = 0;
						$OwnerItemsTax = 0;
						$OwnerFeeTotal = 0;
						$product_category_names = array();
						$shiptype = '';
						$params = array();
						
						$o = 0;
						foreach ($OwnerItems as $Item) {
							if (!empty($customer_shipregion) && (!empty($sellerregion))) {
								$OwnerPayments[$OwnerID][$o]['taxamt'] = $this->GetTaxOfAmount($Item['amt'], $customer_shipregion, $sellerregion);
							}
							$OwnerItemsTotal = number_format(($OwnerItemsTotal+($Item['qty']*$Item['amt'])), 2, '.', '');
							$OwnerItemsWeight = number_format(($OwnerItemsWeight+($Item['qty']*$Item['itemweightvalue'])), 2, '.', '');
							$OwnerItemsTax = number_format(($OwnerItemsTax+($Item['qty']*$OwnerPayments[$OwnerID][$o]['taxamt'])), 2, '.', '');
							// Update product quantities
							$prod_id = (int)$Item['number'];
							$ord_qty = (int)$Item['qty'];
							$updateStock = $this->UpdateProductStock($prod_id, $ord_qty);
							$o++;
						}
						
						$OwnerTotal = number_format(($OwnerTotal+($OwnerItemsTotal + $OwnerItemsTax)), 2, '.', '');
						$OwnerItemsWeight = number_format($OwnerItemsWeight, 2, '.', ',');
						$OwnerItemsTax = number_format($OwnerItemsTax, 2, '.', ',');
						$total = number_format(($total+$OwnerTotal), 2, '.', '');
						if ($total > 0 ) {
							$OwnerFeeTotal = 0;
							$OwnerFeeTotal = number_format(($OwnerTotal * ($transaction_percent * (.01))), 2, '.', '');
							if ($OwnerFeeTotal < ($transaction_amount/$numPayments)) {
								$OwnerFeeTotal = number_format(($transaction_amount/$numPayments), 2, '.', '');
							}
							if ($transaction_mode == 'subtract') {
								$o2 = 0;
								foreach ($OwnerItems as $Item) {
									$OwnerPayments[$OwnerID][$o2]['amt'] = number_format(($Item['amt'] - $OwnerFeeTotal), 2, '.', ',');
									$o2++;
								}
								$OwnerTotal = number_format(($OwnerTotal - $OwnerFeeTotal), 2, '.', '');
								$OwnerItemsTotal = number_format(($OwnerItemsTotal - $OwnerFeeTotal), 2, '.', '');
							} else {
								$OwnerTotal = number_format($OwnerTotal, 2, '.', '');
							}
							$fee_total = number_format(($fee_total+$OwnerFeeTotal), 2, '.', '');
						}
						
						// Validate shipping cost
						//var_dump($shipfreight);
						if (!empty($shipfreight)) {
							if (strpos($shipfreight, " [$") === false) {
								$output = 0;
							} else {
								$shiptype = substr($shipfreight, 0, strpos($shipfreight, " [$"));
								$inputStr = $shipfreight;
								$delimeterLeft = "[$";
								$delimeterRight = "]";
								$posLeft=strpos($inputStr, $delimeterLeft);
								$posLeft+=strlen($delimeterLeft);
								$posRight=strpos($inputStr, $delimeterRight, $posLeft);
								$output = substr($inputStr, $posLeft, $posRight-$posLeft);
							}
							//var_dump($output);
							if ((int)$output > 0) {
								$shipping_ok = false;
								$ship_select = $this->GetShippingSelect($OwnerItemsWeight, $OwnerItemsTotal, 1, $customer_shippostal, $customer_shipregion, 'US');
								//var_dump($ship_select);
								if (strpos(strtolower($ship_select), $output) !== false) {
									$shipping_ok = true;
								}
								if ($shipping_ok === true) {
									$OwnerItemsShipping = number_format(($OwnerItemsShipping+$output), 2, '.', '');
									$shipping_total = number_format(($shipping_total+$output), 2, '.', '');
									$OwnerItemsTotal = number_format(($OwnerItemsTotal+$shipping_total), 2, '.', '');
									$total = number_format(($total+$shipping_total), 2, '.', '');
								} else {
									$submit_vars['message'] = 'Shipping method could not be validated.';
									return $submit_vars;
								}
							}
						} else if ($total_weight > 0) {
							$submit_vars['message'] = 'Shipping Method must be selected.';
							return $submit_vars;
						}
						
						$OwnerTotal = number_format($OwnerTotal, 2, '.', ',');
						$OwnerItemsTotal = number_format($OwnerItemsTotal, 2, '.', ',');
						$OwnerItemsShipping = number_format($OwnerItemsShipping, 2, '.', ',');
															
						$params['Active'] = 'TEMP';
						$params['orderno'] = $order_number;
						$params['prod_id'] = $prod_ids;
						$params['total'] = $OwnerItemsTotal;
						$params['tax'] = $OwnerItemsTax;
						$params['freight'] = $OwnerItemsShipping;
						$params['qty'] = 1;
						$params['unit'] = '';
						$params['weight'] = 1;
						$params['attribute'] = '';
						$params['shiptype'] = $shiptype;
						$params['gadget_table'] = '';
						$params['gadget_id'] = '';
						$params['OwnerID'] = $OwnerID;
						$params['customer_id'] = $customer_id;
						$orderDescription = array(
							'description' => 'Order from '.$site_name,
							'items' => $OwnerPayments[$OwnerID], 
							'customcheckoutfields' => $customfields
						);
						$params['description'] = serialize($orderDescription);
						$params['customer_email'] = $customer_email;
						$params['customer_name'] = (!empty($customer_firstname) ? $customer_firstname.' '.$customer_lastname : $customer_shipfirstname.' '.$customer_shiplastname);
						$params['customer_company'] = '';
						$params['customer_address'] = (!empty($customer_address) ? $customer_address : $customer_shipaddress);
						$params['customer_address2'] = (!empty($customer_address2) ? $customer_address2 : $customer_shipaddress2);
						$params['customer_city'] = (!empty($customer_city) ? $customer_city : $customer_shipcity);
						$params['customer_region'] = (!empty($customer_region) ? $customer_region : $customer_shipregion);
						$params['customer_postal'] = (!empty($customer_postal) ? $customer_postal : $customer_shippostal);
						$params['customer_country'] = (!empty($customer_country) ? $customer_country : $customer_shipcountry);
						$params['customer_phone'] = $customer_phone;
						$params['customer_fax'] = '';
						$params['customer_shipname'] = $customer_shipfirstname.' '.$customer_shiplastname;
						$params['customer_shipaddress'] = $customer_shipaddress;
						$params['customer_shipaddress2'] = $customer_shipaddress2;
						$params['customer_shipcity'] = $customer_shipcity;
						$params['customer_shipregion'] = $customer_shipregion;
						$params['customer_shippostal'] = $customer_shippostal;
						$params['customer_shipcountry'] = $customer_shipcountry;
						$params['sales_id'] = $sales_id;
						$params['customer_cc_type'] = $cc_creditcardtype;
						$params['customer_cc_number'] = $cc_acct;
						$params['customer_cc_exp_month'] = $cc_expdate_month;
						$params['customer_cc_exp_year'] = $cc_expdate_year;
						$params['customer_cc_cvv'] = $cc_cvv2;
						// TODO: Implement recurring, backorder
						$params['backorder'] = '0';
						$params['recurring'] = 'N'; 
												
						$adminHTML = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
						$result = $adminHTML->form_post(true, 'AddOrder', $params, md5('ManualCreditCard'));				
						if (!is_numeric($result)) {
							$submit_vars['message'] = 'Error: Order could not be added [for product IDs: '.$prod_ids.']';
							return $submit_vars;
						}
					}
												
					// Display results
					$submit_vars['form_submit'] = 'true';
					$submit_vars['url'] = $GLOBALS['app']->getSiteURL()."/index.php?gadget=Ecommerce&action=ManualResponse&orderno=".$order_number;
					$submit_vars['message'] = 'true';
					//$GLOBALS['app']->Session->PushLastResponse($result, RESPONSE_NOTICE);
					//return true;
					$order_details = array('ORDER' => $items, 'PAYMENTS' => $OwnerPayments, 'RESULT' => $result);
				}
			}
			if (substr($submit_vars['message'], 0, 6) == 'Error:') {	
				$subject = _t('ECOMMERCE_ERROR_SUBMITTING_ORDER_MAIL_SUBJECT', $GLOBALS['app']->GetSiteURL());
				
				$from_name  = $GLOBALS['app']->Registry->Get('/config/site_name');
				$from_email = $GLOBALS['app']->Registry->Get('/network/site_email');

				$message = $submit_vars['message'];
				if (isset($order_details)) {
					$message .= 'The order details are: '."\n".var_export($order_details, true);
				}
				$mail = new Jaws_Mail;
				$mail->SetHeaders($from_email, $domain ." Store", 'noreply@'.$domain, $subject);
				$mail->AddRecipient($from_email, false, false);
				$mail->SetBody(Jaws_Gadget::ParseText($message, 'Ecommerce'), 'text');
				$mresult = $mail->send();
			}
			return $submit_vars;
		}
	}
	
    /**
     * Returns HTML of shipping select box
     *
     * @access  public
     * @return  string HTML of shipping selects
     */
    function GetShippingSelect($weight = 0, $price = 0, $qty = 1, $zip = '', $state = '', $country = 'US')
    {		
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		//$shipfrom_city = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city');  // City Shipping From
		$shipfrom_state = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');  // State Shipping From
		$shipfrom_zip = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip');  // Zip Shipping From
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');  // Use Shipping Carrier Calculations
		
		$output_html = '';
		//$output_html = '<input type="hidden" id="shipfreight" name="shipfreight" value="0" />';
			
		if (CACHING_ENABLED === true) {
			// Get from cache first
			$cache_path = 'payment_gateway='.$payment_gateway.'&shipfrom_state='.$shipfrom_state;
			$cache_path .= '&shipfrom_zip='.$shipfrom_zip.'&use_carrier_calculated='.$use_carrier_calculated;
			$cache_path .= '&weight='.$weight.'&price='.$price.'&qty='.$qty.'&zip='.$zip.'&country='.$country;
			$cache_file = $GLOBALS['app']->getSyntactsCacheFile($cache_path, 'Ecommerce_shipping');
			$cache_file = JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . $cache_file .".php";
			if (file_exists($cache_file)) {
				return file_get_contents($cache_file);
			}
		}

		if ($weight == 0) {
			return '<span style="color: #FF0000; font-weight: bold;">Weight must be greater than zero.</span>';
		}
		if (empty($zip)) {
			return '<span style="color: #FF0000; font-weight: bold;">Please supply a Zip Code.</span>';
		}
		if (empty($state)) {
			return '<span style="color: #FF0000; font-weight: bold;">Please select your State.</span>';
		}
		if (empty($use_carrier_calculated)) {
			$use_carrier_calculated = 'N';
		} else {
			if ($use_carrier_calculated == 'Y' && empty($shipfrom_state)) {
				// TODO: notify site owner of this.
				return '<span style="color: #FF0000; font-weight: bold;">This merchant has not setup a State to ship from.</span>';
			}
			if ($use_carrier_calculated == 'Y' && empty($shipfrom_zip)) {
				// TODO: notify site owner of this.
				return '<span style="color: #FF0000; font-weight: bold;">This merchant has not setup a Zip Code to ship from.</span>';
			}
		}
		if ($use_carrier_calculated == 'N') {
			$shippings = $this->GetShippingsOfOrder($weight, $price, $qty);
			foreach($shippings as $shipping) {
				//$output_html .= "<option value='".str_replace("'", "\'", $shipping['title'])." [$".$shipping['price']."]'>".$shipping['title']." [$".$shipping['price']."]</option>";
				$output_html .= "|".$shipping['title']." [$".$shipping['price']."]";
			}
		} else {
			$shippings = $this->GetShippings();
			foreach($shippings as $shipping) {
				if ($shipping['type'] == 'default') {
					//$output_html .= "<option value='".str_replace("'", "\'", $shipping['title'])." [$".$shipping['price']."]'>".$shipping['title']." [$".$shipping['price']."]</option>";
					$output_html .= "|".$shipping['title']." [$".$shipping['price']."]";
					break;
				}
			}
			
			/*********** Shipping Services ************/
			/* Here's an array of all the standard
			/* shipping rates. You'll probably want to
			/* comment out the ones you don't want 
			/******************************************/
			
			// UPS
			$services = array();
			$services['ups'] = array();
			$services['ups']['03'] = 'Ground';
			$services['ups']['11'] = 'Standard';
			$services['ups']['59'] = '2nd Day Air Early AM';
			$services['ups']['02'] = '2nd Day Air';
			$services['ups']['12'] = '3 Day Select';
			$services['ups']['14'] = 'Next Day Air Early AM';
			$services['ups']['01'] = 'Next Day Air';
			$services['ups']['65'] = 'Saver';
			/*
			$services['ups']['07'] = 'Worldwide Express';
			$services['ups']['54'] = 'Worldwide Express Plus';
			$services['ups']['08'] = 'Worldwide Expedited';
			*/

			// USPS
			$services['usps'] = array();
			$services['usps']['PARCEL'] = 'Parcel';
			$services['usps']['PRIORITY'] = 'Priority';
			$services['usps']['EXPRESS'] = 'Express';
			$services['usps']['EXPRESS SH'] = 'Express SH';
			$services['usps']['FIRST CLASS'] = 'First Class';
			/*
			$services['usps']['BPM'] = 'BPM';
			$services['usps']['MEDIA '] = 'Media';
			$services['usps']['LIBRARY'] = 'Library';
			*/
			// FedEx
			$services['fedex'] = array();
			$services['fedex']['FEDEXGROUND'] = 'Ground';
			$services['fedex']['FEDEXEXPRESSSAVER'] = 'Express Saver';
			$services['fedex']['FEDEX2DAY'] = '2 Day';
			$services['fedex']['STANDARDOVERNIGHT'] = 'Standard Overnight';
			$services['fedex']['PRIORITYOVERNIGHT'] = 'Priority Overnight';
			$services['fedex']['FIRSTOVERNIGHT'] = 'First Overnight';
			/*
			$services['fedex']['FEDEX1DAYFREIGHT'] = 'Overnight Day Freight';
			$services['fedex']['FEDEX2DAYFREIGHT'] = '2 Day Freight';
			$services['fedex']['FEDEX3DAYFREIGHT'] = '3 Day Freight';
			$services['fedex']['GROUNDHOMEDELIVERY'] = 'Home Delivery';
			$services['fedex']['INTERNATIONALECONOMY'] = 'International Economy';
			$services['fedex']['INTERNATIONALFIRST'] = 'International First';
			$services['fedex']['INTERNATIONALPRIORITY'] = 'International Priority';
			*/
							
			// TODO: Registry key for which Services to display, and their default rate
			
			// Config
			$config = array(
				// Services
				'services' => $services,
				// Weight
				'weight' => $weight, // Default = 1
				'weight_units' => 'lb', // lb (default), oz, gram, kg
				// Size
				'size_length' => 12, // Default = 8
				'size_width' => 6, // Default = 4
				'size_height' => 6, // Default = 2
				'size_units' => 'in', // in (default), feet, cm
				// From
				'from_zip' => $shipfrom_zip, 
				'from_state' => $shipfrom_state, // Only Required for FedEx
				'from_country' => $country,
				// To
				'to_zip' => $zip,
				'to_state' => $state, // Only Required for FedEx
				'to_country' => $country,
				
				// Service Logins
				'ups_access' => 'UPS_LICENSE_KEY', // UPS Access License Key
				'ups_user' => 'UPS_USERNAME', // UPS Username  
				'ups_pass' => 'UPS_PASSWORD', // UPS Password  
				'ups_account' => 'UPS_ACCOUNT_NUMBER', // UPS Account Number
				
				'usps_user' => 'USPS_USERNAME', // USPS User Name
				
				'fedex_account' => 'FEDEX_ACCOUNT_NUMBER', // FedEX Account Number
				'fedex_meter' => 'FEDEX_METER_NUMBER' // FedEx Meter Number 
			);
			
			// Shipping Calculator Class
			require_once JAWS_PATH . 'include/Jaws/ShippingCalculator.php';
			// Create Class (with config array)
			$ship = new ShippingCalculator($config);
			// Get Rates
			$rates = $ship->calculate();
			
			/*
			// Print Array of Rates
			print "
			Rates for sending a ".$config[weight]." ".$config[weight_units].", ".$config[size_length]." x ".$config[size_width]." x ".$config[size_height]." ".$config[size_units]." package from ".$config[from_zip]." to ".$config[to_zip].":
			<xmp>";
			print_r($rates);
			print "</xmp>";
			*/

			/******* Setting Options After Class Creation ********
			If you would rather not set all the config options 
			when you first create an instance of the class you can
			set them like this:

			$ship = new ShippingCalculator ();
			$ship->set_value('from_zip','12345');

			..where the first variable is the name of the value 
			and the second variable is the value
			/*****************************************************/


			/***************** Single Rate ***********************
			If you only want to get one rate you can pass the 
			company and service code via the 'calculate' method

			$ship = new ShippingCalculator ($config);
			$rates = $ship->calculate('usps','FIRST CLASS')

			..this would return a rates array like 
			$rates =>
				'usps' =>
					'FIRST CLASS' = rate;
			/*****************************************************/

			/***************** Printing Rates *********************
			.. and finally, if you wanted to loop through the 
			returned rates and print radio buttons so your user 
			could select a shipping method you can do something 
			like this:
			*/
			if (isset($rates) && is_array($rates) && !count($rates) <= 0) {
				foreach($rates as $company => $codes) {
					if (is_array($codes) && !count($codes) <= 0) {
						foreach($codes as $code => $rate) {
							if (!is_null($rate) && !empty($rate)) {
								//$output_html .= "<option value='".strtoupper($company).':&nbsp;'.str_replace("'", "\'", $services[$company][$code])." [$".$rate."]'>".strtoupper($company).':&nbsp;'.$services[$company][$code]." [$".$rate."]</option>";
								$output_html .= "|".strtoupper($company).": ".$services[$company][$code]." [$".$rate."]";
							}
						}
					}
				}
			}
		}
		if (CACHING_ENABLED === true) {
			if (!is_null($cache_path) && !$GLOBALS['app']->writeSyntactsCacheFile($cache_path, $output_html, 'Ecommerce_shipping')) {
				//Jaws_Error::Fatal("Cache file couldn't be written: ".$this->getSyntactsCacheFile($cache_path, $RequestedGadget)." (".$RequestedGadget."::".$RequestedAction." cache_path: ".$cache_path.")");
			}
		}
		return $output_html;
	}
    
	/**
     * Updates product stock quantities
     *
     * @access  public
     * @return  boolean (true or false)
     */
    function UpdateProductStock($id, $qty = 1)
    {
        $storeModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$productInfo = $storeModel->GetProduct($id);
		if (!Jaws_Error::IsError($productInfo)) {
			$oldstock = $productInfo['instock'];
			if ($oldstock > 0) {
				$newstock = $oldstock - $qty;
				
				$domain = strtolower(str_replace(array('http://', 'https://'), '', $GLOBALS['app']->getSiteURL()));
							
				$recipient = $GLOBALS['app']->Registry->Get('/network/site_email');
				if ((int)$productInfo['ownerid'] > 0) {
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$jUser = new Jaws_User;
					$info = $jUser->GetUserInfoById((int)$productInfo['ownerid'], true);
					if (!Jaws_Error::IsError($info)) {
						$recipient = $info['email'];
					}	
				}
				$sql = '
					UPDATE [[product]] SET
						[instock] = {newstock}, 
						[updated] = {now} 
					WHERE [id] = {prod_id}';

				$params               			= array();
				$params['prod_id']         		= $id;
				$params['newstock']         	= $newstock;
				$params['now']        			= $GLOBALS['db']->Date();

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return $result;
				}
				// Notify of low stock
				if ($productInfo['inventory'] == 'Y' && $newstock >= 0 && $newstock <= $productInfo['lowstock']) {
					// TODO: Insert newsfeed
					// Send e-mail notification
					$domain = strtolower(str_replace(array('http://', 'https://'), '', $GLOBALS['app']->getSiteURL()));
					$subject = '[ '.$domain.' ] Low Stock Notification';
					
					$m_message = "This is to notify you that one of your product's stock level is low.\n\n";            
					$m_message .= "Product ID: ".$id." (".$GLOBALS['app']->GetSiteURL() . '/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url'])).") is now at ".$newstock." in stock, and your low stock level is set to ".$productInfo['lowstock'].". ";
					$m_message .= "This product is able to be purchased through your store until its \"In Stock\" count is at zero. You can also turn \"Inventory Management\" off to allow this product to be purchased indefinitely.\n\n";
					$m_message .= "Visit ".$GLOBALS['app']->GetSiteURL() . '/'.($productInfo['ownerid'] > 0 ? 'index.php?gadget=Store&action=account_' : 'admin.php?gadget=Store&action=')."A_form&id=".$id." to update this product.\n";
					
					$mail = new Jaws_Mail;
					$mail->SetHeaders($recipient, $domain ." Store", 'noreply@'.$domain, $subject);
					$mail->AddRecipient($recipient, false, false);
					$mail->SetBody($m_message, 'text');
					$mresult = $mail->send();
				
				} else if ($productInfo['inventory'] == 'Y' && $newstock == 0) {
					// TODO: Insert newsfeed
					// Send e-mail notification
					$domain = strtolower(str_replace(array('http://', 'https://'), '', $GLOBALS['app']->getSiteURL()));
					$subject = '[ '.$domain.' ] Out of Stock Notification';
					
					$m_message = "This is to notify you that one of your product's stock level is at zero.\n\n";            
					$m_message .= "Product ID: ".$id." (".$GLOBALS['app']->GetSiteURL() . '/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url'])).") is now at zero in stock. ";
					$m_message .= "This product can no longer be purchased through your store until you update its \"In Stock\" field, or turn \"Inventory Management\" off.\n\n";
					$m_message .= "Visit ".$GLOBALS['app']->GetSiteURL() . '/'.($productInfo['ownerid'] > 0 ? 'index.php?gadget=Store&action=account_' : 'admin.php?gadget=Store&action=')."A_form&id=".$id." to update this product.\n";
					
					$mail = new Jaws_Mail;
					$mail->SetHeaders($recipient, $domain ." Store", 'noreply@'.$domain, $subject);
					$mail->AddRecipient($recipient, false, false);
					$mail->SetBody($m_message, 'text');
					$mresult = $mail->send();
				}
			}
		} else {
			return new Jaws_Error("Product ID: ".$id." could not be retrieved to update stock quantities", _t('ECOMMERCE_NAME'));
			//return $productInfo;
		}
		
		// Let everyone know a product has been updated
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateProduct', $productInfo['id']);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
			
		return true;
	}
}
