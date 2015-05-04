<?php
/**
 * Websites AJAX API
 *
 * @category   Ajax
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class WebsitesAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function WebsitesAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeleteWebsites
    /**
     * Deletes a gallery and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteWebsite($id)
    {
		$this->CheckSession('Websites', 'ManageWebsites');
        $this->_Model->DeleteWebsite($id);
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
		$this->CheckSession('Websites', 'ManageWebsites');
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
		$this->CheckSession('Websites', 'ManageWebsites');
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
		$this->CheckSession('Websites', 'ManageWebsites');
        $pages = $this->_Model->SearchWebsiteParents($status, $search, null, 0);
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
		$this->CheckSession('Websites', 'ManageWebsites');
        $pid = (trim($pid) != '' ? (int)$pid : null);
		$pages = $this->_Model->SearchWebsites($status, $search, null, 0, $pid);
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
		$this->CheckSession('Websites', 'ManageWebsites');
        $pages = $this->_Model->SearchBrands($status, $search, null, 0);
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
		$this->CheckSession('Websites', 'ManageWebsites');
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetWebsiteParents($status, $search, $limit, 0);
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
		$this->CheckSession('Websites', 'ManageWebsites');
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        $pid = (trim($pid) != '' ? (int)$pid : null);
        return $gadget->GetWebsites($status, $search, $limit, 0, $pid);
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
		$this->CheckSession('Websites', 'ManageWebsites');
        $gadget = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetBrands($status, $search, $limit, 0);
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
        $this->CheckSession('Websites', 'default');
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }
}
?>
