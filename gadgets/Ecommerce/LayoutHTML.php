<?php
/**
 * Ecommerce Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $actions = array();
        $model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
        
		$actions['ShowCartButton'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('ECOMMERCE_LAYOUT_CARTBUTTON_TITLE'),
			'desc' => _t('ECOMMERCE_LAYOUT_CARTBUTTON_DESCRIPTION')
		);

		$actions['ShowSmallCartButton'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('ECOMMERCE_LAYOUT_SMALLCARTBUTTON_TITLE'),
			'desc' => _t('ECOMMERCE_LAYOUT_SMALLCARTBUTTON_DESCRIPTION')
		);

		$actions['ShowCartLink'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('ECOMMERCE_LAYOUT_CARTLINK_TITLE'),
			'desc' => _t('ECOMMERCE_LAYOUT_CARTLINK_DESCRIPTION')
		);
		
        return $actions;
    }

	/**
     * Displays Add to Cart HTML for given Product ID (Store Gadget).
     *
     * @access 	public
     * @return 	string 	HTML template content
     */
    function Display($pid = null, $embedded = false, $referer = null, $single = false, $type = 'cartbutton')
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');
		$checkout_terms = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/checkout_terms');
		
		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
        
		// Gadget requires HTTPS?
		$require_https = $GLOBALS['app']->Registry->Get('/gadgets/require_https');
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$gadget_requires_https = false;
		$full_url = $GLOBALS['app']->GetFullURL();
		if (!empty($site_ssl_url) && (in_array(strtolower('Ecommerce'), explode(',', strtolower($require_https))) || strtolower('Ecommerce') == strtolower($require_https))) {
			$gadget_requires_https = true;
			if (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') {
				if (!empty($full_url)) {
					/*
					// Update Menus and gadgets with a menu
					$menu_gadgets = array('Store', 'Ecommerce', 'Maps', 'Menu','CustomPage','Properties', 'Forms', 'Jms', 'Layout', 'Settings', 'FlashGallery', 'Languages', 'Users', 'Social', 'Search', 'Tms');
					//foreach($menu_gadgets as $menu_gadget) {
						if (!$this->deleteSyntactsCacheFile($menu_gadgets)) {
							//Jaws_Error::Fatal("Cache file couldn't be deleted");
						}
					//}
					*/
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					Jaws_Header::Location('https://'. str_replace(array('http://','https://'), '', str_replace($GLOBALS['app']->GetSiteURL('', false, 'http'), $GLOBALS['app']->GetSiteURL('', false, 'https'), $full_url)));
					//header('Location: '. str_replace('http://', 'https://', $full_url));
				}
			}
		}
		$GLOBALS['app']->Registry->LoadFile('Ecommerce');
		$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
		$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL($gadget_requires_https) . "/include/Jaws/Ajax/Response.js");
		$GLOBALS['app']->Layout->AddScriptLink("index.php?gadget=Ecommerce&amp;action=Ajax&amp;client=all&amp;stub=EcommerceAjax");
		$GLOBALS['app']->Layout->AddScriptLink("index.php?gadget=Ecommerce&amp;action=AjaxCommonFiles");
		$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL($gadget_requires_https) . "/gadgets/Ecommerce/resources/client_script.js");
		$GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->GetJawsURL($gadget_requires_https) . '/gadgets/Ecommerce/resources/style.css', 'stylesheet', 'text/css');
		// for boxover on date highlighting
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $ecommerceModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		
		if (trim($type) != '') {
			$logged = false;
			// User logged? Get details
			$shipfreight = '';
			$paymentmethod = '';
			$cc_creditcardtype = '';
			$cc_acct = '';
			$cc_expdate_month = '';
			$cc_expdate_year = '';
			$cc_cvv2 = '';
			$firstname = '';
			$lastname = '';
			$customer_shipfirstname = '';
			$customer_shiplastname = '';
			$customer_shipaddress = '';
			$customer_shipaddress2 = '';
			$customer_shipcity = '';
			$customer_shipregion = '';
			$customer_shippostal = '';
			$customer_shipcountry = "US";
			$customer_firstname = '';
			$customer_middlename = '';
			$customer_lastname = '';
			$customer_suffix = '';
			$customer_address = '';
			$customer_address2 = '';
			$customer_city = '';
			$customer_region = '';
			$customer_postal = '';
			$customer_email = '';
			$customer_phone = '';
			$sales_code = '';
			if ($GLOBALS['app']->Session->Logged()) {
				$logged = true;
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
				$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
				if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
					if (isset($userInfo['fname']) && !empty($userInfo['fname']) && isset($userInfo['lname']) && !empty($userInfo['lname'])) {
						$firstname = $userInfo['fname'];
						$lastname = $userInfo['lname'];
					} else if (isset($userInfo['nickname']) && !empty($userInfo['nickname'])) {
						$nameparts = explode(" ", $userInfo['nickname']);
						if (isset($nameparts[0]) && !empty($nameparts[0]) && isset($nameparts[1]) && !empty($nameparts[1])) {
							$firstname = strtolower(str_replace('.','',$nameparts[0]));
							$lastname = '';
							$startpart = 1;
							if ($firstname == 'mr' || $firstname == 'mrs' || $firstname == 'ms' || $firstname == 'dr') {
								$firstname = $nameparts[1];
								$startpart = 2;
							}
							for ($s=$startpart; $s<6; $s++) {
								$lastname .= (isset($nameparts[$s]) ? str_replace('.','',$nameparts[$s]) : '');
							}
						}
					}
					$firstname = (!empty($firstname) ? ucfirst($firstname) : '');
					$lastname = (!empty($lastname) ? $lastname : '');
					$customer_shipfirstname = $firstname;
					$customer_shiplastname = $lastname;
					$customer_shipaddress = $userInfo['address'];
					$customer_shipaddress2 = $userInfo['address2'];
					$customer_shipcity = $userInfo['city'];
					$customer_shipregion = $userInfo['region'];
					if (strlen($customer_shipregion) == 2) {
						$customer_shipregion = strtoupper($customer_shipregion);
					} else {
						switch(strtolower($customer_shipregion)) {
							case "alabama": 
								$customer_shipregion = 'AL';
								break;
							case "alaska": 
								$customer_shipregion = 'AK';
								break;
							case "arizona": 
								$customer_shipregion = 'AZ';
								break;
							case "arkansas": 
								$customer_shipregion = 'AR';
								break;
							case "california": 
								$customer_shipregion = 'CA';
								break;
							case "colorado": 
								$customer_shipregion = 'CO';
								break;
							case "connecticut": 
								$customer_shipregion = 'CT';
								break;
							case "delaware": 
								$customer_shipregion = 'DE';
								break;
							case "florida": 
								$customer_shipregion = 'FL';
								break;
							case "georgia": 
								$customer_shipregion = 'GA';
								break;
							case "hawaii": 
								$customer_shipregion = 'HI';
								break;
							case "idaho": 
								$customer_shipregion = 'ID';
								break;
							case "illinois": 
								$customer_shipregion = 'IL';
								break;
							case "indiana": 
								$customer_shipregion = 'IN';
								break;
							case "iowa": 
								$customer_shipregion = 'IA';
								break;
							case "kansas": 
								$customer_shipregion = 'KS';
								break;
							case "kentucky": 
								$customer_shipregion = 'KY';
								break;
							case "louisiana": 
								$customer_shipregion = 'LA';
								break;
							case "maine": 
								$customer_shipregion = 'ME';
								break;
							case "maryland": 
								$customer_shipregion = 'MD';
								break;
							case "massachusetts": 
								$customer_shipregion = 'MA';
								break;
							case "michigan": 
								$customer_shipregion = 'MI';
								break;
							case "minnesota": 
								$customer_shipregion = 'MN';
								break;
							case "mississippi": 
								$customer_shipregion = 'MS';
								break;
							case "missouri": 
								$customer_shipregion = 'MO';
								break;
							case "montana": 
								$customer_shipregion = 'MT';
								break;
							case "nebraska": 
								$customer_shipregion = 'NE';
								break;
							case "nevada": 
								$customer_shipregion = 'NV';
								break;
							case "new hampshire": 
								$customer_shipregion = 'NH';
								break;
							case "new jersey": 
								$customer_shipregion = 'NJ';
								break;
							case "new mexico": 
								$customer_shipregion = 'NM';
								break;
							case "new york": 
								$customer_shipregion = 'NY';
								break;
							case "north carolina": 
								$customer_shipregion = 'NC';
								break;
							case "north dakota": 
								$customer_shipregion = 'ND';
								break;
							case "ohio": 
								$customer_shipregion = 'OH';
								break;
							case "oklahoma": 
								$customer_shipregion = 'OK';
								break;
							case "oregon": 
								$customer_shipregion = 'OR';
								break;
							case "pennsylvania": 
								$customer_shipregion = 'PA';
								break;
							case "rhode island": 
								$customer_shipregion = 'RI';
								break;
							case "south carolina": 
								$customer_shipregion = 'SC';
								break;
							case "south dakota": 
								$customer_shipregion = 'SD';
								break;
							case "tennessee": 
								$customer_shipregion = 'TN';
								break;
							case "texas": 
								$customer_shipregion = 'TX';
								break;
							case "utah": 
								$customer_shipregion = 'UT';
								break;
							case "vermont": 
								$customer_shipregion = 'VT';
								break;
							case "virginia": 
								$customer_shipregion = 'VA';
								break;
							case "washington": 
								$customer_shipregion = 'WA';
								break;
							case "washington d.c.": 
								$customer_shipregion = 'DC';
								break;
							case "west virginia": 
								$customer_shipregion = 'WV';
								break;
							case "wisconsin": 
								$customer_shipregion = 'WI';
								break;
							case "wyoming": 
								$customer_shipregion = 'WY';
								break;
							default:
								$customer_shipregion = '';
						}
					}
					$customer_shippostal = $userInfo['postal'];
					$customer_shipcountry = "US";
					$customer_firstname = $firstname;
					$customer_lastname = $lastname;
					$customer_address = $userInfo['address'];
					$customer_address2 = $userInfo['address2'];
					$customer_city = $userInfo['city'];
					$customer_region = $userInfo['region'];
					if (strlen($customer_region) == 2) {
						$customer_region = strtoupper($customer_region);
					} else {
						switch(strtolower($customer_region)) {
							case "alabama": 
								$customer_region = 'AL';
								break;
							case "alaska": 
								$customer_region = 'AK';
								break;
							case "arizona": 
								$customer_region = 'AZ';
								break;
							case "arkansas": 
								$customer_region = 'AR';
								break;
							case "california": 
								$customer_region = 'CA';
								break;
							case "colorado": 
								$customer_region = 'CO';
								break;
							case "connecticut": 
								$customer_region = 'CT';
								break;
							case "delaware": 
								$customer_region = 'DE';
								break;
							case "florida": 
								$customer_region = 'FL';
								break;
							case "georgia": 
								$customer_region = 'GA';
								break;
							case "hawaii": 
								$customer_region = 'HI';
								break;
							case "idaho": 
								$customer_region = 'ID';
								break;
							case "illinois": 
								$customer_region = 'IL';
								break;
							case "indiana": 
								$customer_region = 'IN';
								break;
							case "iowa": 
								$customer_region = 'IA';
								break;
							case "kansas": 
								$customer_region = 'KS';
								break;
							case "kentucky": 
								$customer_region = 'KY';
								break;
							case "louisiana": 
								$customer_region = 'LA';
								break;
							case "maine": 
								$customer_region = 'ME';
								break;
							case "maryland": 
								$customer_region = 'MD';
								break;
							case "massachusetts": 
								$customer_region = 'MA';
								break;
							case "michigan": 
								$customer_region = 'MI';
								break;
							case "minnesota": 
								$customer_region = 'MN';
								break;
							case "mississippi": 
								$customer_region = 'MS';
								break;
							case "missouri": 
								$customer_region = 'MO';
								break;
							case "montana": 
								$customer_region = 'MT';
								break;
							case "nebraska": 
								$customer_region = 'NE';
								break;
							case "nevada": 
								$customer_region = 'NV';
								break;
							case "new hampshire": 
								$customer_region = 'NH';
								break;
							case "new jersey": 
								$customer_region = 'NJ';
								break;
							case "new mexico": 
								$customer_region = 'NM';
								break;
							case "new york": 
								$customer_region = 'NY';
								break;
							case "north carolina": 
								$customer_region = 'NC';
								break;
							case "north dakota": 
								$customer_region = 'ND';
								break;
							case "ohio": 
								$customer_region = 'OH';
								break;
							case "oklahoma": 
								$customer_region = 'OK';
								break;
							case "oregon": 
								$customer_region = 'OR';
								break;
							case "pennsylvania": 
								$customer_region = 'PA';
								break;
							case "rhode island": 
								$customer_region = 'RI';
								break;
							case "south carolina": 
								$customer_region = 'SC';
								break;
							case "south dakota": 
								$customer_region = 'SD';
								break;
							case "tennessee": 
								$customer_region = 'TN';
								break;
							case "texas": 
								$customer_region = 'TX';
								break;
							case "utah": 
								$customer_region = 'UT';
								break;
							case "vermont": 
								$customer_region = 'VT';
								break;
							case "virginia": 
								$customer_region = 'VA';
								break;
							case "washington": 
								$customer_region = 'WA';
								break;
							case "washington d.c.": 
								$customer_region = 'DC';
								break;
							case "west virginia": 
								$customer_region = 'WV';
								break;
							case "wisconsin": 
								$customer_region = 'WI';
								break;
							case "wyoming": 
								$customer_region = 'WY';
								break;
							default:
								$customer_region = '';
						}
					}
					$customer_postal = $userInfo['postal'];
					$customer_email = $userInfo['email'];
					$customer_phone = $userInfo['phone'];
				}
			}
			
            if ($cart_data = $GLOBALS['app']->Session->PopSimpleResponse('Ecommerce.Cart.Data')) {
				if (isset($cart_data['paymentmethod']) && !empty($cart_data['paymentmethod'])) {
					$paymentmethod = $cart_data['paymentmethod'];
				}
				if (isset($cart_data['shipfreight']) && !empty($cart_data['shipfreight'])) {
					$shipfreight = $cart_data['shipfreight'];
				}
				if (isset($cart_data['cc_creditcardtype']) && !empty($cart_data['cc_creditcardtype'])) {
					$cc_creditcardtype = $cart_data['cc_creditcardtype'];
				}
				if (isset($cart_data['cc_acct']) && !empty($cart_data['cc_acct'])) {
					$cc_acct = $cart_data['cc_acct'];
				}
				if (isset($cart_data['cc_expdate_month']) && !empty($cart_data['cc_expdate_month'])) {
					$cc_expdate_month = $cart_data['cc_expdate_month'];
				}
				if (isset($cart_data['cc_expdate_year']) && !empty($cart_data['cc_expdate_year'])) {
					$cc_expdate_year = $cart_data['cc_expdate_year'];
				}
				if (isset($cart_data['cc_cvv2']) && !empty($cart_data['cc_cvv2'])) {
					$cc_cvv2 = $cart_data['cc_cvv2'];
				}
				if (isset($cart_data['sales_code']) && !empty($cart_data['sales_code'])) {
					$sales_code = $cart_data['sales_code'];
				}
				if (empty($customer_shipfirstname)) {
					$customer_shipfirstname = $cart_data['customer_shipfirstname'];
				}
				if (empty($customer_shiplastname)) {
					$customer_shiplastname = $cart_data['customer_shiplastname'];
				}
				if (empty($customer_shipaddress)) {
					$customer_shipaddress = $cart_data['customer_shipaddress'];
				}
				if (empty($customer_shipcity)) {
					$customer_shipcity = $cart_data['customer_shipcity'];
				}
				if (empty($customer_shipregion)) {
					$customer_shipregion = $cart_data['customer_shipregion'];
				}
				if (empty($customer_shippostal)) {
					$customer_shippostal = $cart_data['customer_shippostal'];
				}
				if (empty($customer_shipcountry)) {
					$customer_shipcountry = $cart_data['customer_shipcountry'];
				}
				if (empty($customer_shipaddress2)) {
					$customer_shipaddress2 = $cart_data['customer_shipaddress2'];
				}
				if (empty($customer_firstname)) {
					$customer_firstname = $cart_data['customer_firstname'];
				}
				if (empty($customer_middlename)) {
					$customer_middlename = $cart_data['customer_middlename'];
				}
				if (empty($customer_lastname)) {
					$customer_lastname = $cart_data['customer_lastname'];
				}
				if (empty($customer_suffix)) {
					$customer_suffix = $cart_data['customer_suffix'];
				}
				if (empty($customer_address)) {
					$customer_address = $cart_data['customer_address'];
				}
				if (empty($customer_address2)) {
					$customer_address2 = $cart_data['customer_address2'];
				}
				if (empty($customer_city)) {
					$customer_city = $cart_data['customer_city'];
				}
				if (empty($customer_region)) {
					$customer_region = $cart_data['customer_region'];
				}
				if (empty($customer_postal)) {
					$customer_postal = $cart_data['customer_postal'];
				}
				if (empty($customer_country)) {
					$customer_country = $cart_data['customer_country'];
				}
            }
			$cart_html = '';
			
			$title = 'All';
			if (!is_null($pid)) {
				$page = $model->GetProduct($pid);
				if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
					$pid = $page['id'];
					$title = $page['title'];
				} else if (Jaws_Error::IsError($page)) {
					return $page;
				}
			} else {
				$pid = 'all';
			}
			
			$tpl =& new Jaws_Template('gadgets/Ecommerce/templates/');
			$tpl->Load('normal.html');

			$tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', $type.'_' . $pid . '_');
			$tpl->SetVariable('layout_title', $title);

			$tpl->SetBlock('layout/'.$type);
			$tpl->SetVariable('id', $pid);
			//$tpl->SetVariable('base_url', JAWS_DPATH);
			
			$showCouponCode = false;
			
			// Get Shippings
			$showShipping = false;
			//$shippings = $ecommerceModel->GetShippings(null, 'sort_order', 'ASC', false, (isset($page['ownerid']) && !empty($page['ownerid']) ? $page['ownerid'] : 0));
			$shippings = $ecommerceModel->GetShippings(null, 'sort_order', 'ASC', false, 0, 'Y');
			if (!count($shippings) <= 0) {
				$showShipping = true;
			} else if (empty($shipfreight)) {
				$shipfreight = '0';
			}
			
			$admin_email = $GLOBALS['app']->Registry->Get('/network/site_email');
			
			// TODO: Support other Payment Gateways
			if ($payment_gateway == 'ManualCreditCard' || !empty($merchant_id)) {					
				//$GLOBALS['app']->Layout->AddHeadOther('<script id="googlecart-script" type="text/javascript" post-cart-to-sandbox="true" src="https://checkout.google.com/seller/gsc/v2_2/cart.js?mid='.trim($merchant_id).'" close-cart-when-click-away="false" currency="USD"></script>');
				$GLOBALS['app']->Layout->AddHeadOther('<script id="googlecart-script" type="text/javascript" src="'.$GLOBALS['app']->GetJawsURL($gadget_requires_https).'/libraries/simplecartjs/simpleCart.js"></script>');
				$GLOBALS['app']->Layout->AddHeadOther('<script type="text/javascript">
					simpleCart({

						// "div" or "table" - builds the cart as a table or collection of divs
						cartStyle: "div",
						
						// how simpleCart should checkout, see the checkout reference for more info
						checkout: {
							type: "SendForm",
							email: "'.$admin_email.'", 
							url: "index.php?gadget=Ecommerce&action=PostCart",
							method: "POST",
							success: "index.php?gadget=Ecommerce&action='.($payment_gateway == 'ManualCreditCard' ? 'Manual' : $payment_gateway).'Response",
							cancel: "index.php?products/all.html",
							extra_data: {
							}
						},
						
						// set the currency, see the currency reference for more info
						currency: "USD",
						
						// collection of arbitrary data you may want to store
						// with the cart, such as customer info
						data: {},
						
						// set the cart langauge (may be used for checkout)
						language: "english-us",
						
						// array of item fields that will not be sent to checkout
						excludeFromCheckout: [],
						
						// custom function to add shipping cost
						shippingCustom: null,
						
						// flat rate shipping option
						shippingFlatRate: 0,
						
						// added shipping based on this value multiplied by the cart quantity
						shippingQuantityRate: 0,
						
						// added shipping based on this value multiplied by the cart subtotal
						shippingTotalRate: 0,
						
						// tax rate applied to cart subtotal
						taxRate: 0,
						
						// true if tax should be applied to shipping
						taxShipping: false
					});
					simpleCart.bind( "beforeAdd" , function( item ){
						googlecartBeforeAdd(item);
					});
					simpleCart.bind( "afterAdd" , function( item, is_new){
						googlecartAfterAdd(item, is_new);
					});
					simpleCart.bind( "beforeRemove" , function( item ){
						googlecartBeforeRemove(item);
					});
					simpleCart.bind( "ready" , function( ){
						googlecartWidgetLoaded();
					});
					simpleCart.bind( "update" , function( items ){
						googlecartWidgetUpdated();
					});
					simpleCart.bind( "beforeCheckout" , function( items ){
						googlecartCustomCheckout(items);
					});
					document.observe("dom:loaded", function(){
						var dock = "<div id=\"ecommerce-cart-dock-holder\" class=\"ot-container style-slick ecommerce-cart-dock-holder\">";
						dock += "<div id=\"ecommerce-cart-dock\" class=\"opentip ecommerce-cart-dock\">";
						dock += "<span class=\"simpleCart_quantity\"></span> items - <span class=\"simpleCart_total\"></span>"; 
						dock += "<a href=\"javascript:;\" class=\"simpleCart_checkout\">Checkout</a>";
						dock += "</div>";
						dock += "<div style=\"display: none;\" class=\"opentip ym-vlist simpleCart_items\"></div>";
						dock += "</div>";
						
						$(document.body).insert(dock);
						$("ecommerce-cart-dock").observe("click", function(event){
							this.next(".simpleCart_items").toggle();
						});
					});
					Event.observe(window, "resize", function(){
						$$(".simpleCart_items")[0].setStyle({
							maxHeight: (document.viewport.getHeight()-(27))+"px"
						});
					});
					</script>');
				if ($type == 'cartlink' || $type == 'smallcartbutton') {
					$GLOBALS['app']->Layout->AddHeadOther('<!--[if lte IE 7]><style>html>body .ui-window .content { border-top: 1px solid #FFF;}</style><![endif]-->');
					$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
					$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
					$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
					$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
				}
							
				$customcheckoutfields = array();
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onLoadCustomCheckoutFields', (isset($page['id']) && !empty($page['id']) ? $page['id'] : null));
				if (!Jaws_Error::IsError($res) && isset($res['fields']) && is_array($res['fields']) && !count($res['fields']) <= 0) {
					$customcheckoutfields = $res['fields'];
				}
	
				$stpl =& new Jaws_Template('gadgets/Ecommerce/templates/');
				$stpl->Load('SimpleCart.html');
				
				$stpl->SetBlock('googlecart');
				
				$stpl->SetVariable('post_cart_error', _t('ECOMMERCE_ERROR_ORDER_NOT_ADDED'));
				$stpl->SetVariable('tax_shipping_text', _t('ECOMMERCE_CART_TAX_SHIPPING_NEXT'));
				$stpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL('', false, ($gadget_requires_https === true ? 'https' : 'http')));
				$stpl->SetVariable('urlencoded_full_url', urlencode($full_url));
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL($gadget_requires_https));
				
				$stpl->SetVariable('shipfreight', $shipfreight);
				$stpl->SetVariable('paymentmethod', $paymentmethod);
				$stpl->SetVariable('customer_shipfirstname', str_replace(array('"', "'"), '', $customer_shipfirstname));
				$stpl->SetVariable('customer_shiplastname', str_replace(array('"', "'"), '', $customer_shiplastname));
				$stpl->SetVariable('customer_shipaddress', str_replace(array('"', "'"), '', $customer_shipaddress));
				$stpl->SetVariable('customer_shipaddress2', str_replace(array('"', "'"), '', $customer_shipaddress2));
				$stpl->SetVariable('customer_shipcity', str_replace(array('"', "'"), '', $customer_shipcity));
				$stpl->SetVariable('customer_shipregion', str_replace(array('"', "'"), '', $customer_shipregion));
				$stpl->SetVariable('customer_shippostal', str_replace(array('"', "'"), '', $customer_shippostal));
				$stpl->SetVariable('customer_shipcountry', str_replace(array('"', "'"), '', $customer_shipcountry));
				$stpl->SetVariable('customer_firstname', str_replace(array('"', "'"), '', $customer_firstname));
				$stpl->SetVariable('customer_lastname', str_replace(array('"', "'"), '', $customer_lastname));
				$stpl->SetVariable('customer_address', str_replace(array('"', "'"), '', $customer_address));
				$stpl->SetVariable('customer_address2', str_replace(array('"', "'"), '', $customer_address2));
				$stpl->SetVariable('customer_city', str_replace(array('"', "'"), '', $customer_city));
				$stpl->SetVariable('customer_region', str_replace(array('"', "'"), '', $customer_region));
				$stpl->SetVariable('customer_postal', str_replace(array('"', "'"), '', $customer_postal));
				$stpl->SetVariable('customer_country', str_replace(array('"', "'"), '', $customer_country));
				$stpl->SetVariable('customer_phone', str_replace(array('"', "'"), '', $customer_phone));
				$stpl->SetVariable('sales_code', str_replace(array('"', "'"), '', $sales_code));
				
				$stpl->SetVariable('date_year', (int)date('Y'));
				
				if ($payment_gateway != 'GoogleCheckout') {
					$stpl->SetBlock('googlecart/list');
					$stpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL('', false, ($gadget_requires_https === true ? 'https' : 'http')));
					$stpl->SetVariable('urlencoded_full_url', urlencode($full_url));
					$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL($gadget_requires_https));
					$stpl->ParseBlock('googlecart/list');
					
					$stpl->SetBlock('googlecart/shipping');
					$stpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL('', false, ($gadget_requires_https === true ? 'https' : 'http')));
					$stpl->SetVariable('urlencoded_full_url', urlencode($full_url));
					$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL($gadget_requires_https));
					
					if ($showShipping === true) {
						$stpl->SetBlock('googlecart/shipping/show');
						$stpl->ParseBlock('googlecart/shipping/show');
					} else {
						$stpl->SetBlock('googlecart/shipping/hide');
						$stpl->ParseBlock('googlecart/shipping/hide');
					}
					$stpl->ParseBlock('googlecart/shipping');
				}
				
				$stpl->SetBlock('googlecart/credit_card');
				$stpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL('', false, ($gadget_requires_https === true ? 'https' : 'http')));
				$stpl->SetVariable('urlencoded_full_url', urlencode($full_url));
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL($gadget_requires_https));
				
				if ($payment_gateway == 'ManualCreditCard' || $payment_gateway == 'PayPal') {
					$stpl->SetBlock('googlecart/credit_card/card_info');
					$stpl->SetVariable('date_year', (int)date('Y'));
					
					for ($y=(((int)date('Y'))+20);$y>((int)date('Y'))-1;$y--) {
						$year_select = '';
						if ($y == ((int)date('Y')+1)) {
							$year_select = ' selected=\"selected\"';
						}
						$stpl->SetBlock('googlecart/credit_card/card_info/year_select');
						$stpl->SetVariable('year', $y);
						$stpl->SetVariable('year_select', $year_select);
						$stpl->ParseBlock('googlecart/credit_card/card_info/year_select');
					}
					
					if ($payment_gateway == 'PayPal') {
						$stpl->SetBlock('googlecart/credit_card/card_info/paymentselector');
						$stpl->SetVariable('option_value', 'PayPal');
						$stpl->SetVariable('option_name', 'PayPal');
						$stpl->ParseBlock('googlecart/credit_card/card_info/paymentselector');
					}
					$stpl->ParseBlock('googlecart/credit_card/card_info');
				} else {
					$stpl->SetBlock('googlecart/credit_card/paymenthide');
					$stpl->SetVariable('paymentmethod', $payment_gateway);
					$stpl->ParseBlock('googlecart/credit_card/paymenthide');
				}
				
				if (!count($customcheckoutfields) <= 0) {	
					$stpl->SetBlock('googlecart/credit_card/customfields');
					foreach ($customcheckoutfields as $sk) {
						$sv = '';
						if (isset($cart_data['customcheckoutfields'][$sk])) {
							$sv = $cart_data['customcheckoutfields'][$sk];
							// Decrypt value
							if (!empty($sv)) {
								$sv = $JCrypt->rsa->decrypt($sv, $JCrypt->pvt_key);
								if (Jaws_Error::isError($sv)) {
									$sv = '';
								}
							}
						}
						$custom_safe_name = str_replace(' ', '_', $sk);
						$stpl->SetBlock('googlecart/credit_card/customfields/customfield');
						$stpl->SetVariable('custom_safe_name', $custom_safe_name);
						$stpl->SetVariable('custom_name', $sk);
						$stpl->SetVariable('custom_value', $sv);
						$stpl->ParseBlock('googlecart/credit_card/customfields/customfield');
					}
					$stpl->ParseBlock('googlecart/credit_card/customfields');
				}

				$stpl->SetBlock('googlecart/credit_card/coupon_code');
				$stpl->ParseBlock('googlecart/credit_card/coupon_code');
				
				$stpl->ParseBlock('googlecart/credit_card');
				
				if ($payment_gateway == 'Fake') {
					$stpl->SetBlock('googlecart/fake_gateway');
					$stpl->ParseBlock('googlecart/fake_gateway');
				}
				
				$stpl->ParseBlock('googlecart');
				
				//$google_script = str_replace("\r\n", '', $stpl->Get());
				//$google_script = str_replace("\n", '', $google_script);
				//$GLOBALS['app']->Layout->AddHeadOther($google_script);
				$GLOBALS['app']->Layout->AddHeadOther($stpl->Get());
			}
			
			if (isset($page) && $pid != 'all' && !Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
				if (
					$page['inventory'] == 'N' || $page['outstockbuy'] == 'Y' || 
					($page['inventory'] == 'Y' && (isset($page['instock']) && !empty($page['instock']) && 
					$page['instock'] > 0 && ($page['lowstock'] < $page['instock'])))
				) {
					
					// TODO: Support other Payment Gateways
					if ($payment_gateway == 'ManualCreditCard' || !empty($merchant_id)) {					
						
						$GLOBALS['app']->Registry->LoadFile('Store');
						$GLOBALS['app']->Translate->LoadTranslation('Store', JAWS_GADGET);
						$product_id = '<input type="hidden" class="item_productid product-attr-id" value="'.$page['id'].'" />';
										
						$product_image = '';
						if (isset($page['image']) && !empty($page['image'])) {
							$page['image'] = $xss->parse(strip_tags($page['image']));
							if (substr(strtolower($page['image']), 0, 4) == "http") {
								$product_image = $page['image'];
								if (substr(strtolower($page['image']), 0, 7) == "http://") {
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
											$lg_image_src = 'https://'.$img_src;
											break;
										}
									}
								}
								if (strpos(strtolower($product_image), 'data/files/') !== false) {
									$product_image = 'image_thumb.php?uri='.urlencode($product_image);
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($page['image']);
								$medium = Jaws_Image::GetMediumPath($page['image']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$product_image = $GLOBALS['app']->getDataURL('files'.$thumb, true, false, $gadget_requires_https);
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$product_image = $GLOBALS['app']->getDataURL('files'.$medium, true, false, $gadget_requires_https);
								} else if (file_exists(JAWS_DATA . 'files'.$page['image'])) {
									$product_image = $GLOBALS['app']->getDataURL('files'.$page['image'], true, false, $gadget_requires_https);
								}
							}
							$product_image = (!empty($product_image) ? '<input type="hidden" class="item_image product-image" value="'.urlencode('<img class="item-image-holder" src="'.$product_image.'" />').'" />' : '');
						}
						$product_title = $xss->filter(strip_tags($page['title']));
						
						$price = 0;
						if (!empty($page['price']) && ($page['price'] > 0)) {
							$price = number_format($page['price'], 2, '.', '');
						}
						// TODO: Add AJAX coupon code verifier. On coupon code input,
						// disable Add to Cart button, verify code, show result, add
						// product-attr-sale that updates price if necessary and re-enable button
						// sales
						$now = $GLOBALS['db']->Date();
						$sale_min_qty = '';
						$sale_price = $price;
						$sale_string = number_format($price, 2, '.', ',');
						if (isset($page['sales']) && !empty($page['sales'])) {
							$propSales = explode(',', $page['sales']);
							foreach($propSales as $propSale) {		            
								$saleParent = $model->GetSale((int)$propSale);
								if (!Jaws_Error::IsError($saleParent)) {
									if (
										empty($saleParent['coupon_code']) && $saleParent['active'] == 'Y' && 
										($now > $saleParent['startdate'] && $now < $saleParent['enddate'])
									) {
										if ($saleParent['discount_amount'] > 0) {
											$sale_price = number_format($sale_price - number_format($saleParent['discount_amount'], 2, '.', ','), 2, '.', ',');
										} else if ($saleParent['discount_percent'] > 0) {
											$sale_price = number_format($sale_price - ($sale_price * ($saleParent['discount_percent'] * .01)), 2, '.', ',');
										} else if ($saleParent['discount_newprice'] > 0) {
											$sale_price = number_format($saleParent['discount_newprice'], 2, '.', ',');
										}
									}
									if ($saleParent['min_qty'] > 1) {
										$sale_min_qty = '<span><small>'._t('STORE_SALE_MIN_QTY_DISCLAIMER', $saleParent['min_qty']).'</small></span><br />';
									}
								}
							}
							$sale_string = ($sale_price > 0 ? $sale_price : 0.00);
						}
						$price_string = number_format($price, 2, '.', ',');
						$product_price = ($sale_string != $price_string ? $sale_string : $price_string);

						$product_setup_fee = '';
						if (isset($page['setup_fee']) && $page['setup_fee'] > 0) {
							$product_setup_fee = number_format($page['setup_fee'], 2, '.', ',');
							$setup_price = ($product_setup_fee+$product_price);
							$setup_price = number_format($setup_price, 2, '.', ',');
							//$product_price = $setup_price;
							$product_setup_fee = '<span class="item_setupfee product-attr-setup-fee">'._t('STORE_SETUP_FEE').': $'.$product_setup_fee.'</span><br />';
						}
						
						$product_retail = '';
						if (isset($page['retail']) && $page['retail'] > 0) {
							$product_retail = '$'.number_format($page['retail'], 2, '.', ',');
							$product_retail = '<input type="hidden" class="item_retail product-attr-retail" value="'.$product_retail.'" />';
						}
						$product_unit = '';
						if (isset($page['unit']) && !empty($page['unit'])) {
							$product_unit = $xss->filter(strip_tags($page['unit']));
							$product_unit = '<span class="item_unit product-attr-unit">'.$product_unit.'</span>';
						}
						$product_recurring = '';
						if (isset($page['recurring']) && !empty($page['recurring']) && $page['recurring'] == 'Y') {
							$product_recurring = $page['recurring'];
							$product_recurring = '<span class="item_recurring product-attr-recurring">Is subscription: '.$product_recurring.'</span><br />';
						}
						$product_min_qty = '';
						if (isset($page['min_qty']) && !empty($page['min_qty']) && $page['min_qty'] > 0) {
							$product_min_qty = $page['min_qty'];
							$product_min_qty = '<b>Qty</b> <select class="item_quantity googlecart-quantity"><option value="'.$product_min_qty.'" selected="selected">'.$product_min_qty.'</option>';
							$instock = ($page['inventory'] == 'N' || $page['outstockbuy'] == 'Y' ? 999 : $page['instock']);
							for ($q = $page['min_qty']+1; $q < $instock; $q++) {
								$product_min_qty .= '<option value="'.$q.'">'.$q.'</option>';
							}
							$product_min_qty .= '</select> '.(($page['unit'] == '/ Day' || $page['unit'] == '/ Week' || $page['unit'] == '/ Month' || $page['unit'] == '/ Year') ? $page['unit'].'s' : $page['unit']).'<br />';
						}
						$product_max_qty = '';
						if ($page['inventory'] == 'Y' && isset($page['instock']) && !empty($page['instock']) && $page['instock'] > 0 && (($page['lowstock'] < $page['instock']) && $page['outstockbuy'] == 'N')) {
							$product_max_qty = $page['instock'];
							$product_unit = '<input type="hidden" class="googlecart-max-quantity" value="'.$product_max_qty.'" />';
						}
						
						$product_weight = '';
						//if (isset($page['weight']) && !empty($page['weight']) && $page['weight'] != 0) {
							$product_weight = $xss->filter(strip_tags($page['weight']))." lbs.";
							$product_weight = '<input type="hidden" class="item_weight product-weight" value="'.$product_weight.'" />';
						//}
						$product_code = '';
						if (isset($page['product_code']) && !empty($page['product_code'])) {
							$product_code = $xss->filter(strip_tags($page['product_code']));
							$product_code = '<input type="hidden" class="item_productcode product-attr-productcode" value="'.$product_code.'" />';
						}
						$product_sm_description = '';
						if (isset($page['sm_description']) && !empty($page['sm_description'])) {
							$product_sm_description = $xss->filter(strip_tags($page['sm_description']));
							$product_sm_description = '<input type="hidden" class="item_summary product-attr-summary" value="'.$product_sm_description.'" />';
						}
						$product_OwnerID = 0;
						if (isset($page['ownerid']) && $page['ownerid'] > 0) {
							$product_OwnerID = $page['ownerid'];
						}
						$product_OwnerID = '<input type="hidden" class="item_ownerid product-attr-owner-id" value="'.$product_OwnerID.'" />';
						$product_brand = '';
						if (!empty($page['brandid']) && ($page['brandid'] > 0)) {
							$brandParent = $model->GetBrand($page['brandid']);
							if (!Jaws_Error::IsError($brandParent)) {
								$product_brand = '<a href="'.$GLOBALS['app']->getSiteURL('/index.php?gadget=Store&action=Category&id=all&brand='.$brandParent['id'], false, ($gadget_requires_https === true ? 'https' : 'http')).'">'.$xss->filter(strip_tags($brandParent['title'])).'</a>';
								$product_brand = '<input type="hidden" class="item_brand product-attr-brand" value="'.$product_brand.'" />';
							}
						}
						
						$product_url = $GLOBALS['app']->getSiteURL("/index.php?gadget=Store&action=Product&id=".$page['id'], false, ($gadget_requires_https === true ? 'https' : 'http'));
						$product_redirect = '<input type="hidden" class="item_producturl product-url" value="'.$product_url.'" />';

						// attributes
						$attribute_Columns = array();
						$amenity = '';
						$hidden_selects = '';
						if (isset($page['attribute']) && !empty($page['attribute'])) {
							$amenityTypes = $model->GetAttributeTypes();
							if (!Jaws_Error::IsError($amenityTypes)) {
								$propAmenities = explode(',', $page['attribute']);
								foreach($amenityTypes as $amenityType) {		            
									$amenity_header = '';
									$amenity_footer = '';
									$amenity_body = '';
									foreach($propAmenities as $propAmenity) {		            
										$amenityParent = $model->GetAttribute((int)$propAmenity);
										if (!Jaws_Error::IsError($amenityParent) && $amenityType['id'] == $amenityParent['typeid']) {
											if (empty($amenity_header)) {
												// ******************************************************
												// this loops through the question array and then
												// creates an appropriate answer device
												// ******************************************************
												if ($amenityType['itype'] != "HiddenField") {
													//if ($amenityType['required'] == "Y") {
													//	$amenity .= '<b>'.$xss->filter($amenityType['title']).'</b> <i>(Required)</i>:<br />';
													//} else {
														$amenity_header .= '<div class="addtocart-options"><b>'.$xss->filter($amenityType['title']).'</b>:<br />';
													//}

													// dropdown
													if ($amenityType['itype'] == "SelectBox") {
														//$amenity_header .= '<select class="product-attr-'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" onchange="selected = this.options[this.selectedIndex].value.replace(/ /gi, \'\'); selected = selected.replace(/:/gi, \'-\'); if ($(\'select-\'+selected+\'-'.$page['id'].'\')){select_amount = parseFloat($(\'select-\'+selected+\'-'.$page['id'].'\').value); select_amount = number_format(select_amount, 2, \'.\', \',\'); changePrice(\''.$page['id'].'\', select_amount);};">';
														$amenity_header .= '<select name="select-attr-'.$amenityType['id'].'" class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'" onchange="changePrice('.$page['id'].', '.$amenityType['id'].');">';
														$amenity_footer .= '</select>';
														$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
													} else if ($amenityType['itype'] == "TextBox") {
														$amenity_header .= '<input type="text" class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'" size="20"><br />';
														$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
													} else if ($amenityType['itype'] == "TextArea") {
														$amenity_header .= '<textarea rows="3" class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'" cols="28"></textarea><br />';
														$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
													}
													$amenity_footer .= '</div><br />';
												}
											}
								
											$add_amount = 0;
											$attr_price = $product_price;
											if ($amenityParent['add_amount'] > 0) {
												$attr_price = ((number_format($amenityParent['add_amount'], 2, '.', ''))+$product_price);
												$attr_price = number_format($attr_price, 2, '.', '');
											} else if ($amenityParent['add_percent'] > 0) {
												$attr_price = ((($amenityParent['add_percent'] * .01) * ($product_price))+$product_price);
												$attr_price = number_format($attr_price, 2, '.', '');
											} else if ($amenityParent['newprice'] > 0) {
												$attr_price = number_format($amenityParent['newprice'], 2, '.', '');
											}
											if ($attr_price > $product_price) {
												$add_amount = number_format(($attr_price - $product_price), 2, '.', ',');
											} else {
												$add_amount = number_format(($product_price - $attr_price)*(-1), 2, '.', ',');
											}
									
											if ($amenityType['itype'] == 'HiddenField') {
												$amenity_body .= '<input '.(($attr_price != $product_price) ? $product_price = $attr_price : '').' type="hidden" class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'" value="'.$xss->filter(ereg_replace("[^A-Za-z0-9\ \-]", '', $amenityType['title'])).': '.$xss->filter($amenityParent['feature']).'">';
												$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
											} else if ($amenityType['itype'] == 'RadioBtn') {
												//$amenity .= '<input '.(($attr_price != $product_price) ? 'googlecart-set-product-price="'.'$'.$attr_price.'"' : 'googlecart-set-product-price="'.'$'.$product_price.'"').' type="radio" class="product-attr-'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" value="'.$xss->filter($amenityType['title']).': '.$xss->filter($amenityParent['feature']).'"> '.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
												//$amenity .= '<script type="text/javascript">var checked_'.str_replace(' ', '', $xss->filter($amenityType['title'])).'_'.str_replace(' ', '', $xss->filter($amenityParent['feature'])).'_'.$page['id'].' = false;</script>';
												//$amenity .= '<input onclick="if(!checked_'.str_replace(' ', '', $xss->filter($amenityType['title'])).'_'.str_replace(' ', '', $xss->filter($amenityParent['feature'])).'_'.$page['id'].'){checked_'.str_replace(' ', '', $xss->filter($amenityType['title'])).'_'.str_replace(' ', '', $xss->filter($amenityParent['feature'])).'_'.$page['id'].' = true; changePrice('.$page['id'].', '.$add_amount.');};" type="radio" class="product-attr-'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" value="'.$xss->filter($amenityType['title']).': '.$xss->filter($amenityParent['feature']).'"> '.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
												$amenity_body .= '<input onclick="changePrice('.$page['id'].', '.$amenityType['id'].');" name="radio-attr-'.$amenityType['id'].'" id="attr-'.$amenityParent['id'].'" type="radio" class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'" value="'.$xss->filter(ereg_replace("[^A-Za-z0-9\ \-]", '', $amenityType['title'])).': '.$xss->filter($amenityParent['feature']).'" checked> '.$amenityParent['feature'].(($attr_price != $product_price) ? ' &nbsp;<nobr><b>('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')</b></nobr>' : '').'<br />';
												$hidden_selects .= '<input type="hidden" id="attr-'.str_replace(' ', '', $xss->filter($amenityType['title'])).'-'.str_replace(' ', '', $xss->filter($amenityParent['feature'])).'-'.$page['id'].'" value="'.$add_amount.'">';
												$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
											} else if ($amenityType['itype'] == 'CheckBox') {
												//$amenity .= '<input '.(($attr_price != $product_price) ? 'googlecart-set-product-price="'.'$'.$attr_price.'"' : '').' onclick="if(!this.checked){this.setAttribute(\'googlecart-set-product-price\', \'$'.$product_price.'\');$$(\'#addtocart-'.$page['id'].' .product .product-price\').each(function(item) {item.innerHTML = \'$'.$product_price.'\';});$$(\'#addtocart-copy-'.$page['id'].' .product .product-price\').each(function(item) {item.innerHTML = \'$'.$product_price.'\';});}else{this.setAttribute(\'googlecart-set-product-price\', \'$'.$attr_price.'\');};" type="checkbox" class="product-attr-'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" value="'.$xss->filter($amenityType['title']).': '.$xss->filter($amenityParent['feature']).'"> '.$amenityParent['feature']. (($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
												//$amenity .= '<input onclick="if(!this.checked){changePrice('.$page['id'].', '.($add_amount*(-1)).');}else{changePrice('.$page['id'].', '.$add_amount.');};" type="checkbox" class="product-attr-'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->filter($amenityType['title'])).'" value="'.$xss->filter($amenityType['title']).': '.$xss->filter($amenityParent['feature']).'"> '.$amenityParent['feature']. (($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
												$amenity_body .= '<input onclick="changePrice('.$page['id'].', '.$amenityType['id'].');" name="checkbox-attr-'.$amenityType['id'].'" id="attr-'.$amenityParent['id'].'" type="checkbox" class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'" value="'.$xss->filter(ereg_replace("[^A-Za-z0-9\ \-]", '', $amenityType['title'])).': '.$xss->filter($amenityParent['feature']).'"> '.$amenityParent['feature']. (($attr_price != $product_price) ? ' &nbsp;<nobr><b>('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')</b></nobr>' : '').'<br />';
												$hidden_selects .= '<input type="hidden" id="attr-'.str_replace(' ', '', $xss->filter($amenityType['title'])).'-'.str_replace(' ', '', $xss->filter($amenityParent['feature'])).'-'.$page['id'].'" value="'.$add_amount.'">';
												$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
											} else if ($amenityType['itype'] == 'SelectBox') {
												//$amenity .= '<option '.(($attr_price != $product_price) ? 'googlecart-set-product-price="'.'$'.$attr_price.'"' : 'googlecart-set-product-price="'.'$'.$product_price.'"').' value="'.$xss->filter($amenityType['title']).': '.$xss->filter($amenityParent['feature']).'">'.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'</option>';
												$amenity_body .= '<option id="attr-'.$amenityParent['id'].'" value="'.$xss->filter(ereg_replace("[^A-Za-z0-9\ \-]", '', $amenityType['title'])).': '.$xss->filter($amenityParent['feature']).'">'.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')' : '').'</option>';
												$hidden_selects .= '<input type="hidden" id="attr-'.str_replace(' ', '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'-'.str_replace(' ', '', $xss->filter($amenityParent['feature'])).'-'.$page['id'].'" value="'.$add_amount.'">';
											} else if ($amenityType['itype'] == 'Normal') {
												$amenity_body .= '<span '.(($attr_price != $product_price) ? $product_price = $attr_price : '').' class="item_'.str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).' product-attr-'.str_replace(' ', '-', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']))).'">'.$amenityParent['feature'].'</span>'.(($attr_price != $product_price) ? ' &nbsp;<nobr><b>('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')</b></nobr>' : '').'<br />';
												$attribute_Columns[str_replace(array('_'," "), '', $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title'])))] = $xss->filter(ereg_replace("[^A-Za-z0-9\ ]", '', $amenityType['title']));
											}
										}
									}
									$amenity .= $amenity_header.$amenity_body.$amenity_footer; 
								}
							}
						}
						$product_attribute = $amenity.$hidden_selects.'<br />';
						
						// description
						$product_description = '';
						if (isset($page['description']) && !empty($page['description'])) {
							$product_description = $xss->filter(strip_tags($page['description'], 'Store'));
							$product_description = '<input type="hidden" class="item_description product-attr-description" value="'.$product_description.'" />';
						}

						$product_contact = '';
						$product_company = '';
						$product_company_logo = '';
						$product_contact_email = '';
						$product_contact_phone = '';
						$product_contact_website = '';
						
						// contact information
						if ((isset($page['contact']) && !empty($page['contact'])) || (isset($page['company']) && !empty($page['company']))) {
							
							$agent_html = '';
							if (isset($page['contact']) && !empty($page['contact'])) {
								$agent_html .= '<nobr>Listed by: <b>'.($page['ownerid'] > 0 ? '<a href="'.$GLOBALS['app']->getSiteURL('/index.php?gadget=Products&action=Category&id=all&owner_id='.$page['ownerid'], false, ($gadget_requires_https === true ? 'https' : 'http')).'">' : '').$xss->filter(strip_tags($page['contact'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
							}
							$product_contact = $agent_html;
							$product_contact = '<input type="hidden" class="item_contact product-attr-contact" value="'.$product_contact.'" />';
							
							$agent_website = '';
							$agent_website_html = '';
							if (isset($page['contact_website']) && !empty($page['contact_website'])) {
								$agent_website = $xss->filter(strip_tags($page['contact_website']));
								$agent_website_html .= '<br /><nobr>Website: <a href="'.$xss->filter(strip_tags($page['contact_website'])).'" target="_blank">'.$xss->filter(strip_tags($page['contact_website'])).'</a></nobr>';
							} else if (isset($page['company_website']) && !empty($page['company_website'])) {
								$agent_website = $xss->filter(strip_tags($page['company_website']));
								$agent_website_html .= '<br /><nobr>Website: <a href="'.$xss->filter(strip_tags($page['company_website'])).'" target="_blank">'.$xss->filter(strip_tags($page['company_website'])).'</a></nobr>';
							}
							$product_contact_website = $agent_website_html;
							$product_contact_website = '<input type="hidden" class="item_contactwebsite product-attr-contact-website" value="'.$product_contact_website.'" />';
							$broker_html = '';
							if (isset($page['company']) && !empty($page['company'])) {
								//$broker_html .= ($agent_website != '' ? '<a href="'.$agent_website.'" target="_blank">' : '').$xss->filter(strip_tags(str_replace('&nbsp;', ' ', $page['company']))).($agent_website != '' ? '</a>' : '');
								$broker_html .= '<br />'.($agent_html != '' ? '<nobr>of ' : '<nobr><b>').($agent_website != '' ? '<a href="'.$agent_website.'" target="_blank">' : '').$xss->filter(strip_tags(str_replace('&nbsp;', ' ', $page['company']))).($agent_website != '' ? '</a>' : '').($agent_html != '' ? '' : '</b>').'</nobr>';
							}
							$product_company = $broker_html;
							$product_company = '<input type="hidden" class="item_company product-attr-company" value="'.$product_company.'" />';
							
							$agent_phone_html = '';
							if (isset($page['agent_phone']) && !empty($page['contact_phone']) && strpos($page['contact_phone'], "://") === false) {
								$agent_phone_html .= '<br /><nobr>Phone: '.$xss->filter(strip_tags($page['contact_phone'])).'</nobr>';
							} else if (isset($page['company_phone']) && !empty($page['company_phone']) && strpos($page['company_phone'], "://") === false) {
								$agent_phone_html .= '<br /><nobr>Phone: '.$xss->filter(strip_tags($page['company_phone'])).'</nobr>';
							}
							$product_contact_phone = $agent_phone_html;
							$product_contact_phone = '<input type="hidden" class="item_contactphone product-attr-contact-phone" value="'.$product_contact_phone.'" />';
							
							$agent_email_html = '';
							if (isset($page['contact_email']) && !empty($page['contact_email'])) {
								$agent_email_html .= '<br /><nobr>E-mail: '.$xss->filter(strip_tags($page['contact_email'])).'</nobr>';
							} else if (isset($page['company_email']) && !empty($page['company_email'])) {
								$agent_email_html .= '<br /><nobr>E-mail: '.$xss->filter(strip_tags($page['company_email'])).'</nobr>';
							}
							$product_contact_email = $agent_email_html;
							$product_contact_email = '<input type="hidden" class="item_contactemail product-attr-contact-email" value="'.$product_contact_email.'" />';
							
							$broker_logo_src = '';
							if (!empty($page['company_logo']) && isset($page['company_logo'])) {
								$page['company_logo'] = $xss->parse(strip_tags($page['company_logo']));
								if (strpos($page['company_logo'],".swf") !== false) {
									// Flash file not supported
								} else if (substr($page['company_logo'],0,7) == "GADGET:") {
									$broker_logo_src = $page['company_logo'];
								} else {
									$broker_logo_src = $page['company_logo'];
								}
							}
							if (!empty($page['contact_photo']) && isset($page['contact_photo'])) {
								$page['contact_photo'] = $xss->parse(strip_tags($page['contact_photo']));
								if (strpos($page['contact_photo'],".swf") !== false) {
									// Flash file not supported
								} else if (substr($page['contact_photo'],0,7) == "GADGET:") {
									$broker_logo_src = $page['contact_photo'];
								} else {
									$broker_logo_src = $page['contact_photo'];
								}
							}
							$broker_logo = '';
							if (!empty($broker_logo_src)) {
								if (substr(strtolower($broker_logo_src), 0, 4) == "http") {
									if (substr(strtolower($broker_logo_src), 0, 7) == "http://") {
										$broker_logo = explode('http://', $broker_logo_src);
										foreach ($broker_logo as $img_src) {
											if (!empty($img_src)) {
												$broker_logo = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$broker_logo = explode('https://', $broker_logo_src);
										foreach ($broker_logo as $img_src) {
											if (!empty($img_src)) {
												$broker_logo = 'https://'.$img_src;
												break;
											}
										}
									}
									if (strpos(strtolower($broker_logo), 'data/files/') !== false) {
										$broker_logo = 'image_thumb.php?uri='.urlencode($broker_logo);
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($broker_logo_src);
									$medium = Jaws_Image::GetMediumPath($broker_logo_src);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$broker_logo = $GLOBALS['app']->getDataURL('files'.$thumb, true, false, $gadget_requires_https);
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$broker_logo = $GLOBALS['app']->getDataURL('files'.$medium, true, false, $gadget_requires_https);
									} else if (file_exists(JAWS_DATA . 'files'.$broker_logo_src)) {
										$broker_logo = $GLOBALS['app']->getDataURL('files'.$broker_logo_src, true, false, $gadget_requires_https);
									}
								}
								if (!empty($broker_logo)) {
									$broker_logo = (!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Store&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo.'" width="100" '.(strtolower(substr($broker_logo, -3)) == "gif" || strtolower(substr($broker_logo, -3)) == "png" || strtolower(substr($broker_logo, -3)) == "bmp" ? 'height="100"' : '').' />'.(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '');				
								}
							}
							$product_company_logo = $broker_logo;
							$product_company_logo = '<input type="hidden" class="item_companylogo product-attr-company-logo" value="'.$product_company_logo.'" />';
						}
						
						$product_alink = '';
						
						// external links
						if (!empty($page['alink'])) {
							if (!empty($page['alink']) && !empty($page['alinktype'])) {
								$product_alink = '<a href="'.($page['alinktype'] == 'M' ? 'mailto:' : 'http://').$xss->filter(strip_tags($page['alink'])).'" target="_blank">'.(!empty($page['alinktitle']) ? $xss->filter(strip_tags($page['alinktitle'])) : $xss->filter(strip_tags($page['alink']))).'</a>';
								$product_alink = '<input type="hidden" class="item_additionalinfo product-attr-additional-info" value="'.$product_alink.'" />';
							}
						}

						$cart_html .= '<div class="addtocart-holder" id="addtocart-'.$page['id'].'"'.($type == 'cartlink' || $type == 'smallcartbutton' ? ' style="display: none;"' : '').'><form id="addtocart-form-'.$page['id'].'" name="addtocart-form-'.$page['id'].'"><div class="simpleCart_shelfItem product">';
						$cart_html .= $product_image;
						$cart_html .= $sale_min_qty;
						$cart_html .= '<span'.($type != 'cartlink' || $type == 'smallcartbutton' ? ' style="display: none;"' : '').' class="item_name product-title'.(isset($page['weight']) && !empty($page['weight']) && $page['weight'] > 0 ? '' : ' product-digital').'">'.urlencode('<a class="item-name-link" href="'.$product_url.'">'.$product_title.'</a>').'</span><br />
						  Price: <span class="item_price product-price">$'.$product_price.'</span> '.(!empty($product_unit) ? $product_unit : '').'<br />';
						$cart_html .= $product_sm_description;
						$cart_html .= $product_sm_description;
						$cart_html .= $product_setup_fee;
						$cart_html .= $product_attribute;
						$cart_html .= $product_min_qty;
						$cart_html .= $product_recurring;
						$cart_html .= $product_description;
						$cart_html .= $product_retail;
						$cart_html .= $product_weight;
						$cart_html .= $product_code;
						$cart_html .= $product_brand;
						$cart_html .= $product_redirect;
						$cart_html .= $product_alink;
						$cart_html .= $product_contact;
						$cart_html .= $product_company;
						$cart_html .= $product_company_logo;
						$cart_html .= $product_contact_email;
						$cart_html .= $product_contact_phone;
						$cart_html .= $product_contact_website;
						$cart_html .= $product_OwnerID;
						$cart_html .= $product_id;
						$cart_html .= '<input type="hidden" class="item_elementid" value="addtocart-'.$page['id'].'" />';
						$cart_html .= '<a href="javascript:;" title="Add to cart" class="item_add ecommerce-cart-button googlecart-add" id="ecommerce-cart-link-'.$page['id'].'" onclick="if ($(\'googlecart-widget\')) {$(\'googlecart-widget\').style.visibility = \'visible\';};"></a>';
						$cart_html .= "</div></form></div>\n";
						
						if ($type == 'smallcartbutton') {
							$cart_html .= '<div class="list-addtocart-holder" id="list-addtocart-'.$page['id'].'"><form id="list-addtocart-form-'.$page['id'].'" name="list-addtocart-form-'.$page['id'].'"><div class="list-product">';
							$cart_html .= $product_image;
							$cart_html .= 'Price: <span class="item_price product-price">$'.$product_price.'</span> '.(!empty($product_unit) ? $product_unit : '').'<br />';
							$cart_html .= $product_sm_description;
							$cart_html .= $product_setup_fee;
							$cart_html .= $product_recurring;
							$cart_html .= $product_retail;
							$cart_html .= $product_weight;
							$cart_html .= "</div></form></div>\n";
						}
						
						if ($type == 'cartlink') {
							$cart_html .= "<script type=\"text/javascript\">
							//document.whenReady(showWindow);
							var w".$page['id'].";
							function showWindow(id) {
								w".$page['id']." = new UI.Window({
								  theme: \"simpleblue\",
								  height: 300,
								  width: 400,
								  shadow: true,
								  minimize: false,
								  maximize: false,
								  close: 'destroy',
								  resizable: false,
								  draggable: true,
								});
								content = $('addtocart-".$page['id']."').innerHTML;
								//content = content.replace(/&quot;/gi, '\"');
								w".$page['id'].".setContent('<div id=\"addtocart-copy-".$page['id']."\" style=\"margin-top: -10px; padding: 10px; text-align: left;\">'+content+'</div>');
								w".$page['id'].".adapt.bind(w".$page['id'].").delay(0.3);
								w".$page['id'].".show(true).focus();
								w".$page['id'].".center();
								//w".$page['id'].".bottomRight();
								/*
								// Hook the window to an item
								if ($(id)) {
									currentItem = $(id);
									if (currentItem) {
										var itemOffset = currentItem.cumulativeOffset();
										newTop = ((itemOffset.top - windowSize.height) > 10 ? (itemOffset.top - windowSize.height) : 10);
										newLeft = (Math.round(itemOffset.left + ((currentItem.offsetWidth/2)-(windowSize.width/2))) > 10 ? Math.round(itemOffset.left + ((currentItem.offsetWidth/2)-(windowSize.width/2))) : 10);
										w".$page['id'].".setPosition(newTop, newLeft);
									} else {
										w".$page['id'].".destroy();
									}
								}
								*/
								return true;
							}
							Event.observe(window, \"resize\", function() {
								if (w".$page['id'].") {
									w".$page['id'].".center();
								}
							});
							</script>\n";
							$cart_html .= '<a href="javascript: return false;" onclick="showWindow(\'add-cart-link-'.$page['id'].'\'); return false;" class="item_add add-cart-link" id="add-cart-link-'.$page['id'].'">Add to Cart</a>'."\n";
						} else if ($type == 'smallcartbutton') {
							$cart_html .= '<a href="javascript:;" class="item_add add-cart-link list-add-cart-link" id="add-cart-link-'.$page['id'].'"><img alt="Add To Cart" title="Add To Cart" border="0" src="'.$GLOBALS['app']->GetJawsURL($gadget_requires_https) . '/gadgets/Ecommerce/images/moreinfo.png" /></a>'."\n";
						}

						$attribute_Columns_str = '';
						foreach ($attribute_Columns as $key => $value) {
							$attribute_Columns_str .= "\n"."{ attr: '".$key."', label: '".$value."'},";
						}

						$simpleCartColumns = "
						simpleCart({
							cartColumns: [
								{ attr: 'name', label: 'Name'},
								{ view: 'currency', attr: 'price', label: 'Price'},
								{ view: 'decrement', label: false},
								{ attr: 'quantity', label: 'Qty'},
								{ view: 'increment', label: false},
								{ view: 'currency', attr: 'total', label: 'SubTotal'},
								{ view: 'remove', text: 'Remove', label: false},
								{ attr: 'image', label: 'Image'},
								{ attr: 'retail', label: 'Retail'},
								{ attr: 'unit', label: 'Unit'},
								{ attr: 'recurring', label: 'Recurring'},
								{ attr: 'weight', label: 'Weight'},
								{ attr: 'productcode', label: 'Product Code'},
								{ attr: 'summary', label: 'Summary'},
								{ attr: 'description', label: 'Description'},
								{ attr: 'ownerid', label: 'Owner ID'},
								{ attr: 'brand', label: 'Brand'},
								{ attr: 'producturl', label: 'Product URL'},
								{ attr: 'setupfee', label: 'Setup Fee'},
								{ attr: 'contact', label: 'Contact'},
								{ attr: 'contactwebsite', label: 'Contact Website'},
								{ attr: 'company', label: 'Company'},
								{ attr: 'contactphone', label: 'Contact Phone'},
								{ attr: 'contactemail', label: 'Contact Email'},
								{ attr: 'companylogo', label: 'Company Logo'},
								{ attr: 'additionalinfo', label: 'Additional Info'},
								{ attr: 'elementid', label: 'Element ID'},
								{ attr: 'setupfee', label: 'Setup Fee'},
								" . $attribute_Columns_str . "
								{ attr: 'productid', label: 'Product'}
							]
						});";
						
						$script = "<script type=\"text/javascript\">
							var add_amount = 0.00;
							var select_name = 'select-';
							var radio_name = 'radio-';
							var checkbox_name = 'checkbox-';
							var oldprice = ".str_replace(',', '', $product_price).";
							function changePrice(parent, unused) {
								add_amount = 0.00;
								$$(\"#addtocart-\"+parent+\" #addtocart-form-\"+parent).each(function(item) {
									var elem = item.elements; 
									for(var i = 0; i < elem.length; i++) { 
										if (elem[i].name.substr(0, 7) == select_name) {
											selected = elem[i].options[elem[i].selectedIndex].value.replace(/ /gi, ''); 
											selected = selected.replace(/:/gi, '-'); 
											if ($('attr-'+selected+'-'+parent)){
												new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
												add_amount = add_amount + new_amount;
											};
										} else if (elem[i].name.substr(0, 6) == radio_name || elem[i].name.substr(0, 9) == checkbox_name) {
											if (elem[i].checked) {
												selected = elem[i].value.replace(/ /gi, ''); 
												selected = selected.replace(/:/gi, '-'); 
												if ($('attr-'+selected+'-'+parent)){
													new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
													add_amount = add_amount + new_amount;
												};
											};								
										};
									};
								});
								$$(\"#addtocart-copy-\"+parent+\" #addtocart-form-\"+parent).each(function(item) {
									var elem = item.elements; 
									for(var i = 0; i < elem.length; i++) { 
										if (elem[i].name.substr(0, 7) == select_name) {
											selected = elem[i].options[elem[i].selectedIndex].value.replace(/ /gi, ''); 
											selected = selected.replace(/:/gi, '-'); 
											if ($('attr-'+selected+'-'+parent)){
												new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
												add_amount = add_amount + new_amount;
											};
										} else if (elem[i].name.substr(0, 6) == radio_name || elem[i].name.substr(0, 9) == checkbox_name) {
											if (elem[i].checked) {
												selected = elem[i].value.replace(/ /gi, ''); 
												selected = selected.replace(/:/gi, '-'); 
												if ($('attr-'+selected+'-'+parent)){
													new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
													add_amount = add_amount + new_amount;
												};
											};								
										};
									};
								});	
								add_amount = number_format(add_amount, 2, '.', ','); 
								$$(\"#addtocart-\"+parent+\" .product .product-price\").each(function(item) {
									/*
									var oldprice = item.innerHTML;
									oldprice = parseFloat(oldprice.substr(oldprice.indexOf('$')+1, oldprice.length));
									oldprice = number_format(oldprice, 2, '.', ',');
									*/
									newprice = number_format((parseFloat(oldprice)+parseFloat(add_amount)), 2, '.', '');
									item.innerHTML = '$'+newprice;
								});
								$$(\"#addtocart-copy-\"+parent+\" .product .product-price\").each(function(item) {
									/*
									var oldprice = item.innerHTML;
									oldprice = parseFloat(oldprice.substr(oldprice.indexOf('$')+1, oldprice.length));
									oldprice = number_format(oldprice, 2, '.', ',');
									*/
									newprice = number_format((parseFloat(oldprice)+parseFloat(add_amount)), 2, '.', '');
									item.innerHTML = '$'+newprice;
								});							
							}
							".$simpleCartColumns."
							Event.observe(window, 'load', function(){changePrice(".$page['id'].");});
						</script>
						\n";
						//$script = str_replace("\r\n", '', $script);
						//$script = str_replace("\n", '', $script);
						$cart_html .= $script;
					} else {
						$cart_html = '';
					}
				} else {
					$cart_html = (isset($page['outstockmsg']) && !empty($page['outstockmsg']) ? $page['outstockmsg'] : 'This product is sold out. Check back soon.');
				}
			}
			$tpl->SetVariable('layout_content', $cart_html);
			$tpl->ParseBlock('layout/'.$type);

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('layout/embedded');
				$tpl->SetVariable('id', $pid);		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL($gadget_requires_https) . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
				$tpl->SetVariable('bool_resize', "0");		        
				$tpl->ParseBlock('layout/embedded');
			} else {
				$tpl->SetBlock('layout/not_embedded');
				$tpl->SetVariable('id', $pid);		        
				$tpl->ParseBlock('layout/not_embedded');
			}

			$tpl->ParseBlock('layout');
			
			return $tpl->Get();
		}
    }

	/**
     * Display "Add to Cart" buttons.
     *
     * @category 	feature
     * @param 	int 	$pid 	Product ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	Owner ID
     * @access 	public
     * @return 	string 	HTML template content
     */
    function ShowCartButton($pid = 1, $embedded = false, $referer = null, $uid = null)
    {
		$request =& Jaws_Request::getInstance();
		$fetch = array('id');
		$get  = $request->get($fetch, 'get');
		if (is_null($pid)) {
			if (isset($get['id']) && !empty($get['id'])) {
				$pid = (int)$get['id'];
			} else {
				return new Jaws_Error( _t('ECOMMERCE_ERROR_CARTBUTTON_NOT_SHOWN'), _t('ECOMMERCE_NAME'));
			}
		}
		
		return $this->Display($pid, $embedded, $referer, false, 'cartbutton');
    }

	
	/**
     * Display small "Add to Cart" buttons.
     *
     * @param 	int 	$pid 	Product ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	Owner ID
     * @access 	public
     * @return 	string 	HTML template content
     */
    function ShowSmallCartButton($pid = 1, $embedded = false, $referer = null, $uid = null)
    {
		$request =& Jaws_Request::getInstance();
		$fetch = array('id');
		$get  = $request->get($fetch, 'get');
		if (is_null($pid)) {
			if (isset($get['id']) && !empty($get['id'])) {
				$pid = (int)$get['id'];
			} else {
				return new Jaws_Error( _t('ECOMMERCE_ERROR_CARTBUTTON_NOT_SHOWN'), _t('ECOMMERCE_NAME'));
			}
		}
		
		return $this->Display($pid, $embedded, $referer, false, 'smallcartbutton');
    }

	/**
     * Display "Add to Cart" links.
     *
     * @category 	feature
     * @param 	int 	$pid 	Product ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	Owner ID
     * @access 	public
     * @return 	string 	HTML template content
     */
    function ShowCartLink($pid = 1, $embedded = false, $referer = null, $uid = null)
    {
		$request =& Jaws_Request::getInstance();
		$fetch = array('id');
		$get  = $request->get($fetch, 'get');
		if (is_null($pid)) {
			if (isset($get['id']) && !empty($get['id'])) {
				$pid = (int)$get['id'];
			} else {
				return new Jaws_Error( _t('ECOMMERCE_ERROR_CARTBUTTON_NOT_SHOWN'), _t('ECOMMERCE_NAME'));
			}
		}
		
		return $this->Display($pid, $embedded, $referer, false, 'cartlink');
    }
		
}
