<?php
/**
 * Maps AJAX API
 *
 * @category   Ajax
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function MapsAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeletePage
    /**
     * Deletes a map.
     *
     * @access  public
     * @param   int     $id  Map ID
     * @return  array   Response (notice or error)
     */
    function DeleteMap($id)
    {
        $this->CheckSession('Maps', 'ManageMaps');
        $this->_Model->DeleteMap($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Deletes a post
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeletePost($pid)
    {
        $this->CheckSession('Maps', 'ManageMaps');		
        $this->_Model->DeletePost($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }


    /**
     * Executes a massive-delete of pages
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
        $this->CheckSession('Maps', 'ManageMaps');
        $this->_Model->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
        
    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
        $this->CheckSession('Maps', 'default');
        $pages = $this->_Model->SearchMaps($status, $search, null);
        return count($pages);
    }

    /**
     * Returns an array with all the pages
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Pages data
     */
    function SearchMaps($status, $search, $limit)
    {
        $this->CheckSession('Maps', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Maps', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
			return $gadget->GetMaps($status, $search, $limit);
		} else {
			return $gadget->GetMaps($status, $search, $limit, false, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
    }

    /**
     * This function will perform an autodraft of the content and set
     * it's value to not published, which will later be changed when the
     * user clicks on save.
     *
     * @access public
     * @param int    $id        The id of the staticpage id to update
     * @param string $fast_url  The value of the fast_url. This will
     *                          be autocreated if nothing is passed.
     * @param bool   $showtitle This will to know if we show the title or not.
     * @param string $title     The new autosaved title
     * @param string $description   The description of the page
     * @param string $keywords  The keywords of the page
     * @param bool   $active If the item is published or not. Default: draft
     */
    function AutoDraft($id = '', $fast_url = '', $showtitle = '', $title = '', $description = '',
                       $keywords = '', $active = '', $gadget, $fieldnames, $fieldvalues)
    {
        $this->CheckSession('Maps', 'default');

        /*
		if ($id == 'NEW') {
            $this->_Model->AddPage($fast_url, $show_title, $title, $content, $language, $published, true);
            $newid    = $GLOBALS['db']->lastInsertID('static_pages', 'id');
            $response['id'] = $newid;
            $response['message'] = _t('CUSTOMPAGE_PAGE_AUTOUPDATED',
                                      date('H:i:s'),
                                      (int)$id,
                                      date('D, d'));
            $GLOBALS['app']->Session->PushLastResponse($response, RESPONSE_NOTICE);
        } else {
            $this->_Model->UpdatePage($id, $fast_url, $showtitle, $title, $content, $language, $published, true);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
		*/
		return true;
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
        $this->CheckSession('Maps', 'ManageMaps');		
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
     * Saves the value of a key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SetRegistryKey($key, $value)
    {
        $this->CheckSession('Maps', 'ManageMaps');
        $this->_Model->SetRegistryKey($key, $value);
		return $GLOBALS['app']->Session->PopLastResponse();
    }
	
    /**
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $id  ID of the parent
     * @return  array   country DB table data
     */
    function GetRegionsOfParent($id, $where = 'region')
    {
        $this->CheckSession('Maps', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		$res = $gadget->GetRegionsOfParent($id);
		if (Jaws_Error::IsError($res) || !$res) {
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
			return false;
        }
		return $res;
    }
}
?>
