<?php
/**
 * Ecommerce Gadget 
 *
 * @category   Gadget
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Ecommerce';
    /**
     * Constructor
     *
     * @access public
     */
    function EcommerceHTML()
    {
        $this->Init('Ecommerce');
    }

    /**
     * Excutes the default action, currently redirecting to index.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
        //return $this->Order();
	}

    /**
     * Displays an XML file with the requested orders
     *
     * @access public
     * @return string
     */
    function EcommerceXML()
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		/*
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');

        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if(!empty($get['id'])) {
			$gid = (int)$get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$galleryParent = $model->GetOrder($gid);
			
			if (!$galleryParent) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No images were found: $gid");
				}
				$output_xml .= "<gallery>\n";
				$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Ecommerce/images/gallery_no_images.jpg\" textLabel=\"No images have been added or activated for this gallery.\">No images have been added or activated for this gallery.</image>\n";
				$output_xml .= "</gallery>\n";
			} else {
				$image_found = false;
				$output_xml .= "<gallery>\n";
				$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Ecommerce/images/gallery_no_images.jpg\" textLabel=\"No images have been added or activated for this gallery.\">No images have been added or activated for this gallery.</image>\n";
				$output_xml .= "</gallery>\n";
			} 
			
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No images were found: $gid");
			}
			$output_xml .= "<gallery>\n";
			$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Ecommerce/images/gallery_error.jpg\">Error</image>\n";
			$output_xml .= "</gallery>\n";			
		}
		*/
		return $output_xml;
	}
			
	/**
     * Handles Authorize.NET Responses
     *
     * @access public
     * @return string
     */
    function AuthorizeNetResponse()
    {
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$merchant_signature = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_signature');  // Your Merchant Signature
		$shipfrom_city = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city');  // City Shipping From
		$shipfrom_state = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');  // State Shipping From
		$shipfrom_zip = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip');  // Zip Shipping From
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');  // Use Carrier Calculated Shipping
		$checkout_terms = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/checkout_terms');  // Checkout Terms
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		if (empty($site_name)) {
			$site_name = $GLOBALS['app']->GetSiteURL();
		}
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$gateway_logo = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_logo');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$storeModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
		
		require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
				
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('url', 'action', 'orderno', 'error', 'api', 'count'), 'get');
		$post = $request->get(array('confirm'), 'post');
		
		$error = '';
		$OwnerID = null;
		$orderno = '';
		$Active = 'TEMP';
		$confirm = 'Y';
		$count = 0;
		if (isset($get['url']) && !empty($get['url'])) {
			$url = $get['url'];
		} else {	
			return Jaws_HTTPError::Get(404);
		}
		if (isset($get['orderno']) && !empty($get['orderno'])) {
			$orderno = $get['orderno'];
		}
		if (isset($get['error']) && !empty($get['error'])) {
			$error = $get['error'];
		}
		if (isset($get['count']) && !empty($get['count'])) {
			$count = (int)$get['count'];
		}
		if (isset($post['confirm']) && !empty($post['confirm']) && $post['confirm'] == 'Y') {
			$confirm = 'Y';
		}
		$fetch = array(
			'x_response_code', 'x_avs_code', 'x_invoice_num', 'x_description',
			'x_amount', 'x_method', 'x_type', 'x_cust_id', 'x_first_name', 'x_last_name', 
			'x_response_subcode', 'x_response_reason_code', 'x_response_reason_text', 
			'x_auth_code', 'x_trans_id', 'x_company', 'x_address', 'x_city', 'x_state', 
			'x_zip', 'x_country', 'x_phone', 'x_fax', 'x_email', 'x_ship_to_first_name', 
			'x_ship_to_last_name', 'x_ship_to_company', 'x_ship_to_address', 'x_ship_to_city', 
			'x_ship_to_state', 'x_ship_to_zip', 'x_ship_to_country', 'x_tax', 'x_duty', 'x_freight', 
			'x_tax_exempt', 'x_po_num', 'x_MD5_Hash', 'x_cvv2_resp_code', 'x_cavv_response', 
			'x_test_request', 'x_total_weight', 'x_freight_type', 'x_gadget_id', 
			'x_gadget_table', 'x_owner_id'
		);
		$post = $request->get($fetch, 'post');
		$post['x_line_item'] = $request->getRaw('x_line_item', 'post');
		$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		if ($GLOBALS['app']->Session->Logged()) {
			$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
			if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
				$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
				Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
				exit;
			}
		} else if ($count < 2) {
			/*
			$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
			exit;
			*/
			$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
			$output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">";
			$output .= "<head>";
			$output .= "<title>Redirecting...</title>";
			$output .= "</head>";
			$output .= "<body>";
			$output .= "<form action=\"https://".$site_ssl_url . "/index.php?gadget=Ecommerce&action=AuthorizeNetResponse&url=".$get['url']."&orderno=".$get['orderno']."&error=".$get['error']."&api=".$get['api']."&count=".($count+1)."\" method=\"post\" name=\"frm\">";
			foreach ($post as $a => $b) {
				if (strtolower($a) != 'redirect_to') {
					$output .= "<input type='hidden' name='".$a."' value='".$b."'>";
				}
			}
			$output .= "<noscript>";
			$output .= "Redirecting...<br /><input type='submit' value='Click Here to Continue'>";
			$output .= "</noscript>"; 
			$output .= "</form>";
			$output .= "<script type='text/javascript' language='JavaScript'>";
			$output .= "document.frm.submit();";
			$output .= "</script>"; 
			$output .= "</body>"; 
			$output .= "</html>"; 
			echo $output;
			exit;
		} else {
			$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
			exit;
		}
		
		
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		
		$this->AjaxMe('client_script.js');
        
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/black_hud.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Ecommerce/resources/style.css', 'stylesheet', 'text/css');
		
		/*
		Should be receiving the following items:
		array (
		  'x_response_code' => '1',
		  'x_response_subcode' => '1',
		  'x_response_reason_code' => '1',
		  'x_response_reason_text' => '(TESTMODE) This transaction has been approved.',
		  'x_auth_code' => '000000',
		  'x_avs_code' => 'P',
		  'x_trans_id' => '0',
		  'x_invoice_num' => '1',
		  'x_description' => 'Purchase from syntacts.com',
		  'x_method' => 'CC',
		  'x_type' => 'auth_capture',
		  'x_cust_id' => '',
		  'x_amount' => '86.40',
		  'x_first_name' => 'Alan',
		  'x_last_name' => 'Valkoun',
		  'x_company' => 'Company',
		  'x_address' => '123 Street Rd.',
		  'x_city' => 'City',
		  'x_state' => 'NY',
		  'x_zip' => '11530',
		  'x_country' => 'United States',
		  'x_phone' => '',
		  'x_fax' => '',
		  'x_email' => 'info@example.org',
		  'x_ship_to_first_name' => 'Alan',
		  'x_ship_to_last_name' => 'Valkoun',
		  'x_ship_to_company' => 'Company',
		  'x_ship_to_address' => '123 Street Rd.',
		  'x_ship_to_city' => 'City',
		  'x_ship_to_state' => 'NY',
		  'x_ship_to_zip' => '11530',
		  'x_ship_to_country' => 'United States',
		  'x_tax' => '0.0000',
		  'x_duty' => '0.0000',
		  'x_freight' => '0.0000',
		  'x_tax_exempt' => 'FALSE',
		  'x_po_num' => '',
		  'x_MD5_Hash' => '40AD83835BDA89DEDA5910C5B2B1EA03',
		  'x_cvv2_resp_code' => '',
		  'x_cavv_response' => '',
		  'x_test_request' => 'true',
		  'x_line_item' => 
		  array (
			0 => '5<|>Doubt Thermal Sillv<|>/ Each, Size: Small, 100% Cotton Contrast Panel Thermal, Black Embroidery, Silver Lava Wash. This is an Authentic Affliction Thermal., Retail: $65.00, Product Code: AFFLICTION-A1899-301, Brand: Affliction, ID: 5, <|>2<|>43.2<|>Y',
		  ),
		)
		*/
						
		// TODO: Add customer to users table?? For now, just use "order" table to get customer data  
		//if ((isset($post['x_avs_code']) && ($post['x_avs_code'] == 'P' || $post['x_avs_code'] == 'S' || $post['x_avs_code'] == 'X' || $post['x_avs_code'] == 'Y')) && isset($post['x_response_code']) && !empty($post['x_response_code']) && isset($post['x_invoice_num']) && !empty($post['x_invoice_num']) && (!isset($post['x_test_request']) || (isset($post['x_test_request']) && $post['x_test_request'] != 'true'))) {
		if ((isset($post['x_avs_code']) && ($post['x_avs_code'] == 'P' || $post['x_avs_code'] == 'S' || $post['x_avs_code'] == 'X' || $post['x_avs_code'] == 'Y')) && isset($post['x_response_code']) && !empty($post['x_response_code']) && isset($post['x_invoice_num']) && !empty($post['x_invoice_num'])) {
			// Approved
			if ($post['x_response_code'] == '1') {
				// TODO: Insert each line item into order table
				$customer_name = $post['x_first_name'].' '.$post['x_last_name'];
				$OwnerID = $post['x_owner_id'];
				$Active = 'NEW';
				//$orderno = substr($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], 0, strpos($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], '-'));
				if (empty($orderno)) {
					$orderno = $post['x_invoice_num'];
				}
										
				$orders = $model->GetAllItemsOfOrderNo($orderno);
				if (!Jaws_Error::IsError($orders)) {
					foreach ($orders as $pageInfo) {
						if ($pageInfo['ownerid'] == $OwnerID && $pageInfo['customer_id'] == $uid) {
							if (isset($pageInfo['customer_cc_cvv']) && !empty($pageInfo['customer_cc_cvv'])) {
								$pageInfo['customer_cc_cvv'] = $JCrypt->rsa->decrypt($pageInfo['customer_cc_cvv'], $JCrypt->pvt_key);
							}
							if (isset($pageInfo['customer_cc_number']) && !empty($pageInfo['customer_cc_number'])) {
								$pageInfo['customer_cc_number'] = $JCrypt->rsa->decrypt($pageInfo['customer_cc_number'], $JCrypt->pvt_key);
							}
							if (isset($pageInfo['customer_cc_type']) && !empty($pageInfo['customer_cc_type'])) {
								$pageInfo['customer_cc_type'] = $JCrypt->rsa->decrypt($pageInfo['customer_cc_type'], $JCrypt->pvt_key);
							}

							$res = $adminModel->UpdateOrder($pageInfo['id'], $pageInfo['orderno'], $pageInfo['prod_id'], 
								(isset($post['x_amount']) && !empty($post['x_amount']) ? $post['x_amount'] : $pageInfo['price']), 
								$pageInfo['qty'], $pageInfo['unit'], $pageInfo['weight'], $pageInfo['attribute'], 
								$pageInfo['backorder'], $pageInfo['description'], $pageInfo['recurring'], $pageInfo['gadget_table'], 
								$pageInfo['gadget_id'], 'NEW', $pageInfo['customer_email'], $pageInfo['customer_name'], 
								(isset($post['x_company']) && !empty($post['x_company']) && empty($pageInfo['customer_company']) ? $post['x_company'] : $pageInfo['customer_company']), 
								(isset($post['x_address']) && !empty($post['x_address']) && empty($pageInfo['customer_address']) ? $post['x_address'] : $pageInfo['customer_address']), 
								$pageInfo['customer_address2'], 
								(isset($post['x_city']) && !empty($post['x_city']) && empty($pageInfo['customer_city']) ? $post['x_city'] : $pageInfo['customer_city']), 
								(isset($post['x_state']) && !empty($post['x_state']) && empty($pageInfo['customer_region']) ? $post['x_state'] : $pageInfo['customer_region']), 
								(isset($post['x_zip']) && !empty($post['x_zip']) && empty($pageInfo['customer_postal']) ? $post['x_zip'] : $pageInfo['customer_postal']), 
								$pageInfo['customer_country'], 
								(isset($post['x_phone']) && !empty($post['x_phone']) && empty($pageInfo['customer_phone']) ? $post['x_phone'] : $pageInfo['customer_phone']), 
								(isset($post['x_fax']) && !empty($post['x_fax']) && empty($pageInfo['customer_fax']) ? $post['x_fax'] : $pageInfo['customer_fax']), 
								(isset($post['x_ship_to_first_name']) && !empty($post['x_ship_to_first_name']) && empty($pageInfo['customer_shipname']) ? $post['x_ship_to_first_name']." ".$post['x_ship_to_last_name'] : $pageInfo['customer_shipname']), 
								(isset($post['x_ship_to_address']) && !empty($post['x_ship_to_address']) && empty($pageInfo['customer_shipaddress']) ? $post['x_ship_to_address'] : $pageInfo['customer_shipaddress']), 
								$pageInfo['customer_shipaddress2'], 
								(isset($post['x_ship_to_city']) && !empty($post['x_ship_to_city']) && empty($pageInfo['customer_shipcity']) ? $post['x_ship_to_city'] : $pageInfo['customer_shipcity']), 
								(isset($post['x_ship_to_state']) && !empty($post['x_ship_to_state']) && empty($pageInfo['customer_shipregion']) ? $post['x_ship_to_state'] : $pageInfo['customer_shipregion']), 
								(isset($post['x_ship_to_zip']) && !empty($post['x_ship_to_zip']) && empty($pageInfo['customer_shippostal']) ? $post['x_ship_to_zip'] : $pageInfo['customer_shippostal']), 
								$pageInfo['customer_shipcountry'], '', $pageInfo['shiptype'], $pageInfo['sales_id'], 
								$pageInfo['customer_cc_type'], $pageInfo['customer_cc_number'], $pageInfo['customer_cc_exp_month'], 
								$pageInfo['customer_cc_exp_year'], $pageInfo['customer_cc_cvv']
							);
						}
					}
				} else {
					$error .= '<br />'.$orders->GetMessage();
				}
			
			// Declined
			} else if ($post['x_response_code'] == '2') {
				$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'Your order failed to process.');
			// Held for Review
			} else if ($post['x_response_code'] == '4') {
				$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'Your order is under review.');
			} else {
				$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'An unspecified error occurred.');
			}
		} else if (isset($post['x_avs_code']) && ($post['x_avs_code'] != 'P' && $post['x_avs_code'] != 'S' && $post['x_avs_code'] != 'X' && $post['x_avs_code'] != 'Y')) {
			$error = 'The Address Verification Service (AVS) could not verify the information you have submitted.';
		} else {
			$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'An unspecified error occurred.');
		}
		
		$orders = $model->GetAllItemsOfOrderNo($orderno);
		if (!Jaws_Error::IsError($orders) && isset($orders[0]['id']) && !empty($orders[0]['id']) && $uid == $orders[0]['customer_id']) {
			
			$order_status = $orders[0]['active'];
			$orderno = $orders[0]['orderno'];
			$OwnerID = $orders[0]['ownerid'];
			$CustID = $orders[0]['customer_id'];
			
			$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
	        $tpl->Load('ViewOrder.html');
			$tpl->SetBlock('view_order');
			
			foreach ($orders as $pageInfo) {
				if (strpos($pageInfo['description'], 's:12:"Handling fee"') === false) {
					$customer_cc_type = '';
					$customer_cc_number = '';
					$customer_cc_cvv = '';
					if (isset($pageInfo['customer_cc_type']) && !empty($pageInfo['customer_cc_type'])) {
						$customer_cc_type = $pageInfo['customer_cc_type'];
						$customer_cc_type = $JCrypt->rsa->decrypt($customer_cc_type, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_type)) {
							$customer_cc_type = '';
						}
					}
					if (isset($pageInfo['customer_cc_number']) && !empty($pageInfo['customer_cc_number'])) {
						$customer_cc_number = $pageInfo['customer_cc_number'];
						$customer_cc_number = $JCrypt->rsa->decrypt($customer_cc_number, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_number)) {
							$customer_cc_number = '';
						}
					}
					if (isset($pageInfo['customer_cc_cvv']) && !empty($pageInfo['customer_cc_cvv'])) {
						$customer_cc_cvv = $pageInfo['customer_cc_cvv'];
						$customer_cc_cvv = $JCrypt->rsa->decrypt($customer_cc_cvv, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_cvv)) {
							$customer_cc_cvv = '';
						}
					}
					
					if ($confirm == 'Y') {
						$res = $adminModel->UpdateOrderStatus(
							$pageInfo['id'], 'NEW'
						);
						// TODO: rollback to previous status on error?
						if (Jaws_Error::IsError($res)) {
							return $res;
						}
					}
					
					if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || $CustID == $userInfo['id']) {
						$tpl->SetBlock('view_order/order');
						$tpl->SetVariable('OwnerID', $pageInfo['ownerid']);
						$tpl->SetVariable('order_status', ($order_status == 'TEMP' ? 'NEW' : $order_status));
						$tpl->SetVariable('orderno', $pageInfo['orderno'].'-'.($pageInfo['ownerid'] > 0 ? $pageInfo['ownerid'] : '0').'-'.($CustID > 0 ? $CustID : '0'));
						$tpl->SetVariable('order_total', (!empty($pageInfo['total']) && $pageInfo['total'] > 0 ? $pageInfo['total'] : $pageInfo['price']));
						if ((int)$pageInfo['ownerid'] > 0) {
							$ownerInfo = $jUser->GetUserInfoById((int)$pageInfo['ownerid'], true, true, true, true);
							if (!Jaws_Error::IsError($ownerInfo) && isset($ownerInfo['id'])) {
								$tpl->SetBlock('view_order/order/merchant');
								$tpl->SetVariable('merchant_name', $xss->filter(ereg_replace("[^A-Za-z0-9\:\ \,]", '', (!empty($ownerInfo['company']) ? $ownerInfo['company'] : (!empty($ownerInfo['nickname']) ? $ownerInfo['nickname'] : 'ID: '.$pageInfo['ownerid'])))));
								if (
									(($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) || ($uid == $ownerInfo['id'])) && 
									isset($ownerInfo['merchant_id']) && !empty($ownerInfo['merchant_id'])
								) {
									$tpl->SetVariable('merchant_id', $ownerInfo['merchant_id']);
								} else {
									$tpl->SetVariable('merchant_id', $ownerInfo['id']);
								}
								$tpl->ParseBlock('view_order/order/merchant');
							}
						}
						if (!empty($pageInfo['description'])) {
							$pInfo = unserialize($pageInfo['description']);
							$pDesc = $pInfo['description'];
							$tpl->SetVariable('order_description', $xss->filter($pDesc));
							if (isset($pInfo['items']) && is_array($pInfo['items']) && !count($pInfo['items']) <= 0) {
								foreach ($pInfo['items'] as $item_owner => $items) {
									// Update product quantities
									if (isset($items['number']) && !empty($items['number']) && isset($items['qty']) && !empty($items['qty'])) {
										$prod_id = (int)$items['number'];
										$ord_qty = (int)$items['qty'];
										$updateStock = $model->UpdateProductStock($prod_id, $ord_qty);
									}
									//foreach ($items as $item) {
										$tpl->SetBlock('view_order/order/order_item');
										$tpl->SetVariable('item_link', $items['itemurl']);
										$tpl->SetVariable('item_title', $xss->filter(ereg_replace("[^A-Za-z0-9\:\ \,]", '', $items['name'])));
										$tpl->SetVariable('item_price', $items['amt']);
										$tpl->SetVariable('item_qty', $items['qty']);
										$tpl->SetVariable('item_details', $xss->filter($items['desc']));
										$tpl->ParseBlock('view_order/order/order_item');
									//}
								}
							}
							if (isset($pInfo['customcheckoutfields']) && is_array($pInfo['customcheckoutfields']) && !count($pInfo['customcheckoutfields']) <= 0) {
								foreach ($pInfo['customcheckoutfields'] as $ck => $cv) {
									$tpl->SetBlock('view_order/order/customfield');
									$tpl->SetVariable('custom_name', $ck);
									$tpl->SetVariable('custom_value', $xss->filter($JCrypt->rsa->decrypt($cv, $JCrypt->pvt_key)));
									$tpl->ParseBlock('view_order/order/customfield');
								}
							}
						}
						$tpl->ParseBlock('view_order/order');
						
					}
				}
			}
						
						
			if (!empty($gateway_logo) && file_exists(JAWS_DATA . 'files'.$xss->filter($gateway_logo))) {
				$tpl->SetBlock('view_order/gateway_logo');
				$tpl->SetVariable('gateway_logo', $GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($gateway_logo));
				$tpl->ParseBlock('view_order/gateway_logo');
			}
			if ($order_status == 'TEMP' && $confirm != 'Y') {
				$title = "Confirm Your Order";
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_confirm.png';
				$tpl->SetBlock('view_order/footer_form_start');
				$tpl->SetVariable('form_action', $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Ecommerce&action=ManualResponse&orderno='.$orderno);
				$tpl->ParseBlock('view_order/footer_form_start');
				if (!empty($checkout_terms)) {
					$tpl->SetBlock('view_order/accept');
					$tpl->ParseBlock('view_order/accept');
					$tpl->SetBlock('view_order/footer_form_terms');
					$tpl->SetVariable('checkout_terms', $checkout_terms);
					$tpl->ParseBlock('view_order/footer_form_terms');
				} else {
					$tpl->SetBlock('view_order/footer_form_hidden');
					$tpl->ParseBlock('view_order/footer_form_hidden');
				}
				if (empty($error)) {
					$checkout_button = '<input type="submit" value="Checkout" />&nbsp;&nbsp;&nbsp;or&nbsp;';
				}
				$tpl->SetBlock('view_order/footer_form_end');
				$tpl->ParseBlock('view_order/footer_form_end');
			} else {
				$title = "Thanks".(isset($userInfo['fname']) && !empty($userInfo['fname']) ? '&nbsp;'.$userInfo['fname'] : (isset($userInfo['nickname']) && !empty($userInfo['nickname']) ? '&nbsp;'.$userInfo['nickname'] : '')).", you're done!";
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_success.png';
				$tpl->SetBlock('view_order/ack');
				$tpl->SetVariable('site_name', $site_name);
				$tpl->SetBlock('view_order/ack/print');
				$tpl->SetVariable('print_link', $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Ecommerce&action=AuthorizeNetResponse&orderno='.$orderno.'&standalone=1');
				$tpl->ParseBlock('view_order/ack/print');
				$tpl->ParseBlock('view_order/ack');
			}
												
			if (!empty($error)) {
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
				$tpl->SetBlock('view_order/error');
				$tpl->SetVariable('error', $xss->filter($error));
				$tpl->ParseBlock('view_order/error');
			} else {
				$tpl->SetBlock('view_order/billing');
				$tpl->SetVariable('OwnerID', $CustID);
				if (!empty($customer_cc_number) && !empty($customer_cc_type)) {
					$tpl->SetBlock('view_order/billing/customer_cc_type');
					$tpl->SetVariable('customer_cc_type', $customer_cc_type);
					$tpl->ParseBlock('view_order/billing/customer_cc_type');
					$tpl->SetBlock('view_order/billing/customer_cc_number');
					$tpl->SetVariable('customer_cc_number', '************'.substr($customer_cc_number, -4));
					$tpl->ParseBlock('view_order/billing/customer_cc_number');
					/*
					$tpl->SetBlock('view_order/billing/customer_cc_exp');
					$tpl->SetVariable('customer_cc_exp_month', $pageInfo['customer_cc_exp_month']);
					$tpl->SetVariable('customer_cc_exp_year', $pageInfo['customer_cc_exp_year']);
					$tpl->ParseBlock('view_order/billing/customer_cc_exp');
					$tpl->SetBlock('view_order/billing/customer_cc_cvv');
					$tpl->SetVariable('customer_cc_cvv', $customer_cc_cvv);
					$tpl->ParseBlock('view_order/billing/customer_cc_cvv');
					*/
				}
				$tpl->SetBlock('view_order/billing/customer_name');
				$tpl->SetVariable('customer_name', $pageInfo['customer_name']);
				$tpl->ParseBlock('view_order/billing/customer_name');
				$tpl->SetBlock('view_order/billing/customer_email');
				$tpl->SetVariable('customer_email', $pageInfo['customer_email']);
				$tpl->ParseBlock('view_order/billing/customer_email');
				$tpl->SetBlock('view_order/billing/customer_phone');
				$tpl->SetVariable('customer_phone', $pageInfo['customer_phone']);
				$tpl->ParseBlock('view_order/billing/customer_phone');
				$tpl->SetBlock('view_order/billing/customer_fax');
				$tpl->SetVariable('customer_fax', $pageInfo['customer_fax']);
				$tpl->ParseBlock('view_order/billing/customer_fax');
				$tpl->SetBlock('view_order/billing/customer_company');
				$tpl->SetVariable('customer_company', $pageInfo['customer_company']);
				$tpl->ParseBlock('view_order/billing/customer_company');
				$tpl->SetBlock('view_order/billing/customer_address');
				$tpl->SetVariable('customer_address', $pageInfo['customer_address']);
				$tpl->ParseBlock('view_order/billing/customer_address');
				$tpl->SetBlock('view_order/billing/customer_address2');
				$tpl->SetVariable('customer_address2', $pageInfo['customer_address2']);
				$tpl->ParseBlock('view_order/billing/customer_address2');
				$tpl->SetBlock('view_order/billing/customer_city');
				$tpl->SetVariable('customer_city', $pageInfo['customer_city']);
				$tpl->ParseBlock('view_order/billing/customer_city');
				$tpl->SetBlock('view_order/billing/customer_region');
				$tpl->SetVariable('customer_region', $pageInfo['customer_region']);
				$tpl->ParseBlock('view_order/billing/customer_region');
				$tpl->SetBlock('view_order/billing/customer_postal');
				$tpl->SetVariable('customer_postal', $pageInfo['customer_postal']);
				$tpl->ParseBlock('view_order/billing/customer_postal');
				$tpl->SetBlock('view_order/billing/customer_country');
				$tpl->SetVariable('customer_country', $pageInfo['customer_country']);
				$tpl->ParseBlock('view_order/billing/customer_country');
				$tpl->ParseBlock('view_order/billing');
				
				$tpl->SetBlock('view_order/shipping');
				$tpl->SetVariable('OwnerID', $CustID);
				$tpl->SetBlock('view_order/shipping/shiptype');
				$tpl->SetVariable('shiptype', $pageInfo['shiptype']);
				$tpl->ParseBlock('view_order/shipping/shiptype');
				$tpl->SetBlock('view_order/shipping/customer_shipname');
				$tpl->SetVariable('customer_shipname', $pageInfo['customer_shipname']);
				$tpl->ParseBlock('view_order/shipping/customer_shipname');
				$tpl->SetBlock('view_order/shipping/customer_shipaddress');
				$tpl->SetVariable('customer_shipaddress', $pageInfo['customer_shipaddress']);
				$tpl->ParseBlock('view_order/shipping/customer_shipaddress');
				$tpl->SetBlock('view_order/shipping/customer_shipaddress2');
				$tpl->SetVariable('customer_shipaddress2', $pageInfo['customer_shipaddress2']);
				$tpl->ParseBlock('view_order/shipping/customer_shipaddress2');
				$tpl->SetBlock('view_order/shipping/customer_shipcity');
				$tpl->SetVariable('customer_shipcity', $pageInfo['customer_shipcity']);
				$tpl->ParseBlock('view_order/shipping/customer_shipcity');
				$tpl->SetBlock('view_order/shipping/customer_shipregion');
				$tpl->SetVariable('customer_shipregion', $pageInfo['customer_shipregion']);
				$tpl->ParseBlock('view_order/shipping/customer_shipregion');
				$tpl->SetBlock('view_order/shipping/customer_shippostal');
				$tpl->SetVariable('customer_shippostal', $pageInfo['customer_shippostal']);
				$tpl->ParseBlock('view_order/shipping/customer_shippostal');
				$tpl->SetBlock('view_order/shipping/customer_shipcountry');
				$tpl->SetVariable('customer_shipcountry', $pageInfo['customer_shipcountry']);
				$tpl->ParseBlock('view_order/shipping/customer_shipcountry');
				$tpl->ParseBlock('view_order/shipping');
			}
			$tpl->SetBlock('view_order/header');
	        $tpl->SetVariable('title', $title);
	        $tpl->SetVariable('icon', $icon);
	        $tpl->ParseBlock('view_order/header');
			
			$tpl->SetBlock('view_order/footer');
			$tpl->SetVariable('checkout_button', $checkout_button);
			$tpl->SetBlock('view_order/footer/shopping_link');
			$tpl->SetVariable('shopping_link', $GLOBALS['app']->GetSiteURL() .'/index.php?products/all.html');
			$tpl->SetVariable('site_name', $site_name);
			$tpl->ParseBlock('view_order/footer/shopping_link');
			$tpl->ParseBlock('view_order/footer');
						
	        $tpl->ParseBlock('view_order');
			return $tpl->Get();			
		} else {
			return Jaws_HTTPError::Get(404);
		}
	}
	
	/**
     * Handles PayPal Responses
     *
     * @access public
     * @return string
     */
    function PayPalResponse()
    {		
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$merchant_signature = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_signature');  // Your Merchant Signature
		$shipfrom_city = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city');  // City Shipping From
		$shipfrom_state = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');  // State Shipping From
		$shipfrom_zip = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip');  // Zip Shipping From
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');  // Use Carrier Calculated Shipping
		$checkout_terms = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/checkout_terms');  // Checkout Terms
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		if (empty($site_name)) {
			$site_name = $GLOBALS['app']->GetSiteURL();
		}
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$gateway_logo = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_logo');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$storeModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
		
		require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
				
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('token', 'PayerID', 'action', 'orderno', 'error', 'api'), 'get');
		$post = $request->get(array('confirm'), 'post');
		
		$guestorder = false;
		if ($cart_data = $GLOBALS['app']->Session->PopSimpleResponse('Ecommerce.Cart.Data')) {
			if (
				isset($cart_data['session_id']) && !empty($cart_data['session_id']) && $cart_data['session_id'] == $GLOBALS['app']->Session->_SessionID 
			) {
				$GLOBALS['app']->Session->PushSimpleResponse($cart_data, 'Ecommerce.Cart.Data');	
				$guestorder = true;
			}
		}
		
		$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		if ($guestorder === false) {
			if ($GLOBALS['app']->Session->Logged()) {
				$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
				if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
					$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
					Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
					exit;
				}
			} else {
				$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
				Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
				exit;
			}
		}
		
		$error = '';
		$OwnerID = null;
		$orderno = '';
		$Active = 'TEMP';
		$confirm = 'Y';
		if (isset($get['token']) && !empty($get['token'])) {
			$token = $get['token'];
		}
		if (isset($get['api']) && !empty($get['api'])) {
			$api = $get['api'];
		}
		if (isset($get['PayerID']) && !empty($get['PayerID'])) {
			$payerID = $get['PayerID'];
		}
		if (isset($get['orderno']) && !empty($get['orderno'])) {
			$orderno = $get['orderno'];
		}
		if (isset($get['error']) && !empty($get['error'])) {
			$error = $get['error'];
		}
		if (isset($post['confirm']) && !empty($post['confirm']) && $post['confirm'] == 'Y') {
			$confirm = 'Y';
		}
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		
		$this->AjaxMe('client_script.js');
        
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/black_hud.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Ecommerce/resources/style.css', 'stylesheet', 'text/css');
				
		// Included required files.
		require_once JAWS_PATH . 'libraries/PayPal/paypal.nvp.class.php';
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Mail.php';
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		
		// PayPal config
		//date_default_timezone_set('America/Chicago');	// Update to your own timezone.
		$sandbox = false; // TRUE/FALSE for test mode or not.
		if($sandbox) {error_reporting(E_ALL); ini_set('display_errors', '1');}
		/*
		$domain = $GLOBALS['app']->GetSiteURL().'/';
		$api_version = '85.0';
		// Only required for Adaptive Payments.  You get your Live ID when your application is approved by PayPal.
		$application_id = $sandbox ? '' : '';	
		// This is the email you use to sign in to http://developer.paypal.com.  Only required for Adaptive Payments.
		$developer_account_email = 'info@example.org';			
		*/
		// PayPal API Credentials
		$api_username = ($sandbox ? '' : $merchant_id);
		$api_password = ($sandbox ? '' : $merchant_key);
		$api_signature = ($sandbox ? '' : $merchant_signature);
		/*
		// If making calls on behalf a third party, their PayPal email address or account ID goes here.
		$api_subject = '';	
		$device_id = '';
		$device_ip_address = $_SERVER['REMOTE_ADDR'];
		*/
		
		// Setup PayPal object
		$PayPalConfig = array('Sandbox' => $sandbox, 'APIUsername' => $api_username, 'APIPassword' => $api_password, 'APISignature' => $api_signature);
		$PayPal = new PayPal($PayPalConfig);
		
		// Pass the master array into the PayPal class function
		$PayPalDetails = $PayPal->GetExpressCheckoutDetails($token);
		/*
		echo '<pre>GetExpressCheckoutDetails: '."\n";	
		var_dump($PayPalDetails);
		echo '</pre>';
		*/
		
		if (isset($PayPalDetails['TOKEN']) && $PayPalDetails['TOKEN'] = $token && (isset($PayPalDetails['ACK']) && (substr(strtolower($PayPalDetails['ACK']), 0, 7) == 'success' || strtolower($PayPalDetails['ACK']) == 'partialsuccess') && (isset($PayPalDetails['PAYERID']) && !empty($PayPalDetails['PAYERID'])))) {
			$DECPFields = array(
				'token' => $token, 								// Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
				'payerid' => $PayPalDetails['PAYERID'], 							// Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
				'returnfmfdetails' => '0', 					// Flag to indiciate whether you want the results returned by Fraud Management Filters or not.  1 or 0.
				'giftmessage' => '', 						// The gift message entered by the buyer on the PayPal Review page.  150 char max.
				'giftreceiptenable' => '', 					// Pass true if a gift receipt was selected by the buyer on the PayPal Review page. Otherwise pass false.
				'giftwrapname' => '', 						// The gift wrap name only if the gift option on the PayPal Review page was selected by the buyer.
				'giftwrapamount' => '', 					// The amount only if the gift option on the PayPal Review page was selected by the buyer.
				'buyermarketingemail' => '', 				// The buyer email address opted in by the buyer on the PayPal Review page.
				'surveyquestion' => '', 					// The survey question on the PayPal Review page.  50 char max.
				'surveychoiceselected' => '',  				// The survey response selected by the buyer on the PayPal Review page.  15 char max.
				'allowedpaymentmethod' => '', 				// The payment method type. Specify the value InstantPaymentOnly.
				'buttonsource' => 'Syntacts_PHP_Class_DECP' 						// ID code for use by third-party apps to identify transactions in PayPal. 
			);
			$countPayments = count($PayPalDetails['PAYMENTS']);
			reset($PayPalDetails['PAYMENTS']);
			$Payments = array();
			$p = 0;
			foreach ($PayPalDetails['PAYMENTS'] as $opayment) {	
				$customer_name = (isset($PayPalDetails['SHIPTONAME']) ? $PayPalDetails['SHIPTONAME'] : '');
				$Payment = array(
					'amt' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_AMT']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_AMT'] : ''), 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
					'currencycode' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_CURRENCYCODE']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_CURRENCYCODE'] : ''), 					// A three-character currency code.  Default is USD.
					'itemamt' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_ITEMAMT']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_ITEMAMT'] : ''), 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
					'shippingamt' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_SHIPPINGAMT']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_SHIPPINGAMT'] : ''), 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
					'insuranceoptionoffered' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_INSURANCEOPTIONOFFERED']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_INSURANCEOPTIONOFFERED'] : ''), 		// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
					'handlingamt' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_HANDLINGAMT']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_HANDLINGAMT'] : ''), 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
					'taxamt' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_TAXAMT']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_TAXAMT'] : ''), 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
					'desc' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_DESC']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_DESC'] : ''), 							// Description of items on the order.  127 char max.
					'invnum' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_INVNUM']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_INVNUM'] : ''), 						// Your own invoice or tracking number.  127 char max.
					'shiptoname' => (isset($PayPalDetails['SHIPTONAME']) ? $PayPalDetails['SHIPTONAME'] : ''), 							// Required if shipping is included.  Person's name associated with this address.  32 char max.
					'shiptostreet' => (isset($PayPalDetails['SHIPTOSTREET']) ? $PayPalDetails['SHIPTOSTREET'] : ''), 					// Required if shipping is included.  First street address.  100 char max.
					'shiptostreet2' => (isset($PayPalDetails['SHIPTOSTREET2']) ? $PayPalDetails['SHIPTOSTREET2'] : ''), 				// Second street address.  100 char max.
					'shiptocity' => (isset($PayPalDetails['SHIPTOCITY']) ? $PayPalDetails['SHIPTOCITY'] : ''), 							// Required if shipping is included.  Name of city.  40 char max.
					'shiptostate' => (isset($PayPalDetails['SHIPTOSTATE']) ? $PayPalDetails['SHIPTOSTATE'] : ''), 						// Required if shipping is included.  Name of state or province.  40 char max.
					'shiptozip' => (isset($PayPalDetails['SHIPTOZIP']) ? $PayPalDetails['SHIPTOZIP'] : ''), 							// Required if shipping is included.  Postal code of shipping address.  20 char max.
					'shiptocountrycode' => (isset($PayPalDetails['SHIPTOCOUNTRYCODE']) ? $PayPalDetails['SHIPTOCOUNTRYCODE'] : ''), 	// Required if shipping is included.  Country code of shipping address.  2 char max.
					'shiptocountryname' => (isset($PayPalDetails['SHIPTOCOUNTRYNAME']) ? $PayPalDetails['SHIPTOCOUNTRYNAME'] : ''), 	// Required if shipping is included.  Country code of shipping address.  2 char max.
					'shiptophonenum' => (isset($PayPalDetails['SHIPTOPHONENUM']) ? $PayPalDetails['SHIPTOPHONENUM'] : ''),  			// Phone number for shipping address.  20 char max.
					/*
					'notifyurl' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_CUSTOM']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_CUSTOM'] : ''), 						// URL for receiving Instant Payment Notifications
					'custom' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_CUSTOM']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_CUSTOM'] : ''), 						// Free-form field for your own use.  256 char max.
					'notetext' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_CUSTOM']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_CUSTOM'] : ''), 						// Note to the merchant.  255 char max.  
					*/
					'allowedpaymentmethod' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_ALLOWEDPAYMENTMETHOD']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_ALLOWEDPAYMENTMETHOD'] : ''), 			// The payment method type.  Specify the value InstantPaymentOnly.
					'paymentaction' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_PAYMENTACTION']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_PAYMENTACTION'] : ($countPayments > 0 ? 'Order' : '')), 					// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order. 
					'paymentrequestid' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_PAYMENTREQUESTID']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_PAYMENTREQUESTID'] : ''),  				// A unique identifier of the specific payment request, which is required for parallel payments. 
					'sellerpaypalaccountid' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_SELLERPAYPALACCOUNTID']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_SELLERPAYPALACCOUNTID'] : ''), 			// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
					'softdescriptor' => (isset($PayPalDetails['PAYMENTREQUEST_'.$p.'_DESC']) ? $PayPalDetails['PAYMENTREQUEST_'.$p.'_DESC'] : '')					// A per transaction description of the payment that is passed to the buyer's credit card statement.
				);
				
				$PaymentOrderItems = array();
				$i = 0;
				foreach ($PayPalDetails['PAYMENTS'][$p]['ORDERITEMS'] as $oitem) {	
					$name = (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_NAME'.$i]) ? substr($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_NAME'.$i], 0, 40).' - ' : '');
					$name .= (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_DESC'.$i]) ? substr($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_DESC'.$i], 0, 84) : '');
					$Item = array(
						'name' => $name, 							// Item name. 127 char max.
						'desc' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_DESC'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_DESC'.$i] : ''), 							// Item description. 127 char max.
						'amt' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_AMT'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_AMT'.$i] : ''), 								// Cost of item.
						'number' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_NUMBER'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_NUMBER'.$i] : ''), 						// Item number.  127 char max.
						'qty' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_QTY'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_QTY'.$i] : ''), 								// Item qty on order.  Any positive integer.
						'taxamt' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_TAXAMT'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_TAXAMT'.$i] : ''), 						// Item sales tax
						'itemurl' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMURL'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMURL'.$i] : (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_NUMBER'.$i]) ? $GLOBALS['app']->GetSiteURL() . '/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => (int)$PayPalDetails['L_PAYMENTREQUEST_'.$p.'_NUMBER'.$i])) : '')),	// URL for the item.
						'itemweightvalue' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWEIGHTVALUE'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWEIGHTVALUE'.$i] : ''), 		// The weight value of the item.
						'itemweightunit' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWEIGHTUNIT'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWEIGHTUNIT'.$i] : ''), 		// The weight unit of the item.
						'itemheightvalue' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMHEIGHTVALUE'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMHEIGHTVALUE'.$i] : ''), 		// The height value of the item.
						'itemheightunit' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMHEIGHTUNIT'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMHEIGHTUNIT'.$i] : ''), 		// The height unit of the item.
						'itemwidthvalue' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWIDTHVALUE'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWIDTHVALUE'.$i] : ''), 		// The width value of the item.
						'itemwidthunit' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWIDTHUNIT'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMWIDTHUNIT'.$i] : ''), 			// The width unit of the item.
						'itemlengthvalue' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMLENGTHVALUE'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMLENGTHVALUE'.$i] : ''), 		// The length value of the item.
						'itemlengthunit' => (isset($PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMLENGTHUNIT'.$i]) ? $PayPalDetails['L_PAYMENTREQUEST_'.$p.'_ITEMLENGTHUNIT'.$i] : ''),  		// The length unit of the item.
						'ebayitemnumber' => '', 					// Auction item number.  
						'ebayitemauctiontxnid' => '', 				// Auction transaction ID number.  
						'ebayitemorderid' => '',  					// Auction order ID number.
						'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
					);
					array_push($PaymentOrderItems, $Item);
					$i++;
				}
				$Payment['order_items'] = $PaymentOrderItems;			
				$p++;
				$invnums = explode('-', $Payment['invnum']);
				$invnum = (isset($invnums[0]) ? $invnums[0] : substr($Payment['invnum'], 0, strpos($Payment['invnum'], '-')));
				$post[$invnum] = $Payment;
				array_push($Payments, $Payment);
			}
			
			$UserSelectedOptions = array(
				 'shippingcalculationmode' => '', 	// Describes how the options that were presented to the user were determined.  values are:  API - Callback   or   API - Flatrate.
				 'insuranceoptionselected' => '', 	// The Yes/No option that you chose for insurance.
				 'shippingoptionisdefault' => '', 	// Is true if the buyer chose the default shipping option.  
				 'shippingoptionamount' => '', 		// The shipping amount that was chosen by the buyer.
				 'shippingoptionname' => '', 		// Is true if the buyer chose the default shipping option...??  Maybe this is supposed to show the name..??
			);
		
			// Wrap all data arrays into a single, "master" array which will be passed into the class function.
			$RequestData = array(
				'DECPFields' => $DECPFields, 
				'Payments' => $Payments, 
				'UserSelectedOptions' => $UserSelectedOptions
			);
			/*
			echo '<pre>Payments: '."\n";	
			var_dump($Payments);
			echo '</pre>';
			*/
			
			// Pass the master array into the PayPal class function
			$PayPalResult = $PayPal->DoExpressCheckoutPayment($RequestData);
						
			// TODO: Add customer to users table?? For now, just use "order" table to get customer data  
			// Approved
			$error = '';
			if (isset($PayPalResult['TOKEN']) && $PayPalResult['TOKEN'] = $token && (isset($PayPalResult['ACK']) && (substr(strtolower($PayPalResult['ACK']), 0, 7) == 'success' || strtolower($PayPalResult['ACK']) == 'partialsuccess'))) {
				/*
				echo '<pre>DoExpressCheckoutPayment: '."\n";	
				var_dump($PayPalResult);
				echo '</pre>';
				*/
				
				$n = 0;
				$prod_ids = '';
				foreach ($PayPalResult['PAYMENTS'] as $cpayment) {
					if ((isset($PayPalResult['PAYMENTINFO_'.$n.'_TRANSACTIONID']) && !empty($PayPalResult['PAYMENTINFO_'.$n.'_TRANSACTIONID'])) && isset($PayPalResult['PAYMENTINFO_'.$n.'_ACK']) && (substr(strtolower($PayPalResult['PAYMENTINFO_'.$n.'_ACK']), 0, 7) == 'success' || strtolower($PayPalResult['PAYMENTINFO_'.$n.'_ACK']) == 'partialsuccess')) {
						$DCFields = array(
							'authorizationid' => (isset($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_TRANSACTIONID']) && !empty($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_TRANSACTIONID']) ? $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_TRANSACTIONID'] : $PayPalResult['PAYMENTINFO_'.$n.'_TRANSACTIONID']), 				// Required. The authorization identification number of the payment you want to capture. This is the transaction ID returned from DoExpressCheckoutPayment or DoDirectPayment.
							'amt' => $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_AMT'], 							// Required. Must have two decimal places.  Decimal separator must be a period (.) and optional thousands separator must be a comma (,)
							'completetype' => 'Complete', 					// Required.  The value Complete indiciates that this is the last capture you intend to make.  The value NotComplete indicates that you intend to make additional captures.
							'currencycode' => $PayPalResult['PAYMENTINFO_'.$n.'_CURRENCYCODE'], 					// Three-character currency code
							'invnum' => $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], 						// Your invoice number
							'note' => $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_DESC'], 							// Informational note about this setlement that is displayed to the buyer in an email and in his transaction history.  255 character max.
							'softdescriptor' => $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_DESC'], 				// Per transaction description of the payment that is passed to the customer's credit card statement.
							'subject' => (isset($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_SELLERPAYPALACCOUNTID']) ? $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_SELLERPAYPALACCOUNTID'] : ''), 				// Per transaction description of the payment that is passed to the customer's credit card statement.
						);
						$RequestCapture = array(
							'DCFields' => $DCFields
						);
						$Capture = $PayPal->DoCapture($RequestCapture);
						
						if (isset($Capture['ACK']) && (substr(strtolower($Capture['ACK']), 0, 7) == 'success' || strtolower($Capture['ACK']) == 'partialsuccess')) {
														
							$Active = 'NEW';
							$order_ids = explode('-', $PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM']);
							//$orderno = substr($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], 0, strpos($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], '-'));
							if (empty($orderno)) {
								$orderno = (isset($order_ids[0]) ? $order_ids[0] : substr($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], 0, strpos($PayPalResult['REQUESTDATA']['PAYMENTREQUEST_'.$n.'_INVNUM'], '-')));
							}
							$OwnerID = (isset($order_ids[1]) ? (int)$order_ids[1] : 0);
							
							$orders = $model->GetAllItemsOfOrderNo($orderno);
							if (!Jaws_Error::IsError($orders)) {
								foreach ($orders as $pageInfo) {
									if ($pageInfo['ownerid'] == $OwnerID && ($pageInfo['customer_id'] == $uid || $guestorder === true)) {
										if (isset($pageInfo['customer_cc_cvv']) && !empty($pageInfo['customer_cc_cvv'])) {
											$pageInfo['customer_cc_cvv'] = $JCrypt->rsa->decrypt($pageInfo['customer_cc_cvv'], $JCrypt->pvt_key);
										}
										if (isset($pageInfo['customer_cc_number']) && !empty($pageInfo['customer_cc_number'])) {
											$pageInfo['customer_cc_number'] = $JCrypt->rsa->decrypt($pageInfo['customer_cc_number'], $JCrypt->pvt_key);
										}
										if (isset($pageInfo['customer_cc_type']) && !empty($pageInfo['customer_cc_type'])) {
											$pageInfo['customer_cc_type'] = $JCrypt->rsa->decrypt($pageInfo['customer_cc_type'], $JCrypt->pvt_key);
										}
										
										$res = $adminModel->UpdateOrder($pageInfo['id'], $pageInfo['orderno'], $pageInfo['prod_id'], 
											(isset($post[$pageInfo['orderno']]['amt']) && !empty($post[$pageInfo['orderno']]['amt']) ? $post[$pageInfo['orderno']]['amt'] : $pageInfo['price']), 
											$pageInfo['qty'], $pageInfo['unit'], $pageInfo['weight'], $pageInfo['attribute'], 
											$pageInfo['backorder'], $pageInfo['description'], $pageInfo['recurring'], $pageInfo['gadget_table'], 
											$pageInfo['gadget_id'], 'NEW', $pageInfo['customer_email'], $pageInfo['customer_name'], 
											$pageInfo['customer_company'], 
											(isset($post[$pageInfo['orderno']]['shiptostreet']) && !empty($post[$pageInfo['orderno']]['shiptostreet']) && empty($pageInfo['customer_address']) ? $post[$pageInfo['orderno']]['shiptostreet'] : $pageInfo['customer_address']), 
											(isset($post[$pageInfo['orderno']]['shiptostreet2']) && !empty($post[$pageInfo['orderno']]['shiptostreet2']) && empty($pageInfo['customer_address2']) ? $post[$pageInfo['orderno']]['shiptostreet2'] : $pageInfo['customer_address2']), 
											(isset($post[$pageInfo['orderno']]['shiptocity']) && !empty($post[$pageInfo['orderno']]['shiptocity']) && empty($pageInfo['customer_city']) ? $post[$pageInfo['orderno']]['shiptocity'] : $pageInfo['customer_city']), 
											(isset($post[$pageInfo['orderno']]['shiptostate']) && !empty($post[$pageInfo['orderno']]['shiptostate']) && empty($pageInfo['customer_region']) ? $post[$pageInfo['orderno']]['shiptostate'] : $pageInfo['customer_region']), 
											(isset($post[$pageInfo['orderno']]['shiptozip']) && !empty($post[$pageInfo['orderno']]['shiptozip']) && empty($pageInfo['customer_postal']) ? $post[$pageInfo['orderno']]['shiptozip'] : $pageInfo['customer_postal']), 
											$pageInfo['customer_country'], 
											(isset($post[$pageInfo['orderno']]['shiptophonenum']) && !empty($post[$pageInfo['orderno']]['shiptophonenum']) && empty($pageInfo['customer_phone']) ? $post[$pageInfo['orderno']]['shiptophonenum'] : $pageInfo['customer_phone']), 
											$pageInfo['customer_fax'], 
											(isset($post[$pageInfo['orderno']]['shiptoname']) && !empty($post[$pageInfo['orderno']]['shiptoname']) && empty($pageInfo['customer_shipname']) ? $post[$pageInfo['orderno']]['shiptoname'] : $pageInfo['customer_shipname']), 
											(isset($post[$pageInfo['orderno']]['shiptostreet']) && !empty($post[$pageInfo['orderno']]['shiptostreet']) && empty($pageInfo['customer_shipaddress']) ? $post[$pageInfo['orderno']]['shiptostreet'] : $pageInfo['customer_shipaddress']), 
											(isset($post[$pageInfo['orderno']]['shiptostreet2']) && !empty($post[$pageInfo['orderno']]['shiptostreet2']) && empty($pageInfo['customer_shipaddress2']) ? $post[$pageInfo['orderno']]['shiptostreet2'] : $pageInfo['customer_shipaddress2']), 
											(isset($post[$pageInfo['orderno']]['shiptocity']) && !empty($post[$pageInfo['orderno']]['shiptocity']) && empty($pageInfo['customer_shipcity']) ? $post[$pageInfo['orderno']]['shiptocity'] : $pageInfo['customer_shipcity']), 
											(isset($post[$pageInfo['orderno']]['shiptostate']) && !empty($post[$pageInfo['orderno']]['shiptostate']) && empty($pageInfo['customer_shipregion']) ? $post[$pageInfo['orderno']]['shiptostate'] : $pageInfo['customer_shipregion']), 
											(isset($post[$pageInfo['orderno']]['shiptozip']) && !empty($post[$pageInfo['orderno']]['shiptozip']) && empty($pageInfo['customer_shippostal']) ? $post[$pageInfo['orderno']]['shiptozip'] : $pageInfo['customer_shippostal']), 
											$pageInfo['customer_shipcountry'], '', $pageInfo['shiptype'], $pageInfo['sales_id'], 
											$pageInfo['customer_cc_type'], $pageInfo['customer_cc_number'], $pageInfo['customer_cc_exp_month'], 
											$pageInfo['customer_cc_exp_year'], $pageInfo['customer_cc_cvv']
										);
									}
								}
							} else {
								$error .= '<br />'.$orders->GetMessage();
							}
							
						} else if (isset($Capture['ERRORS']) && is_array($Capture['ERRORS']) && count($Capture['ERRORS']) > 0) {
							foreach ($Capture['ERRORS'] as $errmsg) {
								$error .= '<br />'.$errmsg['L_SHORTMESSAGE'].': '.$errmsg['L_LONGMESSAGE'];
							}
						}
					}
				/*
				// Declined
				} else if ($post['x_response_code'] == '2') {
					$header = 'Sorry'.(isset($post['x_first_name']) && !empty($post['x_first_name']) ? '&nbsp;'.$post['x_first_name'] : '').', but your order was declined.';
					$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'Your order failed to process.');
				// Held for Review
				} else if ($post['x_response_code'] == '4') {
					$header = 'Thanks'.(isset($post['x_first_name']) && !empty($post['x_first_name']) ? '&nbsp;'.$post['x_first_name'] : '').', your order is being held for review.';
					$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'Your order is under review.');
				} else {
					$header = 'Sorry'.(isset($post['x_first_name']) && !empty($post['x_first_name']) ? '&nbsp;'.$post['x_first_name'] : '').', but an error occurred while processing your order.';
					$error = (isset($post['x_response_reason_text']) && !empty($post['x_response_reason_text']) ? $post['x_response_reason_text'] : 'An unspecified error occurred.');
				}
				*/
					$n++;
				}
			} else if (isset($PayPalResult['ERRORS']) && is_array($PayPalResult['ERRORS']) && count($PayPalResult['ERRORS']) > 0) {
				foreach ($PayPalResult['ERRORS'] as $errmsg) {
					$error .= '<br />'.$errmsg['L_LONGMESSAGE'];
				}
			} else {
				$error = 'An unspecified error occurred.';
			}
		}			
		
		$orders = $model->GetAllItemsOfOrderNo($orderno);
		//var_dump($orders);
		//var_dump($uid);
		if (!Jaws_Error::IsError($orders) && isset($orders[0]['id']) && !empty($orders[0]['id']) && ($uid == $orders[0]['customer_id'] || $guestorder === true)) {
			
			$order_status = $orders[0]['active'];
			$orderno = $orders[0]['orderno'];
			$OwnerID = $orders[0]['ownerid'];
			$CustID = $orders[0]['customer_id'];
			
			$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
	        $tpl->Load('ViewOrder.html');
			$tpl->SetBlock('view_order');
			
			foreach ($orders as $pageInfo) {
				if (strpos($pageInfo['description'], 's:12:"Handling fee"') === false) {
					$customer_cc_type = '';
					$customer_cc_number = '';
					$customer_cc_cvv = '';
					if (isset($pageInfo['customer_cc_type']) && !empty($pageInfo['customer_cc_type'])) {
						$customer_cc_type = $pageInfo['customer_cc_type'];
						$customer_cc_type = $JCrypt->rsa->decrypt($customer_cc_type, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_type)) {
							$customer_cc_type = '';
						}
					}
					if (isset($pageInfo['customer_cc_number']) && !empty($pageInfo['customer_cc_number'])) {
						$customer_cc_number = $pageInfo['customer_cc_number'];
						$customer_cc_number = $JCrypt->rsa->decrypt($customer_cc_number, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_number)) {
							$customer_cc_number = '';
						}
					}
					if (isset($pageInfo['customer_cc_cvv']) && !empty($pageInfo['customer_cc_cvv'])) {
						$customer_cc_cvv = $pageInfo['customer_cc_cvv'];
						$customer_cc_cvv = $JCrypt->rsa->decrypt($customer_cc_cvv, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_cvv)) {
							$customer_cc_cvv = '';
						}
					}
					
					if ($confirm == 'Y') {
						$res = $adminModel->UpdateOrderStatus(
							$pageInfo['id'], 'NEW'
						);
						// TODO: rollback to previous status on error?
						if (Jaws_Error::IsError($res)) {
							return $res;
						}
					}
					
					if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || (isset($userInfo['id']) && $CustID == $userInfo['id']) || $guestorder === true) {
						$tpl->SetBlock('view_order/order');
						$tpl->SetVariable('OwnerID', $pageInfo['ownerid']);
						$tpl->SetVariable('order_status', ($order_status == 'TEMP' ? 'NEW' : $order_status));
						$tpl->SetVariable('orderno', $pageInfo['orderno'].'-'.($pageInfo['ownerid'] > 0 ? $pageInfo['ownerid'] : '0').'-'.($CustID > 0 ? $CustID : '0'));
						$tpl->SetVariable('order_total', (!empty($pageInfo['total']) && $pageInfo['total'] > 0 ? $pageInfo['total'] : $pageInfo['price']));
						if ((int)$pageInfo['ownerid'] > 0) {
							$ownerInfo = $jUser->GetUserInfoById((int)$pageInfo['ownerid'], true, true, true, true);
							if (!Jaws_Error::IsError($ownerInfo) && isset($ownerInfo['id'])) {
								$tpl->SetBlock('view_order/order/merchant');
								$tpl->SetVariable('merchant_name', $xss->filter(ereg_replace("[^A-Za-z0-9\:\ \,]", '', (!empty($ownerInfo['company']) ? $ownerInfo['company'] : (!empty($ownerInfo['nickname']) ? $ownerInfo['nickname'] : 'ID: '.$pageInfo['ownerid'])))));
								if (
									(($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) || ($uid == $ownerInfo['id'])) && 
									isset($ownerInfo['merchant_id']) && !empty($ownerInfo['merchant_id'])
								) {
									$tpl->SetVariable('merchant_id', $ownerInfo['merchant_id']);
								} else {
									$tpl->SetVariable('merchant_id', $ownerInfo['id']);
								}
								$tpl->ParseBlock('view_order/order/merchant');
							}
						}
						if (!empty($pageInfo['description'])) {
							$pInfo = unserialize($pageInfo['description']);
							$pDesc = $pInfo['description'];
							$tpl->SetVariable('order_description', $xss->filter($pDesc));
							if (isset($pInfo['items']) && is_array($pInfo['items']) && !count($pInfo['items']) <= 0) {
								foreach ($pInfo['items'] as $item_owner => $items) {
									// Update product quantities
									if (isset($items['number']) && !empty($items['number']) && isset($items['qty']) && !empty($items['qty'])) {
										$prod_id = (int)$items['number'];
										$ord_qty = (int)$items['qty'];
										$updateStock = $model->UpdateProductStock($prod_id, $ord_qty);
									}
									//foreach ($items as $item) {
										$tpl->SetBlock('view_order/order/order_item');
										$tpl->SetVariable('item_link', $items['itemurl']);
										$tpl->SetVariable('item_title', $xss->filter(ereg_replace("[^A-Za-z0-9\:\ \,]", '', $items['name'])));
										$tpl->SetVariable('item_price', $items['amt']);
										$tpl->SetVariable('item_qty', $items['qty']);
										$tpl->SetVariable('item_details', $xss->filter($items['desc']));
										$tpl->ParseBlock('view_order/order/order_item');
									//}
								}
							}
							if (isset($pInfo['customcheckoutfields']) && is_array($pInfo['customcheckoutfields']) && !count($pInfo['customcheckoutfields']) <= 0) {
								foreach ($pInfo['customcheckoutfields'] as $ck => $cv) {
									$tpl->SetBlock('view_order/order/customfield');
									$tpl->SetVariable('custom_name', $ck);
									$tpl->SetVariable('custom_value', $xss->filter($JCrypt->rsa->decrypt($cv, $JCrypt->pvt_key)));
									$tpl->ParseBlock('view_order/order/customfield');
								}
							}
						}
						$tpl->ParseBlock('view_order/order');
						
					}
				}
			}
						
						
			if (!empty($gateway_logo) && file_exists(JAWS_DATA . 'files'.$xss->filter($gateway_logo))) {
				$tpl->SetBlock('view_order/gateway_logo');
				$tpl->SetVariable('gateway_logo', $GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($gateway_logo));
				$tpl->ParseBlock('view_order/gateway_logo');
			}
			if ($order_status == 'TEMP' && $confirm != 'Y') {
				$title = "Confirm Your Order";
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_confirm.png';
				$tpl->SetBlock('view_order/footer_form_start');
				$tpl->SetVariable('form_action', $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Ecommerce&action=ManualResponse&orderno='.$orderno);
				$tpl->ParseBlock('view_order/footer_form_start');
				if (!empty($checkout_terms)) {
					$tpl->SetBlock('view_order/accept');
					$tpl->ParseBlock('view_order/accept');
					$tpl->SetBlock('view_order/footer_form_terms');
					$tpl->SetVariable('checkout_terms', $checkout_terms);
					$tpl->ParseBlock('view_order/footer_form_terms');
				} else {
					$tpl->SetBlock('view_order/footer_form_hidden');
					$tpl->ParseBlock('view_order/footer_form_hidden');
				}
				if (empty($error)) {
					$checkout_button = '<input type="submit" value="Checkout" />&nbsp;&nbsp;&nbsp;or&nbsp;';
				}
				$tpl->SetBlock('view_order/footer_form_end');
				$tpl->ParseBlock('view_order/footer_form_end');
			} else {
				$title = "Thanks".(isset($userInfo['fname']) && !empty($userInfo['fname']) ? '&nbsp;'.$userInfo['fname'] : (isset($userInfo['nickname']) && !empty($userInfo['nickname']) ? '&nbsp;'.$userInfo['nickname'] : '')).", you're done!";
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_success.png';
				$tpl->SetBlock('view_order/ack');
				$tpl->SetVariable('site_name', $site_name);
				$tpl->SetBlock('view_order/ack/print');
				$tpl->SetVariable('print_link', $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Ecommerce&action=PayPalResponse&orderno='.$orderno.'&standalone=1');
				$tpl->ParseBlock('view_order/ack/print');
				$tpl->ParseBlock('view_order/ack');
			}
												
			if (!empty($error)) {
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
				$tpl->SetBlock('view_order/error');
				$tpl->SetVariable('error', $xss->filter($error));
				$tpl->ParseBlock('view_order/error');
			} else {
				$tpl->SetBlock('view_order/billing');
				$tpl->SetVariable('OwnerID', $CustID);
				if (!empty($customer_cc_number) && !empty($customer_cc_type)) {
					$tpl->SetBlock('view_order/billing/customer_cc_type');
					$tpl->SetVariable('customer_cc_type', $customer_cc_type);
					$tpl->ParseBlock('view_order/billing/customer_cc_type');
					$tpl->SetBlock('view_order/billing/customer_cc_number');
					$tpl->SetVariable('customer_cc_number', '************'.substr($customer_cc_number, -4));
					$tpl->ParseBlock('view_order/billing/customer_cc_number');
					/*
					$tpl->SetBlock('view_order/billing/customer_cc_exp');
					$tpl->SetVariable('customer_cc_exp_month', $pageInfo['customer_cc_exp_month']);
					$tpl->SetVariable('customer_cc_exp_year', $pageInfo['customer_cc_exp_year']);
					$tpl->ParseBlock('view_order/billing/customer_cc_exp');
					$tpl->SetBlock('view_order/billing/customer_cc_cvv');
					$tpl->SetVariable('customer_cc_cvv', $customer_cc_cvv);
					$tpl->ParseBlock('view_order/billing/customer_cc_cvv');
					*/
				}
				$tpl->SetBlock('view_order/billing/customer_name');
				$tpl->SetVariable('customer_name', $pageInfo['customer_name']);
				$tpl->ParseBlock('view_order/billing/customer_name');
				$tpl->SetBlock('view_order/billing/customer_email');
				$tpl->SetVariable('customer_email', $pageInfo['customer_email']);
				$tpl->ParseBlock('view_order/billing/customer_email');
				$tpl->SetBlock('view_order/billing/customer_phone');
				$tpl->SetVariable('customer_phone', $pageInfo['customer_phone']);
				$tpl->ParseBlock('view_order/billing/customer_phone');
				$tpl->SetBlock('view_order/billing/customer_fax');
				$tpl->SetVariable('customer_fax', $pageInfo['customer_fax']);
				$tpl->ParseBlock('view_order/billing/customer_fax');
				$tpl->SetBlock('view_order/billing/customer_company');
				$tpl->SetVariable('customer_company', $pageInfo['customer_company']);
				$tpl->ParseBlock('view_order/billing/customer_company');
				$tpl->SetBlock('view_order/billing/customer_address');
				$tpl->SetVariable('customer_address', $pageInfo['customer_address']);
				$tpl->ParseBlock('view_order/billing/customer_address');
				$tpl->SetBlock('view_order/billing/customer_address2');
				$tpl->SetVariable('customer_address2', $pageInfo['customer_address2']);
				$tpl->ParseBlock('view_order/billing/customer_address2');
				$tpl->SetBlock('view_order/billing/customer_city');
				$tpl->SetVariable('customer_city', $pageInfo['customer_city']);
				$tpl->ParseBlock('view_order/billing/customer_city');
				$tpl->SetBlock('view_order/billing/customer_region');
				$tpl->SetVariable('customer_region', $pageInfo['customer_region']);
				$tpl->ParseBlock('view_order/billing/customer_region');
				$tpl->SetBlock('view_order/billing/customer_postal');
				$tpl->SetVariable('customer_postal', $pageInfo['customer_postal']);
				$tpl->ParseBlock('view_order/billing/customer_postal');
				$tpl->SetBlock('view_order/billing/customer_country');
				$tpl->SetVariable('customer_country', $pageInfo['customer_country']);
				$tpl->ParseBlock('view_order/billing/customer_country');
				$tpl->ParseBlock('view_order/billing');
				
				$tpl->SetBlock('view_order/shipping');
				$tpl->SetVariable('OwnerID', $CustID);
				$tpl->SetBlock('view_order/shipping/shiptype');
				$tpl->SetVariable('shiptype', $pageInfo['shiptype']);
				$tpl->ParseBlock('view_order/shipping/shiptype');
				$tpl->SetBlock('view_order/shipping/customer_shipname');
				$tpl->SetVariable('customer_shipname', $pageInfo['customer_shipname']);
				$tpl->ParseBlock('view_order/shipping/customer_shipname');
				$tpl->SetBlock('view_order/shipping/customer_shipaddress');
				$tpl->SetVariable('customer_shipaddress', $pageInfo['customer_shipaddress']);
				$tpl->ParseBlock('view_order/shipping/customer_shipaddress');
				$tpl->SetBlock('view_order/shipping/customer_shipaddress2');
				$tpl->SetVariable('customer_shipaddress2', $pageInfo['customer_shipaddress2']);
				$tpl->ParseBlock('view_order/shipping/customer_shipaddress2');
				$tpl->SetBlock('view_order/shipping/customer_shipcity');
				$tpl->SetVariable('customer_shipcity', $pageInfo['customer_shipcity']);
				$tpl->ParseBlock('view_order/shipping/customer_shipcity');
				$tpl->SetBlock('view_order/shipping/customer_shipregion');
				$tpl->SetVariable('customer_shipregion', $pageInfo['customer_shipregion']);
				$tpl->ParseBlock('view_order/shipping/customer_shipregion');
				$tpl->SetBlock('view_order/shipping/customer_shippostal');
				$tpl->SetVariable('customer_shippostal', $pageInfo['customer_shippostal']);
				$tpl->ParseBlock('view_order/shipping/customer_shippostal');
				$tpl->SetBlock('view_order/shipping/customer_shipcountry');
				$tpl->SetVariable('customer_shipcountry', $pageInfo['customer_shipcountry']);
				$tpl->ParseBlock('view_order/shipping/customer_shipcountry');
				$tpl->ParseBlock('view_order/shipping');
			}
			$tpl->SetBlock('view_order/header');
	        $tpl->SetVariable('title', $title);
	        $tpl->SetVariable('icon', $icon);
	        $tpl->ParseBlock('view_order/header');
			
			$tpl->SetBlock('view_order/footer');
			$tpl->SetVariable('checkout_button', $checkout_button);
			$tpl->SetBlock('view_order/footer/shopping_link');
			$tpl->SetVariable('shopping_link', $GLOBALS['app']->GetSiteURL() .'/index.php?products/all.html');
			$tpl->SetVariable('site_name', $site_name);
			$tpl->ParseBlock('view_order/footer/shopping_link');
			$tpl->ParseBlock('view_order/footer');
						
	        $tpl->ParseBlock('view_order');
			return $tpl->Get();			
		} else {
			return Jaws_HTTPError::Get(404);
		}
	}
	
	/**
     * Handles Google Checkout Responses.
     *
     * @access public
     * @return string
     */
    function GoogleCheckoutResponse()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('url'));
		if (isset($get['url']) && !empty($get['url'])) {
			$url = $get['url'];
		} else {	
			die('Target URL not specified');
		}

		//$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		
		$_SERVER['PHP_AUTH_USER'] = $merchant_id;
        $_SERVER['PHP_AUTH_PW'] = $merchant_key;

		$submit_vars = array();
		$submit_vars['gateway'] = md5('GoogleCheckout');
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

		 /* This is the response handler code that will be invoked every time
		  * a notification or request is sent by the Google Server
		  *
		  * To allow this code to receive responses, the url for this file
		  * must be set on the seller page under Settings->Integration as the
		  * "API Callback URL'
		  * Order processing commands can be sent automatically by placing these
		  * commands appropriately
		  *
		  * To use this code for merchant-calculated feedback, this url must be
		  * set also as the merchant-calculations-url when the cart is posted
		  * Depending on your calculations for shipping, taxes, coupons and gift
		  * certificates update parts of the code as required
		  *
		  */

		  require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googleresponse.php');
		  require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googlemerchantcalculations.php');
		  require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googleresult.php');
		  require_once(JAWS_PATH . 'libraries/googlecheckout/1.2.5b/library/googlerequest.php');

		  define('RESPONSE_HANDLER_ERROR_LOG_FILE', JAWS_DATA . 'logs/googleerror.log');
		  define('RESPONSE_HANDLER_LOG_FILE', JAWS_DATA . 'logs/googlemessage.log');
		  
		  if (!empty($site_ssl_url) && !empty($merchant_id) && !empty($merchant_key)) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			
			$server_type = "sandbox";  // change this to go live
			$currency = 'USD';  // set to GBP if in the UK

			  $Gresponse = new GoogleResponse($merchant_id, $merchant_key);

			  $Grequest = new GoogleRequest($merchant_id, $merchant_key, $server_type, $currency);

			  //Setup the log file
			  $Gresponse->SetLogFiles(RESPONSE_HANDLER_ERROR_LOG_FILE, 
													RESPONSE_HANDLER_LOG_FILE, L_ALL);

			  // Retrieve the XML sent in the HTTP POST request to the ResponseHandler
			  $xml_response = isset($HTTP_RAW_POST_DATA)?
								$HTTP_RAW_POST_DATA:file_get_contents("php://input");
			  if (get_magic_quotes_gpc()) {
				$xml_response = stripslashes($xml_response);
			  }
			  list($root, $data) = $Gresponse->GetParsedXML($xml_response);
			  $Gresponse->SetMerchantAuthentication($merchant_id, $merchant_key);

			  $status = $Gresponse->HttpAuthentication();
			  if(!$status) {
				die('authentication failed');
			  }

			  /* Commands to send the various order processing APIs
			   * Send charge order : $Grequest->SendChargeOrder($data[$root]
			   *    ['google-order-number']['VALUE'], <amount>);
			   * Send process order : $Grequest->SendProcessOrder($data[$root]
			   *    ['google-order-number']['VALUE']);
			   * Send deliver order: $Grequest->SendDeliverOrder($data[$root]
			   *    ['google-order-number']['VALUE'], <carrier>, <tracking-number>,
			   *    <send_mail>);
			   * Send archive order: $Grequest->SendArchiveOrder($data[$root]
			   *    ['google-order-number']['VALUE']);
			   *
			   */

			  switch ($root) {
				case "request-received": {
				  break;
				}
				case "error": {
				  break;
				}
				case "diagnosis": {
				  break;
				}
				case "checkout-redirect": {
				  break;
				}
				case "merchant-calculation-callback": {
				  // Create the results and send it
				  $merchant_calc = new GoogleMerchantCalculations($currency);

				  // Loop through the list of address ids from the callback
				  $addresses = $this->get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
				  foreach($addresses as $curr_address) {
					$curr_id = $curr_address['id'];
					$country = $curr_address['country-code']['VALUE'];
					$city = $curr_address['city']['VALUE'];
					$region = $curr_address['region']['VALUE'];
					$postal_code = $curr_address['postal-code']['VALUE'];

					// Loop through each shipping method if merchant-calculated shipping
					// support is to be provided
					if(isset($data[$root]['calculate']['shipping'])) {
					  $shipping = $this->get_arr_result($data[$root]['calculate']['shipping']['method']);
					  foreach($shipping as $curr_ship) {
						$name = $curr_ship['name'];
						//Compute the price for this shipping method and address id
						$price = 12; // Modify this to get the actual price
						$shippable = "true"; // Modify this as required
						$merchant_result = new GoogleResult($curr_id);
						$merchant_result->SetShippingDetails($name, $price, $shippable);

						if($data[$root]['calculate']['tax']['VALUE'] == "true") {
						  //Compute tax for this address id and shipping type
						  $amount = 15; // Modify this to the actual tax value
						  $merchant_result->SetTaxDetails($amount);
						}

						if(isset($data[$root]['calculate']['merchant-code-strings']
							['merchant-code-string'])) {
						  $codes = $this->get_arr_result($data[$root]['calculate']['merchant-code-strings']
							  ['merchant-code-string']);
						  foreach($codes as $curr_code) {
							//Update this data as required to set whether the coupon is valid, the code and the amount
							$coupons = new GoogleCoupons("true", $curr_code['code'], 5, "test2");
							$merchant_result->AddCoupons($coupons);
						  }
						 }
						 $merchant_calc->AddResult($merchant_result);
					  }
					} else {
					  $merchant_result = new GoogleResult($curr_id);
					  if($data[$root]['calculate']['tax']['VALUE'] == "true") {
						//Compute tax for this address id and shipping type
						$amount = 15; // Modify this to the actual tax value
						$merchant_result->SetTaxDetails($amount);
					  }
					  $codes = $this->get_arr_result($data[$root]['calculate']['merchant-code-strings']
						  ['merchant-code-string']);
					  foreach($codes as $curr_code) {
						//Update this data as required to set whether the coupon is valid, the code and the amount
						$coupons = new GoogleCoupons("true", $curr_code['code'], 5, "test2");
						$merchant_result->AddCoupons($coupons);
					  }
					  $merchant_calc->AddResult($merchant_result);
					}
				  }
				  $Gresponse->ProcessMerchantCalculations($merchant_calc);
				  break;
				}
				case "new-order-notification": {
					$Gresponse->SendAck();
					$snoopy = new Snoopy('Ecommerce');
					$submit_url = 'http://'.urldecode($url).'/index.php?gadget=Ecommerce&action=account_form_post&fuseaction=AddOrder';
					if(isset($data[$root]['shopping-cart']['items'])) {
						$items = $this->get_arr_result($data[$root]['shopping-cart']['items']);
						$output = var_export($items, true);
						$globalfile = file_put_contents(JAWS_DATA . 'logs/googleorder.txt', $output);
						return true;
						/*
						foreach($items as $curr_item) {
							$submit_vars['ID'] = ;
						}
						*/
					}
					
					//if($snoopy->submit($submit_url, $submit_vars)) {
					//}
					
					// Update product quantities
					
					// Update address and keyword details, if currently empty
					
					break;
				}
				case "order-state-change-notification": {
				  $Gresponse->SendAck();
				  $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
				  $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];

				  switch($new_financial_state) {
					case 'REVIEWING': {
					  break;
					}
					case 'CHARGEABLE': {
					  $Grequest->SendProcessOrder($data[$root]['google-order-number']['VALUE']);
					  $Grequest->SendChargeOrder($data[$root]['google-order-number']['VALUE'],'');
					  break;
					}
					case 'CHARGING': {
					  break;
					}
					case 'CHARGED': {
					  break;
					}
					case 'PAYMENT_DECLINED': {
					  break;
					}
					case 'CANCELLED': {
					  break;
					}
					case 'CANCELLED_BY_GOOGLE': {
					  //$Grequest->SendBuyerMessage($data[$root]['google-order-number']['VALUE'],
					  //    "Sorry, your order is cancelled by Google", true);
					  break;
					}
					default:
					  break;
				  }

				  switch($new_fulfillment_order) {
					case 'NEW': {
					  break;
					}
					case 'PROCESSING': {
					  break;
					}
					case 'DELIVERED': {
					  break;
					}
					case 'WILL_NOT_DELIVER': {
					  break;
					}
					default:
					  break;
				  }
				  break;
				}
				case "charge-amount-notification": {
				  //$Grequest->SendDeliverOrder($data[$root]['google-order-number']['VALUE'],
				  //    <carrier>, <tracking-number>, <send-email>);
				  //$Grequest->SendArchiveOrder($data[$root]['google-order-number']['VALUE'] );
				  $Gresponse->SendAck();
				  break;
				}
				case "chargeback-amount-notification": {
				  $Gresponse->SendAck();
				  break;
				}
				case "refund-amount-notification": {
				  $Gresponse->SendAck();
				  break;
				}
				case "risk-information-notification": {
				  $Gresponse->SendAck();
				  break;
				}
				default:
				  $Gresponse->SendBadRequestStatus("Invalid or not supported Message");
				  break;
			  }
		}
		//return new Jaws_Error(_t('ECOMMERCE_ERROR_RESPONSE_NOT_HANDLED'), _t('ECOMMERCE_NAME'));
	}
    	
	/**
     * Handles Manual Responses
     *
     * @access public
     * @return string
     */
    function ManualResponse()
    {		
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$merchant_signature = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_signature');  // Your Merchant Signature
		$shipfrom_city = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city');  // City Shipping From
		$shipfrom_state = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');  // State Shipping From
		$shipfrom_zip = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip');  // Zip Shipping From
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');  // Use Carrier Calculated Shipping
		$checkout_terms = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/checkout_terms');  // Checkout Terms
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		if (empty($site_name)) {
			$site_name = $GLOBALS['app']->GetSiteURL();
		}
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$gateway_logo = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_logo');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$storeModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
		
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('orderno', 'error'), 'get');
		$post = $request->get(array('confirm'), 'post');
		$error = '';
		$orderno = '';
		$confirm = 'N';
		if (isset($get['orderno']) && !empty($get['orderno'])) {
			$orderno = $get['orderno'];
		}
		if (isset($get['error']) && !empty($get['error'])) {
			$error = $get['error'];
		}
		if (isset($post['confirm']) && !empty($post['confirm']) && $post['confirm'] == 'Y') {
			$confirm = 'Y';
		}
		
		require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
				
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		
		$this->AjaxMe('client_script.js');
        
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/black_hud.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Ecommerce/resources/style.css', 'stylesheet', 'text/css');
				
		$submit_vars = array();
		
		$guestorder = false;
		if ($cart_data = $GLOBALS['app']->Session->PopSimpleResponse('Ecommerce.Cart.Data')) {
			if (
				isset($cart_data['session_id']) && !empty($cart_data['session_id']) && $cart_data['session_id'] == $GLOBALS['app']->Session->_SessionID 
			) {
				$GLOBALS['app']->Session->PushSimpleResponse($cart_data, 'Ecommerce.Cart.Data');	
				$guestorder = true;
			}
		}
		
		$orders = $model->GetAllItemsOfOrderNo($orderno);
		if (!Jaws_Error::IsError($orders) && isset($orders[0]['id']) && !empty($orders[0]['id'])) {
			if ($guestorder === false) {
				if ($GLOBALS['app']->Session->Logged()) {
					$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
					$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
					if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
						$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
						Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
						exit;
					} else {
						if ($uid != $orders[0]['customer_id']) {
							return Jaws_HTTPError::Get(404);
						}
					}
				} else {
					$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
					Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
					exit;
				}
			}
			
			require_once JAWS_PATH . 'include/Jaws/Crypt.php';
			$JCrypt = new Jaws_Crypt();
			$JCrypt->Init(true);
			
			$order_status = $orders[0]['active'];
			$orderno = $orders[0]['orderno'];
			$OwnerID = $orders[0]['ownerid'];
			$CustID = $orders[0]['customer_id'];
			
			$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
	        $tpl->Load('ViewOrder.html');
	        
			$tpl->SetBlock('view_order');
			
			foreach ($orders as $pageInfo) {
				if (strpos($pageInfo['description'], 's:12:"Handling fee"') === false) {
					$customer_cc_type = '';
					$customer_cc_number = '';
					$customer_cc_cvv = '';
					if (isset($pageInfo['customer_cc_type']) && !empty($pageInfo['customer_cc_type'])) {
						$customer_cc_type = $pageInfo['customer_cc_type'];
						$customer_cc_type = $JCrypt->rsa->decrypt($customer_cc_type, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_type)) {
							$customer_cc_type = '';
						}
					}
					if (isset($pageInfo['customer_cc_number']) && !empty($pageInfo['customer_cc_number'])) {
						$customer_cc_number = $pageInfo['customer_cc_number'];
						$customer_cc_number = $JCrypt->rsa->decrypt($customer_cc_number, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_number)) {
							$customer_cc_number = '';
						}
					}
					if (isset($pageInfo['customer_cc_cvv']) && !empty($pageInfo['customer_cc_cvv'])) {
						$customer_cc_cvv = $pageInfo['customer_cc_cvv'];
						$customer_cc_cvv = $JCrypt->rsa->decrypt($customer_cc_cvv, $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_cvv)) {
							$customer_cc_cvv = '';
						}
					}
					
					if ($confirm == 'Y') {
						$res = $adminModel->UpdateOrderStatus(
							$pageInfo['id'], 'NEW'
						);
						// TODO: rollback to previous status on error?
						if (Jaws_Error::IsError($res)) {
							return $res;
						}
					}
					
					if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || $CustID == $userInfo['id'] || $guestorder === true) {
						$tpl->SetBlock('view_order/order');
						$tpl->SetVariable('OwnerID', $pageInfo['ownerid']);
						$tpl->SetVariable('order_status', ($order_status == 'TEMP' ? 'NEW' : $order_status));
						$tpl->SetVariable('orderno', $pageInfo['orderno'].'-'.($pageInfo['ownerid'] > 0 ? $pageInfo['ownerid'] : '0').'-'.($CustID > 0 ? $CustID : '0'));
						$tpl->SetVariable('order_total', (!empty($pageInfo['total']) && $pageInfo['total'] > 0 ? $pageInfo['total'] : $pageInfo['price']));
						if ((int)$pageInfo['ownerid'] > 0) {
							$ownerInfo = $jUser->GetUserInfoById((int)$pageInfo['ownerid'], true, true, true, true);
							if (!Jaws_Error::IsError($ownerInfo) && isset($ownerInfo['id'])) {
								$tpl->SetBlock('view_order/order/merchant');
								$tpl->SetVariable('merchant_name', $xss->filter(ereg_replace("[^A-Za-z0-9\:\ \,]", '', (!empty($ownerInfo['company']) ? $ownerInfo['company'] : (!empty($ownerInfo['nickname']) ? $ownerInfo['nickname'] : 'ID: '.$pageInfo['ownerid'])))));
								if (
									(($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) || ($uid == $ownerInfo['id'])) && 
									isset($ownerInfo['merchant_id']) && !empty($ownerInfo['merchant_id'])
								) {
									$tpl->SetVariable('merchant_id', $ownerInfo['merchant_id']);
								} else {
									$tpl->SetVariable('merchant_id', $ownerInfo['id']);
								}
								$tpl->ParseBlock('view_order/order/merchant');
							}
						}
						if (!empty($pageInfo['description'])) {
							$pInfo = unserialize($pageInfo['description']);
							$pDesc = $pInfo['description'];
							$tpl->SetVariable('order_description', $xss->filter($pDesc));
							if (isset($pInfo['items']) && is_array($pInfo['items']) && !count($pInfo['items']) <= 0) {
								foreach ($pInfo['items'] as $item_owner => $items) {
									//foreach ($items as $item) {
										$tpl->SetBlock('view_order/order/order_item');
										$tpl->SetVariable('item_link', $items['itemurl']);
										$tpl->SetVariable('item_title', $xss->filter(ereg_replace("[^A-Za-z0-9\:\ \,]", '', $items['name'])));
										$tpl->SetVariable('item_price', $items['amt']);
										$tpl->SetVariable('item_qty', $items['qty']);
										$tpl->SetVariable('item_details', $xss->filter($items['desc']));
										$tpl->ParseBlock('view_order/order/order_item');
									//}
								}
							}
							if (isset($pInfo['customcheckoutfields']) && is_array($pInfo['customcheckoutfields']) && !count($pInfo['customcheckoutfields']) <= 0) {
								foreach ($pInfo['customcheckoutfields'] as $ck => $cv) {
									$tpl->SetBlock('view_order/order/customfield');
									$tpl->SetVariable('custom_name', $ck);
									$tpl->SetVariable('custom_value', $xss->filter($JCrypt->rsa->decrypt($cv, $JCrypt->pvt_key)));
									$tpl->ParseBlock('view_order/order/customfield');
								}
							}
						}
						$tpl->ParseBlock('view_order/order');
						
					}
				}
			}
						
						
			if (!empty($gateway_logo) && file_exists(JAWS_DATA . 'files'.$xss->filter($gateway_logo))) {
				$tpl->SetBlock('view_order/gateway_logo');
				$tpl->SetVariable('gateway_logo', $GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($gateway_logo));
				$tpl->ParseBlock('view_order/gateway_logo');
			}
			if ($order_status == 'TEMP' && $confirm != 'Y') {
				$title = "Confirm Your Order";
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_confirm.png';
				$tpl->SetBlock('view_order/footer_form_start');
				$tpl->SetVariable('form_action', $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Ecommerce&action=ManualResponse&orderno='.$orderno);
				$tpl->ParseBlock('view_order/footer_form_start');
				if (!empty($checkout_terms)) {
					$tpl->SetBlock('view_order/accept');
					$tpl->ParseBlock('view_order/accept');
					$tpl->SetBlock('view_order/footer_form_terms');
					$tpl->SetVariable('checkout_terms', $checkout_terms);
					$tpl->ParseBlock('view_order/footer_form_terms');
				} else {
					$tpl->SetBlock('view_order/footer_form_hidden');
					$tpl->ParseBlock('view_order/footer_form_hidden');
				}
				if (empty($error)) {
					$checkout_button = '<input type="submit" value="Checkout" />&nbsp;&nbsp;&nbsp;or&nbsp;';
				}
				$tpl->SetBlock('view_order/footer_form_end');
				$tpl->ParseBlock('view_order/footer_form_end');
			} else {
				$title = "Thanks".(isset($userInfo['fname']) && !empty($userInfo['fname']) ? '&nbsp;'.$userInfo['fname'] : (isset($userInfo['nickname']) && !empty($userInfo['nickname']) ? '&nbsp;'.$userInfo['nickname'] : '')).", you're done!";
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_success.png';
				$tpl->SetBlock('view_order/ack');
				$tpl->SetVariable('site_name', $site_name);
				$tpl->SetBlock('view_order/ack/print');
				$tpl->SetVariable('print_link', $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Ecommerce&action=ManualResponse&orderno='.$orderno.'&standalone=1');
				$tpl->ParseBlock('view_order/ack/print');
				$tpl->ParseBlock('view_order/ack');
			}
												
			if (!empty($error)) {
				$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
				$tpl->SetBlock('view_order/error');
				$tpl->SetVariable('error', $xss->filter($error));
				$tpl->ParseBlock('view_order/error');
			} else {
				$tpl->SetBlock('view_order/billing');
				$tpl->SetVariable('OwnerID', $CustID);
				if (!empty($customer_cc_number) && !empty($customer_cc_type)) {
					$tpl->SetBlock('view_order/billing/customer_cc_type');
					$tpl->SetVariable('customer_cc_type', $customer_cc_type);
					$tpl->ParseBlock('view_order/billing/customer_cc_type');
					$tpl->SetBlock('view_order/billing/customer_cc_number');
					$tpl->SetVariable('customer_cc_number', '************'.substr($customer_cc_number, -4));
					$tpl->ParseBlock('view_order/billing/customer_cc_number');
					/*
					$tpl->SetBlock('view_order/billing/customer_cc_exp');
					$tpl->SetVariable('customer_cc_exp_month', $pageInfo['customer_cc_exp_month']);
					$tpl->SetVariable('customer_cc_exp_year', $pageInfo['customer_cc_exp_year']);
					$tpl->ParseBlock('view_order/billing/customer_cc_exp');
					$tpl->SetBlock('view_order/billing/customer_cc_cvv');
					$tpl->SetVariable('customer_cc_cvv', $customer_cc_cvv);
					$tpl->ParseBlock('view_order/billing/customer_cc_cvv');
					*/
				}
				$tpl->SetBlock('view_order/billing/customer_name');
				$tpl->SetVariable('customer_name', $pageInfo['customer_name']);
				$tpl->ParseBlock('view_order/billing/customer_name');
				$tpl->SetBlock('view_order/billing/customer_email');
				$tpl->SetVariable('customer_email', $pageInfo['customer_email']);
				$tpl->ParseBlock('view_order/billing/customer_email');
				$tpl->SetBlock('view_order/billing/customer_phone');
				$tpl->SetVariable('customer_phone', $pageInfo['customer_phone']);
				$tpl->ParseBlock('view_order/billing/customer_phone');
				$tpl->SetBlock('view_order/billing/customer_fax');
				$tpl->SetVariable('customer_fax', $pageInfo['customer_fax']);
				$tpl->ParseBlock('view_order/billing/customer_fax');
				$tpl->SetBlock('view_order/billing/customer_company');
				$tpl->SetVariable('customer_company', $pageInfo['customer_company']);
				$tpl->ParseBlock('view_order/billing/customer_company');
				$tpl->SetBlock('view_order/billing/customer_address');
				$tpl->SetVariable('customer_address', $pageInfo['customer_address']);
				$tpl->ParseBlock('view_order/billing/customer_address');
				$tpl->SetBlock('view_order/billing/customer_address2');
				$tpl->SetVariable('customer_address2', $pageInfo['customer_address2']);
				$tpl->ParseBlock('view_order/billing/customer_address2');
				$tpl->SetBlock('view_order/billing/customer_city');
				$tpl->SetVariable('customer_city', $pageInfo['customer_city']);
				$tpl->ParseBlock('view_order/billing/customer_city');
				$tpl->SetBlock('view_order/billing/customer_region');
				$tpl->SetVariable('customer_region', $pageInfo['customer_region']);
				$tpl->ParseBlock('view_order/billing/customer_region');
				$tpl->SetBlock('view_order/billing/customer_postal');
				$tpl->SetVariable('customer_postal', $pageInfo['customer_postal']);
				$tpl->ParseBlock('view_order/billing/customer_postal');
				$tpl->SetBlock('view_order/billing/customer_country');
				$tpl->SetVariable('customer_country', $pageInfo['customer_country']);
				$tpl->ParseBlock('view_order/billing/customer_country');
				$tpl->ParseBlock('view_order/billing');
				
				$tpl->SetBlock('view_order/shipping');
				$tpl->SetVariable('OwnerID', $CustID);
				$tpl->SetBlock('view_order/shipping/shiptype');
				$tpl->SetVariable('shiptype', $pageInfo['shiptype']);
				$tpl->ParseBlock('view_order/shipping/shiptype');
				$tpl->SetBlock('view_order/shipping/customer_shipname');
				$tpl->SetVariable('customer_shipname', $pageInfo['customer_shipname']);
				$tpl->ParseBlock('view_order/shipping/customer_shipname');
				$tpl->SetBlock('view_order/shipping/customer_shipaddress');
				$tpl->SetVariable('customer_shipaddress', $pageInfo['customer_shipaddress']);
				$tpl->ParseBlock('view_order/shipping/customer_shipaddress');
				$tpl->SetBlock('view_order/shipping/customer_shipaddress2');
				$tpl->SetVariable('customer_shipaddress2', $pageInfo['customer_shipaddress2']);
				$tpl->ParseBlock('view_order/shipping/customer_shipaddress2');
				$tpl->SetBlock('view_order/shipping/customer_shipcity');
				$tpl->SetVariable('customer_shipcity', $pageInfo['customer_shipcity']);
				$tpl->ParseBlock('view_order/shipping/customer_shipcity');
				$tpl->SetBlock('view_order/shipping/customer_shipregion');
				$tpl->SetVariable('customer_shipregion', $pageInfo['customer_shipregion']);
				$tpl->ParseBlock('view_order/shipping/customer_shipregion');
				$tpl->SetBlock('view_order/shipping/customer_shippostal');
				$tpl->SetVariable('customer_shippostal', $pageInfo['customer_shippostal']);
				$tpl->ParseBlock('view_order/shipping/customer_shippostal');
				$tpl->SetBlock('view_order/shipping/customer_shipcountry');
				$tpl->SetVariable('customer_shipcountry', $pageInfo['customer_shipcountry']);
				$tpl->ParseBlock('view_order/shipping/customer_shipcountry');
				$tpl->ParseBlock('view_order/shipping');
			}
			$tpl->SetBlock('view_order/header');
	        $tpl->SetVariable('title', $title);
	        $tpl->SetVariable('icon', $icon);
	        $tpl->ParseBlock('view_order/header');
			
			$tpl->SetBlock('view_order/footer');
			$tpl->SetVariable('checkout_button', $checkout_button);
			$tpl->SetBlock('view_order/footer/shopping_link');
			$tpl->SetVariable('shopping_link', $GLOBALS['app']->GetSiteURL() .'/index.php?products/all.html');
			$tpl->SetVariable('site_name', $site_name);
			$tpl->ParseBlock('view_order/footer/shopping_link');
			$tpl->ParseBlock('view_order/footer');
						
	        $tpl->ParseBlock('view_order');
			return $tpl->Get();			
		} else {
			return new Jaws_Error("There was a problem retrieving the order.", _t('ECOMMERCE_NAME'));
		}
	}
	
	/**
     * Print Order Details.
     *
     * @access public
     * @return string
     */
    function PrintOrderDetails()
	{
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('redirect'));
	    $output_html = '';
		if (isset($get['fuseaction']) && !empty($get['fuseaction'])) {
			$page = $this->$get['fuseaction']();
			$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
			$html_output = $users_html->GetAccountHTML('Ecommerce');
			$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
			$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		}
		return $output_html;
	}
	
	/**
     * Adds an order to db table, or shows declined info and redirects to URL.
     *
     * @access public
     * @return string
     */
    function Order()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('redirect'));
	    if (isset($get['id'])) {
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$gallery = $model->GetOrder((int)$get['id']);
			if (!Jaws_Error::IsError($gallery)) {
				$adminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
				$result = $adminModel->AddClick((int)$get['id']);
				if (Jaws_Error::IsError($result) || !$result) {
					return new Jaws_Error(_t('ECOMMERCE_ERROR_ADCLICK_NOT_ADDED'), _t('ECOMMERCE_NAME'));
				} else {
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					Jaws_Header::Location(urldecode(html_entity_decode($get['redirect'])));
					return true;
				}
			} else {
				return $gallery;
			}
		}
		return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'), _t('ECOMMERCE_NAME'));
	}

    /**
     * Checks order status (and subscriptions), sends email if expiring or expired, and redirects to URL.
     *
     * @access public
     * @return string
     */
    function CheckOrder()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('id', 'redirect'));
	    if (isset($get['id'])) {
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$gallery = $model->GetOrder((int)$get['id']);
			if (!Jaws_Error::IsError($gallery)) {
				$adminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
				$result = $adminModel->AddClick((int)$get['id']);
				if (Jaws_Error::IsError($result) || !$result) {
					return new Jaws_Error(_t('ECOMMERCE_ERROR_ADCLICK_NOT_ADDED'), _t('ECOMMERCE_NAME'));
				} else {
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					Jaws_Header::Location(urldecode(html_entity_decode($get['redirect'])));
					return true;
				}
			} else {
				return $gallery;
			}
		}
		return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'), _t('ECOMMERCE_NAME'));
	}
	
    /**
     * Posts cart content, verifies and sends to gateway.
     *
     * @access public
     * @return string
     */
    function PostCart()
    {
		$request =& Jaws_Request::getInstance();
		var_dump($_POST);
		exit;
		$get = $request->get(array('id', 'redirect'), 'get');
		$post = $request->get(array('items', 'total_weight', 'paymentmethod', 'redirect_to', 'customer_shipfirstname', 'customer_shiplastname', 
		'customer_shipaddress', 'customer_shipcity', 'customer_shipregion', 'customer_shippostal', 'customer_shipcountry', 
		'shipfreight', 'customer_shipaddress2', 'customer_firstname', 'customer_middlename', 'customer_lastname', 
		'customer_suffix', 'customer_address', 'customer_address2', 'customer_city', 'customer_region', 
		'customer_postal', 'customer_country', 'cc_creditcardtype', 'cc_acct', 'cc_expdate_month', 'cc_expdate_year', 
		'cc_cvv2', 'customcheckoutfields', 'customer_phone', 'sales_code'), 'post');
		
		$gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$items = $GLOBALS['app']->UTF8->json_decode($post['items']);
		$customcheckoutfields = $GLOBALS['app']->UTF8->json_decode($post['customcheckoutfields']);
		return $gadget->PostCart(
			$items, $total_weight, $paymentmethod, $redirect_to, $customer_shipfirstname, $customer_shiplastname, $customer_shipaddress, $customer_shipcity, 
			$customer_shipregion, $customer_shippostal, $customer_shipcountry, $shipfreight, $customer_shipaddress2, 
			$customer_firstname, $customer_middlename, $customer_lastname, $customer_suffix, 
			$customer_address, $customer_address2, $customer_city, $customer_region, $customer_postal, $customer_country,
			$cc_creditcardtype, $cc_acct, $cc_expdate_month, $cc_expdate_year, $cc_cvv2, $customcheckoutfields, $customer_phone, $usecase
		);

	}

    /**
     * Displays an individual order for embedding.
     *
     * @access public
     * @return string
     */
    function EmbedEcommerce()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
		$output_html = "";
		
		/*
		//$output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" style=\"background: url();\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Ecommerce</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		$theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
		if (isset($get['css'])) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"".$get['css']."\" />\n";
		}
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js?load=effects,controls\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=Ajax&amp;client\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/swfobject.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/libraries/crossframe/cross-frame.js\"></script>\n";
		$output_html .= " </head>\n";
		if (isset($get['id']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			$output_html .= " <body style=\"background: transparent url();\" onLoad=\"sizeFrame".$get['id']."(); document.getElementById('".$this->_Name."-editDivStretch-".$get['id']."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$get['id']."').offsetWidth)-20)+'px';\">\n";
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$get['id']." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$get['id']." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$get['id'].":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			$output_html .= " <div id=\"".$this->_Name."-editDiv-".$get['id']."\"><div id=\"".$this->_Name."-editDivStretch-".$get['id']."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$get['id']."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$get['id']."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$get['id']."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$get['id']."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$get['id']."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=Ecommerce&action=account_view&id=".$get['id']."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
			$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
			$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
			$gallery = $model->GetAd((int)$get['id']);
			if (!Jaws_Error::IsError($gallery)) {
				$layoutGadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
				$output_html .= $layoutGadget->Display($gallery['id'], true, $referer, true);
			} else {
				return $gallery;
			}
			$output_html .= " </body>\n";
		} else if (isset($get['uid']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$info  = $jUser->GetUserInfoById((int)$get['uid'], true);
			if (!Jaws_Error::IsError($info)) {
				$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
				$layoutGadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
				$id = $get['mode'];
				if ($get['mode'] == 'TwoButtons') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowTwoButtons(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowTwoButtons(true, $referer);
					}
				} else if ($get['mode'] == 'FourButtons') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowFourButtons(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowFourButtons(true, $referer);
					}
				} else if ($get['mode'] == 'Banner') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowBanner(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowBanner(true, $referer);
					}
				} else if ($get['mode'] == 'LeaderBoard') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowLeaderBoard(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowLeaderBoard(true, $referer);
					}
				}
			} else {
				return $info;
			}
			$output_html .= " <body style=\"background: transparent url();\" onLoad=\"sizeFrame".$id."(); document.getElementById('".$this->_Name."-editDivStretch-".$id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$id."').offsetWidth)-20)+'px';\">\n";
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$id." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$id.":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			$output_html .= " <div id=\"".$this->_Name."-editDiv-".$id."\"><div id=\"".$this->_Name."-editDivStretch-".$id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=Ecommerce&action=account_admin\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
			$output_html .= $layoutGadgetHTML;
			$output_html .= " </body>\n";
		}
		$output_html .= "</html>\n";
		*/
		
		return $output_html;
    }

	/**
     * Displays user account controls.
     *
     * @param array  $info  user information
     * @access public
     * @return string
     */
    function GetUserAccountControls($info, $groups)
    {
        if (!isset($info['id'])) {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
		//require_once JAWS_PATH . 'include/Jaws/User.php';
        //$jUser = new Jaws_User;
        //$info  = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'));
		//$userModel  = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$pane_groups = array();

		/*
		$pane_status = $userModel->GetGadgetPaneInfoByUserID($this->_Name, $info['id']);
		if (!Jaws_Error::IsError($pane_status) && isset($pane_status['status'])) {
		*/
			//Construct panes for each available pane_method
			$panes = $this->GetUserAccountPanesInfo($groups);
			foreach ($panes as $pane) {
				$pane_groups[] = array(
					'id' => $pane['id'],
					'icon' => $pane['icon'],
					'name' => $pane['name'],
					'method' => $pane['method'],
					'params' => array()
				);
			}
		/*
		} else if (Jaws_Error::IsError($pane_status)) {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
		*/
		return $pane_groups;
    }

     /*
     * Define array of panes for this gadget's account controls.
     * (i.e. Store gadget has "My Products" and "Saved Products" panes) 
     * 
     * $panes array structured as follows:
     * 'AdminHTML->MethodName' => 'Pane Title'
     * 
     * @access public
     * @return array of pane names
     */
    function GetUserAccountPanesInfo($groups = array())
    {		
		$panes = array();
		/*
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		//if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'OwnEcommerce')) {
		if (!empty($site_ssl_url) && !empty($payment_gateway) && !empty($merchant_id) && !empty($merchant_key)) {
		*/
			foreach ($groups as $group) {
				if (
					isset($group['group_name']) && 
					($group['group_name'] == strtolower($this->_Name).'_owners' || $group['group_name'] == strtolower($this->_Name).'_users') && 
					($group['group_status'] == 'active' || $group['group_status'] == 'founder' || $group['group_status'] == 'admin')
				) {
					// FIXME: Add translation string for this
					$panes[] = array(
						'name' => 'Orders',
						'id' => 'Orders',
						'method' => 'User'.ucfirst(str_replace('_','',str_replace(array('_owners','_users'),'',$group['group_name']))),
						'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png'
					);
				}
			}
		//}
		return $panes;
	}
		
    /**
     * Display the pane content.
     *
     * @param int  $user  user id
     * @access public
     * @return string
     */
    function UserEcommerce($user)
    {			
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('users.html');
		$tpl->SetBlock('pane');
		$tpl->SetVariable('title', $this->_Name);
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetBlock('pane/pane_item');
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetVariable('pane', 'UserEcommerce');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
        
		$stpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $stpl->Load('users.html');
        $stpl->SetBlock('UserEcommerceSubscriptions');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$page = $usersHTML->ShowComments('Ecommerce', false, null, 'Ecommerce', false);
		if (!Jaws_Error::IsError($page)) {
			$stpl->SetVariable('element', $page);
		} else {
			$stpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}
        $stpl->ParseBlock('UserEcommerceSubscriptions');
		
		$tpl->SetVariable('gadget_pane', $stpl->Get());
		$tpl->ParseBlock('pane/pane_item');
		$tpl->ParseBlock('pane');
        return $tpl->Get();
	}	
	
    /**
     * Account Admin
     *
     * @access public
     * @return string
     */
    function account_Admin()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
		return $gadget_admin->Admin(true);
    }
	

    /**
     * Account form
     *
     * @access public
     * @return string
     */
    function account_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
		$page = $gadget_admin->form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Ecommerce');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account form_post
     *
     * @access public
     * @return string
     */
    function account_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
		$page = $gadget_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Ecommerce'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_view()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
		$page = $gadget_admin->view(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Ecommerce');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account A_form
     *
     * @access public
     * @return string
     */
    function account_A_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
		$page = $gadget_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Ecommerce');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account A_form_post
     *
     * @access public
     * @return string
     */
    function account_A_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
		$page = $gadget_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Ecommerce'));
		return $output_html;
    }

    /**
     * Account ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function account_ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('Ecommerce', 'OwnEcommerce', true);
    }

    /**
     * sets GB root with DPATH
     *
     * @access public
     * @return javascript string
     */
    function account_SetGBRoot()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}

	  
	  /* In case the XML API contains multiple open tags
		 with the same value, then invoke this function and
		 perform a foreach on the resultant array.
		 This takes care of cases when there is only one unique tag
		 or multiple tags.
		 Examples of this are "anonymous-address", "merchant-code-string"
		 from the merchant-calculations-callback API
	  */
	  function get_arr_result($child_node) {
		$result = array();
		if(isset($child_node)) {
		  if($this->is_associative_array($child_node)) {
			$result[] = $child_node;
		  }
		  else {
			foreach($child_node as $curr_node){
			  $result[] = $curr_node;
			}
		  }
		}
		return $result;
	  }

	  /* Returns true if a given variable represents an associative array */
	  function is_associative_array( $var ) {
		return is_array( $var ) && !is_numeric( implode( '', array_keys( $var ) ) );
	  }
}
