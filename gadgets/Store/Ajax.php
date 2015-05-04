<?php
/**
 * Store AJAX API
 *
 * @category   Ajax
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function StoreAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeletePage
    /**
     * Deletes a product parent.
     *
     * @access  public
     * @param   int     $id  Product parent ID
     * @return  array   Response (notice or error)
     */
    function DeleteProductParent($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeleteProductParent($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a product.
     *
     * @access  public
     * @param   int     $id  Product ID
     * @return  array   Response (notice or error)
     */
    function DeleteProduct($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeleteProduct($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a amenity.
     *
     * @access  public
     * @param   int     $id  Product ID
     * @return  array   Response (notice or error)
     */
    function DeleteAttribute($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeleteProductAttribute($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a amenity.
     *
     * @access  public
     * @param   int     $id  Product ID
     * @return  array   Response (notice or error)
     */
    function DeleteAttributeType($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeleteAttributeType($id);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeletePost($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

	/**
     * Deletes a sale
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeleteSale($pid)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeleteSale($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
	
	/**
     * Deletes a post
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeleteBrand($pid)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->DeleteBrand($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

	/**
     * Hides RSS item
     *
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @access  public
     * @return  array   Response (notice or error)
     */
    function HideRss($pid, $title, $published, $url)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->HideRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Shows RSS item
     *
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @access  public
     * @return  array   Response (notice or error)
     */
    function ShowRss($pid, $title, $published, $url)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->ShowRss($pid, $title, $published, $url);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $gadget->MassiveDelete($pages);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $pages = $gadget->SearchProductParents($status, $search, null);
        return count($pages);
    }

    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearchAttributes($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $pages = $gadget->SearchAttributes($search, $status, null);
        return count($pages);
    }

    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearchAttributeTypes($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $pages = $gadget->SearchAttributeTypes($status, $search, null);
        return count($pages);
    }

    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearchSales($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $pages = $gadget->SearchSales($status, $search, null);
        return count($pages);
    }

    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearchBrands($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $pages = $gadget->SearchBrands($status, $search, null);
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
    function SearchProductParents($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetProductParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchAttributes($search, $status, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetAttributes($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchAttributeTypes($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetAttributeTypes($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchSales($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetSales($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
    function SearchBrands($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetBrands($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}

        /*
		if ($id == 'NEW') {
            $this->_Model->AddPage($fast_url, $show_title, $title, $content, $language, $published, true);
            $newid    = $GLOBALS['db']->lastInsertID('static_pages', 'id');
            $response['id'] = $newid;
            $response['message'] = _t('PROPERTIES_PAGE_AUTOUPDATED',
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
    function SortItem($pids, $newsorts, $table)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
		$res = array();
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $sort = $gadget->SortItem($pids, $newsorts, $table);
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
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $id  ID of the parent
     * @return  array   country DB table data
     */
    function InsertRSSProducts($category, $fetch_url = '', $override_city = '', $rss_url = '', $OwnerID = null, $num)
    {
        /*
		$gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$result = array();
		if (trim($fetch_url) != '') {
			//echo '<br />RSS URL: '.$fetch_url;
			require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
			$rss = fetch_rss($fetch_url);
			if ($rss) {
				//echo '<pre>';
				//var_dump($rss);
				//echo '</pre>';
				//$date = $GLOBALS['app']->loadDate();
				$is_googleBase = false;
				foreach ($rss->items as $item) {
					if (isset($item['g']) && is_array($item['g'])) {
						if ($is_googleBase === false) {
							$is_googleBase = true;
							break;
						}
					}
				}
				$result = $rss->items;
				if ($is_googleBase === true && isset($rss->channel["link_next"]) && !empty($rss->channel["link_next"])) {
					$real_rss_url = (trim($rss_url) != '' ? $rss_url : $fetch_url);
					//$output_html .= '<br />&nbsp;<br />Multi-part RSS feed: '.$rss->channel['link_next'];
					//$output_html .= '<br />Real RSS: '.$real_rss_url;
					$result[] = array('next_category' => $category, 'next_fetch_url' => $rss->channel['link_next'], 'next_override_city' => $override_city, 'next_rss_url' => $real_rss_url, 'next_ownerid' => (is_null($OwnerID) ? 'null' : $OwnerID));
					//$this->InsertRSSProducts($category, $rss->channel['link_next'], $override_city, $real_rss_url, $OwnerID);
					//$output_html .= "<div id=\"insert".md5($rss->channel["link_next"])."\"></div><script>new Ajax.Updater('insert".md5($rss->channel["link_next"])."', 'admin.php?gadget=Products&action=InsertRSSProducts&category=".(int)$category."&fetch_url=".urlencode($rss->channel['link_next'])."&override_city=".urlencode($override_city)."&rss_url=".urlencode($real_rss_url)."&OwnerID=".$OwnerID."', { method: 'post' });</script>";
				}
				//var_dump($rss);
				//var_dump($result);
			} else {
				$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", RESPONSE_ERROR);
				return false;
				//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('PROPERTIES_NAME'));
				//$output_html .= "<br />ERROR: There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.";
			}
			//echo $rss_html.'</table>';
		} else {
			return false;
		}
		return $result;
		*/
    }
	
	/**
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $item  array of info
     * @return  array   country DB table data
     */
    function InsertRSSProduct($item, $override_city = '', $category = 1, $rss_url = '', $OwnerID = null)
    {
        /*
		$gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
		$is_googleBase = false;
		$total = 0;
		$rss_property_link = '';
		$rss_virtual_tour = '';
		if (isset($item['link_self']) && !empty($item['link_self'])) {
			$rss_property_self = (strpos($item['link_self'], "http://") > 6 ? str_replace('http://', '', $item['link_self']) : $item['link_self']);
		} 
		if (isset($item['link']) && !empty($item['link'])) {
			$rss_property_link = (strpos($item['link'], 'http://', 1) > 6 ? substr($item['link'], 0, strpos($item['link'], 'http://', 1)) : $item['link']);
			if (substr($rss_property_link, -1) == '"') {
				$rss_property_link = substr($rss_property_link, 0, strlen($rss_property_link)-1);
			}
			//echo '<br /><pre>LINK: '.$rss_property_link.'</pre>';
			// TODO: Support more Virtual Tour formats
			if (strpos($rss_property_link, 'tour.getmytour.com') !== false) {
				$rss_virtual_tour = $rss_property_link;
			}
		}
		
		// snoopy
		$snoopy = new Snoopy('Store');
		$snoopy->agent = "Jaws";
		
		// Parse a location, if we can't then we won't add the property
		if (isset($item['g']['longitude']) && !empty($item['g']['longitude']) && isset($item['g']['latitude']) && !empty($item['g']['latitude'])) {
			$rss_location = $item['g']['latitude'].','.$item['g']['longitude'];
		} else if (isset($item['g']['location']) && !empty($item['g']['location'])) {
			$rss_location = $item['g']['location'];
		} else if ($snoopy->fetch($rss_property_link)) {
			//echo $snoopy->results;
			if (strpos($snoopy->results, "<meta name=\"geo.position\" content=\"")) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<meta name=\"geo.position\" content=\"";
				$delimeterRight = "\" />";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$rss_location = str_replace(";", ",", substr($inputStr, $posLeft, $posRight-$posLeft));
			}
		}
		
		if (isset($rss_location)) {
			usleep(200000);
			// snoopy
			$snoopy = new Snoopy('Store');
			$snoopy->agent = "Jaws";
			$address = "http://maps.google.com/maps/geo?q=".urlencode($rss_location)."&output=xml&key=".$key;
			//echo '<br />Google Geocoder: '.$address;
			if($snoopy->fetch($address)) {
				$xml_content = $snoopy->results;
			
				// XML Parser
				$xml_parser = new XMLParser;
				$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
				//echo '<pre>';
				//var_dump($xml_result);
				//echo '</pre>';
				$moreImages = array();
				$rss_id = '';
				$rss_title = '';
				$rss_published = '';
				$rss_image = '';
				$rss_location = '';
				$rss_bedrooms = 0;
				$rss_bathrooms = 0;
				$rss_mls_listing_id = '';
				$rss_year = '';
				$rss_square_feet = '';
				$rss_listing_type = '';
				$rss_property_type = '';
				$rss_price = 0;
				$rss_lot_size = '';
				$rss_email = '';
				$rss_agent = '';
				$rss_broker = '';
				$rss_broker_phone = '';
				$rss_phone = '';
				$rss_status = 'forsale';
				$rss_city = '';
				$rss_address = '';
				$rss_region = '';
				$rss_country_id = 999999;
				$rss_postal_code = '';
				$rss_property_author = '';
				$rss_property_author_link = '';
				$rss_property_author_type = '';
				$rss_property_alt_title = '';
				$rss_property_alt_link = '';
				$rss_property_alt_type = '';
				$rss_coordinates = '';
				for ($i=0;$i<$xml_result[1]; $i++) {
					//$is_totalResults = false;
					if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COUNTRYNAMECODE']) && isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && isset($xml_result[0][$i]['LOCALITYNAME']) && isset($xml_result[0][$i]['ADDRESS']) && isset($xml_result[0][$i]['COORDINATES']) && empty($rss_coordinates)) {
						//if (isset($xml_result[0][$i]['COUNTRYNAMECODE'])) {
							//echo '<br /><pre>xml_country: ';
							//var_dump($xml_result[0][$i]['COUNTRYNAMECODE']);
							$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = {is_country}) AND ([country_iso_code] = {country_iso_code})";
							$country = $GLOBALS['db']->queryOne($sql, array('is_country' => 'Y', 'country_iso_code' => $xml_result[0][$i]['COUNTRYNAMECODE']));
							if (!Jaws_Error::IsError($country)) {
								$rss_country_id = $country;
							}	
							//echo '<br />rss_country: ';
							//var_dump($rss_country_id);
							//echo '</pre>';
						//}
						//if (isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME'])) {
							$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = {is_country}) AND ([country_iso_code] = {country_iso_code})";
							$region = $GLOBALS['db']->queryOne($sql, array('is_country' => 'N', 'country_iso_code' => $xml_result[0][$i]['ADMINISTRATIVEAREANAME']));
							if (!Jaws_Error::IsError($region)) {
								$rss_region = $region;
							}	
						//}
						//if (isset($xml_result[0][$i]['LOCALITYNAME']) && $override_city == '') {
						if ($override_city == '') {
							$rss_city = $xml_result[0][$i]['LOCALITYNAME'];
						}
						//if (isset($xml_result[0][$i]['ADDRESS'])) {
							$rss_address = $xml_result[0][$i]['ADDRESS'];
						//}
						if (isset($xml_result[0][$i]['POSTALCODENUMBER'])) {
							$rss_postal_code = $xml_result[0][$i]['POSTALCODENUMBER'];
						}
						//if (isset($xml_result[0][$i]['COORDINATES'])) {
							$rss_coordinates = $xml_result[0][$i]['COORDINATES'];
						//}
					}
				}
				
				if (trim($override_city) != '') {
					$rss_city = $override_city;
				}
				if ((!empty($rss_address) || (!empty($rss_city) && !empty($rss_region)))) {
					$rss_title = $item['title'];
					//$rss_title = str_replace($rss->items['source']['title'], '', $rss_title); 
					$rss_published = (isset($item['date_timestamp']) ? $item['date_timestamp'] : (isset($item['published']) ? $item['published'] : ''));
					if (isset($item['g']) && is_array($item['g'])) {
						if ($is_googleBase === false) {
							$is_googleBase = true;
						}
						//if (is_array($item['g']['image_link'])) {
						//	foreach(
						//} else {
							$rss_image = (isset($item['g']['image_link']) ? $item['g']['image_link'] : '');
						//}
					
						$rss_id = (isset($item['g']['id']) ? $item['g']['id'] : '');
						$rss_bedrooms = (isset($item['g']['bedrooms']) ? (float)$item['g']['bedrooms'] : 0);
						$rss_bathrooms = (isset($item['g']['bathrooms']) ? (float)$item['g']['bathrooms'] : 0);
						$rss_mls_listing_id = (isset($item['g']['mls_listing_id']) ? $item['g']['mls_listing_id'] : '');
						$rss_year = (isset($item['g']['year']) ? $item['g']['year'] : '');
						$rss_square_feet = (isset($item['g']['square_feet']) ? $item['g']['square_feet'] : '');
						$rss_listing_type = (isset($item['g']['listing_type']) ? $item['g']['listing_type'] : '');
						$rss_property_type = (isset($item['g']['property_type']) ? $item['g']['property_type'] : '');
						$rss_price = (isset($item['g']['price']) ? $item['g']['price'] : 0);
						$rss_lot_size = (isset($item['g']['lot_size']) ? $item['g']['lot_size'] : '');
						$rss_email = (isset($item['g']['email']) ? $item['g']['email'] : '');
						$rss_agent = (isset($item['g']['agent']) ? $item['g']['agent'] : '');
						$rss_broker = (isset($item['g']['broker']) ? $item['g']['broker'] : '');
						$rss_broker_phone = (isset($item['g']['broker_phone']) ? $item['g']['broker_phone'] : '');
						$rss_phone = (isset($item['g']['phone']) ? $item['g']['phone'] : '');
						$rss_property_author = (isset($item['g']['author_name']) ? $item['g']['author_name'] : '');
					} else {
						$rss_image = (isset($item['image']['url']) ? $item['image']['url'] : '');
						$rss_property_author = $rss_property_link;
					}
					if (!empty($rss_property_author)) {
						$rss_property_author = 'Source of this listing: '.$rss_property_author;
						$rss_property_author_type = 'E';
					}
					if (strpos(strtolower($rss_property_type), "rent") !== false) {
						$rss_status = "forrent";
					} else if (strpos(strtolower($rss_property_type), "lease") !== false) {
						$rss_status = "forlease";
					}
					if (isset($item['description']) && !empty($item['description'])) {
						$rss_description = $item['description'];
					} else if (isset($item['content']) && !empty($item['content'])) {
						$rss_description = $item['content'];
					} else if (isset($item['atom_content']) && !empty($item['atom_content'])) {
						$rss_description = $item['atom_content'];
					} else {
						$rss_description = (isset($item['g']['summary']) ? $item['summary'] : '');
					}
					if (is_array($rss_description)) {
						$new_desc = '';
						foreach ($rss_description as $desc) {
							$new_desc .= ' '.$desc;
						}
						$rss_description = $new_desc;
					}
					$rss_description = strip_tags($rss_description, '<img><br>');

					// send highest sort_order
					$params = array();
					$params['category'] = $category;
					$sql = "SELECT COUNT([prod_id]) FROM [[products_parents]] WHERE ([parent_id] = {category})";
					$max = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($max)) {
						$GLOBALS['app']->Session->PushLastResponse($max->getMessage(), RESPONSE_ERROR);
						//return new Jaws_Error($max->getMessage(), _t('PROPERTIES_NAME'));
					} else {
						if (!isset($max)) {
							$max = (is_numeric($max) ? $max+1 : 0);
						} else {
							$max = $max+1;
						}
					}	
					
					if (!isset($total)) {
						$sql = 'SELECT COUNT([id]) FROM [[product]]';
						$res = $GLOBALS['db']->queryOne($sql);
						$total = (is_numeric($res) ? $res+1 : 1);
					} else {
						$total++;
					}
					if (isset($rss_property_self) && !empty($rss_property_self)) {
						$prop_checksum = md5($rss_property_self);
					} else if (isset($rss_property_link) && !empty($rss_property_link)) {
						$prop_checksum = md5($rss_property_link);
					} else if ($rss_id != '') {
						$prop_checksum = md5($rss_id);
					} else if ($rss_mls_listing_id != '') {
						$prop_checksum = md5($rss_mls_listing_id);
					} else if ($rss_coordinates != '') {
						$prop_checksum = md5($rss_coordinates);
					} else {
						$prop_checksum = md5($rss_address.', '.$rss_city.', '.$rss_region.', '.$rss_postal_code.', '.$rss_country_id);
					}
					$sql = 'SELECT [id] FROM [[product]] WHERE ([item2] = {checksum})';
					$found = $GLOBALS['db']->queryOne($sql, array('checksum' => $prop_checksum));
					
					//$output_html .= '<br /><pre>Importing: '. 0 .', '.$max.', '.$category.', '.$rss_mls_listing_id.', '.$rss_title.', '.$rss_image.', '.
					//	''.', '.$rss_description.', '.$rss_address.', '.$rss_city.', '.$rss_region.', '.$rss_postal_code.', '.$rss_country_id.', '.
					//	''.', '.''.', '.''.', '.$rss_price.', '. 0 .', '. 0 .', '. 0 .', '.$rss_status.', '.$rss_lot_size.', '.
					//	$rss_square_feet.', '.$rss_bedrooms.', '.$rss_bathrooms.', '.''.', '.$rss_virtual_tour.', '.''.', '.''.', '.''.', '.
					//	''.', '.''.', '.''.', '.''.', '.''.', '.''.', '.$prop_checksum.', '.''.', '.''.', '.''.', '.'N'.', '.'Y'.', '.'N'.', '.$OwnerID.', '.
					//	'Y'.', '. $total .', ' . 0 . ', '.', '.$rss_property_author_link.', '.$rss_property_author.', '.$rss_property_author_type.', '.$rss_property_alt_link.', '.
					//	$rss_property_alt_title.', '.$rss_property_alt_type.', '.''.', '.''.', '.''.', '.''.', '.$rss_year.', '.$rss_url.', '.
					//	$rss_agent.', '.$rss_email.', '.$rss_phone.', '.$rss_property_link.', '.''.', '.$rss_broker.', '.
					//	''.', '.$rss_broker_phone.', '.$rss_property_link.', '.''.', '. true .'</pre>';
						
						//$output_html .= "<div id=\"property".$total."\"></div><script>new Ajax.Updater('property".$total."', 'admin.php?gadget=Store&action=UpdateRSSProduct&num=".$total."', { method: 'post' });</script>";
						
					if (is_numeric($found)) {
						
						//$output_html .= "<div id=\"property".$i."\"></div><script>new Ajax.Updater('property".$i."', 'admin.php?gadget=Store&action=UpdateRSSProduct&id=".$found."&LinkID=0&sort_order=".$max."&category=".$category."
						//&mls=".$rss_mls_listing_id."&title=".$rss_title."&image=".$rss_image."&sm_description=&description=".$rss_description."&address=".$rss_address."&city=".$rss_city."&region=".$rss_region."
						//&postal_code=".$rss_postal_code."&country_id=".$rss_country_id."&community=&phase=&lotno=&price=".$rss_price."&rentdy=0&rentwk=0&rentmo=0&status=".$rss_status."&acreage=".$rss_lot_size."
						//&sqft=".$rss_square_feet."bedroom, $bathroom, $amenity, $i360, $maxchildno, $maxadultno, $petstay, 
						//$occupancy, $maxcleanno, $roomcount, $minstay, $options, $item1, $item2, $item3, 
						//$item4, $item5, $premium, $ShowMap, $featured, 
						//$Active, $propertyno, $internal_propertyno, $alink, $alinkTitle, $alinkType, $alink2, $alink2Title, 
						//$alink2Type, $alink3, $alink3Title, $alink3Type, $calendar_link, $year, $rss_url, 
						//$agent, $agent_email, $agent_phone=".$rss_phone."&agent_website=".$rss_property_link."&agent_photo=&broker=".$rss_broker."&broker_email=&broker_phone=".$rss_broker_phone."&broker_website=".$rss_property_link."&broker_logo=&auto=true', { method: 'post' });</script>";

						// Add the property
						
						$result = $gadget->UpdateProduct($found, 0, $max, $category, $rss_mls_listing_id, $rss_title, $rss_image, 
							'', $rss_description, $rss_address, $rss_city, $rss_region, $rss_postal_code, $rss_country_id, 
							'', '', '', $rss_price, 0, 0, 0, $rss_status, $rss_lot_size, 
							$rss_square_feet, $rss_bedrooms, $rss_bathrooms, '', $rss_virtual_tour, '', '', '', 
							'', '', '', '', '', '', $prop_checksum, '', '', '', 'N', 'Y', 'N', 
							'Y', $total, 0, $rss_property_author_link, $rss_property_author, $rss_property_author_type, $rss_property_alt_link, 
							$rss_property_alt_title, $rss_property_alt_type, '', '', '', '', $rss_year, $rss_url, 
							$rss_agent, $rss_email, $rss_phone, $rss_property_link, '', $rss_broker, 
							'', $rss_broker_phone, $rss_property_link, '', $rss_coordinates, true);
					} else {
						// Add the property
						
						$result = $gadget->AddProduct(0, $max, $category, $rss_mls_listing_id, $rss_title, $rss_image, 
							'', $rss_description, $rss_address, $rss_city, $rss_region, $rss_postal_code, $rss_country_id, 
							'', '', '', $rss_price, 0, 0, 0, $rss_status, $rss_lot_size, 
							$rss_square_feet, $rss_bedrooms, $rss_bathrooms, '', $rss_virtual_tour, '', '', '', 
							'', '', '', '', '', '', $prop_checksum, '', '', '', 'N', 'Y', 'N', $OwnerID, 
							'Y', $total, 0, $rss_property_author_link, $rss_property_author, $rss_property_author_type, $rss_property_alt_link, 
							$rss_property_alt_title, $rss_property_alt_type, '', '', '', '', $rss_year, $rss_url, 
							$rss_agent, $rss_email, $rss_phone, $rss_property_link, '', $rss_broker, 
							'', $rss_broker_phone, $rss_property_link, '', $rss_coordinates, true);
					}
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
						//return new Jaws_Error($result->getMessage(), _t('PROPERTIES_NAME'));
						//$output_html .= "<br />ERROR: ".$result->getMessage();
					}	
					
					$sql = 'SELECT [id] FROM [[product]] WHERE ([item2] = {checksum})';
					$found = $GLOBALS['db']->queryOne($sql, array('checksum' => $prop_checksum));
					if (Jaws_Error::IsError($found) || !is_numeric($found)) {
						$GLOBALS['app']->Session->PushLastResponse('Product Not Added', RESPONSE_ERROR);
					}
					
					//ob_end_flush();
					//break;
				} else {
					$GLOBALS['app']->Session->PushLastResponse('Not geocoded', RESPONSE_ERROR);
				}
			} else {
				$GLOBALS['app']->Session->PushLastResponse(urlencode($rss_location).' could not be geocoded', RESPONSE_ERROR);
			}
		} else {
			$GLOBALS['app']->Session->PushLastResponse('Location could not be parsed', RESPONSE_ERROR);
		}
		return $GLOBALS['app']->Session->PopLastResponse();
		*/
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
		/*
		$res = array();
		if (trim($value) != '') {
			if ((int)$pid > 0) {
				$pid = (int)$pid;
			} else {
				$pid = null;
			}
			if (trim($table) == '') {
				$table = null;
			}
			
			if (!is_null($table)) {
				$gadget = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
				$haystacks = $gadget->SearchRegions(substr($value, 0, 1), $pid, $table);
			} else {
				if (!is_numeric(trim($value))) {
					$search = substr($value, 0, 1);
				}
				$gadget = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
				$haystacks = $gadget->SearchKeyWithProducts($search, '', '', '', '', '', '', null, null, false, 'city', 'ASC', 'city');
			}

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
				$res['suggestions'] = $haystacks;
				$res['value'] = $closest;
			} else {
				$res['value'] = false;
			}
		} else {
			$res['value'] = false;
		}
		return $res;
		*/
	}	
	
    /**
     * Returns product row
     *
     * @access  public
     * @param   integer  $id  ID of the product
     * @return  array   product DB result
     */
    function GetProduct($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$property = $gadget->GetProduct($id);
		if (Jaws_Error::IsError($property)) {
			return false;
		} else {
			if ($property['ownerid'] === $GLOBALS['app']->Session->GetAttribute('user_id')) {
				return $property;
			} else {
				return false;
			}
		}
	}

    /**
     * Adds a form quickly
     *
     * @access public
     * @param string	$method	The method to call
     * @param array	$params	The params to pass to method
     * @param string	$callback	The method to call afterwards
     * @return  array	Response (notice or error)
     */
    function SaveQuickAdd($addtype = 'CustomPage', $method, $params, $callback = '') 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
			$this->CheckSession('Store', 'ManageProducts');
		}
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Store';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(true, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddProductParent' || $method == 'EditProductParent') {
				$post = $model->GetProductParent($id);
			} else if ($method == 'AddProduct' || $method == 'EditProduct') {
				$post = $model->GetProduct($id);
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			} else if ($method == 'AddProductAttribute' || $method == 'EditProductAttribute') {
				$post = $model->GetAttribute($id);
			} else if ($method == 'AddAttributeType' || $method == 'EditAttributeType') {
				$post = $model->GetAttributeType($id);
			} else if ($method == 'AddSale' || $method == 'EditSale') {
				$post = $model->GetSale($id);
			} else if ($method == 'AddBrand' || $method == 'EditBrand') {
				$post = $model->GetBrand($id);
			}
		}
		if ($post && !Jaws_Error::IsError($post)) {
			if ($method == 'AddProductParent' || $method == 'EditProductParent') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new product category" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_form&id='.$id;
				}
				$image = $post['productparentimage'];
				$title = $post['productparentcategory_name'];
				$description = $post['productparentdescription'];
			} else if ($method == 'AddProduct' || $method == 'EditProduct') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new product" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $post['fast_url']));
					$post['html'] = '';
					$hook = $GLOBALS['app']->loadHook('Store', 'Comment');
					if ($hook !== false) {
						if (method_exists($hook, 'GetStoreComment')) {
							$comment = $hook->GetStoreComment(array('gadget_reference' => $post['id'], 'public' => false));
							if (!Jaws_Error::IsError($comment) && isset($comment['msg_txt']) && !empty($comment['msg_txt'])) {
								$post['html'] = $comment['msg_txt'];
							}
						}
					}
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_A_form&id='.$id;
				}
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added info to a product" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = '';
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_A_form2&id='.$id;
				}
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddProductAttribute' || $method == 'EditProductAttribute') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new product attribute" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Attribute', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_B_form&id='.$id;
				}
				$image = '';
				$title = $post['feature'];
				$description = $post['description'];
			} else if ($method == 'AddAttributeType' || $method == 'EditAttributeType') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new attribute type" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = '';
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_B_form2&id='.$id;
				}
				$image = '';
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddSale' || $method == 'EditSale') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new sale" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Sale', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_C_form&id='.$id;
				}
				$image = '';
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddBrand' || $method == 'EditBrand') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new brand" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Brand', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Store&action=account_D_form&id='.$id;
				}
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			}
			$el = array();
			$el = $post;
			// TODO: Return different array if callback is requested ("notify" mode)
			if (!empty($callback)) {
			} else {
				$image_src = '';
				$el['tname'] = $title;
				$el['taction'] = $action_str;
				$el['tactiondesc'] = substr(strip_tags($description), 0, 100).(strlen(strip_tags($description)) > 100 ? '...' : '');
				if (!empty($image)) {
					if (isset($image) && !empty($image)) {
						$image = $xss->filter(strip_tags($image));
						if (substr(strtolower($image), 0, 4) == "http") {
							if (substr(strtolower($image), 0, 7) == "http://") {
								$image_src = explode('http://', $image);
								foreach ($image_src as $img_src) {
									if (!empty($img_src)) {
										$image_src = 'http://'.$img_src;
										break;
									}
								}
							} else {
								$image_src = explode('https://', $image);
								foreach ($image_src as $img_src) {
									if (!empty($img_src)) {
										$image_src = 'https://'.$img_src;
										break;
									}
								}
							}
						} else {
							$thumb = Jaws_Image::GetThumbPath($image);
							$medium = Jaws_Image::GetMediumPath($image);
							if (file_exists(JAWS_DATA . 'files'.$thumb)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
							} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$image)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$image;
							}
						}
					}
				}
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Store/images/logo.png';
				//$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=EditElementAction&id='.$id.'&method='.str_replace('Add', 'Edit', $method);
				$url_ea = $shout_params['edit_url'];
				$el['eaurl'] = $url_ea;
				$el['image_thumb'] = $image_src;
				$el['eaid'] = 'ea'.$id;
				//$el['section_id'] = $post['section_id'];
			}
			$res = $el;
			$res['success'] = true;
			$res['method'] = $method;
			$res['addtype'] = $addtype;
			if (isset($params['sharing']) && !empty($params['sharing'])) {
				$res['sharing'] = $params['sharing'];
			}
		} else {
			//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
			$GLOBALS['app']->Session->PushLastResponse($post->GetMessage(), RESPONSE_ERROR);
			$res['success'] = false;
		}
		if (!empty($callback)) {
			// Let everyone know content has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout($callback, $shout_params);
			if (!Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
				$res['success'] = false;
			}
		}
		
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
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
    function NewStoreComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone', $reply = false)
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			//$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$this->CheckSession('Store', 'ManageProducts');
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
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing, 'Store'
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
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))){
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
    function DeleteStoreComment($id)
    {
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			//$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$this->CheckSession('Store', 'ManageProducts');
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
