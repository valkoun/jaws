<?php
/**
 * Websites AJAX API
 *
 * @category   Ajax
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class WebsitesAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function WebsitesAjax(&$model)
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
    function DeleteWebsiteParent($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
        $gadget->DeleteWebsiteParent($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes an ad and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteWebsite($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
        $gadget->DeleteWebsite($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a saved ad and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteSavedWebsite($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
        $gadget->DeleteSavedWebsite($id);
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
		$res = array();
		$gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
        $pages = $gadget->SearchWebsiteParents($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
        $pid = (trim($pid) != '' ? (int)$pid : null);
        $pages = $gadget->SearchWebsites($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'), $pid);
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
        $pages = $gadget->SearchSavedWebsites($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchWebsiteParents($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetWebsiteParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchWebsites($status, $search, $limit, $pid = null)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        $pid = (trim($pid) != '' ? (int)$pid : null);
        return $gadget->GetWebsites($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'), $pid);
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
    function SearchSavedWebsites($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetSavedWebsites($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
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
		if (!$this->GetPermission('Websites', 'ManageWebsites') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Websites', 'OwnWebsites')) {
			$this->CheckSession('Websites', 'ManageWebsites');
		}
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }
}
?>
