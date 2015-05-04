<?php
/**
 * Ads AJAX API
 *
 * @category   Ajax
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class AdsAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function AdsAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}

    /**
     * Deletes a ad parent and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteAdParent($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $gadget->DeleteAdParent($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes an ad and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteAd($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $gadget->DeleteAd($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a saved ad and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteSavedAd($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $gadget->DeleteSavedAd($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a brand and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteBrand($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $gadget->DeleteBrand($id);
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
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
		$res = array();
		$gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
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
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
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
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $pages = $gadget->SearchAdParents($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Get total galleries of a search
     *
     * @access  public
     * @param   string  $status  Status of gallery(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch1($status, $search, $pid = null)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $pid = (trim($pid) != '' ? (int)$pid : null);
        $pages = $gadget->SearchAds($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'), $pid);
        return count($pages);
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
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $pages = $gadget->SearchBrands($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Get total galleries of a search
     *
     * @access  public
     * @param   string  $status  Status of gallery(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch3($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $pages = $gadget->SearchSavedAds($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchAdParents($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetAdParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchAds($status, $search, $limit, $pid = null)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        $pid = (trim($pid) != '' ? (int)$pid : null);
        return $gadget->GetAds($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'), $pid);
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
    function SearchSavedAds($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetSavedAds($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchBrands($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetBrands($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
			$this->CheckSession('Ads', 'ManageAds');
		}
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

    /**
     * Adds record ads_clicks db table
     *
     * @access  public
     * @params  int  $id id of ad that was clicked
     * @return  array   Response (notice or error)
     */
    function AddClick($id)
    {
		//$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		//if (!$this->GetPermission('Ads', 'ManageAds') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
		//	$this->CheckSession('Ads', 'ManageAds');
		//}
		$gadget = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$gadget->AddClick($id);
		return $GLOBALS['app']->Session->PopLastResponse();
    }
}
