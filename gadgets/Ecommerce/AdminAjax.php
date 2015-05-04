<?php
/**
 * Ecommerce AJAX API
 *
 * @category   Ajax
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function EcommerceAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeleteOrder
    /**
     * Deletes a gallery and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteOrder($id)
    {
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $this->_Model->DeleteOrder($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    // }}}
    // {{{ Function DeleteShipping
    /**
     * Deletes a gallery and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteShipping($id)
    {
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $this->_Model->DeleteShipping($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    // }}}
    // {{{ Function DeleteTaxes
    /**
     * Deletes a gallery and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteTaxes($id)
    {
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $this->_Model->DeleteTaxes($id);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
		$res = array();
		$sort = $this->_Model->SortItem($pids, $newsorts);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $this->_Model->MassiveDelete($pages);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $pages = $this->_Model->SearchOrders($status, $search, null, 0);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetOrders($status, $search, $limit);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $pages = $this->_Model->SearchShipping($status, $search, null, 0);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetShippings($status, $search, $limit, 0);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $pages = $this->_Model->SearchTaxes($status, $search, null, 0);
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
		$this->CheckSession('Ecommerce', 'ManageEcommerce');
        $gadget = $GLOBALS['app']->LoadGadget('Ecommerce', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetTaxes($status, $search, $limit, 0);
    }

    /**
     * Save user config settings
     *
     * @access  public
     * @param   string  $priority  Priority
     * @param   string  $method    Authentication method
     * @param   string  $anon      Anonymous users can auto-register
     * @param   string  $recover   Users can recover their passwords
     * @return  array   Response (notice or error)
     */
    function SaveSettings(
		$payment_gateway, $gateway_id, $gateway_key, $gateway_signature, $gateway_logo, 
		$notify_expiring_freq, $shipfrom_city, $shipfrom_state, $shipfrom_zip, $use_carrier_calculated, 
		$transaction_percent = 0, $transaction_amount = 0, $transaction_mode = 'subtract', $checkout_terms = ''
	) {
        $this->CheckSession('Ecommerce', 'ManageEcommerce');
        $res = $this->_Model->SaveSettings(
			$payment_gateway, $gateway_id, $gateway_key, $gateway_signature, $gateway_logo, 
			$notify_expiring_freq, $shipfrom_city, $shipfrom_state, $shipfrom_zip, $use_carrier_calculated,
			$transaction_percent, $transaction_amount, $transaction_mode, $checkout_terms
		);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('ECOMMERCE_SETTINGS_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
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
        $this->CheckSession('Ecommerce', 'default');
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

    /**
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $id  ID of the parent
     * @return  array   country DB table data
     */
    function GetRegionsOfParent($id)
    {
        //$this->CheckSession('Properties', 'default');
		$GLOBALS['app']->Registry->LoadFile('Maps');
		$GLOBALS['app']->Translate->LoadTranslation('Maps', JAWS_GADGET);
        $gadget = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		$result = $gadget->GetRegionsOfParent($id);
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), _t('MAPS_NAME'));
        }
		return $result;
    }

    /**
     * Returns closest match of city from the country DB table
     *
     * @access  public
     * @param   string  $value  seed to match
     * @param   integer  $pid  ID of the parent
     * @return  array   country DB result
     */
    function GetClosestMatch($value, $pid, $table = '')
    {
		$res = array();
		if ((int)$pid > 0 && trim($value) != '') {
			if (trim($table) == '') {
				$table = null;
			}
			$GLOBALS['app']->Registry->LoadFile('Maps');
			$GLOBALS['app']->Translate->LoadTranslation('Maps', JAWS_GADGET);
			$gadget = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
			$haystacks = $gadget->SearchRegions(substr($value, 0, 1), $pid, $table);
			
			$shortest = -1;
			$closest = null;
			foreach ($haystacks as $haystack){
				foreach ($haystack as $word){
					$lev = levenshtein($value, $word);
					if ($lev == 0) {
						$closest = $word; $shortest = 0; break;
					}
					if ($lev <= $shortest || $shortest <0) {
						$closest  = $word; $shortest = $lev;
					}
				}
			}
			if (!is_null($closest)) {
				$res['value'] = $closest;
			} else {
				$res['value'] = false;
			}
		} else {
			$res['value'] = false;
		}
		return $res;
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
