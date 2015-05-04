<?php
/**
 * Ecommerce Gadget
 *
 * @category   GadgetModel
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/Ecommerce/Model.php';
class EcommerceAdminModel extends EcommerceModel
{
    var $_Name = 'Ecommerce';
	
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
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onAddEcommerceOrder');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onDeleteEcommerceOrder');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onUpdateEcommerceOrder');		// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onAddEcommerceShipping');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onDeleteEcommerceShipping');		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onUpdateEcommerceShipping');		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onAddEcommerceTax');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onDeleteEcommerceTax');		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onUpdateEcommerceTax');		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onBeforeAddToCart');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onAddToCart');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onBeforeCheckout');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onCheckout');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onAuthorizeNetResponse');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onGoogleCheckoutResponse');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onPayPalResponse');   		
        $GLOBALS['app']->Shouter->NewShouter('Ecommerce', 'onBeforeUpdateEcommerceSettings');   		

		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/payment_gateway', 'GoogleCheckout');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/gateway_id', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/gateway_key', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/gateway_signature', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/gateway_logo', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/notify_expiring_freq', '1,2,3,5,10,15,30');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/shipfrom_city', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/shipfrom_state', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/shipfrom_zip', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/use_carrier_calculated', 'Y');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/transaction_percent', '0');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/transaction_amount', '0');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/transaction_mode', 'subtract');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Ecommerce/checkout_terms', '');
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Ecommerce', 'onDeleteUser', 'RemoveUserEcommerce');
        $GLOBALS['app']->Listener->NewListener('Ecommerce', 'onUpdateUser', 'UpdateUserEcommerce');
		$GLOBALS['app']->Listener->NewListener('Ecommerce', 'onAddEcommerceOrder', 'NotifyEcommerceOrder');
		
        if (Jaws_Utils::is_writable(JAWS_DATA . 'logs/')) {
            $result = file_put_contents(JAWS_DATA . 'logs/googlemessage.log', '');
            if ($result === false) {
                return new Jaws_Error("Couldn't create googlemessage.log file", _t('ECOMMERCE_NAME'));
                //return false;
			}
            $result2 = file_put_contents(JAWS_DATA . 'logs/googleerror.log', '');
            if ($result2 === false) {
               return new Jaws_Error("Couldn't create googleerror.log file", _t('ECOMMERCE_NAME'));
               //return false;
			}
		}
        
		if (Jaws_Utils::is_writable(JAWS_DATA . 'logs/')) {
            $result = file_put_contents(JAWS_DATA . 'logs/authorizemessage.log', '');
            if ($result === false) {
                return new Jaws_Error("Couldn't create authorizemessage.log file", _t('ECOMMERCE_NAME'));
                //return false;
			}
            $result2 = file_put_contents(JAWS_DATA . 'logs/authorizeerror.log', '');
            if ($result2 === false) {
               return new Jaws_Error("Couldn't create authorizeerror.log file", _t('ECOMMERCE_NAME'));
               //return false;
			}
		}

		if (!in_array('Ecommerce', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', 'Ecommerce');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items').',Ecommerce');
			}
		}
		if (!in_array('Ecommerce', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/require_https')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/require_https') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/require_https', 'Ecommerce');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/require_https', $GLOBALS['app']->Registry->Get('/gadgets/require_https').',Ecommerce');
			}
		}
		/*
		if (!in_array('Ecommerce', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'Ecommerce');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',Ecommerce');
			}
		}
		*/
		
		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('ecommerce_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('ecommerce_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Ecommerce/OwnEcommerce', 'true');
        }
        //$userModel->addGroup('ecommerce_users', false); //Don't check if it returns true or false

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
        $tables = array('order',
                        'cart',
						'taxes');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('ECOMMERCE_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Events
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onAddEcommerceOrder');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onDeleteEcommerceOrder');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onUpdateEcommerceOrder');		// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onAddEcommerceShipping');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onDeleteEcommerceShipping');		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onUpdateEcommerceShipping');		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onAddEcommerceTax');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onDeleteEcommerceTax');		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onUpdateEcommerceTax');		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onBeforeAddToCart');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onAddToCart');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onBeforeCheckout');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onCheckout');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Ecommerce', 'onBeforeUpdateEcommerceSettings');   		

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Ecommerce', 'RemoveUserEcommerce');
        $GLOBALS['app']->Listener->DeleteListener('Ecommerce', 'UpdateUserEcommerce');

		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/payment_gateway');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/gateway_id');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/gateway_key');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/gateway_signature');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/gateway_logo');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/notify_expiring_freq');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/shipfrom_city');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/shipfrom_state');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/shipfrom_zip');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/use_carrier_calculated');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/transaction_percent');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/transaction_amount');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/transaction_mode');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Ecommerce/checkout_terms');
		
		if (!Jaws_Utils::Delete(JAWS_DATA . 'logs/googlemessage.log', false)) {
			return new Jaws_Error("Couldn't delete googlemessage.log file", _t('ECOMMERCE_NAME'));
			//return false;
		}
		if (!Jaws_Utils::Delete(JAWS_DATA . 'logs/googleerror.log', false)) {
			return new Jaws_Error("Couldn't delete googleerror.log file", _t('ECOMMERCE_NAME'));
			//return false;
		}
		if (!Jaws_Utils::Delete(JAWS_DATA . 'logs/authorizemessage.log', false)) {
			return new Jaws_Error("Couldn't delete authorizemessage.log file", _t('ECOMMERCE_NAME'));
			//return false;
		}
		if (!Jaws_Utils::Delete(JAWS_DATA . 'logs/authorizeerror.log', false)) {
			return new Jaws_Error("Couldn't delete authorizeerror.log file", _t('ECOMMERCE_NAME'));
			//return false;
		}
		if (in_array('Ecommerce', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == 'Ecommerce') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', str_replace(',Ecommerce', '', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')));
			}
		}
		if (in_array('Ecommerce', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/require_https')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == 'Ecommerce') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', str_replace(',Ecommerce', '', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')));
			}
		}
		/*
		if (in_array('Ecommerce', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'Ecommerce') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',Ecommerce', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}
		*/
		
		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('ecommerce_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Ecommerce/OwnEcommerce');
		}
        /*
		$group = $userModel->GetGroupInfoByName('ecommerce_users');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
		}
		*/
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
     * Creates a new order.
     *
     * @access  public
     * @param   string  $orderno		number of order
     * @param   string  $prod_id		id of product
     * @param   string  $price			price of product per unit
     * @param   string  $qty			number of products
     * @param   string  $unit			describes how product is sold
     * @param   string  $weight			weight of product
     * @param   string  $attribute      attributes selected for product
     * @param   string  $backorder      number of backordered product 
     * @param   string  $description    description
     * @param   string  $recurring      (Y/N) If the order should be repeated, depending on qty and unit of time
     * @param   string  $OwnerID
     * @param   string  $active         Order status (active or not)
     * @return  bool    Success/failure
     */
							
    function AddOrder($orderno, $prod_id, $price, $qty, $unit, $weight, 
		$attribute, $backorder, $description, $recurring = 'N', 
		$gadget_table, $gadget_id, $OwnerID = null, $active, $customer_email, $customer_name, 
		$customer_company, $customer_address, $customer_address2, 
		$customer_city, $customer_region, $customer_postal, $customer_country, 
		$customer_phone, $customer_fax, $customer_shipname, $customer_shipaddress, 
		$customer_shipaddress2, $customer_shipcity, $customer_shipregion, 
		$customer_shippostal, $customer_shipcountry, $total, $shiptype, $customer_id = null, 
		$sales_id = null, $customer_cc_type = '', $customer_cc_number = '', $customer_cc_exp_month = '', 
		$customer_cc_exp_year = '', $customer_cc_cvv = '', $update_status_note = '', $checksum = '')
    {
        if ((int)$orderno == 0) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'), _t('ECOMMERCE_NAME'));
        }
						
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$storeModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$pages = $model->GetOrders();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}
		
		// Format price
		if (!empty($price)) {
			$newstring = "";
			$array = str_split($price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$price = number_format($newstring, 2, '.', '');
		}

		// Format total
		if (!empty($total)) {
			$newstring = "";
			$array = str_split($total);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$total = number_format($newstring, 2, '.', '');
		}

		if (empty($total) && !empty($price)) {
			$total = $price;
		}
		
		// Format weight
		if (!empty($weight)) {
			$newstring = "";
			$array = str_split($weight);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$weight = number_format($newstring, 2, '.', '');
		}

		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
		
		// Encrypt CC type
		if (!empty($customer_cc_type)) {
			$orig_customer_cc_type = $customer_cc_type;
			$customer_cc_type = $JCrypt->rsa->encrypt($customer_cc_type, $JCrypt->pub_key);
			if (Jaws_Error::isError($customer_cc_type)) {
				return new Jaws_Error("The credit card type could not be encrypted when adding order.", _t('ECOMMERCE_NAME'));
			} else {
				$dec_customer_cc_type = $JCrypt->rsa->decrypt($customer_cc_type, $JCrypt->pvt_key);
				if ($dec_customer_cc_type != $orig_customer_cc_type) {
					return new Jaws_Error("The credit card type could not be encrypted when adding order. Decrypted: ".var_export($dec_customer_cc_type, true)." != Original: ".var_export($orig_customer_cc_type, true), _t('ECOMMERCE_NAME'));
				}
			}
		}
		
		// Encrypt CC Number
		if (!empty($customer_cc_number)) {
			$orig_customer_cc_number = $customer_cc_number;
			$customer_cc_number = $JCrypt->rsa->encrypt($customer_cc_number, $JCrypt->pub_key);
			if (Jaws_Error::isError($customer_cc_number)) {
				return new Jaws_Error("The credit card number could not be encrypted when adding order.", _t('ECOMMERCE_NAME'));
			} else {
				$dec_customer_cc_number = $JCrypt->rsa->decrypt($customer_cc_number, $JCrypt->pvt_key);
				if ($dec_customer_cc_number != $orig_customer_cc_number) {
					return new Jaws_Error("The credit card number could not be encrypted when adding order. Decrypted: ".var_export($dec_customer_cc_number, true)." != Original: ".var_export($orig_customer_cc_number, true), _t('ECOMMERCE_NAME'));
				}
			}
		}
		
		// Encrypt CC CVV
		if (!empty($customer_cc_cvv)) {
			$orig_customer_cc_cvv = $customer_cc_cvv;
			$customer_cc_cvv = $JCrypt->rsa->encrypt($customer_cc_cvv, $JCrypt->pub_key);
			if (Jaws_Error::isError($customer_cc_cvv)) {
				return new Jaws_Error("The credit card CVV digits could not be encrypted when adding order.", _t('ECOMMERCE_NAME'));
			} else {
				$dec_customer_cc_cvv = $JCrypt->rsa->decrypt($customer_cc_cvv, $JCrypt->pvt_key);
				if ($dec_customer_cc_cvv != $orig_customer_cc_cvv) {
					return new Jaws_Error("The credit card CVV could not be encrypted when adding order. Decrypted: ".var_export($dec_customer_cc_cvv, true)." != Original: ".var_export($orig_customer_cc_cvv, true), _t('ECOMMERCE_NAME'));
				}
			}
		}
		
		$sql = "
            INSERT INTO [[order]]
                ([orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated],
			[customer_email], [customer_name], [customer_company], [customer_address], [customer_address2], 
			[customer_city], [customer_region], [customer_postal], [customer_country], 
			[customer_phone], [customer_fax], [customer_shipname], [customer_shipaddress], 
			[customer_shipaddress2], [customer_shipcity], [customer_shipregion], 
			[customer_shippostal], [customer_shipcountry], [shiptype], [checksum], [customer_id], [sales_id], 
			[customer_cc_type], [customer_cc_number], [customer_cc_exp_month], [customer_cc_exp_year], [customer_cc_cvv])
            VALUES
                ({orderno}, {prod_id}, {price}, {qty}, {unit}, {weight}, 
			{attribute}, {total}, {backorder}, {description}, {recurring}, 
			{gadget_table}, {gadget_id}, {OwnerID}, {Active}, {now}, {now},
			{customer_email}, {customer_name}, {customer_company}, {customer_address}, {customer_address2}, 
			{customer_city}, {customer_region}, {customer_postal}, {customer_country}, 
			{customer_phone}, {customer_fax}, {customer_shipname}, {customer_shipaddress}, 
			{customer_shipaddress2}, {customer_shipcity}, {customer_shipregion}, 
			{customer_shippostal}, {customer_shipcountry}, {shiptype}, {checksum}, {customer_id}, {sales_id}, 
			{customer_cc_type}, {customer_cc_number}, {customer_cc_exp_month}, {customer_cc_exp_year}, {customer_cc_cvv})";
		
		$OwnerID 		= (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		$customer_id 	= (!is_null($customer_id)) ? (int)$customer_id : 0;
		$sales_id 		= (!is_null($sales_id)) ? (int)$sales_id : 0;
        $params               			= array();
        $params['orderno']         		= $orderno;
        $params['prod_id']         		= $prod_id;
        $params['price']         		= $price;
        $params['qty']         			= (int)$qty;
        $params['unit']         		= $unit;
        $params['weight']         		= $weight;
        $params['attribute']         	= $attribute;
        $params['total']         		= $total;
        $params['backorder']         	= (int)$backorder;
		$params['description'] 			= $description;
        $params['recurring'] 			= $recurring;
        $params['gadget_table'] 		= strip_tags($gadget_table);
        $params['gadget_id']   			= (int)$gadget_id;
        $params['Active'] 				= $active;
        $params['OwnerID'] 				= $OwnerID;
        $params['now']        			= $GLOBALS['db']->Date();
		$params['customer_email'] 		= $customer_email;
		$params['customer_name'] 		= $customer_name;
		$params['customer_company'] 	= $customer_company;
		$params['customer_address'] 	= $customer_address;
		$params['customer_address2'] 	= $customer_address2;
		$params['customer_city'] 		= $customer_city;
		$params['customer_region'] 		= $customer_region;
		$params['customer_postal'] 		= $customer_postal;
		$params['customer_country'] 	= $customer_country;
		$params['customer_phone'] 		= $customer_phone;
		$params['customer_fax'] 		= $customer_fax;
		$params['customer_shipname'] 	= $customer_shipname;
		$params['customer_shipaddress'] = $customer_shipaddress;
		$params['customer_shipaddress2'] = $customer_shipaddress2;
		$params['customer_shipcity'] 	= $customer_shipcity;
		$params['customer_shipregion'] 	= $customer_shipregion;
		$params['customer_shippostal'] 	= $customer_shippostal;
		$params['customer_shipcountry'] = $customer_shipcountry;
		$params['shiptype'] 			= $shiptype;
		$params['customer_id'] 			= $customer_id;
		$params['sales_id'] 			= $sales_id;
		$params['customer_cc_type'] 	= $customer_cc_type;
		$params['customer_cc_number'] 	= $customer_cc_number;
		$params['customer_cc_exp_month'] = $customer_cc_exp_month;
		$params['customer_cc_exp_year'] = $customer_cc_exp_year;
		$params['customer_cc_cvv'] 		= $customer_cc_cvv;
		$params['checksum'] 			= $checksum;
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onBeforeAddEcommerceOrder', 
			$params
		);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'), RESPONSE_ERROR);
			return $res;
		/*
		} else if (isset($res['params']) && !count($res['params']) <= 0) {
			$params = $res['params'];
		*/
		}
		
		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('order', 'id');
		
		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params2               	= array();
			$params2['id'] 			= $newid;
			$params2['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[order]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params2);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Update user details (address, etc if currently empty) and keywords
		if ((int)$params['customer_id'] > 0) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$userInfo = $jUser->GetUserInfoById((int)$params['customer_id'], true, true, true, true);
			if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				$new_name = (empty($userInfo['nickname']) && !empty($params['customer_name']) ? $params['customer_name'] : $userInfo['nickname']);
				$new_address = (empty($userInfo['address']) && !empty($params['customer_address']) ? $params['customer_address'] : $userInfo['address']);
				$new_address2 = (empty($userInfo['address2']) && !empty($params['customer_address2']) ? $params['customer_address2'] : $userInfo['address2']);
				$new_city = (empty($userInfo['city']) && !empty($params['customer_city']) ? $params['customer_city'] : $userInfo['city']);
				$new_region = (empty($userInfo['region']) && !empty($params['customer_region']) ? $params['customer_region'] : $userInfo['region']);
				switch ($new_region) {
					case 'AL':
					$new_region = 'Alabama';
					break;
					case 'AK':
					$new_region = 'Alaska';
					break;
					case 'AZ':
					$new_region = 'Arizona';
					break;
					case 'AR':
					$new_region = 'Arkansas';
					break;
					case 'CA':
					$new_region = 'California';
					break;
					case 'CO':
					$new_region = 'Colorado';
					break;
					case 'CT':
					$new_region = 'Connecticut';
					break;
					case 'DE':
					$new_region = 'Delaware';
					break;
					case 'FL':
					$new_region = 'Florida';
					break;
					case 'GA':
					$new_region = 'Georgia';
					break;
					case 'HI':
					$new_region = 'Hawaii';
					break;
					case 'ID':
					$new_region = 'Idaho';
					break;
					case 'IL':
					$new_region = 'Illinois';
					break;
					case 'IN':
					$new_region = 'Indiana';
					break;
					case 'IA':
					$new_region = 'Iowa';
					break;
					case 'KS':
					$new_region = 'Kansas';
					break;
					case 'KY':
					$new_region = 'Kentucky';
					break;
					case 'LA':
					$new_region = 'Louisiana';
					break;
					case 'ME':
					$new_region = 'Maine';
					break;
					case 'MD':
					$new_region = 'Maryland';
					break;
					case 'MA':
					$new_region = 'Massachusetts';
					break;
					case 'MI':
					$new_region = 'Michigan';
					break;
					case 'MN':
					$new_region = 'Minnesota';
					break;
					case 'MS':
					$new_region = 'Mississippi';
					break;
					case 'MO':
					$new_region = 'Missouri';
					break;
					case 'MT':
					$new_region = 'Montana';
					break;
					case 'NE':
					$new_region = 'Nebraska';
					break;
					case 'NV':
					$new_region = 'Nevada';
					break;
					case 'NH':
					$new_region = 'New Hampshire';
					break;
					case 'NJ':
					$new_region = 'New Jersey';
					break;
					case 'NM':
					$new_region = 'New Mexico';
					break;
					case 'NY':
					$new_region = 'New York';
					break;
					case 'NC':
					$new_region = 'North Carolina';
					break;
					case 'ND':
					$new_region = 'North Dakota';
					break;
					case 'OH':
					$new_region = 'Ohio';
					break;
					case 'OK':
					$new_region = 'Oklahoma';
					break;
					case 'OR':
					$new_region = 'Oregon';
					break;
					case 'PA':
					$new_region = 'Pennsylvania';
					break;
					case 'RI':
					$new_region = 'Rhode Island';
					break;
					case 'SC':
					$new_region = 'South Carolina';
					break;
					case 'SD':
					$new_region = 'South Dakota';
					break;
					case 'TN':
					$new_region = 'Tennessee';
					break;
					case 'TX':
					$new_region = 'Texas';
					break;
					case 'UT':
					$new_region = 'Utah';
					break;
					case 'VT':
					$new_region = 'Vermont';
					break;
					case 'VA':
					$new_region = 'Virginia';
					break;
					case 'WA':
					$new_region = 'Washington';
					break;
					case 'DC':
					$new_region = 'Washington D.C.';
					break;
					case 'WV':
					$new_region = 'West Virginia';
					break;
					case 'WI':
					$new_region = 'Wisconsin';
					break;
					case 'WY':
					$new_region = 'Wyoming';
					break;
				}
				$new_postal = (empty($userInfo['postal']) && !empty($params['customer_postal']) ? $params['customer_postal'] : $userInfo['postal']);
				//$new_country = (empty($userInfo['country']) && !empty($params['customer_country']) ? $params['customer_country'] : $userInfo['country']);
				$new_country = "United States";
				$new_phone = (empty($userInfo['phone']) && !empty($params['customer_phone']) ? $params['customer_phone'] : $userInfo['phone']);
				$new_keywords = '';
				$product_category_names = array();
				$prod_ids = explode(',', $params['prod_id']);
				foreach ($prod_ids as $prod_id) {
					$prod_id = (int)$prod_id;
					if ($prod_id > 0) {
						$productInfo = $storeModel->GetProduct($prod_id);
						if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
							if (isset($productInfo['category']) && !empty($productInfo['category'])) {
								$propCategories = explode(',', $productInfo['category']);
								foreach($propCategories as $propCategory) {		            
									$catParent = $storeModel->GetProductParent((int)$propCategory);
									if (!Jaws_Error::IsError($catParent)) {
										if (isset($catParent['productparentcategory_name']) && !empty($catParent['productparentcategory_name']) && !in_array($catParent['productparentcategory_name'], $product_category_names)) {
											$product_category_names[] = $catParent['productparentcategory_name'];
										}
									}
								}
							}
						}
					}
				}
				foreach ($product_category_names as $new_keyword) {
					if (!in_array($new_keyword, explode(',',$userInfo['keywords']))) {
						$new_keywords .= (!empty($new_keywords) ? ',' : '').$new_keyword;
					}
				}
				$new_keywords .= (!empty($userInfo['keywords']) ? ',' : '').$userInfo['keywords'];
				$update_user = $jUser->UpdateUser(
					 $userInfo['id'],
					 $xss->parse($userInfo['username']),
					 $xss->parse($new_name),
					 $xss->parse($userInfo['email']),
					 null, 
					 null,
					 null
				);
				$update_user = $jUser->UpdatePersonalInfo($userInfo['id'], 
					array(
						'address' => $xss->parse($new_address), 
						'address2' => $xss->parse($new_address2), 
						'city' => $xss->parse($new_city), 
						'country' => $xss->parse($new_country), 
						'region' => $xss->parse($new_region), 
						'postal' => $xss->parse($new_postal), 
						'phone' => $xss->parse($new_phone), 
						'keywords' => $xss->parse($new_keywords)
					)
				); 
			}
		}

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onAddEcommerceOrder', 
			array(
				'id' => $newid, 
				'status_note' => $update_status_note
			)
		);
		
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ORDER_CREATED'), RESPONSE_NOTICE);
		return $newid;
    }

    /**
     * Updates an order.
     *
     * @access  public
     * @param   int     $id             The ID of the gallery to update.
     * @param   string  $orderno		number of order
     * @param   string  $prod_id		id of product
     * @param   string  $price			price of product per unit
     * @param   string  $qty			number of products
     * @param   string  $unit			describes how product is sold
     * @param   string  $weight			weight of product
     * @param   string  $attribute      attributes selected for product
     * @param   string  $backorder      number of backordered product 
     * @param   string  $description    description
     * @param   string  $recurring      (Y/N) If the order should be repeated, depending on qty and unit of time
     * @param   string  $active         Order status (active or not)
     * @return  boolean Success/failure
     */						
    function UpdateOrder($id, $orderno, $prod_id, $price, $qty, $unit, $weight, 
		$attribute, $backorder, $description, $recurring, 
		$gadget_table, $gadget_id, $active, $customer_email, $customer_name, 
		$customer_company, $customer_address, $customer_address2, 
		$customer_city, $customer_region, $customer_postal, $customer_country, 
		$customer_phone, $customer_fax, $customer_shipname, $customer_shipaddress, 
		$customer_shipaddress2, $customer_shipcity, $customer_shipregion, 
		$customer_shippostal, $customer_shipcountry, $total, $shiptype, $sales_id,
		$customer_cc_type, $customer_cc_number, $customer_cc_exp_month, 
		$customer_cc_exp_year, $customer_cc_cvv, $update_status_note = '')
	{

		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
        $page = $model->GetOrder($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
	
		$status_changed = false;
		// Detect order status change
		if ($page['active'] != $active) {
			$status_changed = true;
		}

		// Format price
		if (!empty($price)) {
			$newstring = "";
			$array = str_split($price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$price = number_format($newstring, 2, '.', '');
		}

		// Format total
		if (!empty($total)) {
			$newstring = "";
			$array = str_split($total);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$total = number_format($newstring, 2, '.', '');
		}

		if (empty($total) && !empty($price)) {
			$total = $price;
		}
		
		// Format weight
		if (!empty($weight)) {
			$newstring = "";
			$array = str_split($weight);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$weight = number_format($newstring, 2, '.', '');
		}

		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
		
		// Encrypt CC type
		if (!empty($customer_cc_type)) {
			$orig_customer_cc_type = $customer_cc_type;
			$customer_cc_type = $JCrypt->rsa->encrypt($customer_cc_type, $JCrypt->pub_key);
			if (Jaws_Error::isError($customer_cc_type)) {
				return new Jaws_Error("The credit card type could not be encrypted when adding order.", _t('ECOMMERCE_NAME'));
			} else {
				$dec_customer_cc_type = $JCrypt->rsa->decrypt($customer_cc_type, $JCrypt->pvt_key);
				if ($dec_customer_cc_type != $orig_customer_cc_type) {
					return new Jaws_Error("The credit card type could not be encrypted when adding order. Decrypted: ".var_export($dec_customer_cc_type, true)." != Original: ".var_export($orig_customer_cc_type, true), _t('ECOMMERCE_NAME'));
				}
			}
		}
		
		// Encrypt CC Number
		if (!empty($customer_cc_number)) {
			$orig_customer_cc_number = $customer_cc_number;
			$customer_cc_number = $JCrypt->rsa->encrypt($customer_cc_number, $JCrypt->pub_key);
			if (Jaws_Error::isError($customer_cc_number)) {
				return new Jaws_Error("The credit card number could not be encrypted when adding order.", _t('ECOMMERCE_NAME'));
			} else {
				$dec_customer_cc_number = $JCrypt->rsa->decrypt($customer_cc_number, $JCrypt->pvt_key);
				if ($dec_customer_cc_number != $orig_customer_cc_number) {
					return new Jaws_Error("The credit card number could not be encrypted when adding order. Decrypted: ".var_export($dec_customer_cc_number, true)." != Original: ".var_export($orig_customer_cc_number, true), _t('ECOMMERCE_NAME'));
				}
			}
		}
		
		// Encrypt CC CVV
		if (!empty($customer_cc_cvv)) {
			$orig_customer_cc_cvv = $customer_cc_cvv;
			$customer_cc_cvv = $JCrypt->rsa->encrypt($customer_cc_cvv, $JCrypt->pub_key);
			if (Jaws_Error::isError($customer_cc_cvv)) {
				return new Jaws_Error("The credit card CVV digits could not be encrypted when adding order.", _t('ECOMMERCE_NAME'));
			} else {
				$dec_customer_cc_cvv = $JCrypt->rsa->decrypt($customer_cc_cvv, $JCrypt->pvt_key);
				if ($dec_customer_cc_cvv != $orig_customer_cc_cvv) {
					return new Jaws_Error("The credit card CVV could not be encrypted when adding order. Decrypted: ".var_export($dec_customer_cc_cvv, true)." != Original: ".var_export($orig_customer_cc_cvv, true), _t('ECOMMERCE_NAME'));
				}
			}
		}
		
        $sql = '
            UPDATE [[order]] SET
				[orderno] = {orderno}, 
				[prod_id] = {prod_id}, 
				[price] = {price}, 
				[qty] = {qty}, 
				[unit] = {unit}, 
				[weight] = {weight}, 
				[attribute] = {attribute}, 
				[total] = {total}, 
				[backorder] = {backorder}, 
				[description] = {description}, 
				[recurring] = {recurring}, 
				[gadget_table] = {gadget_table}, 
				[gadget_id] = {gadget_id}, 
				[active] = {Active}, 
				[updated] = {now},
				[customer_email] = {customer_email}, 
				[customer_name] = {customer_name}, 
				[customer_company] = {customer_company}, 
				[customer_address] = {customer_address}, 
				[customer_address2] = {customer_address2}, 
				[customer_city] = {customer_city}, 
				[customer_region] = {customer_region}, 
				[customer_postal] = {customer_postal}, 
				[customer_country] = {customer_country}, 
				[customer_phone] = {customer_phone}, 
				[customer_fax] = {customer_fax}, 
				[customer_shipname] = {customer_shipname}, 
				[customer_shipaddress] = {customer_shipaddress}, 
				[customer_shipaddress2] = {customer_shipaddress2}, 
				[customer_shipcity] = {customer_shipcity}, 
				[customer_shipregion] = {customer_shipregion}, 
				[customer_shippostal] = {customer_shippostal}, 
				[customer_shipcountry] = {customer_shipcountry},
				[shiptype] = {shiptype},
				[sales_id] = {sales_id}, 
				[customer_cc_type] = {customer_cc_type}, 
				[customer_cc_number] = {customer_cc_number}, 
				[customer_cc_exp_month] = {customer_cc_exp_month},
				[customer_cc_exp_year] = {customer_cc_exp_year},
				[customer_cc_cvv] = {customer_cc_cvv}
			WHERE [id] = {id}';

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['orderno']         		= $orderno;
        $params['prod_id']         		= $prod_id;
        $params['price']         		= $price;
        $params['qty']         			= (int)$qty;
        $params['unit']         		= $unit;
        $params['weight']         		= $weight;
        $params['attribute']         	= $attribute;
        $params['total']         		= $total;
        $params['backorder']         	= (int)$backorder;
        $params['description'] 			= $description;
        $params['recurring'] 			= $recurring;
        $params['gadget_table'] 		= strip_tags($gadget_table);
        $params['gadget_id']   			= (int)$gadget_id;
        $params['Active'] 				= $active;
        $params['now']        			= $GLOBALS['db']->Date();		
		$params['customer_email'] 		= $customer_email;
		$params['customer_name'] 		= $customer_name;
		$params['customer_company'] 	= $customer_company;
		$params['customer_address'] 	= $customer_address;
		$params['customer_address2'] 	= $customer_address2;
		$params['customer_city'] 		= $customer_city;
		$params['customer_region'] 		= $customer_region;
		$params['customer_postal'] 		= $customer_postal;
		$params['customer_country'] 	= $customer_country;
		$params['customer_phone'] 		= $customer_phone;
		$params['customer_fax'] 		= $customer_fax;
		$params['customer_shipname'] 	= $customer_shipname;
		$params['customer_shipaddress'] = $customer_shipaddress;
		$params['customer_shipaddress2'] = $customer_shipaddress2;
		$params['customer_shipcity'] 	= $customer_shipcity;
		$params['customer_shipregion'] 	= $customer_shipregion;
		$params['customer_shippostal'] 	= $customer_shippostal;
		$params['customer_shipcountry'] = $customer_shipcountry;
		$params['shiptype'] 			= $shiptype;
		$params['sales_id'] 			= $sales_id;
		$params['customer_cc_type'] 	= $customer_cc_type;
		$params['customer_cc_number'] 	= $customer_cc_number;
		$params['customer_cc_exp_month'] = $customer_cc_exp_month;
		$params['customer_cc_exp_year'] = $customer_cc_exp_year;
		$params['customer_cc_cvv'] 		= $customer_cc_cvv;
        
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
						
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onUpdateEcommerceOrder', 
			array(
				'id' => $id, 
				'status_changed' => $status_changed, 
				'status_note' => $update_status_note
			)
		);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ORDER_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Update order statuses, with custom notes.
     *
     * @category  feature
     * @access  public
     * @param   int     $id	 The ID of the order to update.
     * @param   string  $active	 Order status (TEMP/NEW/REVIEWING/CHARGEABLE/CHARGING/CHARGED/
										PAYMENT_DECLINED/CANCELLED/CANCELLED_BY_GATEWAY/PROCESSING/
										DELIVERED/WILL_NOT_DELIVER/REFUNDED)
     * @param   string     $update_status_note 	Detailed note sent along with status update, describing the update.
     * @return  boolean Success/failure
     */
    function UpdateOrderStatus($id, $active, $update_status_note = '')
	{
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
        $page = $model->GetOrder($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
			
		$status_changed = false;
		if (strtolower(trim($page['active'])) != strtolower(trim($active))) {
			$status_changed = true;
		}
		
        $sql = '
            UPDATE [[order]] SET
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['Active'] 				= $active;
        $params['now']        			= $GLOBALS['db']->Date();		
        
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
						
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onUpdateEcommerceOrder', 
			array(
				'id' => $id, 
				'status_changed' => $status_changed, 
				'status_note' => $update_status_note
			)
		);
		
		$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ORDER_UPDATED'), RESPONSE_NOTICE);
		return true;
    }
	
	/**
     * Delete an order
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteOrder($id, $massive = false)
    {
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$parent = $model->GetOrder((int)$id);
	        if (Jaws_Error::IsError($parent)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_DELETED'), _t('ECOMMERCE_NAME'));
	        }

	        if(!isset($parent['id'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_DELETED'), _t('ECOMMERCE_NAME'));
			} else {
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onDeleteEcommerceOrder', $id);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
				
				$sql = 'DELETE FROM [[order]] WHERE [id] = {id}';
				$res = $GLOBALS['db']->query($sql, array('id' => $id));
				if (Jaws_Error::IsError($res)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_DELETED'), _t('ECOMMERCE_NAME'));
				}
	        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ORDER_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a group of orders
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_MASSIVE_DELETED'), _t('ECOMMERCE_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteOrder($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_MASSIVE_DELETED'), _t('ECOMMERCE_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ORDER_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

	
    /**
     * Create shipping rules by weight or price and set defaults.
     *
     * @category 	feature
     * @param   int  $sort_order		Priority
     * @param   string  $type		Type of shipping (price/weight/default)
     * @param   string  $title			Title
     * @param   string  $minfactor			Minimum factor (depending on price/weight) that triggers this shipping rule
     * @param   string  $maxfactor			Maximum factor that triggers this shipping rule
     * @param   string  $price			Price of shipping
     * @param   string  $description      Long description of this rule
     * @param   string  $OwnerID      User ID that owns this rule 
     * @param   string  $Active    (Y/N) If the rule is active
     * @param   string  $checksum 	unique ID
     * @access  public
     * @return  bool    Success/failure
     */
    function AddShipping($sort_order = 0, $type, $title, $minfactor, $maxfactor, $price, 
							$description, $OwnerID, $Active = 'Y', $checksum = '')
    {		
		$sql = "
            INSERT INTO [[shipping]]
                ([sort_order], [type], [title], [minfactor], [maxfactor], 
				[price], [description], [ownerid], [active], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {type}, {title}, {minfactor}, {maxfactor}, 
				{price}, {description}, {OwnerID}, {Active}, {now}, {now}, {checksum})";
		
		
		if ($type == 'default') {
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$shippings = $model->GetShippings();
	        if (Jaws_Error::IsError($shippings)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
	        } else {
				foreach ($shippings as $shipping) {
					if ($shipping['type'] == 'default') {
						return $this->UpdateShipping(
							$shipping['id'], $shipping['sort_order'], 'default', 'Flat Rate', 0, 0, 
							$price, $description, $OwnerID, $Active
						);
					}
					if (!empty($checksum)) {
						if ($shipping['checksum'] == $checksum) {
							return true;
						}
					}		
				}
			}
		}
		
		// Format price
		if (!empty($price)) {
			$newstring = "";
			$array = str_split($price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$price = number_format($newstring, 2, '.', '');
		}

		if ($type == 'default') {
			$minfactor = 0.00;
			$maxfactor = 0.00;
			$title = 'Flat Rate';
		} else {
			// Format minfactor
			if (!empty($minfactor)) {
				$newstring = "";
				$array = str_split($minfactor);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$minfactor = number_format($newstring, 2, '.', '');
			}

			// Format maxfactor
			if (!empty($maxfactor)) {
				$newstring = "";
				$array = str_split($maxfactor);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$maxfactor = number_format($newstring, 2, '.', '');
			}

		}
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
        $params               			= array();
        $params['sort_order']         	= $sort_order;
        $params['type']         		= $type;
        $params['title']         		= $title;
        $params['minfactor']         	= $minfactor;
        $params['maxfactor']         	= $maxfactor;
        $params['price']         		= $price;
        $params['description'] 			= strip_tags($description);
        $params['Active'] 				= $Active;
        $params['OwnerID'] 				= $OwnerID;
        $params['checksum'] 			= $checksum;
        $params['now']        			= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPING_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('shipping', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[shipping]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddEcommerceShipping', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_SHIPPING_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a shipping.
     *
     * @access  public
     * @param   int     $id             The ID of the gallery to update.
     * @param   string  $orderno		number of order
     * @param   string  $prod_id		id of product
     * @param   string  $price			price of product per unit
     * @param   string  $qty			number of products
     * @param   string  $unit			describes how product is sold
     * @param   string  $weight			weight of product
     * @param   string  $attribute      attributes selected for product
     * @param   string  $backorder      number of backordered product 
     * @param   string  $description    description
     * @param   string  $recurring      (Y/N) If the order should be repeated, depending on qty and unit of time
     * @param   string  $active         Order status (active or not)
     * @return  boolean Success/failure
     */
    function UpdateShipping($id, $sort_order = 0, $type, $title, $minfactor, $maxfactor, $price, 
		$description, $Active)
	{

		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
        $page = $model->GetShipping((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPING_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
		if ($type == 'default') {
			$shippings = $model->GetShippings();
	        if (Jaws_Error::IsError($shippings)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
	        } else {
				foreach ($shippings as $shipping) {
					if ($shipping['type'] == 'default' && $shipping['id'] != $id) {
						return $this->UpdateShipping(
							$shipping['id'], $shipping['sort_order'], 'default', 'Flat Rate', 0, 0, 
							$price, $description, $OwnerID, $Active
						);
					}
				}
			}
		}

        $sql = '
            UPDATE [[shipping]] SET
				[sort_order] = {sort_order}, 
				[type] = {type}, 
				[title] = {title}, 
				[minfactor] = {minfactor}, 
				[maxfactor] = {maxfactor}, 
				[price] = {price}, 
				[description] = {description}, 
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		// Format price
		if (!empty($price)) {
			$newstring = "";
			$array = str_split($price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$price = number_format($newstring, 2, '.', '');
		}

		if ($type == 'default') {
			$minfactor = 0.00;
			$maxfactor = 0.00;
			$title = 'Flat Rate';
		} else {
			// Format minfactor
			if (!empty($minfactor)) {
				$newstring = "";
				$array = str_split($minfactor);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$minfactor = number_format($newstring, 2, '.', '');
			}

			// Format maxfactor
			if (!empty($maxfactor)) {
				$newstring = "";
				$array = str_split($maxfactor);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$maxfactor = number_format($newstring, 2, '.', '');
			}
		}

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['sort_order']         	= $sort_order;
        $params['type']         		= $type;
        $params['title']         		= $title;
        $params['minfactor']         	= $minfactor;
        $params['maxfactor']         	= $maxfactor;
        $params['price']         		= $price;
        $params['description'] 			= strip_tags($description);
        $params['Active'] 				= $Active;
        $params['now']        			= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPING_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateEcommerceShipping', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_SHIPPING_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete an order
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteShipping($id, $massive = false)
    {
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$parent = $model->GetShipping((int)$id);
	        if (Jaws_Error::IsError($parent)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPING_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_DELETED'), _t('ECOMMERCE_NAME'));
	        }

	        if(!isset($parent['id'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPING_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_DELETED'), _t('ECOMMERCE_NAME'));
			} else {
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onDeleteEcommerceShipping', $id);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
				
				$sql = 'DELETE FROM [[shipping]] WHERE [id] = {id}';
				$res = $GLOBALS['db']->query($sql, array('id' => $id));
				if (Jaws_Error::IsError($res)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_SHIPPING_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_DELETED'), _t('ECOMMERCE_NAME'));
				}
	        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_SHIPPING_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

	/**
     * Create tax rules, by location and set default.
     *
     * @category 	feature
     * @param   string  $sort_order		Priority
     * @param   string  $title			title of this rule
     * @param   string  $locations		locations this tax rule applies to
     * @param   string  $taxpercent		percent that is added
     * @param   string  $OwnerID 	User ID that owns this rule
     * @param   string  $always      	(Y/N) If the tax rule should be always be applied
     * @param   string  $Active         (Y/N) Tax is active
     * @param   string  $checksum 	Unique ID
     * @access  public
     * @return  bool    Success/failure
     */
    function AddTax($sort_order = 0, $title = '', $locations = '', $taxpercent = 0.00, 
	$OwnerID, $always = 'N', $Active = 'Y', $checksum = '')
    {		
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$pages = $model->GetTaxes();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}
		
		$sql = "
            INSERT INTO [[taxes]]
                ([sort_order], [title], [locations], [taxpercent], 
				[ownerid], [always], [active], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {title}, {locations}, {taxpercent}, 
				{OwnerID}, {always}, {Active}, {now}, {now}, {checksum})";
		
		
		// Format taxpercent
		if (!empty($taxpercent)) {
			$newstring = "";
			$array = str_split($taxpercent);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$taxpercent = number_format($newstring, 2, '.', '');
		}
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
        $params               			= array();
        $params['sort_order']         	= $sort_order;
        $params['title']         		= $title;
        $params['locations'] 			= strip_tags($locations);
        $params['taxpercent']         	= $taxpercent;
        $params['Active'] 				= $Active;
        $params['always']         		= $always;
        $params['OwnerID'] 				= $OwnerID;
        $params['checksum'] 			= $checksum;
        $params['now']        			= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_TAX_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('taxes', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[taxes]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddEcommerceTax', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_TAX_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a tax rule.
     *
     * @access  public
     * @param   int     $id             The ID of the gallery to update.
     * @param   string  $sort_order		number of order
     * @param   string  $title			title of this rule
     * @param   string  $locations		locations this tax rule applies to
     * @param   string  $taxpercent		percent that is added
     * @param   string  $always      	(Y/N) If the tax rule should be always be applied
     * @param   string  $Active         Order status (active or not)
     * @return  boolean Success/failure
     */
    function UpdateTax($id, $sort_order, $title, $locations, $taxpercent, $always, $Active)
	{

		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
        $page = $model->GetTax((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_TAX_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
		
        $sql = '
            UPDATE [[taxes]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[locations] = {locations}, 
				[taxpercent] = {taxpercent}, 
				[always] = {always}, 
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		// Format price
		if (!empty($taxpercent)) {
			$newstring = "";
			$array = str_split($taxpercent);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$taxpercent = number_format($newstring, 2, '.', '');
		}

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['sort_order']         	= $sort_order;
        $params['title']         		= $title;
        $params['locations'] 			= strip_tags($locations);
        $params['taxpercent']         	= $taxpercent;
        $params['active'] 				= $Active;
        $params['always']         		= $always;
        $params['now']        			= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_TAX_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateEcommerceTax', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_TAX_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete a tax rule
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteTax($id, $massive = false)
    {
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$parent = $model->GetTax((int)$id);
	        if (Jaws_Error::IsError($parent)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_TAX_NOT_FOUND'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_TAX_NOT_FOUND'), _t('ECOMMERCE_NAME'));
	        }

	        if(!isset($parent['id'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_TAX_NOT_FOUND'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ECOMMERCE_ERROR_TAX_NOT_FOUND'), _t('ECOMMERCE_NAME'));
			} else {
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onDeleteEcommerceTax', $id);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}

				$sql = 'DELETE FROM [[taxes]] WHERE [id] = {id}';
				$res = $GLOBALS['db']->query($sql, array('id' => $id));
				if (Jaws_Error::IsError($res)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_TAX_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('ECOMMERCE_ERROR_TAX_NOT_DELETED'), _t('ECOMMERCE_NAME'));
				}
	        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_TAX_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
	 * Re-sorts orders
     *
     * @access  public
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts)
    {
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [[order]] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_ORDER_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ORDER_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

	/**
     * Search for orders that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of order(s) we want to display
     * @param   string  $search  Keyword (title/description) of orders we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchOrders($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();

		$params['status'] = $status;

        $sql = '
            SELECT [id], [orderno], [prod_id], [price], [qty], [unit], [weight], 
			[attribute], [total], [backorder], [description], [recurring], 
			[gadget_table], [gadget_id], [ownerid], [active], [created], [updated], [checksum]
            FROM [[order]]
			WHERE (orderno > 0';
	    
        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
        }
        $sql .= ')';
        
        if (!is_null($OwnerID)) {
			$sql .= ' AND ([ownerid] = {OwnerID})';
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
                $sql .= " AND ([description] LIKE {textLike_".$i."} OR [gadget_table] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'integer', 'integer', 'decimal', 'integer', 'text', 'decimal', 
			'text', 'decimal', 'integer', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDERS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDERS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
	
	/**
     * Search for shipping that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of order(s) we want to display
     * @param   string  $search  Keyword (title/description) of orders we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchShippings($status, $search, $offSet = null, $OwnerID = 0)
    {
        $params = array();

		$params['status'] = $status;

        $sql = "
            SELECT [id], [sort_order], [type], [title], [minfactor], [maxfactor], 
				[price], [description], [ownerid], [active], [created], [updated], [checksum]
            FROM [[shipping]]
			WHERE (title != ''";
	    
        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
        }
        $sql .= ')';
        
		$sql .= ' AND ([ownerid] = {OwnerID})';
		$params['OwnerID'] = $OwnerID;

		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([description] LIKE {textLike_".$i."} OR [type] LIKE {textLike_".$i."} OR [title] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'text', 'decimal', 'decimal', 'decimal', 
			'text', 'integer', 'text', 'timestamp', 'timestamp', 'text'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPINGS_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
	
	/**
     * Search for tax rules that matches a status and/or a keyword
     * in the title or content
     *
     * @access  public
     * @param   string  $status  Status of order(s) we want to display
     * @param   string  $search  Keyword (title/description) of orders we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchTaxes($status, $search, $offSet = null, $OwnerID = 0)
    {
        $params = array();

		$params['status'] = $status;

        $sql = "
            SELECT [id], [sort_order], [title], [locations], [taxpercent], 
				[ownerid], [always], [active], [created], [updated], [checksum]
            FROM [[taxes]]
			WHERE (title != ''";
	    
        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
        }
        $sql .= ')';
        
		$sql .= ' AND ([ownerid] = {OwnerID})';
		$params['OwnerID'] = $OwnerID;

		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [locations] LIKE {textLike_".$i."} OR [taxpercent] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'text', 'decimal', 
			'integer', 'text', 'text', 'timestamp', 'timestamp', 'text'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ECOMMERCE_ERROR_TAXES_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ECOMMERCE_ERROR_TAXES_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
	
    /**
     * Save user config settings
     *
     * @access  public
     * @param   string  $priority  Priority
     * @param   string  $method    Authentication method
     * @param   string  $anon      Anonymous users can auto-register
     * @param   string  $recover   Users can recover their passwords
     * @return  boolean Success/Failure
     */
    function SaveSettings(
		$payment_gateway, $gateway_id, $gateway_key, $gateway_signature, $gateway_logo, 
		$notify_expiring_freq, $shipfrom_city, $shipfrom_state, $shipfrom_zip, $use_carrier_calculated = '', 
		$transaction_percent = 0, $transaction_amount = 0, $transaction_mode = 'subtract', $checkout_terms = ''
	) {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onBeforeUpdateEcommerceSettings', 
			array(
				'payment_gateway' => $payment_gateway, 
				'gateway_id' => $gateway_id, 
				'gateway_key' => $gateway_key, 
				'gateway_signature' => $gateway_signature, 
				'gateway_logo' => $gateway_logo, 
				'notify_expiring_freq' => $notify_expiring_freq, 
				'shipfrom_city' => $shipfrom_city, 
				'shipfrom_state' => $shipfrom_state, 
				'shipfrom_zip' => $shipfrom_zip, 
				'use_carrier_calculated' => $use_carrier_calculated, 
				'transaction_percent' => $transaction_percent, 
				'transaction_amount' => $transaction_amount, 
				'transaction_mode' => $transaction_mode,
				'checkout_terms' => $checkout_terms
			)
		);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $payment_gateway 	= $xss->parse($payment_gateway);
        $gateway_id			= $xss->parse($gateway_id);
        $gateway_key		= $xss->parse($gateway_key);
        $gateway_signature	= $xss->parse($gateway_signature);
        $gateway_logo     	= $xss->parse($gateway_logo);
        $shipfrom_city     	= $xss->parse($shipfrom_city);
        $shipfrom_state     = $xss->parse($shipfrom_state);
        $shipfrom_zip     	= $xss->parse($shipfrom_zip);
        $transaction_percent	= $xss->parse($transaction_percent);
        $transaction_amount	= $xss->parse($transaction_amount);
		// Format setup_fee
		if (!empty($transaction_amount)) {
			$newstring = "";
			$array = str_split($transaction_amount);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$transaction_amount = number_format($newstring, 2, '.', '');
		}
        $transaction_mode  	= $xss->parse($transaction_mode);
        $checkout_terms  	= $xss->parse($checkout_terms);
		
		// build address
		$region = '';
		$city = '';
		$postal_code = $shipfrom_zip;
		$address_region = '';
		
		if (!empty($shipfrom_city) || !empty($shipfrom_state) || !empty($shipfrom_zip)) {
			$error = '';
			$marker_address = $shipfrom_city;
			$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
			if (!empty($shipfrom_state)) {
				$country = $model->GetRegion((int)$shipfrom_state);
				if (!Jaws_Error::IsError($country)) {
					if (strpos($country['region'], " - US") !== false) {
						$country['region'] = str_replace(" - US", '', $country['region']);
					}
					if (strpos($country['region'], " - British") !== false) {
						$country['region'] = str_replace(" - British", '', $country['region']);
					}
					if (strpos($country['region'], " SAR") !== false) {
						$country['region'] = str_replace(" SAR", '', $country['region']);
					}
					if (strpos($country['region'], " - Islas Malvinas") !== false) {
						$country['region'] = str_replace(" - Islas Malvinas", '', $country['region']);
					}
					$address_region = $country['region'];
				} else {
					return new Jaws_Error($country->GetMessage(), _t('ECOMMERCE_NAME'));
				}
			}
			$marker_address .= (!empty($marker_address) ? ', ' : '').$address_region;
			$marker_address .= (!empty($marker_address) ? ', ' : '').$shipfrom_zip;
			$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
			// snoopy
			$snoopy = new Snoopy('Ecommerce');
			$snoopy->agent = "Jaws";
			$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
			//echo '<br />Google Geocode URL: '.$geocode_url;
			if($snoopy->fetch($geocode_url)) {
				$xml_content = $snoopy->results;
			
				// XML Parser
				$xml_parser = new XMLParser;
				$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
				//echo '<pre>';
				//var_dump($xml_result);
				//echo '</pre>';
				for ($i=0;$i<$xml_result[1]; $i++) {
					if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COUNTRYNAMECODE']) && isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && isset($xml_result[0][$i]['LOCALITYNAME']) && (empty($region) || empty($city))) {
						$region = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
						$city = $xml_result[0][$i]['LOCALITYNAME'];
						if (isset($xml_result[0][$i]['POSTALCODENUMBER'])) {
							$postal_code = $xml_result[0][$i]['POSTALCODENUMBER'];
						}
						//$error .= '<br /> :: SETTING city: '.$city.' state: '.$region.' zip: '.$postal_code;
					}
				}
			} else {
				$error .= $snoopy->error;
			}
		}
		
		//$error .= '<br /> :: AFTER city: '.$city.' state: '.$region.' zip: '.$postal_code;
		$res1 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/payment_gateway', $payment_gateway);
        $res2 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/gateway_id', $gateway_id);
        $res3 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/gateway_key', $gateway_key);
        $res4 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/gateway_logo', $gateway_logo);
        $res6 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/shipfrom_city', $city);
        $res7 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/shipfrom_state', $region);
        $res8 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/shipfrom_zip', $postal_code);
        $res9 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/use_carrier_calculated', $use_carrier_calculated);
        $res10 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/gateway_signature', $gateway_signature);
        $res11 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/transaction_percent', $transaction_percent);
        $res12 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/transaction_amount', $transaction_amount);
        $res13 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/transaction_mode', $transaction_mode);
        $res14 = $GLOBALS['app']->Registry->Set('/gadgets/Ecommerce/checkout_terms', $checkout_terms);
        if (!empty($error)) {
			return new Jaws_Error($error, _t('ECOMMERCE_NAME'));
        }
		$str = ""; 
		$comma = "";
        foreach ($notify_expiring_freq as $Key => $Value) {
			$str .= $comma.$Value;
			$comma=",";
        }

        $res5 = $GLOBALS['app']->Registry->Set("/gadgets/Ecommerce/notify_expiring_freq",$str);
		
		if ($res1 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating payment_gateway", _t('ECOMMERCE_NAME'));
		}
		if ($res2 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating gateway_id", _t('ECOMMERCE_NAME'));
		}
		if ($res3 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating gateway_key", _t('ECOMMERCE_NAME'));
		}
		if ($res4 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating gateway_logo", _t('ECOMMERCE_NAME'));
		}
		if ($res5 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating notify_expiring_freq", _t('ECOMMERCE_NAME'));
		}
		if ($res6 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating shipfrom_city", _t('ECOMMERCE_NAME'));
		}
		if ($res7 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating shipfrom_state", _t('ECOMMERCE_NAME'));
		}
		if ($res8 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating shipfrom_zip", _t('ECOMMERCE_NAME'));
		}
		if ($res9 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating use_carrier_calculated", _t('ECOMMERCE_NAME'));
		}
		if ($res10 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating gateway_signature", _t('ECOMMERCE_NAME'));
        }
		if ($res11 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating transaction_percent", _t('ECOMMERCE_NAME'));
        }
		if ($res12 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating transaction_amount", _t('ECOMMERCE_NAME'));
        }
		if ($res13 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating transaction_mode", _t('ECOMMERCE_NAME'));
        }
		if ($res14 === false) {
			//return new Jaws_Error(_t('ECOMMERCE_SETTINGS_CANT_UPDATE'), _t('ECOMMERCE_NAME'));
			return new Jaws_Error("There was a problem updating checkout_terms", _t('ECOMMERCE_NAME'));
        }
		$GLOBALS['app']->Registry->Commit('core');
		$GLOBALS['app']->Registry->Commit('Ecommerce');
		return true;
    }
	
    /**
     * Notifies on Ecommerce orders
     *
     * @access  public
     * @param   int  $id  Order ID
     * @return  array   Response
     */
    function NotifyEcommerceOrder($params) 
    {
		$id = $params['id'];
		$status_changed = false;
		if (isset($params['status_changed'])) {
			$status_changed = $params['status_changed'];
		}
		if (isset($params['status_note'])) {
			$status_note = $params['status_note'];
		}
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$orderInfo = $model->GetOrder($id);
		if (!Jaws_Error::IsError($orderInfo)) {
			if ($orderInfo['active'] != 'TEMP') {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$usersModel = $GLOBALS['app']->LoadGadget('Users', 'Model');
				
				// Send e-mail notification
				$domain = str_replace(array('http://', 'https://'), '', $GLOBALS['app']->GetSiteURL());
				if (strpos($domain, '/') !== false) {
					$domain = substr($domain, 0, strpos($domain, '/'));
				}
				$from_email = $GLOBALS['app']->Registry->Get('/network/site_email');
				if (empty($from_email)) {
					$from_email = 'webmaster@'.$domain;
				}
				$owner_email = $from_email;
				if ((int)$orderInfo['ownerid'] > 0) {
					$ownerInfo = $jUser->GetUserInfoById((int)$orderInfo['ownerid'], true);
					if (!Jaws_Error::IsError($ownerInfo)) {
						$owner_email = $ownerInfo['email'];
					}
				}
				if (!empty($owner_email)) {
					$mresult = $usersModel->MailComment($owner_email, '', '', '', '', 'Ecommerce', $orderInfo['id'], false, false, true);
				}
								
				require_once JAWS_PATH . 'include/Jaws/Mail.php';
				$created = $GLOBALS['db']->Date();
				
				$subject = '[ '.$domain.' ] Order';

				// Get customer info
				$customer_email = '';
				if (!empty($orderInfo['customer_email'])) {
					$customer_email = $orderInfo['customer_email'];
				} else if ((int)$orderInfo['customer_id'] > 0) {
					$info = $jUser->GetUserInfoById((int)$orderInfo['customer_id'], true);
					if (!Jaws_Error::IsError($info)) {
						$customer_email = $info['email'];
					}
				}
				
				if (!empty($customer_email)) {
					$mresult = $usersModel->MailComment($customer_email, '', '', '', '', 'Ecommerce', $orderInfo['id'], false, false, true);
				}

				$message = '';
				$message .= 'Date: ' . $created."\n";            
				if (!empty($status_note)) {
					$message .= "\n".$status_note."\n\n";            
				}
				$m_message = '';            
				foreach ($orderInfo as $server_key => $server_val) {
					$m_message .= $server_key.': ' . $server_val."\n";
				}
				$a_message = 'orderno: '.$orderInfo['orderno']."\n";
				$f_message = "\n".'View: '.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Ecommerce&action=view&id='.$orderInfo['id']."\n";
				
				/*
				$mail = new Jaws_Mail;
				$mail->SetHeaders($from_email, $domain.' Orders', $from_email, $subject);
				$mail->AddRecipient($owner_email, false, false);
				$mail->SetBody($message.$a_message.$f_message, 'text');
				$mresult = $mail->send();
				*/
								
			}
		}
		return true;
	}
        
	/**
     * Updates a User's Ecommerce stuff
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserEcommerce($uid) 
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$info = $jUser->GetUserInfoById((int)$uid, true);
		if (!Jaws_Error::IsError($info)) {
			$params           	= array();
			$params['id']     	= $info['id'];
			if (!$info['enabled']) {
				$params['Active'] = 'N';
				$params['was'] = 'Y';
			} else {
				$params['Active'] = 'Y';
				$params['was'] = 'N';
			}
			$sql = '
				UPDATE [[order]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_ORDERS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql2 = '
				UPDATE [[shipping]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result2 = $GLOBALS['db']->query($sql2, $params);
			if (Jaws_Error::IsError($result2)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_SHIPPINGS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql3 = '
				UPDATE [[taxes]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result3 = $GLOBALS['db']->query($sql3, $params);
			if (Jaws_Error::IsError($result3)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_TAXES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_USER_ORDERS_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_ORDERS_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's Ecommerce stuff
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserEcommerce($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$parents = $model->GetEcommerceOfUserID((int)$uid);
		if (!Jaws_Error::IsError($parents)) {
			foreach ($parents as $page) {
				$result = $this->DeleteOrder($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_ORDER_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_USER_ORDERS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_ORDERS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$taxes = $model->GetTaxesOfUserID((int)$uid);
		if (!Jaws_Error::IsError($taxes)) {
			foreach ($taxes as $page) {
				$result = $this->DeleteTax($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_TAX_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_USER_TAXES_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_TAXES_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$shippings = $model->GetShippingsOfUserID((int)$uid);
		if (!Jaws_Error::IsError($shippings)) {
			foreach ($shippings as $page) {
				$result = $this->DeleteShipping($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_SHIPPING_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_USER_SHIPPINGS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_ERROR_USER_SHIPPINGS_NOT_DELETED'), RESPONSE_NOTICE);
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
		if ($gadget == 'Ecommerce') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$parents = $model->GetOrders();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[order]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddEcommerceOrder', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}

				}
			}
			$parents = $model->GetShippings();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[shipping]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddEcommerceShipping', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
			$parents = $model->GetTaxes();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[taxes]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddEcommerceTax', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
		}
		return true;
    }
}