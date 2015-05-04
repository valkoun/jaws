<?php
/**
 * Ecommerce AJAX API
 *
 * @category   Ajax
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function EcommerceAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}

    /**
     * Deletes a gallery and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteOrder($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
        $gadget->DeleteOrder($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }


     /**
     * Moves an item in the sort_order
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @param   string  $direction  'up', or 'down'
     * @return  array   Response (notice or error)
     */
    function SortItem($pids, $newsorts)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
		$res = array();
		$gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
        $sort = $gadget->SortItem($pids, $newsorts);
        if ($sort === false) {
            $res['success'] = false;
        } else {
            //$res['id'] = (int)$pid;
            //if ($direction == 'up') {
			//	$res['moved'] = -1;
            //} else {
			//	$res['moved'] = 1;
			//}
			$res['success'] = true;
        }
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
    }

    /**
     * Executes a massive-delete of galleries
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminModel');
        $gadget->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total galleries of a search
     *
     * @access  public
     * @param   string  $status  Status of gallery(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $pages = $this->_Model->SearchOrders($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Returns an array with all the galleries
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Galleries data
     */
    function SearchOrders($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetOrders($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
    }

    /**
     * Get total galleries of a search
     *
     * @access  public
     * @param   string  $status  Status of gallery(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch1($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $pages = $this->_Model->SearchShipping($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Returns an array with all the galleries
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Galleries data
     */
    function SearchShippings($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetShippings($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
    }

    /**
     * Get total galleries of a search
     *
     * @access  public
     * @param   string  $status  Status of gallery(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch2($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $pages = $this->_Model->SearchTaxes($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Returns an array with all the galleries
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Galleries data
     */
    function SearchTaxes($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetTaxes($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
    }

    /**
     * Adds URL to embed_gadgets
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function AddEmbedSite($gadget, $url, $gadget_url, $layout)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ecommerce', 'ManageEcommerce') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ecommerce', 'OwnEcommerce')) {
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		}
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

    /**
     * Before Add Items in Cart
     *
     * @access  public
     * @params  array  $items in cart
     * @return  array   Response (notice or error)
     */
    function onBeforeAddToCart($item = null, $index = null, $newQuantity = null, $opt_node = null) {
		$result = array();

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onBeforeAddToCart', 
			array(
				'item' => $item, 
				'index' => $index, 
				'newQuantity' => $newQuantity, 
				'opt_node' => $opt_node
			)
		);
		var_dump($res);
		exit;
		if (Jaws_Error::IsError($res) || !$res) {
			$result['message'] = 'Error: '.(Jaws_Error::IsError($res) ? $res->GetMessage() : "Could not add to cart. Please try again later.");
		} else if (isset($res['message']) && !empty($res['message'])) {
			$result['message'] = $res['message'];
			if (isset($res['url'])) {
				$result['url'] = $res['url'];
			}
			if (isset($res['body'])) {
				$result['body'] = $res['body'];
			}
			if (isset($res['form_submit'])) {
				$result['form_submit'] = $res['form_submit'];
			}
			if (isset($res['form'])) {
				$result['form'] = $res['form'];
			}
		} else {
			$result['message'] = 'true';
		}
		return $result;
	}
	
    /**
     * Add Items in Cart
     *
     * @access  public
     * @params  array  $items in cart
     * @return  array   Response (notice or error)
     */
    function onAddToCart($item = null, $index = null) {
		$result = array();

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onAddToCart', 
			array(
				'item' => $item, 
				'index' => $index
			)
		);
		if (Jaws_Error::IsError($res) || !$res) {
			$result['message'] = 'Error: '.(Jaws_Error::IsError($res) ? $res->GetMessage() : "Could not add to cart. Please try again later.");
		} else if (isset($res['message']) && !empty($res['message'])) {
			$result['message'] = $res['message'];
			if (isset($res['url'])) {
				$result['url'] = $res['url'];
			}
			if (isset($res['body'])) {
				$result['body'] = $res['body'];
			}
			if (isset($res['form_submit'])) {
				$result['form_submit'] = $res['form_submit'];
			}
			if (isset($res['form'])) {
				$result['form'] = $res['form'];
			}
		} else {
			$result['message'] = 'true';
		}
		return $result;
	}
	
    /**
     * Posts Items in Cart
     *
     * @access  public
     * @params  array  $items in cart
     * @return  array   Response (notice or error)
     */
    function PostCart(
		$items, $total_weight = 0.00, $paymentmethod = '', $redirect_to = '', $customer_shipfirstname = '', $customer_shiplastname = '', 
		$customer_shipaddress = '', $customer_shipcity = '', $customer_shipregion = '', $customer_shippostal = '', $customer_shipcountry = '', 
		$shipfreight = '', $customer_shipaddress2 = '', $customer_firstname = '', $customer_middlename = '', $customer_lastname = '', 
		$customer_suffix = '', $customer_address = '', $customer_address2 = '', $customer_city = '', $customer_region = '', $customer_postal = '', $customer_country = '', 
		$cc_creditcardtype = '', $cc_acct = '', $cc_expdate_month = '', $cc_expdate_year = '', 
		$cc_cvv2 = '', $customcheckoutfields = array(), $customer_phone = '', $usecase = 'DigitalUsecase'
	) {
		$gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		//$items = $GLOBALS['app']->UTF8->json_decode($GLOBALS['app']->UTF8->json_encode($items));
		$result = $gadget->PostCart(
			$items, $total_weight, $paymentmethod, $redirect_to, $customer_shipfirstname, $customer_shiplastname, $customer_shipaddress, $customer_shipcity, 
			$customer_shipregion, $customer_shippostal, $customer_shipcountry, $shipfreight, $customer_shipaddress2, 
			$customer_firstname, $customer_middlename, $customer_lastname, $customer_suffix, 
			$customer_address, $customer_address2, $customer_city, $customer_region, $customer_postal, $customer_country,
			$cc_creditcardtype, $cc_acct, $cc_expdate_month, $cc_expdate_year, $cc_cvv2, $customcheckoutfields, $customer_phone, $usecase
		);
		return $result;
    }
	
	/**
     * Displays select options for shipping.
     *
     * @access public
     * @return string
     */
    function GetShippingSelect($weight = 0, $price = 0, $qty = 1, $zip = '', $state = '', $country = 'US')
    {
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
		return $gadget->GetShippingSelect($weight, $price, $qty, $zip, $state, $country);
    }
	
	/**
     * Get Sale by Code
     *
     * @access public
     * @return string
     */
    function GetSaleByCode($code = '', $total = null)
    {
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$res = $gadget->GetSaleByCode($code);
		if (Jaws_Error::IsError($res)) {
			return array();
		} else {
			$now = $GLOBALS['db']->Date();
			if ($res['active'] == 'Y' && ($now > $res['startdate'] && $now < $res['enddate'])) {
				if (!is_null($total)) { 
					if ($res['discount_amount'] > 0) {
						$total = number_format($total - number_format($res['discount_amount'], 2, '.', ''), 2, '.', '');
					} else if ($res['discount_percent'] > 0) {
						$total = number_format($total - ($total * ($res['discount_percent'] * .01)), 2, '.', '');
					} else if ($res['discount_newprice'] > 0) {
						$total = number_format($res['discount_newprice'], 2, '.', '');
					}
					$res['newprice'] = $total;
				}
				return $res;
			}
		}
	}
	
	/**
     * Adds a comment
     *
     * @access  public
     * @param   string  $title      Title of the comment
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $parentId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */
    function NewEcommerceComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone', $reply = false)
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		} else {
			if (empty($parentId)) {
				$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				(!empty($info['company']) ? $info['company'] : $info['nickname']), $title, $info['url'], $info['email'], $comments, 
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing, 'Ecommerce'
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				if ((int)$parent == 0 && $reply === false) {
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
					$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					if (!empty($result['image'])) {
						$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					}
				}
				$res['name'] = $result['name'];
				$full_style = '';
				$preview_style = ' style="display: none;"';
				//$msg_reply = strip_tags($result['comment']);
				$msg_reply = $result['comment'];
				$msg_reply_preview = '';
				/*
				if (strlen($msg_reply) > 150) {
					$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Read it</a>';
					$msg_reply .= '&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				*/
				$res['full_style'] = $full_style;
				$res['preview_style'] = $preview_style;
				$res['comment'] = $msg_reply;
				$res['preview_comment'] = $msg_reply_preview;
				$res['title'] = $result['title'];
				$res['created'] = $result['created'];
				$res['permalink'] = $result['permalink'];
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$shout = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $result['permalink']));
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))) {
					$res['permalink'] = $shout['url'];
				}
				$res['activity'] = '';
			}
		}
		return $res;
    }
	
    /**
     * Deletes a comment
     *
     * @access  public
     * @param   int     $id   Comment ID
     * @return  array   Response (notice or error)
     */
    function DeleteEcommerceComment($id)
    {
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('Ecommerce', 'ManageEcommerce');
		} else {
			$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			$params 		= array();
			$params['id']   = (int)$id;
					
			$sql = '
				SELECT
					[gadget], [parent], [ownerid]
				FROM [[comments]]
				WHERE [id] = {id}';

			$gadget = $GLOBALS['db']->queryRow($sql, $params);
			if (Jaws_Error::IsError($gadget) || !isset($gadget['gadget']) || empty($gadget['gadget'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				return $GLOBALS['app']->Session->PopLastResponse();
			}
			// Is this a child comment of current user? They can delete it...
			if ((int)$gadget['parent'] > 0) {
				$params 		= array();
				$params['id']	= (int)$gadget['parent'];
						
				$sql = '
					SELECT
						[gadget], [parent], [ownerid]
					FROM [[comments]]
					WHERE [id] = {id}';

				$parent = $GLOBALS['db']->queryRow($sql, $params);
				if (Jaws_Error::IsError($parent) || !isset($parent['gadget']) || empty($parent['gadget'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
					return $GLOBALS['app']->Session->PopLastResponse();
				}
			}
			if ($uid != $gadget['ownerid'] && (isset($parent['ownerid']) && $uid != $parent['ownerid'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
			} else {
				$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
				$delete = $model->DeleteComment($id, $gadget['gadget']);
				if (!Jaws_Error::IsError($delete)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_DELETED'), RESPONSE_NOTICE);
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				}
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}
