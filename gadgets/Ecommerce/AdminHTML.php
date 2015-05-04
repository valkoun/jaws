<?php
/**
 * Ecommerce Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function EcommerceAdminHTML()
    {
        $this->Init('Ecommerce');
    }

    /**
     * Builds the menubar
     *
     * @access       public
     * @param        string  $selected Selected action
     * @return       string  The html menubar
     */
    function MenuBar($selected)
    {
        $actions = array('Admin','B','B_form','B_form_post','Settings','form','form_post','view');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
            $menubar->AddOption('Admin', _t('ECOMMERCE_MENU_ADMIN'),
                                'admin.php?gadget=Ecommerce&amp;action=Admin', STOCK_DOCUMENTS);
        }
		if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'view' || strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('ECOMMERCE_MENU_ORDER'),
	                                'javascript:void(0);', STOCK_NEW);
			}
		}
        if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
            $menubar->AddOption('B', _t('ECOMMERCE_MENU_SHIPPING'),
                                'admin.php?gadget=Ecommerce&amp;action=B', STOCK_EDIT);
		}
        if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
            $menubar->AddOption('Settings', _t('ECOMMERCE_MENU_SETTINGS'),
                                'admin.php?gadget=Ecommerce&amp;action=Settings', STOCK_ALIGN_CENTER);
		}
		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Ecommerce&amp;action=form', STOCK_ADD);
			}
		}
        $menubar->Activate($selected);

        return $menubar->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function DataGrid()
    {
        //$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
        $sql = 'SELECT COUNT([id]) FROM [[order]]';
		if (BASE_SCRIPT == 'index.php') {
			$sql .= ' WHERE [ownerid] = {OwnerID}';
			$params['OwnerID'] = $GLOBALS['app']->Session->GetAttribute('user_id');
        }
		$res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : (int)$res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('ecommerce_datagrid');
        $grid->SetAction('next', 'javascript:nextOrderValues();');
        $grid->SetAction('prev', 'javascript:previousOrderValues();');
        $grid->SetAction('first', 'javascript:firstOrderValues();');
        $grid->SetAction('last', 'javascript:lastOrderValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_VENDOR')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_ORDERNO')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_TOTAL')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_RECURRING')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }
    
	/**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function ShippingDataGrid()
    {
        //$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[shipping]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('shipping_datagrid');
        $grid->setAction('next', 'javascript:nextShippingValues();');
        $grid->setAction('prev', 'javascript:previousShippingValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_TYPE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_SHIP_MINFACTOR')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_SHIP_MAXFACTOR')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_PRICE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }
	
	/**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function TaxesDataGrid()
    {
        //$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[taxes]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('taxes_datagrid');
        $grid->setAction('next', 'javascript:nextTaxesValues();');
        $grid->setAction('prev', 'javascript:previousTaxesValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_TAXPERCENT')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ECOMMERCE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetOrders($status, $search, $limit, $OwnerID = null)
    {
        $model = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
        $pages = $model->SearchOrders($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Ecommerce&amp;action=view&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Ecommerce&amp;action=account_view&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['status']  = $page['active'];
			$pageData['vendor']  = $page['ownerid'];
			$pageData['orderno'] = '<a href="'.$edit_url.$page['id'].'">'.$page['orderno'].'</a>';

			$pageData['total']  = $page['total'];
			$pageData['recurring']  = $page['recurring'];
			$pageData['date']  = $date->Format($page['updated']);
			
			$actions = '';
			if ($this->GetPermission('ManageEcommerce')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($this->GetPermission('ManageEcommerce')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('ECOMMERCE_CONFIRM_DELETE_ORDER', _t('ECOMMERCE_ORDER'))."')) ".
											"deleteOrder('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('ECOMMERCE_CONFIRM_DELETE_ORDER', _t('ECOMMERCE_ORDER'))."')) ".
												"deleteOrder('".$page['id']."');",
												"images/ICON_delete2.gif");
					$actions.= $link->Get().'&nbsp;';
				}
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['id'];
			$data[] = $pageData;
        }
        return $data;
    }
    
	/**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetShippings($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
        $pages = $model->SearchShippings($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Ecommerce&amp;action=B_form&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Ecommerce&amp;action=account_B_form&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			if ($page['active'] == 'Y') {
				$pageData['status']  = _t('ECOMMERCE_PUBLISHED');
			} else {
				$pageData['status']  = _t('ECOMMERCE_NOTPUBLISHED');
			}
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';

			$pageData['type']  = $page['type'];
			$pageData['minfactor']  = $page['minfactor'];
			$pageData['maxfactor']  = $page['maxfactor'];
			$pageData['price']  = $page['price'];
			
			$actions = '';
			if ($this->GetPermission('ManageEcommerce')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($this->GetPermission('ManageEcommerce')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('ECOMMERCE_CONFIRM_DELETE_SHIPPING', _t('ECOMMERCE_SHIPPING'))."')) ".
											"deleteOrder('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('ECOMMERCE_CONFIRM_DELETE_SHIPPING', _t('ECOMMERCE_SHIPPING'))."')) ".
												"deleteOrder('".$page['id']."');",
												"images/ICON_delete2.gif");
					$actions.= $link->Get().'&nbsp;';
				}
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['id'];
			$data[] = $pageData;
        }
        return $data;
    }

    /**
     * Display the default administration page which currently lists all orders
     *
     * @access public
     * @return string
     */
    function Admin($account = false)
    {
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$require_https = $GLOBALS['app']->Registry->Get('/gadgets/require_https');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		if (
			$account === false && empty($action) && 
			((empty($site_ssl_url) && in_array('Ecommerce', explode(',',$require_https))) || 
				empty($payment_gateway) || 
				($payment_gateway != 'ManualCreditCard' && (empty($merchant_id) || empty($merchant_key))))
		) {
			require_once JAWS_PATH . 'include/Jaws/Header.php';	
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ecommerce&action=Settings');
		}

		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ecommerce', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
			$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");
			$tpl->SetVariable('account-style', '');
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
			$tpl->SetVariable('workarea-style', '');
			$tpl->SetVariable('account-style', ' display: none;');
		}
        				
		$page = '';
		
		// TODO: Support other Payment Gateways
/*
		if ($payment_gateway == 'GoogleCheckout') {
			$icon_url = "https://checkout.google.com/buttons/checkout.gif?merchant_id=".(!empty($merchant_id) ? $merchant_id : '')."&w=180&h=46&style=white&variant=text&loc=en_US";
			$url = "http://checkout.google.com";
			$link =& Piwi::CreateWidget('Link', "Click Here to Manage Orders",
										$url, '', '_blank');
			$page = "<div style=\"text-align: center;\"><img border=\"0\" src=\"".$icon_url."\" style=\"padding: 10px;\" align=\"left\"/> Manage all of your orders through your Google Checkout account. <br />".$link->Get()."</div>";
		} else if ($payment_gateway == 'PayPal') {
			$icon_url = "https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif";
			$url = "https://www.paypal.com/cgi-bin/webscr?cmd=_login-run";
			$link =& Piwi::CreateWidget('Link', "Click Here to Manage Orders",
										$url, '', '_blank');
			$page = "<div style=\"text-align: center;\"><img border=\"0\" src=\"".$icon_url."\" style=\"padding: 10px;\" align=\"left\"/> Manage all of your orders through your PayPal account. <br />".$link->Get()."</div>";
		} else {
*/
			$stpl = new Jaws_Template('gadgets/Ecommerce/templates/');
			$stpl->Load('admin.html');
			$stpl->SetBlock('order_admin');
			$stpl->SetVariable('account', $account_prefix);
			$stpl->SetVariable('base_script', $base_url);

			$stpl->SetVariable('grid', $this->DataGrid());

			$toolBar   =& Piwi::CreateWidget('HBox');

			$deleteAll =& Piwi::CreateWidget('Button', 'deleteAllOrders',
											 _t('GLOBAL_DELETE'),
											 STOCK_DELETE);
			$deleteAll->AddEvent(ON_CLICK,
								 "javascript: massiveDelete('"._t('ECOMMERCE_CONFIRM_MASIVE_DELETE_ORDER')."');");

			$toolBar->Add($deleteAll);

			$stpl->SetVariable('tools', $toolBar->Get());
					
			$stpl->SetVariable('entries', $this->Datagrid());

			/*
			if ($account === false) {
				$addPage =& Piwi::CreateWidget('Button', 'add_order', _t('ECOMMERCE_ADD_ORDER'), STOCK_ADD);
				$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Ecommerce&amp;action=".$account_prefix."form';");
				$stpl->SetVariable('add_order', $addPage->Get());
			} else {
			*/
				//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=Ecommerce&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
				$stpl->SetVariable('add_order', '');
			//}

			$stpl->ParseBlock('order_admin');
			$page .= $stpl->Get();
//		}
		
		$page .= '<div style="clear: both;">&nbsp;</div>';
		
		$tpl->SetVariable('content', $page);
       
	    $tpl->ParseBlock('gadget_page');

        return $tpl->Get();
    }


    /**
     * We are on a form page
     *
     * @access public
     * @return string
     */
    function form($account = false)
    {
		$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ecommerce', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Ecommerce';";
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Ecommerce/admin_Ecommerce_form');
			$OwnerID = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."';";
		}
		$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");
		
		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Ecommerce');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				$editable = true;
				$submit_vars['SUBMIT_BUTTON'] = '';
				if (!is_null($get['id'])) {
					$editable = false;
					// send page records
					$pageInfo = $model->GetOrder((int)$get['id']);
					if (Jaws_Error::IsError($pageInfo)) {
						//$error = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
					}
					if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || (int)$pageInfo['ownerid'] == $OwnerID || (int)$pageInfo['customer_id'] == $OwnerID) {
						if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || (int)$pageInfo['ownerid'] == $OwnerID) {
							$editable = true;
						}
					} else {
						return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
					}
					if ($editable == true) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Ecommerce');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ecommerce/admin_Ecommerce_form_help", 'txt');
				$snoopy = new Snoopy('Ecommerce');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditOrder' : 'AddOrder';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";
				
				$address2 = (isset($pageInfo['customer_address2'])) ? $pageInfo['customer_address2'] : '';
				$address2Hidden =& Piwi::CreateWidget('HiddenEntry', 'customer_address2', $address2);
		        $form_content .= $address2Hidden->Get()."\n";
				
				$shipaddress2 = (isset($pageInfo['customer_shipaddress2'])) ? $pageInfo['customer_shipaddress2'] : '';
				$shipaddress2Hidden =& Piwi::CreateWidget('HiddenEntry', 'customer_shipaddress2', $shipaddress2);
		        $form_content .= $shipaddress2Hidden->Get()."\n";
				
				$recurring = 'N';
				$recurringHidden =& Piwi::CreateWidget('HiddenEntry', 'recurring', $recurring);
		        $form_content .= $recurringHidden->Get()."\n";
				
				$qty = 1;
				$qtyHidden =& Piwi::CreateWidget('HiddenEntry', 'qty', $qty);
		        $form_content .= $qtyHidden->Get()."\n";
				
				$shiptype = (isset($pageInfo['shiptype'])) ? $pageInfo['shiptype'] : 'Flat Rate';
				$shiptypeHidden =& Piwi::CreateWidget('HiddenEntry', 'shiptype', $shiptype);
		        $form_content .= $shiptypeHidden->Get()."\n";
				
				$gadget_table = (isset($pageInfo['gadget_table'])) ? $pageInfo['gadget_table'] : '';
				$gadget_tableHidden =& Piwi::CreateWidget('HiddenEntry', 'gadget_table', $gadget_table);
		        $form_content .= $gadget_tableHidden->Get()."\n";
				
				$gadget_id = (isset($pageInfo['gadget_id'])) ? $pageInfo['gadget_id'] : '';
				$gadget_idHidden =& Piwi::CreateWidget('HiddenEntry', 'gadget_id', $gadget_id);
		        $form_content .= $gadget_idHidden->Get()."\n";
				
				$prod_id = (isset($pageInfo['prod_id'])) ? $pageInfo['prod_id'] : '0';
				$prod_idHidden =& Piwi::CreateWidget('HiddenEntry', 'prod_id', $prod_id);
				$form_content .= $prod_idHidden->Get()."\n";
				
				$description = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
				$descHidden =& Piwi::CreateWidget('HiddenEntry', 'description', $description);
				$form_content .= $descHidden->Get()."\n";
				
				if ($editable === true) {
					// Status
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_ACTIVE')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					if (isset($pageInfo['active'])) {
						$active = $pageInfo['active'];
					} else {
						$form_content .= "<tr><td colspan=\"2\"><div style=\"background:url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/images/right_menu_bg.png) repeat-x scroll left top #FFFFFF; border:1px solid #BABDB6; font-size:90%;margin:1em 20%;padding:15px;text-align:center;width:60%;\"><b>IMPORTANT:</b> If you intend to receive compensation for this order, you'll need to manually charge it through your payment processing gateway.</div></td></tr>";
						$active = 'NEW';
					}
					$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
					$activeCombo->AddOption('New', 'NEW');
					$activeCombo->AddOption('Reviewing', 'REVIEWING');
					$activeCombo->AddOption('Chargeable', 'CHARGEABLE');
					$activeCombo->AddOption('Charging', 'CHARGING');
					$activeCombo->AddOption('Charged', 'CHARGED');
					$activeCombo->AddOption('Payment Declined', 'PAYMENT_DECLINED');
					$activeCombo->AddOption('Cancelled', 'CANCELLED');
					$activeCombo->AddOption('Cancelled By Gateway', 'CANCELLED_BY_GATEWAY');
					$activeCombo->AddOption('Processing', 'PROCESSING');
					$activeCombo->AddOption('Delivered', 'DELIVERED');
					$activeCombo->AddOption('Will Not Deliver', 'WILL_NOT_DELIVER');
					$activeCombo->AddOption('Refunded', 'REFUNDED');
					$activeCombo->SetDefault($active);
					$activeCombo->setTitle(_t('ECOMMERCE_ACTIVE'));
					if (isset($pageInfo['id'])) {
						//$activeCombo->AddEvent(ON_CHANGE, "if($('update_status_row')){if (this.value == '".$active."'){$('update_status_row').style.display = 'none';$('update_status_note').value = '';}else{$('update_status_row').style.display = 'block';}};");
						$activeCombo->AddEvent(ON_CHANGE, "$('update_status_row').style.display = '';");
					}
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				} else {
					$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'Active', $active);
					$form_content .= $activeHidden->Get()."\n";
				}
													
				// Status Update Note
				$update_status =& Piwi::CreateWidget('TextArea', 'update_status_note', '');
				$update_status->SetID('update_status_note');
				$update_status->SetRows(5);
				$update_status->SetStyle('width: 300px;');
				
				$form_content .= "<tr id=\"update_status_row\" style=\"display: ".(isset($pageInfo['id']) ? "" : "none").";\"><td class=\"syntacts-form-row\"><label for=\"update_status_note\"><nobr>"._t('ECOMMERCE_STATUSUPDATE_NOTE')."</nobr></label></td><td class=\"syntacts-form-row\">".$update_status->Get()."</td></tr>";
				
				// Orderno
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_ORDERNO')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				if (isset($pageInfo['orderno'])) {
					$orderno = $pageInfo['orderno'];
				} else {
					// send highest sort_order
					$sql = "SELECT [orderno] FROM [[order]] ORDER BY [orderno] DESC LIMIT 1";
					$max = $GLOBALS['db']->queryOne($sql);
					if (Jaws_Error::IsError($max)) {
						$error = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $max->GetMessage())."\n";
						//return $max;
					}
					$orderno = (int)$max+1;
				}
				$ordernoEntry =& Piwi::CreateWidget('Entry', 'orderno', $orderno);
				$ordernoEntry->SetTitle(_t('ECOMMERCE_ORDERNO'));
				$ordernoEntry->SetStyle('direction: ltr; width: 300px;');
				if (!is_null($get['id'])) {
					$ordernoEntry->SetReadOnly(true);
					$ordernoEntry->SetEnabled(false);
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"orderno\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$ordernoEntry->Get()."</td></tr>";
				
				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_DESC')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$desc = $description;
				$unserialized = unserialize($desc);
				if (is_array($unserialized) && isset($unserialized['description'])) {
					$desc = $unserialized['description'];
				}
				$descEntry =& Piwi::CreateWidget('TextArea', 'view_description', $desc);
				$descEntry->SetTitle(_t('ECOMMERCE_DESCRIPTION'));
				$descEntry->SetStyle('direction: ltr; width: 300px;');
				$descEntry->SetRows(5);
				if (!is_null($get['id']) && $editable === false) {
					$descEntry->SetReadOnly(true);
					$descEntry->SetEnabled(false);
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"view_description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$descEntry->Get()."</td></tr>";

				// Total
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_TOTAL')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$total = (isset($pageInfo['total'])) ? $pageInfo['total'] : '';
				$totalEntry =& Piwi::CreateWidget('Entry', 'total', $total);
				$totalEntry->SetTitle(_t('ECOMMERCE_TOTAL'));
				$totalEntry->SetStyle('direction: ltr; width: 300px;');
				if (!is_null($get['id']) && $editable === false) {
					$totalEntry->SetReadOnly(true);
					$totalEntry->SetEnabled(false);
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"total\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$totalEntry->Get()."</td></tr>";

				// Weight
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_WEIGHT')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				if (isset($pageInfo['weight'])) {
					$weight = $pageInfo['weight'];
				} else {
					$weight = 1;
				}
				$weightEntry =& Piwi::CreateWidget('Entry', 'weight', $weight);
				$weightEntry->SetTitle(_t('ECOMMERCE_WEIGHT'));
				$weightEntry->SetStyle('direction: ltr; width: 300px;');
				if (!is_null($get['id']) && $editable === false) {
					$weightEntry->SetReadOnly(true);
					$weightEntry->SetEnabled(false);
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"weight\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$weightEntry->Get()."</td></tr>";
				
				// Backorder
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_BACKORDER')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				if (isset($pageInfo['backorder'])) {
					$backorder = $pageInfo['backorder'];
				} else {
					$backorder = 0;
				}
				$backorderEntry =& Piwi::CreateWidget('Entry', 'backorder', $backorder);
				$backorderEntry->SetTitle(_t('ECOMMERCE_BACKORDER'));
				$backorderEntry->SetStyle('direction: ltr; width: 300px;');
				if (!is_null($get['id']) && $editable === false) {
					$backorderEntry->SetReadOnly(true);
					$backorderEntry->SetEnabled(false);
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"backorder\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$backorderEntry->Get()."</td></tr>";

				if ($account === false) {
					// Customer ID
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == "Existing User") {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$customer_id = 0;
					if (isset($pageInfo['customer_id'])) {
						$customer_id = $pageInfo['customer_id'];
					}
					$customerIDCombo =& Piwi::CreateWidget('Combo', 'customer_id');
					$customerIDCombo->AddOption('Select...', 0);
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$jUser = new Jaws_User;
					$users = $jUser->GetUsers();
					foreach ($users as $user) {
						$customerIDCombo->AddOption($user['nickname'], $user['id']);
					}
					$customerIDCombo->SetDefault($customer_id);
					$customerIDCombo->AddEvent(ON_CHANGE, 'javascript:if(this.value!=0 && this.value!="0"){$$(".new_customers").each(function(element){element.style.display="none";});}else{$$(".new_customers").each(function(element){element.style.display="block";});}');
					$customerIDCombo->setTitle("Existing User");
					$form_content .= "<tr class=\"existing_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_id\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$customerIDCombo->Get()."</td></tr>";
				} else {
					$customerIDHidden =& Piwi::CreateWidget('HiddenEntry', 'customer_id', 0);
					$form_content .= $customerIDHidden->Get()."\n";
				}

				// Customer E-mail
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_EMAIL')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_email = (isset($pageInfo['customer_email'])) ? $pageInfo['customer_email'] : '';
				$keywordEntry =& Piwi::CreateWidget('Entry', 'customer_email', $customer_email);
				$keywordEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_EMAIL'));
				$keywordEntry->SetStyle('direction: ltr; width: 300px;');
				if (!is_null($get['id']) && $editable === false) {
					$keywordEntry->SetReadOnly(true);
					$keywordEntry->SetEnabled(false);
				}
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_email\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$keywordEntry->Get()."</td></tr>";
				
				// Customer Name
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_NAME')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_name = (isset($pageInfo['customer_name'])) ? $pageInfo['customer_name'] : '';
				$nameEntry =& Piwi::CreateWidget('Entry', 'customer_name', $customer_name);
				$nameEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_NAME'));
				$nameEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$nameEntry->Get()."</td></tr>";
				
				// Customer Company
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_COMPANY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_company = (isset($pageInfo['customer_company'])) ? $pageInfo['customer_company'] : '';
				$companyEntry =& Piwi::CreateWidget('Entry', 'customer_company', $customer_company);
				$companyEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_COMPANY'));
				$companyEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_company\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$companyEntry->Get()."</td></tr>";
				
				// Customer Address
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_ADDRESS')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_address = (isset($pageInfo['customer_address'])) ? $pageInfo['customer_address'] : '';
				$addressEntry =& Piwi::CreateWidget('Entry', 'customer_address', $customer_address);
				$addressEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_ADDRESS'));
				$addressEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_address\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$addressEntry->Get()."</td></tr>";
				
				// Customer City
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_CITY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_city = (isset($pageInfo['customer_city'])) ? $pageInfo['customer_city'] : '';
				$cityEntry =& Piwi::CreateWidget('Entry', 'customer_city', $customer_city);
				$cityEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_CITY'));
				$cityEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_city\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cityEntry->Get()."</td></tr>";
				
				// Customer Region
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_REGION')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_region = (isset($pageInfo['customer_region'])) ? $pageInfo['customer_region'] : '';
				$regionEntry =& Piwi::CreateWidget('Entry', 'customer_region', $customer_region);
				$regionEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_REGION'));
				$regionEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_region\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$regionEntry->Get()."</td></tr>";
				
				// Customer Postal
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_POSTAL')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_postal = (isset($pageInfo['customer_postal'])) ? $pageInfo['customer_postal'] : '';
				$postalEntry =& Piwi::CreateWidget('Entry', 'customer_postal', $customer_postal);
				$postalEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_POSTAL'));
				$postalEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_postal\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$postalEntry->Get()."</td></tr>";
				
				// Customer Country
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_COUNTRY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_country = (isset($pageInfo['customer_country'])) ? $pageInfo['customer_country'] : '';
				$countryEntry =& Piwi::CreateWidget('Entry', 'customer_country', $customer_country);
				$countryEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_COUNTRY'));
				$countryEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_country\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$countryEntry->Get()."</td></tr>";
				
				// Customer Phone
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_PHONE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_phone = (isset($pageInfo['customer_phone'])) ? $pageInfo['customer_phone'] : '';
				$phoneEntry =& Piwi::CreateWidget('Entry', 'customer_phone', $customer_phone);
				$phoneEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_PHONE'));
				$phoneEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_phone\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$phoneEntry->Get()."</td></tr>";
				
				// Customer Fax
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_FAX')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_fax = (isset($pageInfo['customer_fax'])) ? $pageInfo['customer_fax'] : '';
				$faxEntry =& Piwi::CreateWidget('Entry', 'customer_fax', $customer_fax);
				$faxEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_FAX'));
				$faxEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_fax\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$faxEntry->Get()."</td></tr>";

				// Customer Ship Name
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPNAME')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_shipname = (isset($pageInfo['customer_shipname'])) ? $pageInfo['customer_shipname'] : '';
				$shipnameEntry =& Piwi::CreateWidget('Entry', 'customer_shipname', $customer_shipname);
				$shipnameEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPNAME'));
				$shipnameEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipname\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipnameEntry->Get()."</td></tr>";
								
				// Customer Ship Address
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPADDRESS')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_shipaddress = (isset($pageInfo['customer_shipaddress'])) ? $pageInfo['customer_shipaddress'] : '';
				$shipaddressEntry =& Piwi::CreateWidget('Entry', 'customer_shipaddress', $customer_shipaddress);
				$shipaddressEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPADDRESS'));
				$shipaddressEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipaddress\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipaddressEntry->Get()."</td></tr>";
				
				// Customer Ship City
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPCITY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_shipcity = (isset($pageInfo['customer_shipcity'])) ? $pageInfo['customer_shipcity'] : '';
				$shipcityEntry =& Piwi::CreateWidget('Entry', 'customer_shipcity', $customer_shipcity);
				$shipcityEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPCITY'));
				$shipcityEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipcity\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipcityEntry->Get()."</td></tr>";
				
				// Customer Ship Region
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPREGION')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_shipregion = (isset($pageInfo['customer_shipregion']) ? $pageInfo['customer_shipregion'] : '');
				/*
				$google_script .= "<option value=\"\"".(empty($customer_region) ? $selected : '').">Select your State...</option>"; 
				$google_script .= "<option value=\"AL\" ".(strtolower($customer_region)=="al" || strtolower($customer_region)=="alabama" ? $selected : '').">Alabama</option>"; 
				$google_script .= "<option value=\"AK\" ".(strtolower($customer_region)=="ak" || strtolower($customer_region)=="alaska" ? $selected : '').">Alaska</option>";
				$google_script .= "<option value=\"AZ\" ".(strtolower($customer_region)=="az" || strtolower($customer_region)=="arizona" ? $selected : '').">Arizona</option>";
				$google_script .= "<option value=\"AR\" ".(strtolower($customer_region)=="ar" || strtolower($customer_region)=="arkansas" ? $selected : '').">Arkansas</option>";
				$google_script .= "<option value=\"CA\" ".(strtolower($customer_region)=="ca" || strtolower($customer_region)=="california" ? $selected : '').">California</option>";
				$google_script .= "<option value=\"CO\" ".(strtolower($customer_region)=="co" || strtolower($customer_region)=="colorado" ? $selected : '').">Colorado</option>";
				$google_script .= "<option value=\"CT\" ".(strtolower($customer_region)=="ct" || strtolower($customer_region)=="connecticut" ? $selected : '').">Connecticut</option>";
				$google_script .= "<option value=\"DE\" ".(strtolower($customer_region)=="de" || strtolower($customer_region)=="delaware" ? $selected : '').">Delaware</option>";
				$google_script .= "<option value=\"FL\" ".(strtolower($customer_region)=="fl" || strtolower($customer_region)=="florida" ? $selected : '').">Florida</option>";
				$google_script .= "<option value=\"GA\" ".(strtolower($customer_region)=="ga" || strtolower($customer_region)=="georgia" ? $selected : '').">Georgia</option>";
				$google_script .= "<option value=\"HI\" ".(strtolower($customer_region)=="hi" || strtolower($customer_region)=="hawaii" ? $selected : '').">Hawaii</option>";
				$google_script .= "<option value=\"ID\" ".(strtolower($customer_region)=="id" || strtolower($customer_region)=="idaho" ? $selected : '').">Idaho</option>";
				$google_script .= "<option value=\"IL\" ".(strtolower($customer_region)=="il" || strtolower($customer_region)=="illinois" ? $selected : '').">Illinois</option>";
				$google_script .= "<option value=\"IN\" ".(strtolower($customer_region)=="in" || strtolower($customer_region)=="indiana" ? $selected : '').">Indiana</option>";
				$google_script .= "<option value=\"IA\" ".(strtolower($customer_region)=="ia" || strtolower($customer_region)=="iowa" ? $selected : '').">Iowa</option>";
				$google_script .= "<option value=\"KS\" ".(strtolower($customer_region)=="ks" || strtolower($customer_region)=="kansas" ? $selected : '').">Kansas</option>";
				$google_script .= "<option value=\"KY\" ".(strtolower($customer_region)=="ky" || strtolower($customer_region)=="kentucky" ? $selected : '').">Kentucky</option>";
				$google_script .= "<option value=\"LA\" ".(strtolower($customer_region)=="la" || strtolower($customer_region)=="louisiana" ? $selected : '').">Louisiana</option>";
				$google_script .= "<option value=\"ME\" ".(strtolower($customer_region)=="me" || strtolower($customer_region)=="maine" ? $selected : '').">Maine</option>";
				$google_script .= "<option value=\"MD\" ".(strtolower($customer_region)=="md" || strtolower($customer_region)=="maryland" ? $selected : '').">Maryland</option>";
				$google_script .= "<option value=\"MA\" ".(strtolower($customer_region)=="ma" || strtolower($customer_region)=="massachusetts" ? $selected : '').">Massachusetts</option>";
				$google_script .= "<option value=\"MI\" ".(strtolower($customer_region)=="mi" || strtolower($customer_region)=="michigan" ? $selected : '').">Michigan</option>";
				$google_script .= "<option value=\"MN\" ".(strtolower($customer_region)=="mn" || strtolower($customer_region)=="minnesota" ? $selected : '').">Minnesota</option>";
				$google_script .= "<option value=\"MS\" ".(strtolower($customer_region)=="ms" || strtolower($customer_region)=="mississippi" ? $selected : '').">Mississippi</option>";
				$google_script .= "<option value=\"MO\" ".(strtolower($customer_region)=="mo" || strtolower($customer_region)=="missouri" ? $selected : '').">Missouri</option>";
				$google_script .= "<option value=\"MT\" ".(strtolower($customer_region)=="mt" || strtolower($customer_region)=="montana" ? $selected : '').">Montana</option>";
				$google_script .= "<option value=\"NE\" ".(strtolower($customer_region)=="ne" || strtolower($customer_region)=="nebraska" ? $selected : '').">Nebraska</option>";
				$google_script .= "<option value=\"NV\" ".(strtolower($customer_region)=="nv" || strtolower($customer_region)=="nevada" ? $selected : '').">Nevada</option>";
				$google_script .= "<option value=\"NH\" ".(strtolower($customer_region)=="nh" || strtolower($customer_region)=="new hampshire" ? $selected : '').">New Hampshire</option>";
				$google_script .= "<option value=\"NJ\" ".(strtolower($customer_region)=="nj" || strtolower($customer_region)=="new jersey" ? $selected : '').">New Jersey</option>";
				$google_script .= "<option value=\"NM\" ".(strtolower($customer_region)=="nm" || strtolower($customer_region)=="new mexico" ? $selected : '').">New Mexico</option>";
				$google_script .= "<option value=\"NY\" ".(strtolower($customer_region)=="ny" || strtolower($customer_region)=="new york" ? $selected : '').">New York</option>";
				$google_script .= "<option value=\"NC\" ".(strtolower($customer_region)=="nc" || strtolower($customer_region)=="north carolina" ? $selected : '').">North Carolina</option>";
				$google_script .= "<option value=\"ND\" ".(strtolower($customer_region)=="nd" || strtolower($customer_region)=="north dakota" ? $selected : '').">North Dakota</option>";
				$google_script .= "<option value=\"OH\" ".(strtolower($customer_region)=="oh" || strtolower($customer_region)=="ohio" ? $selected : '').">Ohio</option>";
				$google_script .= "<option value=\"OK\" ".(strtolower($customer_region)=="ok" || strtolower($customer_region)=="oklahoma" ? $selected : '').">Oklahoma</option>";
				$google_script .= "<option value=\"OR\" ".(strtolower($customer_region)=="or" || strtolower($customer_region)=="oregon" ? $selected : '').">Oregon</option>";
				$google_script .= "<option value=\"PA\" ".(strtolower($customer_region)=="pa" || strtolower($customer_region)=="pennsylvania" ? $selected : '').">Pennsylvania</option>";
				$google_script .= "<option value=\"RI\" ".(strtolower($customer_region)=="ri" || strtolower($customer_region)=="rhode island" ? $selected : '').">Rhode Island</option>";
				$google_script .= "<option value=\"SC\" ".(strtolower($customer_region)=="sc" || strtolower($customer_region)=="south carolina" ? $selected : '').">South Carolina</option>";
				$google_script .= "<option value=\"SD\" ".(strtolower($customer_region)=="sd" || strtolower($customer_region)=="south dakota" ? $selected : '').">South Dakota</option>";
				$google_script .= "<option value=\"TN\" ".(strtolower($customer_region)=="tn" || strtolower($customer_region)=="tennessee" ? $selected : '').">Tennessee</option>";
				$google_script .= "<option value=\"TX\" ".(strtolower($customer_region)=="tx" || strtolower($customer_region)=="texas" ? $selected : '').">Texas</option>";
				$google_script .= "<option value=\"UT\" ".(strtolower($customer_region)=="ut" || strtolower($customer_region)=="utah" ? $selected : '').">Utah</option>";
				$google_script .= "<option value=\"VT\" ".(strtolower($customer_region)=="vt" || strtolower($customer_region)=="vermont" ? $selected : '').">Vermont</option>";
				$google_script .= "<option value=\"VA\" ".(strtolower($customer_region)=="va" || strtolower($customer_region)=="virginia" ? $selected : '').">Virginia</option>";
				$google_script .= "<option value=\"WA\" ".(strtolower($customer_region)=="wa" || strtolower($customer_region)=="washington" ? $selected : '').">Washington</option>";
				$google_script .= "<option value=\"DC\" ".(strtolower($customer_region)=="dc" || strtolower($customer_region)=="washington d.c." ? $selected : '').">Washington D.C.</option>";
				$google_script .= "<option value=\"WV\" ".(strtolower($customer_region)=="wv" || strtolower($customer_region)=="west virginia" ? $selected : '').">West Virginia</option>";
				$google_script .= "<option value=\"WI\" ".(strtolower($customer_region)=="wi" || strtolower($customer_region)=="wisconsin" ? $selected : '').">Wisconsin</option>";
				$google_script .= "<option value=\"WY\" ".(strtolower($customer_region)=="wy" || strtolower($customer_region)=="wyoming" ? $selected : '').">Wyoming</option>";
				$google_script .= '</select>\';
				*/
				$shipregionEntry =& Piwi::CreateWidget('Entry', 'customer_shipregion', $customer_shipregion);
				$shipregionEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPREGION'));
				$shipregionEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipregion\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipregionEntry->Get()."</td></tr>";
				
				// Customer Ship Postal
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPPOSTAL')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_shippostal = (isset($pageInfo['customer_shippostal'])) ? $pageInfo['customer_shippostal'] : '';
				$shippostalEntry =& Piwi::CreateWidget('Entry', 'customer_shippostal', $customer_shippostal);
				$shippostalEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPPOSTAL'));
				$shippostalEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shippostal\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shippostalEntry->Get()."</td></tr>";
				
				// Customer Ship Country
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPCOUNTRY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customer_shipcountry = (isset($pageInfo['customer_shipcountry'])) ? $pageInfo['customer_shipcountry'] : '';
				$shipcountryEntry =& Piwi::CreateWidget('Entry', 'customer_shipcountry', $customer_shipcountry);
				$shipcountryEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPCOUNTRY'));
				$shipcountryEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipcountry\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipcountryEntry->Get()."</td></tr>";
				
				if (
					is_null($get['id']) || 
					(isset($pageInfo['customer_cc_type']) && !empty($pageInfo['customer_cc_type']) &&
					isset($pageInfo['customer_cc_number']) && !empty($pageInfo['customer_cc_number']) &&
					isset($pageInfo['customer_cc_exp_month']) && !empty($pageInfo['customer_cc_exp_month']) &&
					isset($pageInfo['customer_cc_exp_year']) && !empty($pageInfo['customer_cc_exp_year']))
				) {
					require_once JAWS_PATH . 'include/Jaws/Crypt.php';
					$JCrypt = new Jaws_Crypt();
					$JCrypt->Init(true);
					
					// Customer CC Type
					$helpString = _t('ECOMMERCE_CUSTOMER_CC_TYPE');
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_TYPE')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$customer_cc_type = '';
					if (isset($pageInfo['customer_cc_type'])) {
						$customer_cc_type = $JCrypt->rsa->decrypt($pageInfo['customer_cc_type'], $JCrypt->pvt_key);
						if (Jaws_Error::isError($customer_cc_type)) {
							$customer_cc_type = '';
						}
					}
					$cc_typeCombo =& Piwi::CreateWidget('Combo', 'customer_cc_type');
					$cc_typeCombo->AddOption('Select...', '');
					$cc_typeCombo->AddOption('Visa', 'Visa');
					$cc_typeCombo->AddOption('MasterCard', 'MasterCard');
					$cc_typeCombo->AddOption('Discover', 'Discover');
					$cc_typeCombo->AddOption('Amex', 'Amex');
					$cc_typeCombo->SetDefault($customer_cc_type);
					$cc_typeCombo->setTitle(_t('ECOMMERCE_CUSTOMER_CC_TYPE'));
					if (!is_null($get['id']) && $editable === false) {
						$cc_typeCombo->SetEnabled(false);
					}
					$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_typeCombo->Get()."</td></tr>";
					
					// Customer CC Number
					$helpString = _t('ECOMMERCE_CUSTOMER_CC_NUMBER');
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_NUMBER')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$customer_cc_number = (isset($pageInfo['customer_cc_number'])) ? $pageInfo['customer_cc_number'] : '';
					$customer_cc_number = $JCrypt->rsa->decrypt($customer_cc_number, $JCrypt->pvt_key);
					if (Jaws_Error::isError($customer_cc_number)) {
						$customer_cc_number = '';
					}
					$cc_numberEntry =& Piwi::CreateWidget('Entry', 'customer_cc_number', $customer_cc_number);
					$cc_numberEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_CC_NUMBER'));
					$cc_numberEntry->SetStyle('direction: ltr; width: 300px;');
					if (!is_null($get['id']) && $editable === false) {
						$cc_numberEntry->SetReadOnly(true);
						$cc_numberEntry->SetEnabled(false);
					}
					$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_number\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_numberEntry->Get()."</td></tr>";
					
					// Customer CC Exp Month
					$helpString = _t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH');
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$customer_cc_exp_month = '';
					if (isset($pageInfo['customer_cc_exp_month'])) {
						$customer_cc_exp_month = $pageInfo['customer_cc_exp_month'];
					}
					$cc_exp_monthCombo =& Piwi::CreateWidget('Combo', 'customer_cc_exp_month');
					for ($m=1;$m<12;$m++) {
						if ($m < 10) {
							$m = '0'.$m;
						}
						$cc_exp_monthCombo->AddOption($m, $m);
					}
					$cc_exp_monthCombo->SetDefault($customer_cc_exp_month);
					$cc_exp_monthCombo->setTitle(_t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH'));
					if (!is_null($get['id']) && $editable === false) {
						$cc_exp_monthCombo->SetEnabled(false);
					}
					$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_exp_month\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_exp_monthCombo->Get()."</td></tr>";
							
					// Customer CC Exp Year
					$helpString = _t('ECOMMERCE_CUSTOMER_CC_EXP_YEAR');
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_EXP_YEAR')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$customer_cc_exp_year = (int)date('Y');
					if (isset($pageInfo['customer_cc_exp_year'])) {
						$customer_cc_exp_year = (int)$pageInfo['customer_cc_exp_year'];
					}
					$cc_exp_yearCombo =& Piwi::CreateWidget('Combo', 'customer_cc_exp_year');
					for ($y=(((int)date('Y'))+20);$y>((int)date('Y'))-1;$y--) {
						$cc_exp_yearCombo->AddOption($y, $y);
					}
					$cc_exp_yearCombo->SetDefault($customer_cc_exp_year);
					$cc_exp_yearCombo->setTitle(_t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH'));
					if (!is_null($get['id']) && $editable === false) {
						$cc_exp_yearCombo->SetEnabled(false);
					}
					$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_exp_year\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_exp_yearCombo->Get()."</td></tr>";
					
					// Customer CC CVV
					$helpString = _t('ECOMMERCE_CUSTOMER_CC_CVV');
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_CVV')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$customer_cc_cvv = (isset($pageInfo['customer_cc_cvv'])) ? $pageInfo['customer_cc_cvv'] : '';
					$customer_cc_cvv = $JCrypt->rsa->decrypt($customer_cc_cvv, $JCrypt->pvt_key);
					if (Jaws_Error::isError($customer_cc_cvv)) {
						$customer_cc_cvv = '';
					}
					$cc_cvvEntry =& Piwi::CreateWidget('Entry', 'customer_cc_cvv', $customer_cc_cvv);
					$cc_cvvEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_CC_NUMBER'));
					$cc_cvvEntry->SetStyle('direction: ltr; width: 300px;');
					if (!is_null($get['id']) && $editable === false) {
						$cc_cvvEntry->SetReadOnly(true);
						$cc_cvvEntry->SetEnabled(false);
					}
					$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_cvv\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_cvvEntry->Get()."</td></tr>";
				}
				
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
		}
		
        $tpl->ParseBlock('gadget_page');
        return $tpl->Get();

    }

    /**
     * We are on the form_post page
     *
     * @access public
     * @return string
     */
    function form_post($account = false, $fuseaction = '', $params = array(), $gateway = '')
    {
		$request =& Jaws_Request::getInstance();
		if (empty($fuseaction)) {
			$fuseaction = $request->get('fuseaction', 'post');
		}
		if (empty($gateway)) {
			$gateway = $request->get('gateway', 'post');
		}
		$get  = $request->get(array('fuseaction', 'linkid', 'id', 'gateway'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
		
        if (empty($gateway)) {
			$gateway = $get['gateway'];
		}
		
		// check session
		if (
			empty($gateway) || ($gateway != '463fdab1475b6227078a3929e71e9c4d' && 
			$gateway != '15669f086da3aba94b4875bd741056bc' && 
			$gateway != '50b58c7352d3cb2b06b5f920a74fc5ca' && 
			$gateway != 'ad69e733ebae8d264bccaa38d68830e8')
		) {
			if ($account === false) {
				$GLOBALS['app']->Session->CheckPermission('Ecommerce', 'default');
			} else {
				$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
				if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
					if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
						$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
						$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
						return $userHTML->DefaultAction();
					}
				}
			}
		}

		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
        
		$adminModel = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$output_html = '';
		
		$editorder = false;
		$editshipping = false;
		$edittax = false;

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddOrder": 
						$keys = array(
							'orderno', 'prod_id', 'price', 'qty', 'unit', 'total', 'weight', 'attribute', 
							'backorder', 'description', 'recurring', 'gadget_table', 'gadget_id', 'Active',
							'customer_email', 'customer_name', 'customer_company', 'customer_address', 'customer_address2', 
							'customer_city', 'customer_region', 'customer_postal', 'customer_country', 
							'customer_phone', 'customer_fax', 'customer_shipname', 'customer_shipaddress', 
							'customer_shipaddress2', 'customer_shipcity', 'customer_shipregion', 
							'customer_shippostal', 'customer_shipcountry', 'shiptype', 'OwnerID', 'customer_id',
							'sales_id', 'customer_cc_type', 'customer_cc_number', 'customer_cc_exp_month', 
							'customer_cc_exp_year', 'customer_cc_cvv', 'update_status_note'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						/*
						foreach($postData as $key => $value) {
							$output_html .= '<br />'.$key."=".$value."\n";
						}
						*/
						// add OwnerID if no permissions or not sent from gateway
						if (isset($postData['OwnerID']) && !empty($postData['OwnerID'])) {
							$OwnerID = (int)$postData['OwnerID'];
						} else if (
							!empty($gateway) && ($gateway == '463fdab1475b6227078a3929e71e9c4d' || 
							$gateway == '15669f086da3aba94b4875bd741056bc' || 
							$gateway == '50b58c7352d3cb2b06b5f920a74fc5ca' || 
							$gateway == 'ad69e733ebae8d264bccaa38d68830e8')
						) {
							$OwnerID = null;
						} else {
							if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') && $account === false) {
								$OwnerID = null;
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							}
						}
						$result = $adminModel->AddOrder(
							str_replace('-', '', $postData['orderno']), $postData['prod_id'], '', '', '', $postData['weight'], 
							'', $postData['backorder'], $postData['description'], $postData['recurring'], 
							$postData['gadget_table'], $postData['gadget_id'], $OwnerID, $postData['Active'], $postData['customer_email'], 
							$postData['customer_name'],  $postData['customer_company'], $postData['customer_address'], $postData['customer_address2'], 
							$postData['customer_city'], $postData['customer_region'], $postData['customer_postal'], $postData['customer_country'], 
							$postData['customer_phone'], $postData['customer_fax'], $postData['customer_shipname'], $postData['customer_shipaddress'], 
							$postData['customer_shipaddress2'], $postData['customer_shipcity'], $postData['customer_shipregion'], 
							$postData['customer_shippostal'], $postData['customer_shipcountry'], $postData['total'], $postData['shiptype'], 
							$postData['customer_id'], $postData['sales_id'], $postData['customer_cc_type'], $postData['customer_cc_number'],
							$postData['customer_cc_exp_month'], $postData['customer_cc_exp_year'], $postData['customer_cc_cvv'], 
							$postData['update_status_note']
						);
						if ($result && !Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editorder = true;
						} else {
							$editorder = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							$header = 'Sorry'.(isset($postData['customer_name']) && !empty($postData['customer_name']) ? '&nbsp;'.$postData['customer_name'] : '').', but an error occurred while processing your order.';
							$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
							$output_html .= '<h1><img border="0" align="left" style="padding-right: 10px; padding-bottom: 10px;" src="'.$icon.'" />'.$header.'</h1>';
							return $output_html.(Jaws_Error::IsError($result) ? '<p>'.$result->GetMessage().'</p>' : '').'<br />'.$link->Get();
						}
						break;
                case "EditOrder": 
						$keys = array(
							'ID', 'orderno', 'prod_id', 'price', 'qty', 'unit', 'total', 'weight', 'attribute', 
							'backorder', 'description', 'recurring', 'gadget_table', 'gadget_id', 'Active',
							'customer_email', 'customer_name', 'customer_company', 'customer_address', 'customer_address2', 
							'customer_city', 'customer_region', 'customer_postal', 'customer_country', 
							'customer_phone', 'customer_fax', 'customer_shipname', 'customer_shipaddress', 
							'customer_shipaddress2', 'customer_shipcity', 'customer_shipregion', 
							'customer_shippostal', 'customer_shipcountry', 'shiptype',
							'sales_id', 'customer_cc_type', 'customer_cc_number', 'customer_cc_exp_month', 
							'customer_cc_exp_year', 'customer_cc_cvv', 'update_status_note'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						if (isset($postData['ID'])) {
							if (!empty($gateway) && ($gateway == '463fdab1475b6227078a3929e71e9c4d' || $gateway == '15669f086da3aba94b4875bd741056bc' || $gateway == '50b58c7352d3cb2b06b5f920a74fc5ca' || $gateway == 'ad69e733ebae8d264bccaa38d68830e8')) {
								$result = $adminModel->UpdateOrder(
									$postData['ID'], $postData['orderno'], $postData['prod_id'], '', '', '', 
									$postData['weight'], '', $postData['backorder'], $postData['description'], $postData['recurring'], 
									$postData['gadget_table'], $postData['gadget_id'], $postData['Active'], $postData['customer_email'], 
									$postData['customer_name'],  $postData['customer_company'], $postData['customer_address'], $postData['customer_address2'], 
									$postData['customer_city'], $postData['customer_region'], $postData['customer_postal'], $postData['customer_country'], 
									$postData['customer_phone'], $postData['customer_fax'], $postData['customer_shipname'], $postData['customer_shipaddress'], 
									$postData['customer_shipaddress2'], $postData['customer_shipcity'], $postData['customer_shipregion'], 
									$postData['customer_shippostal'], $postData['customer_shipcountry'], $postData['total'], $postData['shiptype'], 
									$postData['sales_id'], $postData['customer_cc_type'], $postData['customer_cc_number'],
									$postData['customer_cc_exp_month'], $postData['customer_cc_exp_year'], $postData['customer_cc_cvv'], 
									$postData['update_status_note']
								);
							} else {
								// add OwnerID if no permissions
								if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
									$result = $adminModel->UpdateOrder(
										$postData['ID'], $postData['orderno'], $postData['prod_id'], '', '', '', 
										$postData['weight'], '', $postData['backorder'], $postData['description'], $postData['recurring'], 
										$postData['gadget_table'], $postData['gadget_id'], $postData['Active'], $postData['customer_email'], 
										$postData['customer_name'],  $postData['customer_company'], $postData['customer_address'], $postData['customer_address2'], 
										$postData['customer_city'], $postData['customer_region'], $postData['customer_postal'], $postData['customer_country'], 
										$postData['customer_phone'], $postData['customer_fax'], $postData['customer_shipname'], $postData['customer_shipaddress'], 
										$postData['customer_shipaddress2'], $postData['customer_shipcity'], $postData['customer_shipregion'], 
										$postData['customer_shippostal'], $postData['customer_shipcountry'], $postData['total'], $postData['shiptype'], 
										$postData['sales_id'], $postData['customer_cc_type'], $postData['customer_cc_number'],
										$postData['customer_cc_exp_month'], $postData['customer_cc_exp_year'], $postData['customer_cc_cvv'], 
										$postData['update_status_note']
									);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$parent = $model->GetOrder((int)$postData['ID']);
									if ($OwnerID == $parent['ownerid']) {
										$result = $adminModel->UpdateOrder(
											$postData['ID'], $postData['orderno'], $postData['prod_id'], '', '', '', 
											$postData['weight'], '', $postData['backorder'], $postData['description'], $postData['recurring'], 
											$postData['gadget_table'], $postData['gadget_id'], $postData['Active'], $postData['customer_email'], 
											$postData['customer_name'],  $postData['customer_company'], $postData['customer_address'], $postData['customer_address2'], 
											$postData['customer_city'], $postData['customer_region'], $postData['customer_postal'], $postData['customer_country'], 
											$postData['customer_phone'], $postData['customer_fax'], $postData['customer_shipname'], $postData['customer_shipaddress'], 
											$postData['customer_shipaddress2'], $postData['customer_shipcity'], $postData['customer_shipregion'], 
											$postData['customer_shippostal'], $postData['customer_shipcountry'], $postData['total'], $postData['shiptype'], 
											$postData['sales_id'], $postData['customer_cc_type'], $postData['customer_cc_number'],
											$postData['customer_cc_exp_month'], $postData['customer_cc_exp_year'], $postData['customer_cc_cvv'], 
											$postData['update_status_note']
										);
									} else {
										return _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									}
								}
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$editorder = true;
						} else {
							$editorder = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							$header = 'Sorry'.(isset($postData['customer_name']) && !empty($postData['customer_name']) ? '&nbsp;'.$postData['customer_name'] : '').', but an error occurred while processing your order.';
							$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
							$output_html = '<h1><img border="0" align="left" style="padding-right: 10px; padding-bottom: 10px;" src="'.$icon.'" />'.$header.'</h1>';
							return $output_html.(Jaws_Error::IsError($result) ? '<p>'.$result->GetMessage().'</p>' : '').'<br />'.$link->Get();
						}
                       break;
                case "DeleteOrder": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('ID');
						$postData = $request->get($keys, 'post');
						$id = $postData['ID'];
						if (is_null($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
								$result = $adminModel->DeleteOrder((int)$id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetOrder((int)$id);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->DeleteOrder((int)$id);
								} else {
									return _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}						
						if ($result && !Jaws_Error::IsError($result)) {
							$editorder = true;
						} else {
							$editorder = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							$header = 'Sorry'.(isset($postData['customer_name']) && !empty($postData['customer_name']) ? '&nbsp;'.$postData['customer_name'] : '').', but an error occurred while processing your order.';
							$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
							$output_html = '<h1><img border="0" align="left" style="padding-right: 10px; padding-bottom: 10px;" src="'.$icon.'" />'.$header.'</h1>';
							return $output_html.(Jaws_Error::IsError($result) ? '<p>'.$result->GetMessage().'</p>' : '').'<br />'.$link->Get();
						}
						break;
                case "AddShipping": 
						$keys = array(
							'type', 'price', 'minfactor', 'maxfactor', 'title', 'Active',
							'description', 'sort_order'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions or not sent from gateway
						if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddShipping(
							$postData['sort_order'], $postData['type'], $postData['title'], $postData['minfactor'], $postData['maxfactor'], $postData['price'], 
							$postData['description'], $OwnerID, $postData['Active']
						);
						if ($result && !Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editshipping = true;
						} else {
							$editshipping = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'</p><br />'.$link->Get();
						}
						break;
                case "EditShipping": 
						$keys = array(
							'ID', 'type', 'price', 'minfactor', 'maxfactor', 'title', 'Active',
							'description', 'sort_order'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
								$result = $adminModel->UpdateShipping(
									$postData['ID'], $postData['sort_order'], $postData['type'], $postData['title'], $postData['minfactor'], $postData['maxfactor'], 
									$postData['price'], $postData['description'], $postData['Active']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetOrder((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateShipping(
										$postData['ID'], $postData['sort_order'], $postData['type'], $postData['title'], $postData['minfactor'], $postData['maxfactor'], 
										$postData['price'], $postData['description'], $postData['Active']
									);
								} else {
									return _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$editshipping = true;
						} else {
							$editshipping = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'</p><br />'.$link->Get();
						}
                       break;
                case "DeleteShipping": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('ID');
						$postData = $request->get($keys, 'post');
						$id = $postData['ID'];
						if (is_null($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
								$result = $adminModel->DeleteShipping((int)$id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetShipping((int)$id);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->DeleteShipping((int)$id);
								} else {
									return _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}						
						if ($result && !Jaws_Error::IsError($result)) {
							$editshipping = true;
						} else {
							$editshipping = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'</p><br />'.$link->Get();
						}
						break;
            }
			
			// TODO: Insert each line item into order table
			// If GoogleCheckout or PayPal
			$output_html = "";
			if (
				!empty($gateway) && ($gateway == '463fdab1475b6227078a3929e71e9c4d' || 
				$gateway == '50b58c7352d3cb2b06b5f920a74fc5ca' || 
				$gateway == 'ad69e733ebae8d264bccaa38d68830e8')
			) {
				// Send us to the appropriate page
				if ($editorder === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$output_html .= '<p>&nbsp;</p>';
						$output_html .= '<table width="100%" border="0" cellspacing="0" cellpadding="3" id="syntactsCategories_order'.$OwnerID.'" class="syntactsCategories">'."\n";
						$output_html .= '<tbody>'."\n";
						$output_html .= '<tr noDrop="true" noDrag="true" id="syntactsCategories_head">'."\n";
						$output_html .= '<TD WIDTH="50%" class="color_bkgnd_primary" style="padding: 3px;">Order Information&nbsp;</TD>'."\n";
						$output_html .= '<TD WIDTH="50%" class="color_bkgnd_primary" style="padding: 3px;">&nbsp;</TD>'."\n";				
						$output_html .= '</tr>'."\n";
						$output_html .= '<tr noDrop="true" noDrag="true">'."\n";
						$output_html .= '<td style="background: #EDF3FE; border-top: dotted 1pt #E2E2E2;" class="syntacts-form-row" style="padding: 10px;"><b>Order Number:</b></td>'."\n";
						$output_html .= '<td style="background: #EDF3FE; border-top: dotted 1pt #E2E2E2;" class="syntacts-form-row" style="padding: 10px;">'.$postData['orderno'].'-'.($OwnerID > 0 ? $OwnerID : '0').'-'.($postData['customer_id'] > 0 ? $postData['customer_id'] : '0').'</td>'."\n";
						$output_html .= '</tr>'."\n";
						$output_html .= '<tr noDrop="true" noDrag="true">'."\n";
						$output_html .= '<td style="background: #FFFFFF;" class="syntacts-form-row" style="padding: 10px;"><b>Description:</b></td>'."\n";
						$descriptions = explode('__SYNTACTS__', str_replace($postData['orderno'].'-'.$OwnerID.'-'.$postData['customer_id'], '', $postData['description']));
						$desc_html = '';
						foreach ($descriptions as $desc) {
							$descs = explode('<|>', $desc);
							if (isset($descs[1]) && !empty($descs[1]) && isset($descs[2]) && !empty($descs[2])) {
								$desc_html .= '<b>'.$descs[1].'</b><br />'.substr($descs[2], (strpos($descs[2], ',')+1), strlen($descs[2])).'<br />';
								if (isset($descs[3]) && !empty($descs[3])) {
									$desc_html .= 'Qty: '.$descs[3];
								}
								$desc_html .= '<br />&nbsp;<br />'."\n";
							} else {
								$desc_html .= $desc."\n";
							}
						}
						$output_html .= '<td style="background: #FFFFFF;" class="syntacts-form-row" style="padding: 10px;">'.$desc_html.'</td>'."\n";
						$output_html .= '</tr>'."\n";
						$output_html .= '<tr noDrop="true" noDrag="true">'."\n";
						$output_html .= '<td style="background: #EDF3FE;" class="syntacts-form-row" style="padding: 10px;"><b>Order Total:</b></td>'."\n";
						$output_html .= '<td style="background: #EDF3FE;" class="syntacts-form-row" style="padding: 10px;">'.(!empty($postData['total']) && $postData['total'] > 0 ? $postData['total'] : $postData['price']).'</td>'."\n";
						$output_html .= '</tr>'."\n";
						if ($OwnerID > 0) {
							require_once JAWS_PATH . 'include/Jaws/User.php';
							$jUser = new Jaws_User;
							$userInfo = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
							if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id'])) {
								$output_html .= '<tr noDrop="true" noDrag="true">'."\n";
								$output_html .= '<td style="background: #FFFFFF;" class="syntacts-form-row" style="padding: 10px;"><b>Merchant:</b></td>'."\n";
								$output_html .= '<td style="background: #FFFFFF;" class="syntacts-form-row" style="padding: 10px;">'.(!empty($userInfo['company']) ? $userInfo['company'] : (!empty($userInfo['nickname']) ? $userInfo['nickname'] : 'Merchant ID: '.$OwnerID)).'</td>'."\n";
								$output_html .= '</tr>'."\n";
							}
						}
						if (!empty($postData['prod_id']) && (strpos($postData['prod_id'], ',') !== false || (int)$postData['prod_id'] > 0)) {
							$storeModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
							$order_items_html = '';
							$prod_ids = explode(',',$postData['prod_id']);
							foreach ($prod_ids as $prod_id) {
								$prod_id = (int)$prod_id;
								if ($prod_id > 0) {
									$product = $storeModel->GetProduct($prod_id);
									if (!Jaws_Error::IsError($product) && !empty($product['title']) && $product['ownerid'] == $OwnerID) {
										$order_items_html .= (!empty($order_items_html) ? ', ' : '').'<a href="'.$GLOBALS['app']->GetSiteURL() . '/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $product['fast_url'])).'">'.$product['title'].'</a>';
									//} else if (Jaws_Error::IsError($product)) {
									//	$jaws_error = new Jaws_Error($product->GetMessage().' '.var_export($postData, true).' File:'.__FILE__ . ', Line: ' . __LINE__, _t('ECOMMERCE_NAME'));
									}
								}
							}
							if (!empty($order_items_html)) {
								$output_html .= '<tr noDrop="true" noDrag="true">'."\n";
								$output_html .= '<td style="background: #EDF3FE;" class="syntacts-form-row" style="padding: 10px;"><b>Order Items:</b></td>'."\n";
								$output_html .= '<td style="background: #EDF3FE;" class="syntacts-form-row" style="padding: 10px;">'.$order_items_html.'</td>'."\n";
								$output_html .= '</tr>'."\n";
							}
						}
						$output_html .= '</table>'."\n";
						$output_html .= '<p>&nbsp;</p>'."\n";
						
						if ($fuseaction == 'DeleteOrder') {
							$redirect = BASE_SCRIPT . '?gadget=Ecommerce&action=Admin';
						} else if (is_numeric($result)) {
							$redirect = BASE_SCRIPT . '?gadget=Ecommerce&action=view&id='.$result;
						} else if (isset($postData['ID'])) {
							$redirect = BASE_SCRIPT . '?gadget=Ecommerce&action=view='.$postData['ID'];
						} else {
							$redirect = BASE_SCRIPT . '?gadget=Ecommerce&action=view='.$get['id'];
						}
					}
				} else if ($editshipping === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$redirect = BASE_SCRIPT . '?gadget=Ecommerce&action=B&id='.$get['linkid'];
					}
				} else if ($edittax === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$redirect = BASE_SCRIPT . '?gadget=Ecommerce&action=C';
					}
				} else {
					if ($account === false) {
						Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ecommerce');
					} else {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					}
				}
			} else {
				if ($account === false) {
					Jaws_Header::Location($redirect);
				} else {
					if ($editorder === true) {
						$output_html .= "<script>\n";
						$output_html .= "	if (window.opener && !window.opener.closed) {\n";
						$output_html .= "		window.opener.location.reload();\n";
						$output_html .= "	}\n";
						if ($fuseaction == 'DeleteOrder') {
							$output_html .= "	window.location.href='".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."';\n";
						} else {
							$output_html .= "	window.location.href='index.php?gadget=Ecommerce&action=account_view&id=".(is_numeric($result) ? $result : $postData['ID'])."';\n";
						}
						$output_html .= "</script>\n";
						$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					} else if ($editshipping === true || $edittax === true) {
						$output_html .= "<script>\n";
						$output_html .= "if (window.opener && !window.opener.closed) {\n";
						$output_html .= "	window.opener.location.reload();\n";
						$output_html .= "	window.close();\n";
						$output_html .= "}\n";
						$output_html .= "</script>\n";
						$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					}
				}
			}

		} else {
			if ($account === false) {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ecommerce');
			} else {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}
		return $output_html;

    }

    /**
     * We are on the view page
     *
     * @access public
     * @return string
     */
    function view($account = false)
    {
		//$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ecommerce', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
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
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		$pid = $request->get('id', 'get');
		$orderno = $request->get('orderno', 'get');
		
		require_once JAWS_PATH . 'include/Jaws/Crypt.php';
		$JCrypt = new Jaws_Crypt();
		$JCrypt->Init(true);
		
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		// initialize template
		$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('msgbox-wrapper');
        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if (($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) && $responses) {
            foreach ($responses as $msg_id => $response) {
                $tpl->SetBlock('msgbox-wrapper/msgbox');
                $tpl->SetVariable('msg-css', $response['css']);
                $tpl->SetVariable('msg-txt', $response['message']);
                $tpl->SetVariable('msg-id', $msg_id);
                $tpl->ParseBlock('msgbox-wrapper/msgbox');
            }
        }
        $tpl->ParseBlock('msgbox-wrapper');
		$tpl->SetBlock('gadget_page');
		$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");
        $tpl->SetVariable('actionsTitle', _t('ECOMMERCE_ACTIONS'));
		
		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Ecommerce&amp;action=Admin';";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$uid = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$this->AjaxMe('client_script.js');
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ecommerce/admin_Ecommerce_view");
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}

		$GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css', 'default');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
        //$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');
		/*
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simplewhite.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
		*/

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Ecommerce');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
				$stpl->LoadFromString($snoopy->results);
				$stpl->SetBlock('view');
			
				$editable = true;
				if (!is_null($pid) || !is_null($orderno)) {
					$editable = false;
					$submit_vars['SUBMIT_BUTTON'] = '';

					$orders = array();
					if (!is_null($pid)) {
						$pageInfo = $model->GetOrder($pid);
						if (Jaws_Error::IsError($pageInfo) || !isset($pageInfo['id']) || empty($pageInfo['id'])) {
							return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
						}
						$orders[] = $pageInfo;
					} else {
						$orders = $model->GetAllItemsOfOrderNo($orderno);
						if (Jaws_Error::IsError($orders) || !isset($orders[0]['id']) || empty($orders[0]['id'])) {
							return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
						}
					}
					
					$order_status = $orders[0]['active'];
					$orderno = $orders[0]['orderno'];
					$OwnerID = $orders[0]['ownerid'];
					$CustID = $orders[0]['customer_id'];
					
					$viewtpl = new Jaws_Template('gadgets/Ecommerce/templates/');
					$viewtpl->Load('ViewOrder.html');
					
					$viewtpl->SetBlock('view_order');
					
					foreach ($orders as $pageInfo) {
						if (
							($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || 
							$pageInfo['ownerid'] == $uid || $pageInfo['customer_id'] == $uid) && 
							strpos($pageInfo['description'], 's:12:"Handling fee"') === false
						) {
							if (
								$GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || 
								$pageInfo['ownerid'] == $uid
							) {
								if (!is_null($pid)) {
									$editable = true;
									$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
								}
							}
													
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
													
							$viewtpl->SetBlock('view_order/order');
							$viewtpl->SetVariable('OwnerID', $pageInfo['ownerid']);
							$viewtpl->SetVariable('order_status', $order_status);
							$viewtpl->SetVariable('orderno', $pageInfo['orderno'].'-'.($pageInfo['ownerid'] > 0 ? $pageInfo['ownerid'] : '0').'-'.($CustID > 0 ? $CustID : '0'));
							$viewtpl->SetVariable('order_total', (!empty($pageInfo['total']) && $pageInfo['total'] > 0 ? $pageInfo['total'] : $pageInfo['price']));
							if ((int)$pageInfo['ownerid'] > 0) {
								$ownerInfo = $jUser->GetUserInfoById((int)$pageInfo['ownerid'], true, true, true, true);
								if (!Jaws_Error::IsError($ownerInfo) && isset($ownerInfo['id'])) {
									$viewtpl->SetBlock('view_order/order/merchant');
									$viewtpl->SetVariable('merchant_name', $xss->filter(preg_replace("[^A-Za-z0-9\:\ \,]", '', (!empty($ownerInfo['company']) ? $ownerInfo['company'] : (!empty($ownerInfo['nickname']) ? $ownerInfo['nickname'] : 'ID: '.$pageInfo['ownerid'])))));
									if (
										(($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) || ($uid == $ownerInfo['id'])) && 
										isset($ownerInfo['merchant_id']) && !empty($ownerInfo['merchant_id'])
									) {
										$viewtpl->SetVariable('merchant_id', $ownerInfo['merchant_id']);
									}
									$viewtpl->ParseBlock('view_order/order/merchant');
								}
							}
							if (!empty($pageInfo['description'])) {
								$pInfo = unserialize($pageInfo['description']);
								$pDesc = $pInfo['description'];
								$viewtpl->SetVariable('order_description', $xss->filter($pDesc));
								if (isset($pInfo['items']) && is_array($pInfo['items']) && !count($pInfo['items']) <= 0) {
									foreach ($pInfo['items'] as $item_owner => $items) {
										//foreach ($items as $item) {
											$viewtpl->SetBlock('view_order/order/order_item');
											$viewtpl->SetVariable('item_link', $items['itemurl']);
											$viewtpl->SetVariable('item_title', $xss->filter(preg_replace("[^A-Za-z0-9\:\ \,]", '', $items['name'])));
											$viewtpl->SetVariable('item_price', $items['amt']);
											$viewtpl->SetVariable('item_qty', $items['qty']);
											$viewtpl->SetVariable('item_details', $xss->filter($items['desc']));
											$viewtpl->ParseBlock('view_order/order/order_item');
										//}
									}
								}
								if (isset($pInfo['customcheckoutfields']) && is_array($pInfo['customcheckoutfields']) && !count($pInfo['customcheckoutfields']) <= 0) {
									foreach ($pInfo['customcheckoutfields'] as $ck => $cv) {
										$viewtpl->SetBlock('view_order/order/customfield');
										$viewtpl->SetVariable('custom_name', $ck);
										$viewtpl->SetVariable('custom_value', $xss->filter($JCrypt->rsa->decrypt($cv, $JCrypt->pvt_key)));
										$viewtpl->ParseBlock('view_order/order/customfield');
									}
								}
							}
							$viewtpl->ParseBlock('view_order/order');
						}
					}
					
					if (!empty($gateway_logo) && file_exists(JAWS_DATA . 'files'.$xss->filter($gateway_logo))) {
						$viewtpl->SetBlock('view_order/gateway_logo');
						$viewtpl->SetVariable('gateway_logo', $GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($gateway_logo));
						$viewtpl->ParseBlock('view_order/gateway_logo');
					}
					
					$invnum = 'Details';
					if (isset($orderno) && !empty($orderno)) {
						$invnum = 'Number:&nbsp;'.$orderno.(!is_null($pid) ? '-'.($OwnerID > 0 ? $OwnerID : '0').'-'.($CustID > 0 ? $CustID : '0') : '');
					}
					
					$title = "Order&nbsp;".$invnum;
					if ($order_status == 'DELIVERED' || $order_status == 'ACTIVE' || $order_status == 'PAIDINFULL') { 
						$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_success.png';
					} else {
						$icon = $GLOBALS['app']->GetJawsURL() . '/gadgets/Ecommerce/images/payment_error.png';
					}
													
					$viewtpl->SetBlock('view_order/billing');
					$viewtpl->SetVariable('OwnerID', $CustID);
					if (!empty($customer_cc_number) && !empty($customer_cc_type)) {
						$viewtpl->SetBlock('view_order/billing/customer_cc_type');
						$viewtpl->SetVariable('customer_cc_type', $customer_cc_type);
						$viewtpl->ParseBlock('view_order/billing/customer_cc_type');
						$viewtpl->SetBlock('view_order/billing/customer_cc_number');
						if ($account === false) {
							$viewtpl->SetVariable('customer_cc_number', $customer_cc_number);
							$viewtpl->ParseBlock('view_order/billing/customer_cc_number');
							$viewtpl->SetBlock('view_order/billing/customer_cc_exp');
							$viewtpl->SetVariable('customer_cc_exp_month', $pageInfo['customer_cc_exp_month']);
							$viewtpl->SetVariable('customer_cc_exp_year', $pageInfo['customer_cc_exp_year']);
							$viewtpl->ParseBlock('view_order/billing/customer_cc_exp');
							$viewtpl->SetBlock('view_order/billing/customer_cc_cvv');
							$viewtpl->SetVariable('customer_cc_cvv', $customer_cc_cvv);
							$viewtpl->ParseBlock('view_order/billing/customer_cc_cvv');
						} else {
							$viewtpl->SetVariable('customer_cc_number', '************'.substr($customer_cc_number, -4));
							$viewtpl->ParseBlock('view_order/billing/customer_cc_number');
						}
					}
					$viewtpl->SetBlock('view_order/billing/customer_name');
					$viewtpl->SetVariable('customer_name', $pageInfo['customer_name']);
					$viewtpl->ParseBlock('view_order/billing/customer_name');
					$viewtpl->SetBlock('view_order/billing/customer_email');
					$viewtpl->SetVariable('customer_email', $pageInfo['customer_email']);
					$viewtpl->ParseBlock('view_order/billing/customer_email');
					$viewtpl->SetBlock('view_order/billing/customer_phone');
					$viewtpl->SetVariable('customer_phone', $pageInfo['customer_phone']);
					$viewtpl->ParseBlock('view_order/billing/customer_phone');
					$viewtpl->SetBlock('view_order/billing/customer_fax');
					$viewtpl->SetVariable('customer_fax', $pageInfo['customer_fax']);
					$viewtpl->ParseBlock('view_order/billing/customer_fax');
					$viewtpl->SetBlock('view_order/billing/customer_company');
					$viewtpl->SetVariable('customer_company', $pageInfo['customer_company']);
					$viewtpl->ParseBlock('view_order/billing/customer_company');
					$viewtpl->SetBlock('view_order/billing/customer_address');
					$viewtpl->SetVariable('customer_address', $pageInfo['customer_address']);
					$viewtpl->ParseBlock('view_order/billing/customer_address');
					$viewtpl->SetBlock('view_order/billing/customer_address2');
					$viewtpl->SetVariable('customer_address2', $pageInfo['customer_address2']);
					$viewtpl->ParseBlock('view_order/billing/customer_address2');
					$viewtpl->SetBlock('view_order/billing/customer_city');
					$viewtpl->SetVariable('customer_city', $pageInfo['customer_city']);
					$viewtpl->ParseBlock('view_order/billing/customer_city');
					$viewtpl->SetBlock('view_order/billing/customer_region');
					$viewtpl->SetVariable('customer_region', $pageInfo['customer_region']);
					$viewtpl->ParseBlock('view_order/billing/customer_region');
					$viewtpl->SetBlock('view_order/billing/customer_postal');
					$viewtpl->SetVariable('customer_postal', $pageInfo['customer_postal']);
					$viewtpl->ParseBlock('view_order/billing/customer_postal');
					$viewtpl->SetBlock('view_order/billing/customer_country');
					$viewtpl->SetVariable('customer_country', $pageInfo['customer_country']);
					$viewtpl->ParseBlock('view_order/billing/customer_country');
					$viewtpl->ParseBlock('view_order/billing');
					
					$viewtpl->SetBlock('view_order/shipping');
					$viewtpl->SetVariable('OwnerID', $CustID);
					$viewtpl->SetBlock('view_order/shipping/shiptype');
					$viewtpl->SetVariable('shiptype', (empty($pageInfo['shiptype']) ? 'N/A' : $pageInfo['shiptype']));
					$viewtpl->ParseBlock('view_order/shipping/shiptype');
					$viewtpl->SetBlock('view_order/shipping/customer_shipname');
					$viewtpl->SetVariable('customer_shipname', $pageInfo['customer_shipname']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shipname');
					$viewtpl->SetBlock('view_order/shipping/customer_shipaddress');
					$viewtpl->SetVariable('customer_shipaddress', $pageInfo['customer_shipaddress']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shipaddress');
					$viewtpl->SetBlock('view_order/shipping/customer_shipaddress2');
					$viewtpl->SetVariable('customer_shipaddress2', $pageInfo['customer_shipaddress2']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shipaddress2');
					$viewtpl->SetBlock('view_order/shipping/customer_shipcity');
					$viewtpl->SetVariable('customer_shipcity', $pageInfo['customer_shipcity']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shipcity');
					$viewtpl->SetBlock('view_order/shipping/customer_shipregion');
					$viewtpl->SetVariable('customer_shipregion', $pageInfo['customer_shipregion']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shipregion');
					$viewtpl->SetBlock('view_order/shipping/customer_shippostal');
					$viewtpl->SetVariable('customer_shippostal', $pageInfo['customer_shippostal']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shippostal');
					$viewtpl->SetBlock('view_order/shipping/customer_shipcountry');
					$viewtpl->SetVariable('customer_shipcountry', $pageInfo['customer_shipcountry']);
					$viewtpl->ParseBlock('view_order/shipping/customer_shipcountry');
					$viewtpl->ParseBlock('view_order/shipping');

					$viewtpl->SetBlock('view_order/header');
					$viewtpl->SetVariable('title', $title);
					$viewtpl->SetVariable('icon', $icon);
					$viewtpl->ParseBlock('view_order/header');
														
					$viewtpl->ParseBlock('view_order');
																
					$uneditable = '';
					if ($editable === false) {
						$uneditable = '
						<script type="text/javascript">
							Event.observe(window, "load",function(){$("Ecommerce_actionbar").style.display = "none";});
						</script>'."\n";
					}
					
					$stpl->SetVariable('content', $uneditable.$viewtpl->Get());
					
					// send requesting URL to syntacts
					$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
					//$stpl->SetVariable('DPATH', JAWS_DPATH);
					$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
					$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
					$stpl->SetVariable('gadget', 'Ecommerce');
					$stpl->SetVariable('controller', $base_url);
					$stpl->SetVariable('id', $pid);
					
					/*
					$embed_options = '';
					// send embedding options
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ecommerce&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;mode=full', 'Embed This Order');\">This Order</a>&nbsp;&nbsp;&nbsp;\n";
					if ($account === true) {
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Ecommerce&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=LeaderBoard', 'Embed My LeaderBoards');\">My LeaderBoards</a>&nbsp;&nbsp;&nbsp;\n";
					}
					$stpl->SetVariable('embed_options', $embed_options);
					*/
				} else {
					return new Jaws_Error(_t('ECOMMERCE_ERROR_ORDER_NOT_FOUND'), _t('ECOMMERCE_NAME'));
				}

				$stpl->ParseBlock('view');
				$page .= $stpl->Get();

				// syntacts page for form
				$syntactsUrl2 = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Ecommerce/admin_Ecommerce_form');
				$submit_vars['CLOSE_BUTTON'] = "document.getElementById('form_content').style.display = 'none'; document.getElementById('view_content').style.display = '';";

				if ($syntactsUrl2) {
					// snoopy
					$snoopy2 = new Snoopy('Ecommerce');
					$submit_url2 = $syntactsUrl2;
					
					if($snoopy2->fetch($submit_url2)) {
						//while(list($key,$val) = each($snoopy->headers))
							//echo $key.": ".$val."<br>\n";
						//echo "<p>\n";
						
						//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
						$form_content = '';
						
						// initialize template
						$stpl = new Jaws_Template();
						$stpl->LoadFromString($snoopy2->results);
						$stpl->SetBlock('form');
						
						$stpl->SetVariable('id', $pageInfo['id']);
						$stpl->SetVariable('image', $xss->filter($pageInfo['image']));
						$stpl->SetVariable('url', $xss->filter($pageInfo['url']));
						$stpl->SetVariable('title', $xss->filter($pageInfo['title']));
						$stpl->SetVariable('keyword', $xss->filter($pageInfo['keyword']));
						$stpl->SetVariable('sitewide', $pageInfo['sitewide']);
						$stpl->SetVariable('OwnerID', $pageInfo['ownerid']);
						$stpl->SetVariable('Active', $pageInfo['active']);
						$stpl->SetVariable('Created', $pageInfo['created']);
						$stpl->SetVariable('Updated', $pageInfo['updated']);

						// send requesting URL to syntacts
						$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
						//$stpl->SetVariable('DPATH', JAWS_DPATH);
						$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
						$stpl->SetVariable('gadget', 'Ecommerce');
						$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
						$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
						$stpl->SetVariable('controller', $base_url);
						
						// Get Help documentation
						$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ecommerce/admin_Ecommerce_form_help", 'txt');
						$snoopy3 = new Snoopy('Ecommerce');

						if($snoopy3->fetch($help_url)) {
							$helpContent = Jaws_Utils::split2D($snoopy3->results);
						}
						
						// Hidden elements
						$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
						$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
						$form_content .= $idHidden->Get()."\n";

						$fuseaction = (isset($pageInfo['id'])) ? 'EditOrder' : 'AddOrder';
						$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
						$form_content .= $fuseactionHidden->Get()."\n";
						
						$address2 = (isset($pageInfo['customer_address2'])) ? $pageInfo['customer_address2'] : '';
						$address2Hidden =& Piwi::CreateWidget('HiddenEntry', 'customer_address2', $address2);
						$form_content .= $address2Hidden->Get()."\n";
						
						$shipaddress2 = (isset($pageInfo['customer_shipaddress2'])) ? $pageInfo['customer_shipaddress2'] : '';
						$shipaddress2Hidden =& Piwi::CreateWidget('HiddenEntry', 'customer_shipaddress2', $shipaddress2);
						$form_content .= $shipaddress2Hidden->Get()."\n";
						
						$recurring = 'N';
						$recurringHidden =& Piwi::CreateWidget('HiddenEntry', 'recurring', $recurring);
						$form_content .= $recurringHidden->Get()."\n";
						
						$qty = 1;
						$qtyHidden =& Piwi::CreateWidget('HiddenEntry', 'qty', $qty);
						$form_content .= $qtyHidden->Get()."\n";
						
						$shiptype = (isset($pageInfo['shiptype'])) ? $pageInfo['shiptype'] : 'Flat Rate';
						$shiptypeHidden =& Piwi::CreateWidget('HiddenEntry', 'shiptype', $shiptype);
						$form_content .= $shiptypeHidden->Get()."\n";
						
						$gadget_table = (isset($pageInfo['gadget_table'])) ? $pageInfo['gadget_table'] : '';
						$gadget_tableHidden =& Piwi::CreateWidget('HiddenEntry', 'gadget_table', $gadget_table);
						$form_content .= $gadget_tableHidden->Get()."\n";
						
						$gadget_id = (isset($pageInfo['gadget_id'])) ? $pageInfo['gadget_id'] : '';
						$gadget_idHidden =& Piwi::CreateWidget('HiddenEntry', 'gadget_id', $gadget_id);
						$form_content .= $gadget_idHidden->Get()."\n";
						
						$prod_id = (isset($pageInfo['prod_id'])) ? $pageInfo['prod_id'] : '0';
						$prod_idHidden =& Piwi::CreateWidget('HiddenEntry', 'prod_id', $prod_id);
						$form_content .= $prod_idHidden->Get()."\n";
						
						$description = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
						$descHidden =& Piwi::CreateWidget('HiddenEntry', 'description', $description);
						$form_content .= $descHidden->Get()."\n";
						
						// Status
						$active = (isset($pageInfo['active']) ? $pageInfo['active'] : 'NEW');
						
						if ($editable === true) {
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ECOMMERCE_ACTIVE')) {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							if (!isset($pageInfo['active'])) {
								$form_content .= "<tr><td colspan=\"2\"><div style=\"background:url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/images/right_menu_bg.png) repeat-x scroll left top #FFFFFF; border:1px solid #BABDB6; font-size:90%;margin:1em 20%;padding:15px;text-align:center;width:60%;\"><b>IMPORTANT:</b> If you intend to receive compensation for this order, you'll need to manually charge it through your payment processing gateway.</div></td></tr>";
							}
							$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
							$activeCombo->AddOption('New', 'NEW');
							$activeCombo->AddOption('Reviewing', 'REVIEWING');
							$activeCombo->AddOption('Chargeable', 'CHARGEABLE');
							$activeCombo->AddOption('Charging', 'CHARGING');
							$activeCombo->AddOption('Charged', 'CHARGED');
							$activeCombo->AddOption('Payment Declined', 'PAYMENT_DECLINED');
							$activeCombo->AddOption('Cancelled', 'CANCELLED');
							$activeCombo->AddOption('Cancelled By Gateway', 'CANCELLED_BY_GATEWAY');
							$activeCombo->AddOption('Processing', 'PROCESSING');
							$activeCombo->AddOption('Delivered', 'DELIVERED');
							$activeCombo->AddOption('Will Not Deliver', 'WILL_NOT_DELIVER');
							$activeCombo->AddOption('Refunded', 'REFUNDED');
							$activeCombo->SetDefault($active);
							$activeCombo->setTitle(_t('ECOMMERCE_ACTIVE'));
							if (isset($pageInfo['id'])) {
								//$activeCombo->AddEvent(ON_CHANGE, "if($('update_status_row')){if (this.value == '".$active."'){$('update_status_row').style.display = 'none';$('update_status_note').value = '';}else{$('update_status_row').style.display = 'block';}};");
								$activeCombo->AddEvent(ON_CHANGE, "$('update_status_row').style.display = '';");
							}
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
						} else {
							$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'Active', $active);
							$form_content .= $activeHidden->Get()."\n";
						}
															
						// Status Update Note
						$update_status =& Piwi::CreateWidget('TextArea', 'update_status_note', '');
						$update_status->SetID('update_status_note');
						$update_status->SetRows(5);
						$update_status->SetStyle('width: 300px;');
						
						$form_content .= "<tr id=\"update_status_row\" style=\"display: ".(isset($pageInfo['id']) ? "" : "none").";\"><td class=\"syntacts-form-row\"><label for=\"update_status_note\"><nobr>"._t('ECOMMERCE_STATUSUPDATE_NOTE')."</nobr></label></td><td class=\"syntacts-form-row\">".$update_status->Get()."</td></tr>";
						
						// Orderno
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_ORDERNO')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						if (isset($pageInfo['orderno'])) {
							$orderno = $pageInfo['orderno'];
						} else {
							// send highest sort_order
							$sql = "SELECT [orderno] FROM [[order]] ORDER BY [orderno] DESC LIMIT 1";
							$max = $GLOBALS['db']->queryOne($sql);
							if (Jaws_Error::IsError($max)) {
								$error = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $max->GetMessage())."\n";
								//return $max;
							}
							$orderno = (int)$max+1;
						}
						$ordernoEntry =& Piwi::CreateWidget('Entry', 'orderno', $orderno);
						$ordernoEntry->SetTitle(_t('ECOMMERCE_ORDERNO'));
						$ordernoEntry->SetStyle('direction: ltr; width: 300px;');
						if (!is_null($get['id'])) {
							$ordernoEntry->SetReadOnly(true);
							$ordernoEntry->SetEnabled(false);
						}
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"orderno\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$ordernoEntry->Get()."</td></tr>";
						
						// Description
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_DESC')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$desc = $description;
						$unserialized = unserialize($desc);
						if (is_array($unserialized) && isset($unserialized['description'])) {
							$desc = $unserialized['description'];
						}
						$descEntry =& Piwi::CreateWidget('TextArea', 'view_description', $desc);
						$descEntry->SetTitle(_t('ECOMMERCE_DESCRIPTION'));
						$descEntry->SetStyle('direction: ltr; width: 300px;');
						$descEntry->SetRows(5);
						if (!is_null($get['id']) && $editable === false) {
							$descEntry->SetReadOnly(true);
							$descEntry->SetEnabled(false);
						}
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"view_description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$descEntry->Get()."</td></tr>";

						// Total
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_TOTAL')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$total = (isset($pageInfo['total'])) ? $pageInfo['total'] : '';
						$totalEntry =& Piwi::CreateWidget('Entry', 'total', $total);
						$totalEntry->SetTitle(_t('ECOMMERCE_TOTAL'));
						$totalEntry->SetStyle('direction: ltr; width: 300px;');
						if (!is_null($get['id']) && $editable === false) {
							$totalEntry->SetReadOnly(true);
							$totalEntry->SetEnabled(false);
						}
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"total\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$totalEntry->Get()."</td></tr>";

						// Weight
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_WEIGHT')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						if (isset($pageInfo['weight'])) {
							$weight = $pageInfo['weight'];
						} else {
							$weight = 1;
						}
						$weightEntry =& Piwi::CreateWidget('Entry', 'weight', $weight);
						$weightEntry->SetTitle(_t('ECOMMERCE_WEIGHT'));
						$weightEntry->SetStyle('direction: ltr; width: 300px;');
						if (!is_null($get['id']) && $editable === false) {
							$weightEntry->SetReadOnly(true);
							$weightEntry->SetEnabled(false);
						}
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"weight\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$weightEntry->Get()."</td></tr>";
						
						// Backorder
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_BACKORDER')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						if (isset($pageInfo['backorder'])) {
							$backorder = $pageInfo['backorder'];
						} else {
							$backorder = 0;
						}
						$backorderEntry =& Piwi::CreateWidget('Entry', 'backorder', $backorder);
						$backorderEntry->SetTitle(_t('ECOMMERCE_BACKORDER'));
						$backorderEntry->SetStyle('direction: ltr; width: 300px;');
						if (!is_null($get['id']) && $editable === false) {
							$backorderEntry->SetReadOnly(true);
							$backorderEntry->SetEnabled(false);
						}
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"backorder\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$backorderEntry->Get()."</td></tr>";

						if ($account === false) {
							// Customer ID
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == "Existing User") {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							$customer_id = 0;
							if (isset($pageInfo['customer_id'])) {
								$customer_id = $pageInfo['customer_id'];
							}
							$customerIDCombo =& Piwi::CreateWidget('Combo', 'customer_id');
							$customerIDCombo->AddOption('Select...', 0);
							require_once JAWS_PATH . 'include/Jaws/User.php';
							$jUser = new Jaws_User;
							$users = $jUser->GetUsers();
							foreach ($users as $user) {
								$customerIDCombo->AddOption($user['nickname'], $user['id']);
							}
							$customerIDCombo->SetDefault($customer_id);
							$customerIDCombo->AddEvent(ON_CHANGE, 'javascript:if(this.value!=0 && this.value!="0"){$$(".new_customers").each(function(element){element.style.display="none";});}else{$$(".new_customers").each(function(element){element.style.display="block";});}');
							$customerIDCombo->setTitle("Existing User");
							$form_content .= "<tr class=\"existing_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_id\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$customerIDCombo->Get()."</td></tr>";
						} else {
							$customerIDHidden =& Piwi::CreateWidget('HiddenEntry', 'customer_id', 0);
							$form_content .= $customerIDHidden->Get()."\n";
						}

						// Customer E-mail
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_EMAIL')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_email = (isset($pageInfo['customer_email'])) ? $pageInfo['customer_email'] : '';
						$keywordEntry =& Piwi::CreateWidget('Entry', 'customer_email', $customer_email);
						$keywordEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_EMAIL'));
						$keywordEntry->SetStyle('direction: ltr; width: 300px;');
						if (!is_null($get['id']) && $editable === false) {
							$keywordEntry->SetReadOnly(true);
							$keywordEntry->SetEnabled(false);
						}
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_email\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$keywordEntry->Get()."</td></tr>";
						
						// Customer Name
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_NAME')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_name = (isset($pageInfo['customer_name'])) ? $pageInfo['customer_name'] : '';
						$nameEntry =& Piwi::CreateWidget('Entry', 'customer_name', $customer_name);
						$nameEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_NAME'));
						$nameEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$nameEntry->Get()."</td></tr>";
						
						// Customer Company
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_COMPANY')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_company = (isset($pageInfo['customer_company'])) ? $pageInfo['customer_company'] : '';
						$companyEntry =& Piwi::CreateWidget('Entry', 'customer_company', $customer_company);
						$companyEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_COMPANY'));
						$companyEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_company\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$companyEntry->Get()."</td></tr>";
						
						// Customer Address
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_ADDRESS')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_address = (isset($pageInfo['customer_address'])) ? $pageInfo['customer_address'] : '';
						$addressEntry =& Piwi::CreateWidget('Entry', 'customer_address', $customer_address);
						$addressEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_ADDRESS'));
						$addressEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_address\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$addressEntry->Get()."</td></tr>";
						
						// Customer City
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_CITY')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_city = (isset($pageInfo['customer_city'])) ? $pageInfo['customer_city'] : '';
						$cityEntry =& Piwi::CreateWidget('Entry', 'customer_city', $customer_city);
						$cityEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_CITY'));
						$cityEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_city\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cityEntry->Get()."</td></tr>";
						
						// Customer Region
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_REGION')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_region = (isset($pageInfo['customer_region'])) ? $pageInfo['customer_region'] : '';
						$regionEntry =& Piwi::CreateWidget('Entry', 'customer_region', $customer_region);
						$regionEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_REGION'));
						$regionEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_region\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$regionEntry->Get()."</td></tr>";
						
						// Customer Postal
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_POSTAL')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_postal = (isset($pageInfo['customer_postal'])) ? $pageInfo['customer_postal'] : '';
						$postalEntry =& Piwi::CreateWidget('Entry', 'customer_postal', $customer_postal);
						$postalEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_POSTAL'));
						$postalEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_postal\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$postalEntry->Get()."</td></tr>";
						
						// Customer Country
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_COUNTRY')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_country = (isset($pageInfo['customer_country'])) ? $pageInfo['customer_country'] : '';
						$countryEntry =& Piwi::CreateWidget('Entry', 'customer_country', $customer_country);
						$countryEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_COUNTRY'));
						$countryEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_country\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$countryEntry->Get()."</td></tr>";
						
						// Customer Phone
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_PHONE')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_phone = (isset($pageInfo['customer_phone'])) ? $pageInfo['customer_phone'] : '';
						$phoneEntry =& Piwi::CreateWidget('Entry', 'customer_phone', $customer_phone);
						$phoneEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_PHONE'));
						$phoneEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_phone\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$phoneEntry->Get()."</td></tr>";
						
						// Customer Fax
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_FAX')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_fax = (isset($pageInfo['customer_fax'])) ? $pageInfo['customer_fax'] : '';
						$faxEntry =& Piwi::CreateWidget('Entry', 'customer_fax', $customer_fax);
						$faxEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_FAX'));
						$faxEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_fax\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$faxEntry->Get()."</td></tr>";

						// Customer Ship Name
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPNAME')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_shipname = (isset($pageInfo['customer_shipname'])) ? $pageInfo['customer_shipname'] : '';
						$shipnameEntry =& Piwi::CreateWidget('Entry', 'customer_shipname', $customer_shipname);
						$shipnameEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPNAME'));
						$shipnameEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipname\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipnameEntry->Get()."</td></tr>";
										
						// Customer Ship Address
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPADDRESS')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_shipaddress = (isset($pageInfo['customer_shipaddress'])) ? $pageInfo['customer_shipaddress'] : '';
						$shipaddressEntry =& Piwi::CreateWidget('Entry', 'customer_shipaddress', $customer_shipaddress);
						$shipaddressEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPADDRESS'));
						$shipaddressEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipaddress\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipaddressEntry->Get()."</td></tr>";
						
						// Customer Ship City
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPCITY')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_shipcity = (isset($pageInfo['customer_shipcity'])) ? $pageInfo['customer_shipcity'] : '';
						$shipcityEntry =& Piwi::CreateWidget('Entry', 'customer_shipcity', $customer_shipcity);
						$shipcityEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPCITY'));
						$shipcityEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipcity\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipcityEntry->Get()."</td></tr>";
						
						// Customer Ship Region
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPREGION')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_shipregion = (isset($pageInfo['customer_shipregion']) ? $pageInfo['customer_shipregion'] : '');
						/*
						$google_script .= "<option value=\"\"".(empty($customer_region) ? $selected : '').">Select your State...</option>"; 
						$google_script .= "<option value=\"AL\" ".(strtolower($customer_region)=="al" || strtolower($customer_region)=="alabama" ? $selected : '').">Alabama</option>"; 
						$google_script .= "<option value=\"AK\" ".(strtolower($customer_region)=="ak" || strtolower($customer_region)=="alaska" ? $selected : '').">Alaska</option>";
						$google_script .= "<option value=\"AZ\" ".(strtolower($customer_region)=="az" || strtolower($customer_region)=="arizona" ? $selected : '').">Arizona</option>";
						$google_script .= "<option value=\"AR\" ".(strtolower($customer_region)=="ar" || strtolower($customer_region)=="arkansas" ? $selected : '').">Arkansas</option>";
						$google_script .= "<option value=\"CA\" ".(strtolower($customer_region)=="ca" || strtolower($customer_region)=="california" ? $selected : '').">California</option>";
						$google_script .= "<option value=\"CO\" ".(strtolower($customer_region)=="co" || strtolower($customer_region)=="colorado" ? $selected : '').">Colorado</option>";
						$google_script .= "<option value=\"CT\" ".(strtolower($customer_region)=="ct" || strtolower($customer_region)=="connecticut" ? $selected : '').">Connecticut</option>";
						$google_script .= "<option value=\"DE\" ".(strtolower($customer_region)=="de" || strtolower($customer_region)=="delaware" ? $selected : '').">Delaware</option>";
						$google_script .= "<option value=\"FL\" ".(strtolower($customer_region)=="fl" || strtolower($customer_region)=="florida" ? $selected : '').">Florida</option>";
						$google_script .= "<option value=\"GA\" ".(strtolower($customer_region)=="ga" || strtolower($customer_region)=="georgia" ? $selected : '').">Georgia</option>";
						$google_script .= "<option value=\"HI\" ".(strtolower($customer_region)=="hi" || strtolower($customer_region)=="hawaii" ? $selected : '').">Hawaii</option>";
						$google_script .= "<option value=\"ID\" ".(strtolower($customer_region)=="id" || strtolower($customer_region)=="idaho" ? $selected : '').">Idaho</option>";
						$google_script .= "<option value=\"IL\" ".(strtolower($customer_region)=="il" || strtolower($customer_region)=="illinois" ? $selected : '').">Illinois</option>";
						$google_script .= "<option value=\"IN\" ".(strtolower($customer_region)=="in" || strtolower($customer_region)=="indiana" ? $selected : '').">Indiana</option>";
						$google_script .= "<option value=\"IA\" ".(strtolower($customer_region)=="ia" || strtolower($customer_region)=="iowa" ? $selected : '').">Iowa</option>";
						$google_script .= "<option value=\"KS\" ".(strtolower($customer_region)=="ks" || strtolower($customer_region)=="kansas" ? $selected : '').">Kansas</option>";
						$google_script .= "<option value=\"KY\" ".(strtolower($customer_region)=="ky" || strtolower($customer_region)=="kentucky" ? $selected : '').">Kentucky</option>";
						$google_script .= "<option value=\"LA\" ".(strtolower($customer_region)=="la" || strtolower($customer_region)=="louisiana" ? $selected : '').">Louisiana</option>";
						$google_script .= "<option value=\"ME\" ".(strtolower($customer_region)=="me" || strtolower($customer_region)=="maine" ? $selected : '').">Maine</option>";
						$google_script .= "<option value=\"MD\" ".(strtolower($customer_region)=="md" || strtolower($customer_region)=="maryland" ? $selected : '').">Maryland</option>";
						$google_script .= "<option value=\"MA\" ".(strtolower($customer_region)=="ma" || strtolower($customer_region)=="massachusetts" ? $selected : '').">Massachusetts</option>";
						$google_script .= "<option value=\"MI\" ".(strtolower($customer_region)=="mi" || strtolower($customer_region)=="michigan" ? $selected : '').">Michigan</option>";
						$google_script .= "<option value=\"MN\" ".(strtolower($customer_region)=="mn" || strtolower($customer_region)=="minnesota" ? $selected : '').">Minnesota</option>";
						$google_script .= "<option value=\"MS\" ".(strtolower($customer_region)=="ms" || strtolower($customer_region)=="mississippi" ? $selected : '').">Mississippi</option>";
						$google_script .= "<option value=\"MO\" ".(strtolower($customer_region)=="mo" || strtolower($customer_region)=="missouri" ? $selected : '').">Missouri</option>";
						$google_script .= "<option value=\"MT\" ".(strtolower($customer_region)=="mt" || strtolower($customer_region)=="montana" ? $selected : '').">Montana</option>";
						$google_script .= "<option value=\"NE\" ".(strtolower($customer_region)=="ne" || strtolower($customer_region)=="nebraska" ? $selected : '').">Nebraska</option>";
						$google_script .= "<option value=\"NV\" ".(strtolower($customer_region)=="nv" || strtolower($customer_region)=="nevada" ? $selected : '').">Nevada</option>";
						$google_script .= "<option value=\"NH\" ".(strtolower($customer_region)=="nh" || strtolower($customer_region)=="new hampshire" ? $selected : '').">New Hampshire</option>";
						$google_script .= "<option value=\"NJ\" ".(strtolower($customer_region)=="nj" || strtolower($customer_region)=="new jersey" ? $selected : '').">New Jersey</option>";
						$google_script .= "<option value=\"NM\" ".(strtolower($customer_region)=="nm" || strtolower($customer_region)=="new mexico" ? $selected : '').">New Mexico</option>";
						$google_script .= "<option value=\"NY\" ".(strtolower($customer_region)=="ny" || strtolower($customer_region)=="new york" ? $selected : '').">New York</option>";
						$google_script .= "<option value=\"NC\" ".(strtolower($customer_region)=="nc" || strtolower($customer_region)=="north carolina" ? $selected : '').">North Carolina</option>";
						$google_script .= "<option value=\"ND\" ".(strtolower($customer_region)=="nd" || strtolower($customer_region)=="north dakota" ? $selected : '').">North Dakota</option>";
						$google_script .= "<option value=\"OH\" ".(strtolower($customer_region)=="oh" || strtolower($customer_region)=="ohio" ? $selected : '').">Ohio</option>";
						$google_script .= "<option value=\"OK\" ".(strtolower($customer_region)=="ok" || strtolower($customer_region)=="oklahoma" ? $selected : '').">Oklahoma</option>";
						$google_script .= "<option value=\"OR\" ".(strtolower($customer_region)=="or" || strtolower($customer_region)=="oregon" ? $selected : '').">Oregon</option>";
						$google_script .= "<option value=\"PA\" ".(strtolower($customer_region)=="pa" || strtolower($customer_region)=="pennsylvania" ? $selected : '').">Pennsylvania</option>";
						$google_script .= "<option value=\"RI\" ".(strtolower($customer_region)=="ri" || strtolower($customer_region)=="rhode island" ? $selected : '').">Rhode Island</option>";
						$google_script .= "<option value=\"SC\" ".(strtolower($customer_region)=="sc" || strtolower($customer_region)=="south carolina" ? $selected : '').">South Carolina</option>";
						$google_script .= "<option value=\"SD\" ".(strtolower($customer_region)=="sd" || strtolower($customer_region)=="south dakota" ? $selected : '').">South Dakota</option>";
						$google_script .= "<option value=\"TN\" ".(strtolower($customer_region)=="tn" || strtolower($customer_region)=="tennessee" ? $selected : '').">Tennessee</option>";
						$google_script .= "<option value=\"TX\" ".(strtolower($customer_region)=="tx" || strtolower($customer_region)=="texas" ? $selected : '').">Texas</option>";
						$google_script .= "<option value=\"UT\" ".(strtolower($customer_region)=="ut" || strtolower($customer_region)=="utah" ? $selected : '').">Utah</option>";
						$google_script .= "<option value=\"VT\" ".(strtolower($customer_region)=="vt" || strtolower($customer_region)=="vermont" ? $selected : '').">Vermont</option>";
						$google_script .= "<option value=\"VA\" ".(strtolower($customer_region)=="va" || strtolower($customer_region)=="virginia" ? $selected : '').">Virginia</option>";
						$google_script .= "<option value=\"WA\" ".(strtolower($customer_region)=="wa" || strtolower($customer_region)=="washington" ? $selected : '').">Washington</option>";
						$google_script .= "<option value=\"DC\" ".(strtolower($customer_region)=="dc" || strtolower($customer_region)=="washington d.c." ? $selected : '').">Washington D.C.</option>";
						$google_script .= "<option value=\"WV\" ".(strtolower($customer_region)=="wv" || strtolower($customer_region)=="west virginia" ? $selected : '').">West Virginia</option>";
						$google_script .= "<option value=\"WI\" ".(strtolower($customer_region)=="wi" || strtolower($customer_region)=="wisconsin" ? $selected : '').">Wisconsin</option>";
						$google_script .= "<option value=\"WY\" ".(strtolower($customer_region)=="wy" || strtolower($customer_region)=="wyoming" ? $selected : '').">Wyoming</option>";
						$google_script .= '</select>\';
						*/
						$shipregionEntry =& Piwi::CreateWidget('Entry', 'customer_shipregion', $customer_shipregion);
						$shipregionEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPREGION'));
						$shipregionEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipregion\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipregionEntry->Get()."</td></tr>";
						
						// Customer Ship Postal
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPPOSTAL')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_shippostal = (isset($pageInfo['customer_shippostal'])) ? $pageInfo['customer_shippostal'] : '';
						$shippostalEntry =& Piwi::CreateWidget('Entry', 'customer_shippostal', $customer_shippostal);
						$shippostalEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPPOSTAL'));
						$shippostalEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shippostal\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shippostalEntry->Get()."</td></tr>";
						
						// Customer Ship Country
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ECOMMERCE_CUSTOMER_SHIPCOUNTRY')) {
								$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
								if ($help[1]) {
									if ($help[2]) {
										$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
									}
									$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
									$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
									$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
									$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
									if ($help[2]) {
										$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
									}
									$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
									if ($help[2]) {
										$helpString .= "</a>";
									}
								}
							}
						}
						$customer_shipcountry = (isset($pageInfo['customer_shipcountry'])) ? $pageInfo['customer_shipcountry'] : '';
						$shipcountryEntry =& Piwi::CreateWidget('Entry', 'customer_shipcountry', $customer_shipcountry);
						$shipcountryEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_SHIPCOUNTRY'));
						$shipcountryEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr class=\"new_customers\"><td class=\"syntacts-form-row\"><label for=\"customer_shipcountry\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$shipcountryEntry->Get()."</td></tr>";
						
						if (
							$account == false && (is_null($get['id']) || 
							(!empty($customer_cc_type) &&
							!empty($customer_cc_number) &&
							!empty($customer_cc_exp_month) &&
							!empty($customer_cc_exp_year)))
						) {					
							
							// Customer CC Type
							$helpString = _t('ECOMMERCE_CUSTOMER_CC_TYPE');
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_TYPE')) {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							$cc_typeCombo =& Piwi::CreateWidget('Combo', 'customer_cc_type');
							$cc_typeCombo->AddOption('Select...', '');
							$cc_typeCombo->AddOption('Visa', 'Visa');
							$cc_typeCombo->AddOption('MasterCard', 'MasterCard');
							$cc_typeCombo->AddOption('Discover', 'Discover');
							$cc_typeCombo->AddOption('Amex', 'Amex');
							$cc_typeCombo->SetDefault($customer_cc_type);
							$cc_typeCombo->setTitle(_t('ECOMMERCE_CUSTOMER_CC_TYPE'));
							if (!is_null($get['id']) && $editable === false) {
								$cc_typeCombo->SetEnabled(false);
							}
							$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_typeCombo->Get()."</td></tr>";
							
							// Customer CC Number
							$helpString = _t('ECOMMERCE_CUSTOMER_CC_NUMBER');
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_NUMBER')) {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							$cc_numberEntry =& Piwi::CreateWidget('Entry', 'customer_cc_number', $customer_cc_number);
							$cc_numberEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_CC_NUMBER'));
							$cc_numberEntry->SetStyle('direction: ltr; width: 300px;');
							if (!is_null($get['id']) && $editable === false) {
								$cc_numberEntry->SetReadOnly(true);
								$cc_numberEntry->SetEnabled(false);
							}
							$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_number\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_numberEntry->Get()."</td></tr>";
							
							// Customer CC Exp Month
							$helpString = _t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH');
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH')) {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							$customer_cc_exp_month = '';
							if (isset($pageInfo['customer_cc_exp_month'])) {
								$customer_cc_exp_month = $pageInfo['customer_cc_exp_month'];
							}
							$cc_exp_monthCombo =& Piwi::CreateWidget('Combo', 'customer_cc_exp_month');
							for ($m=1;$m<12;$m++) {
								if ($m < 10) {
									$m = '0'.$m;
								}
								$cc_exp_monthCombo->AddOption($m, $m);
							}
							$cc_exp_monthCombo->SetDefault($customer_cc_exp_month);
							$cc_exp_monthCombo->setTitle(_t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH'));
							if (!is_null($get['id']) && $editable === false) {
								$cc_exp_monthCombo->SetEnabled(false);
							}
							$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_exp_month\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_exp_monthCombo->Get()."</td></tr>";
									
							// Customer CC Exp Year
							$helpString = _t('ECOMMERCE_CUSTOMER_CC_EXP_YEAR');
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_EXP_YEAR')) {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							$customer_cc_exp_year = (int)date('Y');
							if (isset($pageInfo['customer_cc_exp_year'])) {
								$customer_cc_exp_year = (int)$pageInfo['customer_cc_exp_year'];
							}
							$cc_exp_yearCombo =& Piwi::CreateWidget('Combo', 'customer_cc_exp_year');
							for ($y=(((int)date('Y'))+20);$y>((int)date('Y'))-1;$y--) {
								$cc_exp_yearCombo->AddOption($y, $y);
							}
							$cc_exp_yearCombo->SetDefault($customer_cc_exp_year);
							$cc_exp_yearCombo->setTitle(_t('ECOMMERCE_CUSTOMER_CC_EXP_MONTH'));
							if (!is_null($get['id']) && $editable === false) {
								$cc_exp_yearCombo->SetEnabled(false);
							}
							$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_exp_year\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_exp_yearCombo->Get()."</td></tr>";
							
							// Customer CC CVV
							$helpString = _t('ECOMMERCE_CUSTOMER_CC_CVV');
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ECOMMERCE_CUSTOMER_CC_CVV')) {
									$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
									if ($help[1]) {
										if ($help[2]) {
											$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
										}
										$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
										$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
										$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
										$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
										if ($help[2]) {
											$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
										}
										$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
										if ($help[2]) {
											$helpString .= "</a>";
										}
									}
								}
							}
							$cc_cvvEntry =& Piwi::CreateWidget('Entry', 'customer_cc_cvv', $customer_cc_cvv);
							$cc_cvvEntry->SetTitle(_t('ECOMMERCE_CUSTOMER_CC_NUMBER'));
							$cc_cvvEntry->SetStyle('direction: ltr; width: 300px;');
							if (!is_null($get['id']) && $editable === false) {
								$cc_cvvEntry->SetReadOnly(true);
								$cc_cvvEntry->SetEnabled(false);
							}
							$form_content .= "<tr class=\"cc_details\"><td class=\"syntacts-form-row\"><label for=\"customer_cc_cvv\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$cc_cvvEntry->Get()."</td></tr>";
						}
						
						$stpl->SetVariable('content', $form_content);
						$stpl->ParseBlock('form');
						$page .= $stpl->Get();
					} else {
						$page .= _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy2->error)."\n";
					}
				} else {
					// Send us to the appropriate page
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					if ($account == true) {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					} else {
						Jaws_Header::Location($base_url.'?gadget=Ecommerce&action=Admin');
					}
				}
				if (isset($orders) && isset($orders[0]) && (isset($orders[0]['id']) && !empty($orders[0]['id']))) {
					$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					$page .= $usersHTML->ShowComments('Ecommerce', false, $orders[0]['id'], 'Comments', true, 10, 'full', false);
				}
			} else {
				$page .= _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
		}

		$tpl->SetVariable('content', $page);
        $tpl->ParseBlock('gadget_page');

        return $tpl->Get();

    }

    /**
     * Shipping
     *
     * @access  public
     * @return  string HTML content
     */
    function B($account = false)
    {
		$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		$merchant_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');  // Your Merchant ID
		$merchant_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');  // Your Merchant Key
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		if ($account === false && empty($action) && (empty($site_ssl_url) || empty($payment_gateway) || ($payment_gateway != 'ManualCreditCard' && (empty($merchant_id) || empty($merchant_key))))) {
			require_once JAWS_PATH . 'include/Jaws/Header.php';	
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ecommerce&action=Settings');
		}

		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ecommerce', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
			$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");
			$tpl->SetVariable('account-style', '');
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
			$tpl->SetVariable('workarea-style', '');
			$tpl->SetVariable('account-style', ' display: none;');
		}
        				
		$page = '';
		
		// TODO: Support other Payment Gateways
		if ($payment_gateway == 'GoogleCheckout') {
			/*
			// Ship From Address
			$state_code = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');
			$sql = "SELECT [region] FROM [[country]] WHERE ([is_country] = 'N') AND ([parent] = 1) AND ([country_iso_code] = '".$state_code."')";
			$country = $GLOBALS['db']->queryOne($sql);
			if (!Jaws_Error::IsError($country) && !empty($country)) {
				$shipfrom_state = $country;
			}	
			
			$shipfrom_html = '<fieldset id="fieldsetShipFrom" style="margin-top: 30px;" title="vertical">';
			$shipfrom_html .= '<legend id="fieldsetShipFrom_legend">'._t('ECOMMERCE_SETTINGS_SHIPFROM_ADDRESS').'</legend>';
			$shipfrom_html .= '<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<TR>
					<TD class="syntacts-form-row" VALIGN="top"><B>Region:</B></TD>
					<TD VALIGN="top" class="syntacts-form-row">
					<SELECT SIZE="1" NAME="country_id" ID="country_id">
							<OPTION VALUE="1">United States</OPTION>
					</select>
					</TD>
				  </TR>

				  <TR>
					<TD class="syntacts-form-row" VALIGN="top"><B>State/Country:</B></TD>
					<TD VALIGN="top" class="syntacts-form-row">
					 <SELECT SIZE="1" NAME="region" id="region">
						</SELECT>
					</TD>
				  </TR>
				<TR>
				  <TD class="syntacts-form-row" VALIGN="top"><B>City/Locale:</B></TD>
				  <TD VALIGN="top" class="syntacts-form-row">
					<INPUT TYPE="text" NAME="city" SIZE="53" STYLE="font-family: Arial, \'MS Sans Serif\'; font-size: 8pt" VALUE="'.$GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city').'" ID="city">
					<div id="city_choices" class="autocomplete"></div>
				  </TD>
				</TR>
				<TR>
				  <TD class="syntacts-form-row" VALIGN="top"><B>Zip/Postal Code:</B></TD>
				  <TD VALIGN="top" class="syntacts-form-row">
				  <INPUT TYPE="text" VALUE="'.$GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip').'" NAME="postal_code" ID="postal_code">
				  </TD>
				</TR>
			</table>
			<script type="text/javascript">
				function getStateId(element, entry) {
					return entry+"&methodcount=1&initial1gadget=Maps&initial1method=SearchRegions&initial1paramcount=2&initial1param1="+$(\'region\').value+"&initial1param2=country";
				}

				Event.observe(window, "load",function(){getRegionsOfParent(document.getElementById(\'country_id\').value, \'region\', \''.$shipfrom_state.'\');});
				new Ajax.Autocompleter("city", "city_choices", "index.php?gadget=Maps&action=AutoCompleteRegions", {
				  paramName: "query", 
				  minChars: 1,
				  callback: getStateId
				});
			</script>';
			$shipfrom_html .= '</fieldset>';
			*/
			$page .= '<fieldset id="fieldsetShipFrom" style="margin-top: 30px;" title="vertical">';
			$page .= '<legend id="fieldsetShipFrom_legend">'._t('ECOMMERCE_SETTINGS_SHIPPING_OPTIONS').'</legend>';
			$page .= '<div>';
			
			$cityHidden =& Piwi::CreateWidget('HiddenEntry', 'region', '');
			$stateHidden =& Piwi::CreateWidget('HiddenEntry', 'city', '');
			$zipHidden =& Piwi::CreateWidget('HiddenEntry', 'postal_code', '');

			$url_shipping = "https://checkout.google.com/sell2/settings?section=SellerEditableShipping&try=1&pli=1";
			$shipping_link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_SHIPPING_LINK'),
										$url_shipping, '', '_blank');
			$page .= $cityHidden->Get().$stateHidden->Get().$zipHidden->Get().$shipping_link->Get().'</div></fieldset>';
		} else {
			/*
			$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');
			if ($use_carrier_calculated == 'Y') {
				$page .= "<div style=\"background:url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/images/right_menu_bg.png) repeat-x scroll left top #FFFFFF; border:1px solid #BABDB6; font-size:90%;margin:1em 20%;padding:15px;text-align:center;width:60%;\"><b>Shipping is currently using Carrier Calculated options</b>. If you want to manually set Shipping prices, <a href=\"admin.php?gadget=Ecommerce&action=Settings\">Click Here and Set \"Use Carrier Calculated Shipping\" to \"No\" then click \"Save\"</a>. Come back here to Shipping when you're done.</div>";
			}
			*/
			$stpl = new Jaws_Template('gadgets/Ecommerce/templates/');
			$stpl->Load('admin.html');
			$stpl->SetBlock('shipping_admin');
			$stpl->SetVariable('account', $account_prefix);
			$stpl->SetVariable('base_script', $base_url);
			$stpl->SetVariable('grid', $this->ShippingDataGrid());

			$toolBar   =& Piwi::CreateWidget('HBox');

			$deleteAll =& Piwi::CreateWidget('Button', 'deleteAllShipping',
											 _t('GLOBAL_DELETE'),
											 STOCK_DELETE);
			$deleteAll->AddEvent(ON_CLICK,
								 "javascript: massiveDelete('"._t('ECOMMERCE_CONFIRM_MASIVE_DELETE_SHIPPING')."');");

			$toolBar->Add($deleteAll);

			$stpl->SetVariable('tools', $toolBar->Get());
			$stpl->SetVariable('entries', $this->ShippingDatagrid());

			if ($account === false) {
				$addPage =& Piwi::CreateWidget('Button', 'add_shipping', _t('ECOMMERCE_ADD_SHIPPING'), STOCK_ADD);
				$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Ecommerce&amp;action=".$account_prefix."B_form';");
				$stpl->SetVariable('add_shipping', $addPage->Get());
			} else {
				$stpl->SetVariable('add_shipping', '');
			}

			$stpl->ParseBlock('shipping_admin');

			$page .= $stpl->Get();
		}
		
		$page .= '<div style="clear: both;">&nbsp;</div>';
				
		$tpl->SetVariable('content', $page);
       
	    $tpl->ParseBlock('gadget_page');

        return $tpl->Get();

    }

    /**
     * We are on a B_form page
     *
     * @access public
     * @return string
     */
    function B_form($account = false)
    {
		$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ecommerce', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ecommerce', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Ecommerce&amp;action=B';";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Ecommerce/admin_Ecommerce_B_form');
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
		$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Ecommerce');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');
				if (!is_null($get['id'])) {
					// send page records
					$pageInfo = $model->GetShipping($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || $pageInfo['ownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('ECOMMERCE_ERROR_SHIPPING_NOT_FOUND'), _t('ECOMMERCE_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Ecommerce');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ecommerce/admin_Ecommerce_B_form_help", 'txt');
				$snoopy = new Snoopy('Ecommerce');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditShipping' : 'AddShipping';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";
				
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[shipping]] ORDER BY [sort_order] DESC";
				$max = $GLOBALS['db']->queryOne($sql);
				if (Jaws_Error::IsError($max)) {
					$error = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $max->GetMessage())."\n";
					//return $max;
				}
				$sort_order = $max+1;
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'sort_order', $sort_order);
		        $form_content .= $sort_orderHidden->Get()."\n";

				if ($account === false) {
					// Active
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ECOMMERCE_PUBLISHED')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if ($help[2]) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if ($help[2]) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if ($help[2]) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$active = (isset($pageInfo['active'])) ? $pageInfo['active'] : 'Y';
					$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
					$activeCombo->AddOption(_t('ECOMMERCE_PUBLISHED'), 'Y');
					$activeCombo->AddOption(_t('ECOMMERCE_NOTPUBLISHED'), 'N');
					$activeCombo->SetDefault($active);
					$activeCombo->setTitle(_t('ECOMMERCE_PUBLISHED'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				} else {
					$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'Active', 'N');
					$form_content .= $activeHidden->Get()."\n";
				}
					
				// type Fieldset
				include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
				$fieldset = new Jaws_Widgets_FieldSet(_t('ECOMMERCE_TYPE'));
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_TYPE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$type = (isset($pageInfo['type'])) ? $pageInfo['type'] : 'weight';
				$typeCombo =& Piwi::CreateWidget('Combo', 'type');
				$typeCombo->AddOption(_t('ECOMMERCE_SHIPBYWEIGHT'), 'weight');
				$typeCombo->AddOption(_t('ECOMMERCE_SHIPBYPRICE'), 'price');
				$typeCombo->AddOption(_t('ECOMMERCE_SHIPBYPRODUCTS'), 'products');
				$typeCombo->AddOption(_t('ECOMMERCE_DEFAULT'), 'default');
				$typeCombo->SetDefault($type);
				//$typeCombo->setTitle(_t('ECOMMERCE_TYPE'));
				$fieldset->Add($typeCombo);
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$fieldset->Get()."</td></tr>";
				
				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('GLOBAL_TITLE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$title = (isset($pageInfo['title'])) ? $pageInfo['title'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
				$titleEntry->SetTitle(_t('GLOBAL_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				// Min Factor
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_SHIP_MINFACTOR')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$minfactor = (isset($pageInfo['minfactor'])) ? $pageInfo['minfactor'] : '0';
				$minfactorEntry =& Piwi::CreateWidget('Entry', 'minfactor', $minfactor);
				$minfactorEntry->SetTitle(_t('ECOMMERCE_SHIP_MINFACTOR'));
				$minfactorEntry->SetStyle('direction: ltr; width: 100px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"minfactor\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$minfactorEntry->Get()."</td></tr>";
				
				// Max Factor
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_SHIP_MAXFACTOR')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$maxfactor = (isset($pageInfo['maxfactor'])) ? $pageInfo['maxfactor'] : '0';
				$maxfactorEntry =& Piwi::CreateWidget('Entry', 'maxfactor', $maxfactor);
				$maxfactorEntry->SetTitle(_t('ECOMMERCE_SHIP_MAXFACTOR'));
				$maxfactorEntry->SetStyle('direction: ltr; width: 100px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"maxfactor\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$maxfactorEntry->Get()."</td></tr>";

				// Price
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_PRICE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$price = (isset($pageInfo['price'])) ? $pageInfo['price'] : '';
				$priceEntry =& Piwi::CreateWidget('Entry', 'price', $price);
				$priceEntry->SetTitle(_t('ECOMMERCE_PRICE'));
				$priceEntry->SetStyle('direction: ltr; width: 100px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"price\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$priceEntry->Get()."</td></tr>";

				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ECOMMERCE_DESC')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				// send editor HTML to syntacts
				$description = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
				$editor =& $GLOBALS['app']->LoadEditor('CustomPage', 'description', $description, false);
				$editor->TextArea->SetStyle('width: 100%;');
				$editor->SetWidth('490px');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('ECOMMERCE_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('ECOMMERCE_NAME'));
		}
		
        $tpl->ParseBlock('gadget_page');
        return $tpl->Get();

    }

    /**
     * We are on the B_form_post page
     *
     * @access public
     * @return string
     */
    function B_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * Edit settings
     *
     * @access  public
     * @return  string HTML content
     */
    function Settings()
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/ControlPanel/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
        $tpl->Load('Properties.html');
        $tpl->SetBlock('Properties');

		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        
		$form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Ecommerce'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveSettings'));

		$merchant_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
        $site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$gateway_id = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_id');
		$gateway_key = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_key');
		$use_carrier_calculated = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/use_carrier_calculated');
		$gateway_signature = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_signature');
		$checkout_terms = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/checkout_terms');
        
		if (empty($merchant_gateway) || $merchant_gateway == 'ManualCreditCard') {
			$GLOBALS['app']->Layout->AddHeadOther('<style>#gateway_id {display: none;} #gateway_id_label {display: none;} #gateway_key {display: none;} #gateway_key_label {display: none;} #gateway_signature_label {display: none;} #gateway_signature {display: none;}</style>');
		}
		
		if ((empty($site_ssl_url) && in_array('Ecommerce', explode(',',$require_https)))) {
			$url = BASE_SCRIPT . '?gadget=Settings';
			$ssl_Link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_SSL_LINK'),
										$url);
			$form->Add($ssl_Link);
		} else {			
			$default_gateway = (isset($merchant_gateway) && !empty($merchant_gateway) ? $merchant_gateway : 'ManualCreditCard');
			
			$payment_gateway =& Piwi::CreateWidget('Combo', 'payment_gateway');
			$payment_gateway->SetTitle(_t('ECOMMERCE_SETTINGS_PAYMENT_GATEWAY'));
			$payment_gateway->AddOption(_t('ECOMMERCE_SETTINGS_MANUALCREDITCARD'), 'ManualCreditCard');
			$payment_gateway->AddOption(_t('ECOMMERCE_SETTINGS_GOOGLECHECKOUT'), 'GoogleCheckout');
			$payment_gateway->AddOption(_t('ECOMMERCE_SETTINGS_AUTHORIZENET'), 'AuthorizeNet');
			$payment_gateway->AddOption(_t('ECOMMERCE_SETTINGS_PAYPAL'), 'PayPal');
			$payment_gateway->AddEvent(ON_CHANGE, "javascript: if(document.getElementById('GoogleCheckout') && document.getElementById('PayPal') && document.getElementById('AuthorizeNet')){if (this.value=='ManualCreditCard'){document.getElementById('gateway_id_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_ID')."';  document.getElementById('gateway_id').style.display = 'none'; document.getElementById('gateway_id_label').style.display = 'none'; document.getElementById('gateway_key_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_KEY')."'; document.getElementById('gateway_key').style.display = 'none'; document.getElementById('gateway_key_label').style.display = 'none'; document.getElementById('gateway_signature_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_SIGNATURE')."'; document.getElementById('gateway_signature_label').style.display = 'none'; document.getElementById('gateway_signature').style.display = 'none'; document.getElementById('GoogleCheckout').style.display = 'none';document.getElementById('PayPal').style.display = 'none';document.getElementById('AuthorizeNet').style.display = 'none';} else if (this.value=='GoogleCheckout'){document.getElementById('gateway_id_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_ID')."'; document.getElementById('gateway_id').style.display = 'block'; document.getElementById('gateway_id_label').style.display = 'block'; document.getElementById('gateway_key_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_KEY')."'; document.getElementById('gateway_key').style.display = 'block'; document.getElementById('gateway_key_label').style.display = 'block'; document.getElementById('gateway_signature_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_SIGNATURE')."'; document.getElementById('gateway_signature_label').style.display = 'none'; document.getElementById('gateway_signature').style.display = 'none';document.getElementById('GoogleCheckout').style.display = 'block';document.getElementById('PayPal').style.display = 'none';document.getElementById('AuthorizeNet').style.display = 'none';} else if (this.value=='PayPal'){document.getElementById('gateway_id_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_PAYPAL_ID')."'; document.getElementById('gateway_id').style.display = 'block'; document.getElementById('gateway_id_label').style.display = 'block'; document.getElementById('gateway_key_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_PAYPAL_KEY')."'; document.getElementById('gateway_key').style.display = 'block'; document.getElementById('gateway_key_label').style.display = 'block'; document.getElementById('gateway_signature_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_PAYPAL_SIGNATURE')."'; document.getElementById('gateway_signature_label').style.display = 'block'; document.getElementById('gateway_signature').style.display = 'block';document.getElementById('PayPal').style.display = 'block';document.getElementById('GoogleCheckout').style.display = 'none'; document.getElementById('AuthorizeNet').style.display = 'none';}else{document.getElementById('gateway_id_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_ID')."'; document.getElementById('gateway_id').style.display = 'block'; document.getElementById('gateway_id_label').style.display = 'block'; document.getElementById('gateway_key_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_KEY')."'; document.getElementById('gateway_key').style.display = 'block'; document.getElementById('gateway_key_label').style.display = 'block'; document.getElementById('gateway_signature_label').innerHTML = '"._t('ECOMMERCE_SETTINGS_GATEWAY_SIGNATURE')."'; document.getElementById('gateway_signature_label').style.display = 'none'; document.getElementById('gateway_signature').style.display = 'none';document.getElementById('AuthorizeNet').style.display = 'block';document.getElementById('PayPal').style.display = 'none';document.getElementById('GoogleCheckout').style.display = 'none';}};");
			$payment_gateway->SetDefault($default_gateway);

			$gateway_fieldset = new Jaws_Widgets_FieldSet('');
			$gateway_fieldset->SetTitle('vertical');
			$gateway_fieldset->Add($payment_gateway);
			$form->Add($gateway_fieldset);

			// Google Checkout
			$google_fieldset = new Jaws_Widgets_FieldSet('');
			$google_fieldset->SetTitle('vertical');
			$google_fieldset->SetID('GoogleCheckout');
			if ($merchant_gateway != 'GoogleCheckout' || (isset($gateway_id) && !empty($gateway_id) && isset($gateway_key) && !empty($gateway_key))) {
				$google_fieldset->SetStyle('margin-top: 30px; display: none;');
			} else {
				$google_fieldset->SetStyle('margin-top: 30px;');
			}
			
			$url = "http://checkout.google.com";
			$gateway_Link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_GOOGLECHECKOUT_LINK'),
										$url, '', '_blank');
			$google_fieldset->Add($gateway_Link);
			
			$url_integration = "https://checkout.google.com/sell/settings?section=Integration";
			$gateway_Integration =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_GOOGLECHECKOUT_INTEGRATION'),
										$url_integration, '', '_blank');
			$google_fieldset->Add($gateway_Integration);
			
			$callback =& Piwi::CreateWidget('TextArea', 'callback_url', "https://".$site_ssl_url."/index.php?gadget=Ecommerce&action=GoogleCheckoutResponse&url=".urlencode(str_replace('http://', '', str_replace('https://', '', $GLOBALS['app']->getSiteURL()))), _t('ECOMMERCE_SETTINGS_GOOGLECHECKOUT_INTEGRATION_URL'), 1, 153);
			$callback->SetStyle('font-size: 0.85em; margin-top: 10px;');
			$google_fieldset->Add($callback);
			
			$step3_Link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_GOOGLECHECKOUT_STEP3'),
										$url_integration, '', '_blank');
			$google_fieldset->Add($step3_Link);
			
			$form->Add($google_fieldset);
			
			// PayPal
			$paypal_fieldset = new Jaws_Widgets_FieldSet('');
			$paypal_fieldset->SetTitle('vertical');
			$paypal_fieldset->SetID('PayPal');
			if ($merchant_gateway != 'PayPal' || (isset($gateway_id) && !empty($gateway_id) && isset($gateway_key) && !empty($gateway_key))) {
				$paypal_fieldset->SetStyle('margin-top: 30px; display: none;');
			} else {
				$paypal_fieldset->SetStyle('margin-top: 30px;');
			}
			
			$url = "http://www.paypal.com";
			$gateway_Link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_PAYPAL_LINK'),
										$url, '', '_blank');
			$paypal_fieldset->Add($gateway_Link);
			
			$url_integration = "https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-api-access";
			$step2_Link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_PAYPAL_STEP2'),
										$url_integration, '', '_blank');
			$paypal_fieldset->Add($step2_Link);
			
			$form->Add($paypal_fieldset);
						
			// Authorize.NET
			$authorize_fieldset = new Jaws_Widgets_FieldSet('');
			$authorize_fieldset->SetTitle('vertical');
			$authorize_fieldset->SetID('AuthorizeNet');
			if ($merchant_gateway != 'AuthorizeNet' || (isset($gateway_id) && !empty($gateway_id) && isset($gateway_key) && !empty($gateway_key))) {
				$authorize_fieldset->SetStyle('margin-top: 30px; display: none;');
			} else {
				$authorize_fieldset->SetStyle('margin-top: 30px;');
			}
			
			$url2 = "http://www.authorize.net/application/";
			$gateway2_Link =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_AUTHORIZENET_LINK'),
										$url2, '', '_blank');
			$authorize_fieldset->Add($gateway2_Link);
			
			$url_integration2 = "https://secure.authorize.net";
			$gateway_Integration2 =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_AUTHORIZENET_INTEGRATION', $_SERVER['SERVER_NAME']),
										$url_integration2, '', '_blank');
			$authorize_fieldset->Add($gateway_Integration2);
			
			$callback2 =& Piwi::CreateWidget('TextArea', 'callback_url', "https://".$site_ssl_url."/index.php?gadget=Ecommerce&action=AuthorizeNetResponse&url=".urlencode(str_replace('http://', '', str_replace('https://', '', $GLOBALS['app']->getSiteURL()))), _t('ECOMMERCE_SETTINGS_AUTHORIZENET_INTEGRATION_URL'), 1, 153);
			$callback2->SetStyle('font-size: 0.85em; margin-top: 10px;');
			$authorize_fieldset->Add($callback2);
			
			$step3_Link2 =& Piwi::CreateWidget('Link', _t('ECOMMERCE_SETTINGS_AUTHORIZENET_STEP3'),
										$url_integration2, '', '_blank');
			$authorize_fieldset->Add($step3_Link2);


			// Ship From Address
			$state_code = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_state');
			$sql = "SELECT [region] FROM [[country]] WHERE ([is_country] = 'N') AND ([parent] = 1) AND ([country_iso_code] = '".$state_code."')";
			$country = $GLOBALS['db']->queryOne($sql);
			if (!Jaws_Error::IsError($country) && !empty($country)) {
				$shipfrom_state = $country;
			}	
			
			$shipfrom_html = '';
			if ($merchant_gateway == 'AuthorizeNet' || $merchant_gateway == 'ManualCreditCard') {
				$shipfrom_html = '<fieldset id="fieldsetShipFrom" style="margin-top: 30px;" title="vertical">';
				$shipfrom_html .= '<legend id="fieldsetShipFrom_legend">'._t('ECOMMERCE_SETTINGS_SHIPFROM_ADDRESS').'</legend>';
				$shipfrom_html .= '<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<TR>
						<TD class="syntacts-form-row" VALIGN="top"><B>Use Carrier Calculated Shipping:</B></TD>
						<TD VALIGN="top" class="syntacts-form-row">
						<SELECT SIZE="1" NAME="use_carrier_calculated" ID="use_carrier_calculated">
								<OPTION VALUE="Y" '.($use_carrier_calculated == 'Y' ? 'selected="selected"' : '').'>Yes</OPTION>
								<OPTION VALUE="N" '.($use_carrier_calculated == 'N' || empty($use_carrier_calculated) ? 'selected="selected"' : '').'>No</OPTION>
						</select>
						</TD>
					  </TR>
					<TR>
						<TD class="syntacts-form-row" VALIGN="top"><B>Region:</B></TD>
						<TD VALIGN="top" class="syntacts-form-row">
						<SELECT SIZE="1" NAME="country_id" ID="country_id">
								<OPTION VALUE="1">United States</OPTION>
						</select>
						</TD>
					  </TR>

					  <TR>
						<TD class="syntacts-form-row" VALIGN="top"><B>State/Country:</B></TD>
						<TD VALIGN="top" class="syntacts-form-row">
						 <SELECT SIZE="1" NAME="region" id="region">
							</SELECT>
						</TD>
					  </TR>
					<TR>
					  <TD class="syntacts-form-row" VALIGN="top"><B>City/Locale:</B></TD>
					  <TD VALIGN="top" class="syntacts-form-row">
						<INPUT TYPE="text" NAME="city" SIZE="53" STYLE="font-family: Arial, \'MS Sans Serif\'; font-size: 8pt" VALUE="'.$GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_city').'" ID="city">
						<div id="search_choices" class="autocomplete"></div>
					  </TD>
					</TR>
					<TR>
					  <TD class="syntacts-form-row" VALIGN="top"><B>Zip/Postal Code:</B></TD>
					  <TD VALIGN="top" class="syntacts-form-row">
					  <INPUT TYPE="text" VALUE="'.$GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/shipfrom_zip').'" NAME="postal_code" ID="postal_code">
					  </TD>
					</TR>
				</table>
				<script type="text/javascript">
					function getStateId(element, entry) {
						autoCompleteHost = "index.php?gadget=Maps&action=AutoCompleteRegions";
						autoCompleteURL = "&element=city&methodcount=1&initial1gadget=Maps&initial1method=SearchRegions&initial1paramcount=2&initial1param1="+$(\'region\').value+"&initial1param2=country";
						return entry+"&methodcount=1&initial1gadget=Maps&initial1method=SearchRegions&initial1paramcount=2&initial1param1="+$(\'region\').value+"&initial1param2=country";
					}
						
					function showNoMatches(text, li) {
						value = li.innerHTML;
						if(value.indexOf("No matches") != -1){
							text.value = "No matches were found.";
							$("search_choices").style.display = "";
						}
					}
					
					var autoCompleteHost = "";
					var autoCompleteURL = "";
					var showingChoices = false;
					var MyComboBox = Class.create();

					MyComboBox.Autocompleter = Ajax.Autocompleter;

					// Overload onBlur
					MyComboBox.Autocompleter.prototype.onBlur = function(event) {
						if (Element.getStyle(this.update, "display") == "none") { return; }
						// Dont hide the div on "blur" if the user clicks scrollbar
						if(event&&Element.getStyle(this.update, "height") != ""){
							if( Position.within( this.update , Event.pointerX(event) , Event.pointerY(event) ) ){ 
								Event.observe(this.update, "blur", this.onBlur.bindAsEventListener(this),true); 
								// make sure blur is still around on
								return; 
							}
						}
						// needed to make click events working
						setTimeout(this.hide.bind(this), 250);
						this.hasFocus = false;
						this.active = false; 
					}

					MyComboBox.Autocompleter.prototype.markPrevious = function() {
						if(this.index > 0) this.index--
						else this.index = this.entryCount-1;
						this.getEntry(this.index).scrollIntoView(false);
					}
						
					MyComboBox.prototype = {
						initialize: function(textBox, resultsElement, array, options) {
						this.textBox = $(textBox);
								
						//Cache for allChoicesHtml
						this.allChoicesHtml = null
						
						this.results = $(resultsElement);
						
						this.array = array;
						
						this.results.style.display 	= "none";
						
						this.events = {
							showChoices: 	this.showChoices.bindAsEventListener(this),
							hideChoices: 	this.hideChoices.bindAsEventListener(this),
							click:			this.click.bindAsEventListener(this),
							keyDown:		this.keyDown.bindAsEventListener(this)
						}
						
						this.autocompleter = new MyComboBox.Autocompleter(this.textBox, this.results, this.array, options);
						
						Event.observe(this.textBox, "click", this.events.click);
						Event.observe(this.textBox, "keydown", this.events.keyDown);
						},
						
						keyDown: function(e) {
							if (e.keyCode == Event.KEY_DOWN && this.choicesVisible() ) {
								this.showChoices();
							}			
						},
						
						// returns boolean indicating whether the choices are displayed
						choicesVisible: function() { return (Element.getStyle(this.autocompleter.update, "display") == "none"); },
						
						click: function() {
							if (this.choicesVisible() ) {
								this.showChoices();
							} else {
								this.hideChoices();
							}
						},
							
						showChoices: function() {
							this.textBox.focus();
							this.autocompleter.changed = false;
							this.autocompleter.hasFocus = true;
							//this.getAllChoices();
						},
						
						hideChoices: function() {
							this.autocompleter.onBlur();
						}
					}
					Event.observe(window, "load",function(){
						var ComboBox = new MyComboBox("city", "search_choices", "'.$GLOBALS['app']->GetSiteURL().'/index.php?gadget=Maps&action=AutoCompleteRegions", {paramName: "query", minChars: 3, callback: getStateId, afterUpdateElement: showNoMatches});
						getRegionsOfParent(document.getElementById(\'country_id\').value, \'region\', \''.$shipfrom_state.'\');
					});
				</script>';
				$shipfrom_html .= '</fieldset>';
			}
			$form->Add($authorize_fieldset);

			// Merchant ID
			$gateway_idEntry =& Piwi::CreateWidget('Entry', 'gateway_id', $gateway_id);
			$gateway_idEntry->SetTitle(_t('ECOMMERCE_SETTINGS_'.($merchant_gateway == 'PayPal' ? strtoupper($merchant_gateway) : 'GATEWAY').'_ID'));
			$gateway_idEntry->SetStyle('direction: ltr; width: 300px;');
			
			// Merchant Key
			$gateway_keyEntry =& Piwi::CreateWidget('Entry', 'gateway_key', $gateway_key);
			$gateway_keyEntry->SetTitle(_t('ECOMMERCE_SETTINGS_'.($merchant_gateway == 'PayPal' ? strtoupper($merchant_gateway) : 'GATEWAY').'_KEY'));
			$gateway_keyEntry->SetStyle('direction: ltr; width: 300px;');

			// Merchant Signature
			$gateway_sigEntry =& Piwi::CreateWidget('Entry', 'gateway_signature', $gateway_signature);
			$gateway_sigEntry->SetTitle(_t('ECOMMERCE_SETTINGS_'.($merchant_gateway == 'PayPal' ? strtoupper($merchant_gateway) : 'GATEWAY').'_SIGNATURE'));
			$gateway_sigEntry->SetStyle(($merchant_gateway != 'PayPal' ? 'display: none; ' : '').'direction: ltr; width: 300px;');

			// Image
			$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$gateway_logo = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/gateway_logo');
			$image = (!empty($gateway_logo)) ? $gateway_logo : '';
			$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($image);
			$image_preview = '';
			if ($image != '' && file_exists($image_src)) { 
				$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\" />";
			}
			$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Ecommerce', 'NULL', 'NULL', 'main_image', 'gateway_logo', 1, 500, 34);});</script>";
			$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'gateway_logo', $image);
			$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Uploaded Files\" onclick=\"openUploadWindow('gateway_logo')\" style=\"font-family: Arial; font-size: 10pt; font-weight: bold\" />";
			$imageEntry =& Piwi::CreateWidget('UploadEntry', 'gateway_logo', _t('ECOMMERCE_SETTINGS_GATEWAY_LOGO'), $image_preview, $imageScript, $imageHidden->Get(), $imageButton);
			
			$fieldset = new Jaws_Widgets_FieldSet('');
			$fieldset->SetTitle('vertical');
			$fieldset->Add($gateway_idEntry);
			$fieldset->Add($gateway_keyEntry);
			$fieldset->Add($gateway_sigEntry);
			$fieldset->Add($imageEntry);
			$form->Add($fieldset);
			
			// Transaction Fees
			/*
			$transaction_percent = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/transaction_percent');
			$transaction_amount = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/transaction_amount');
			$transaction_mode = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/transaction_mode');
			
			$transaction_fieldset = new Jaws_Widgets_FieldSet(_t('ECOMMERCE_SETTINGS_TRANSACTION_FEES'));
			$transaction_fieldset->SetTitle('vertical');
			$transaction_fieldset->SetStyle('margin-top: 50px;');
			
			// Transaction Percentage
			$transactionPercentCombo =& Piwi::CreateWidget('Combo', 'transaction_percent');
			for ($p=0;$p<1000;$p++) {
				$transactionPercentCombo->AddOption($p, $p);
			}
			$transactionPercentCombo->SetTitle(_t('ECOMMERCE_SETTINGS_TRANSACTION_PERCENT'));
			$transactionPercentCombo->SetDefault((int)$transaction_percent);
			
			// Transaction Dollar Amount
			$transactionAmount =& Piwi::CreateWidget('Entry', 'transaction_amount', number_format($transaction_amount, 2, '.', ''));
			$transactionAmount->SetTitle(_t('ECOMMERCE_SETTINGS_TRANSACTION_AMOUNT'));
			$transactionAmount->SetStyle('direction: ltr; width: 300px;');

			// Transaction Mode
			$transactionModeCombo =& Piwi::CreateWidget('Combo', 'transaction_mode');
			$transactionModeCombo->SetTitle(_t('ECOMMERCE_SETTINGS_TRANSACTION_MODE'));
			$transactionModeCombo->AddOption(_t('ECOMMERCE_SETTINGS_TRANSACTION_MODE_ADD'), 'add');
			$transactionModeCombo->AddOption(_t('ECOMMERCE_SETTINGS_TRANSACTION_MODE_SUBTRACT'), 'subtract');
			$transactionModeCombo->SetDefault($transaction_mode);
			
			$transaction_fieldset->Add($transactionPercentCombo);
			$transaction_fieldset->Add($transactionAmount);
			$transaction_fieldset->Add($transactionModeCombo);
			$form->Add($transaction_fieldset);
			*/
			
			// Notify Expiring Subscription Frequency
			$gadgets_fieldset = new Jaws_Widgets_FieldSet(_t('ECOMMERCE_SETTINGS_NOTIFY_EXPIRING_FREQ'));
			$gadgets_fieldset->SetTitle('vertical');
			$gadgets_fieldset->SetStyle('margin-top: 50px;');

			$checks =& Piwi::CreateWidget('CheckButtons', 'notify_expiring_freq','vertical');
			$checked = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/notify_expiring_freq');
			$checked = explode(",",$checked);
			
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_1DAY'), '1', null, in_array('1', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_2DAY'), '2', null, in_array('2', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_3DAY'), '3', null, in_array('3', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_5DAY'), '5', null, in_array('5', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_10DAY'), '10', null, in_array('10', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_15DAY'), '15', null, in_array('15', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_1MONTH'), '30', null, in_array('30', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_2MONTH'), '61', null, in_array('61', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_3MONTH'), '91', null, in_array('91', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_6MONTH'), '182', null, in_array('182', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_9MONTH'), '274', null, in_array('274', $checked));
			$checks->AddOption(_t('ECOMMERCE_SETTINGS_NOTIFY_1YEAR'), '365', null, in_array('365', $checked));
			
			$gadgets_fieldset->Add($checks);
			$form->Add($gadgets_fieldset);
			
			// Checkout Terms
			$terms_fieldset = new Jaws_Widgets_FieldSet(_t('ECOMMERCE_SETTINGS_TERMS'));
			$terms_fieldset->SetTitle('vertical');
			$terms_fieldset->SetStyle('margin-top: 50px;');
			$termsEntry =& Piwi::CreateWidget('TextArea', 'checkout_terms', $checkout_terms);
			$termsEntry->SetStyle('width: 100%;');
			$terms_fieldset->Add($termsEntry);
			$form->Add($terms_fieldset);
			
			$buttons =& Piwi::CreateWidget('HBox');
			$buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

			$save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
			$save->AddEvent(ON_CLICK, 'javascript: saveSettings();');

			$buttons->Add($save);
			$buttons_html = $buttons->Get();
			//$form->Add($buttons);

		}
        $form_html = $form->Get();
		$tpl->SetVariable('form', str_replace('</form>', $shipfrom_html.$buttons_html.'</form>', $form_html));
        $tpl->SetVariable('menubar', $this->MenuBar('Settings'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();
    }

    /**
     * ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('Ecommerce', 'OwnEcommerce');
    }

    /**
     * sets GB root with DPATH
     *
     * @access public
     * @return javascript string
     */
    function SetGBRoot()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}

}
