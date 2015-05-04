<?php
/**
 * CustomPage Gadget
 *
 * @category   GadgetModel
 * @package    CustomPage
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageModel extends Jaws_Model
{
    var $_Name = 'CustomPage';
	
    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetPage($id = null, $gadget = null, $gadget_action = null, $linkid = null)
    {
        $sql = '
            SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
            FROM [[pages]]
			WHERE [id] > 0';

        $params             = array();
        if (!is_null($id)) {
			$params['id']       = $id;
			if (is_numeric($id)) {
				$sql .= ' AND [id] = {id}';
			} else {
				$sql .= ' AND [fast_url] = {id}';
			}
        }
		if (!is_null($gadget)) {
			$params['gadget']       	= $gadget;
            $sql .= ' AND [gadget] = {gadget}';
        }
        if (!is_null($gadget_action)) {
			$params['gadget_action']	= $gadget_action;
            $sql .= ' AND [gadget_action] = {gadget_action}';
        }
        if (!is_null($linkid)) {
			$params['linkid']	= $linkid;
            $sql .= ' AND [linkid] = {linkid}';
        }
        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
		if (!Jaws_Error::IsError($row) && isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
    }

    /**
     * Gets the default page.
     *
     * @access  public
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetDefaultPage()
    {
        $defaultPage = $GLOBALS['app']->Registry->Get('/gadgets/CustomPage/default_page');

        $res = $this->GetPage($defaultPage);
        if (Jaws_Error::IsError($res) || !isset($res['id']) || $res['active'] == 'N') {
            $params              = array();
            $params['Active'] = 'Y';
            $sql = 'SELECT MAX([id]) FROM [[pages]] WHERE [active] = {Active}';

            $max = $GLOBALS['db']->queryOne($sql, $params, array('integer'));
            if (Jaws_Error::IsError($max)) {
                return array();
            }

            $res = $this->GetPage($max);
            if (Jaws_Error::IsError($res)) {
                return array();
            }
        }
        return $res;
    }
    

    /**
     * Gets a home page of group ID.
     *
     * @access  public
     * @param   int     $id     The ID of the group to get the page for.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetGroupHomePage($group)
    {
        $sql = '
            SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
            FROM [[pages]]
			WHERE ([gadget] = {gadget} AND [gadget_action] = {gadget_action}) AND ([linkid] = {group}) AND ([content] = {title})';
			
        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $params             		= array();
        $params['gadget']   		= 'Users';
        $params['gadget_action']   	= 'GroupPage';
        $params['group'] 			= $group;
        $params['title'] 			= 'Main';

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
    }

    /**
     * Gets an index of all the pages.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetPages($limit = null, $sortColumn = 'sm_description', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('gadget', 'ownerid', 'sm_description', 'title', 'fast_url', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('CUSTOMPAGE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sm_description';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
            FROM [[pages]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = $OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
			$result = $GLOBALS['db']->setLimit(10, $offSet);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
			}
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
			}
		}

        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

		return $result;
    }
    
    /**
     * Returns all posts that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllSubPagesOfPage($id)
    {
	    $sql  = '
			SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
			FROM [[pages]] WHERE [pid] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }

	/**
     * Returns all pages by type (%gadget%)
     *
     * @access  public
     * @param   string  $gadget    The gadget name
     * @return  array  Array with all the pages or Jaws_Error on error
     */
    function GetAllPagesOfGadget($gadget)
    {
	    $sql  = '
			SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
			FROM [[pages]] 
			WHERE [gadget] = {gadget}';
        
        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

	    
		$result = $GLOBALS['db']->queryAll($sql, array('gadget' => $gadget), $types);
		
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }

    /**
     * Gets the users pages by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the pages and false on error
     */
    function GetCustomPageOfUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
			SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
			FROM [[pages]]
            WHERE ([ownerid] = {id})';
		
        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }

	/**
     * Returns all pages of given Group
     *
     * @access  public
     * @param   string  $group    The group ID
     * @return  array  Array with all the pages or Jaws_Error on error
     */
    function GetCustomPageOfGroup($group)
    {
		$params       = array();
        $params['group'] = $group;
        $params['gadget'] = 'Users';
        $params['gadget_action'] = 'GroupPage';
	    $sql  = '
			SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
			FROM [[pages]] 
			WHERE ([gadget] = {gadget} AND [gadget_action] = {gadget_action}) AND ([linkid] = {group})';
        
        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

	    
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }
        
	/**
     * Gets a single post by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetPost($id, $updated = false)
    {
        if (substr($id, 0, 3) == 'new') {
			$now = $GLOBALS['db']->Date();
			//$description = '<h2>'._t('CUSTOMPAGE_NEWPOST_SAMPLE_TITLE').'</h2><p>'._t('CUSTOMPAGE_NEWPOST_SAMPLE_DESCRIPTION').'</p>';
			return array(
				'id' => $id,
				'sort_order' => 0, 
				'linkid' => 0, 
				'title' => '', 
				'description' => '<p>&nbsp;</p>', 
				'image' => '', 
				'image_width' => 0, 
				'image_height' => 0, 
				'layout' => 0, 
				'active' => 'Y', 
				'ownerid' => 0, 
				'created' => $now, 
				'updated' => $now, 
				'gadget' => 'text',
				'url' => '', 
				'url_target' => '_self', 
				'rss_url' => '', 
				'section_id' => 0, 
				'image_code' => '', 
				'checksum' => ''
			);
		}
		
		$sql = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated], [gadget],
				[url], [url_target], [rss_url], [section_id], [image_code], [checksum]
			FROM [[pages_posts]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 
			'text', 'integer', 'timestamp', 'timestamp', 'text',
			'text', 'text', 'text', 'integer', 'text', 'text'	
		);

        $params             = array();
        $params['id']       = (int)$id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		$date = $GLOBALS['app']->loadDate();
		if (isset($row['id'])) {
			$additem = true;
			if ($updated === true && (time() < $GLOBALS['app']->UTC2UserTime($row['updated']))) {
				$additem = false;
			}
			// Update deleted gadget posts
			
			if (strtolower($row['gadget']) != 'text') {
				$adminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
				$layoutGadget = $GLOBALS['app']->LoadGadget($row['gadget'], 'LayoutHTML');
				$layoutActions = $this->GetGadgetActions($row['gadget']);
				if (!Jaws_Error::IsError($layoutGadget)) {
					$additem = false;
					foreach ($layoutActions as $lactions) {
						if (isset($lactions['action']) && isset($lactions['name'])) {
							if ($lactions['action'] == $row['image']) {
								$additem = true;
								break;
							}
						}
					}
					if ($additem === false) {
						$error_url = $GLOBALS['app']->getFullURL();
						$error = new Jaws_Error('Auto deleted inactive Gadget post on: '.$error_url."\n".var_export($row['image'], true)."\n valid actions: ".var_export($layoutActions, true), _t('CUSTOMPAGE_NAME'));
						if (!$adminModel->DeletePost($row['id'])) {
							$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
						}
					}
				}
			}
			if ($additem === true) {
				return $row;
			}
        }

        return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
    }

    /**
     * Returns all posts that belongs to a page
     *
     * @param   int     $id     The ID of the page to get posts for.
     * @param   int     $section     The ID of the section to get posts for.	 
     * @param   boolean     $blog     Blog mode.
     * @param   boolean     $updated     Get only posts with valid display date 
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllPostsOfPage($id, $section = null, $blog = false, $updated = false, $active = null, $limit = null, $offSet = false)
    {
	    $sql  = 'SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated], [gadget],
				[url], [url_target], [rss_url], [section_id], [image_code], [checksum]
			FROM [[pages_posts]] WHERE [linkid] = {id}';
			
		$params = array();
		$params['id'] = $id;
		if (!is_null($active)) {
			$params['active'] = $active;
			$sql .= " AND [active] = {active}";
		}
		if (!is_null($section)) {
			$params['section'] = $section;
			$sql .= " AND [section_id] = {section}";
		}
		if ($blog === true) {
			$sql .=	' ORDER BY [created] DESC';
		} else {
			$sql .=	' ORDER BY [sort_order] ASC';
		}
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 
			'text', 'integer', 'timestamp', 'timestamp', 'text',
			'text', 'text', 'text', 'integer', 'text', 'text'
		);
		
        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
                }
            }
        }
		
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }
		
		$results = array();
		foreach ($result as $res) {
			$additem = true;
			if ($updated === true && (time() < $GLOBALS['app']->UTC2UserTime($res['updated']))) {
				$additem = false;
			}
			// Update deleted gadget posts
			if (strtolower($res['gadget']) != 'text') {
				$adminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
				$layoutGadget = $GLOBALS['app']->LoadGadget($res['gadget'], 'LayoutHTML');
				$layoutActions = $this->GetGadgetActions($res['gadget']);
				if (!Jaws_Error::isError($layoutGadget)) {
					$additem = false;
					foreach ($layoutActions as $lactions) {
						if (isset($lactions['action']) && isset($lactions['name'])) {
							if ($lactions['action'] == $res['image']) {
								$additem = true;
								break;
							}
						}
					}
					if ($additem === false) {
						$error_url = $GLOBALS['app']->getFullURL();
						$error = new Jaws_Error('Auto deleted inactive Gadget post on: '.$error_url."\n".var_export($res['image'], true)."\n valid actions: ".var_export($layoutActions, true), _t('CUSTOMPAGE_NAME'));
						if (!$adminModel->DeletePost($res['id'])) {
							$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
						}
					}
				}
			}
			if ($additem === true) {
				$results[] = $res;
			}
		}
		return $results;
	}

    /**
     * Returns pages sections
     *
     * @access  public
     * @params  int 	$id 	Element ID
     * @params  int 	$page_id 	Page ID
     * @params  int 	$layout_id 	Layout item ID
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetPageElement($id = null, $page_id = null, $layout_id = null)
    {
	    $sql  = 'SELECT [id], [sort_order], [page_id], [layout_id], [stack], 
				[created], [updated], [checksum]
			FROM [[pages_sections]] 
			WHERE 
		';
		
		$params = array();
		if (!is_null($id)) {
			$params['id'] = $id;
			$sql  .= ' [id] = {id}
			';
		} else if (!is_null($page_id) && !is_null($layout_id)) {
			$params['page_id'] = $page_id;
			$params['layout_id'] = $layout_id;
			$sql  .= ' [page_id] = {page_id} AND [layout_id] = {layout_id}
			';
		}
		$sql  .= ' ORDER BY [sort_order] ASC';
       
		$types = array(
			'integer', 'integer', 'integer', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);
		
		$result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }

    /**
     * Returns all sections that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllSectionsOfPage($id, $blog = false)
    {
        $params = array();
		$params['id'] = $id;
		if ($blog === true) {
			$GLOBALS['db']->dbc->loadModule('Function', null, true);
			$post_id = $GLOBALS['db']->dbc->function->replace($GLOBALS['db']->dbc->function->replace('[[layout]].[gadget_action]', '"ShowPost("', '""'), '")"', '""');
			$sql = "
				SELECT 
					[[pages_posts]].[id], [[pages_posts]].[sort_order], [[pages_posts]].[linkid] as page_id, 
					[[layout]].[id] as layout_id, [[pages_posts]].[created], [[pages_posts]].[updated], 
					[[pages_posts]].[checksum]
				FROM [[pages_posts]] 
                INNER JOIN [[layout]] ON $post_id = [[pages_posts]].[id]
				WHERE [[pages_posts]].[linkid] = {id}
				ORDER BY [[pages_posts]].[created] DESC
			";
			$types = array(
				'integer', 'integer', 'integer', 'integer',
				'timestamp', 'timestamp', 'text'
			);
        } else {
			$sql = '
				SELECT 
					[id], [sort_order], [page_id], [layout_id], [stack], 
					[created], [updated], [checksum]
				FROM [[pages_sections]] 
				WHERE [page_id] = {id}
				ORDER BY [sort_order] ASC
			';
			$types = array(
				'integer', 'integer', 'integer', 'integer', 'text', 
				'timestamp', 'timestamp', 'text'
			);
		}
				
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

		if ($blog === true) {
			$i = 0;
			foreach ($result as $res) {
				$result[$i]['sort_order'] = $i;
				$i++;
			}
		}
		
        return $result;
    }

    /**
     * Find a record related on fast url
     *
     * @access  public
     * @param   string  $fasturl    The FastURL string
     * @return  array   An array containing the page info and Jaws_Error on error
     */
    function GetFastURL($fasturl)
    {
        $params = array();
        $params['fasturl'] = $fasturl;

        $sql = '
            SELECT
                [id], [title], [fast_url]
            FROM [[pages]]
            WHERE [fast_url] = {fasturl}';

        $types = array('integer', 'text', 'text');

        $res = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($res)) {
            return false;
        }

        return $res;
    }
		
    /**
     * Gets a single post by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetSplashPanel($id)
    {
        $sql = '
            SELECT [id], [sort_order], [linkid], [image], 
				[splash_width], [splash_height], [code], 
				[ownerid], [created], [updated], [checksum]
			FROM [[splash_panels]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'integer', 'integer', 
			'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);

        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
    }

    /**
     * Returns all splash panels that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the panel IDs or Jaws_Error on error
     */
    function GetSplashPanelsOfPage($id)
    {
	    $sql  = 'SELECT [id], [sort_order], [linkid], [image], 
				[splash_width], [splash_height], [code], 
				[ownerid], [created], [updated], [checksum]
			FROM [[splash_panels]] WHERE [linkid] = {id}
			ORDER BY [sort_order] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'integer', 'integer', 
			'text', 'integer', 'timestamp', 'timestamp', 'text'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }

    /**
     * Returns all rss items that should be hidden
     *
     * @access  public
     * @return  array  Array with all the rss info or Jaws_Error on error
     */
    function GetHiddenRssOfPage($id)
    {
	    $sql  = 'SELECT [id], [linkid], [title], [published], [url], [element]
			FROM [[rss_hide]] WHERE [linkid] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 'integer'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        return $result;
    }

    /**
     * Get actions of a given gadget
     * 
     * @access  public
     * @param   string  $gadget 
     * @return  array   Array with the actions of the given gadget
     */
    function GetGadgetActions($g, $limit = null, $offset = null)
    { 
        $res = array();
		if (file_exists(JAWS_PATH . 'gadgets/'. $g. '/'. 'LayoutHTML.php')) {
            $reForceRead = false;

			$canAdd = false;
            $adminGadget = $GLOBALS['app']->loadGadget($g, 'AdminHTML');
            if (method_exists($adminGadget, 'GetQuickAddForm')) {
				$canAdd = true;
			}
						
            $layoutGadget = $GLOBALS['app']->loadGadget($g, 'LayoutHTML');
            if (!Jaws_Error::IsError($layoutGadget)) {
                if (method_exists($layoutGadget, 'LoadLayoutActions')) {
                    $info = $GLOBALS['app']->LoadGadget($g, 'Info');
                    $actions = $layoutGadget->LoadLayoutActions($limit, $offset);
                } else {
                    $reForceRead = true;
                }
            } else {
                $reForceRead = true;
            }

            $ractions = $GLOBALS['app']->GetGadgetActions($g);
            if (isset($ractions['LayoutAction'])) {
                if (!$reForceRead) {
                    $actions = $actions + $ractions['LayoutAction'];
                } else {
                    $actions = $ractions['LayoutAction'];
                }
            }
            if (count($actions) > 0) {
                foreach ($actions as $actionName => $actionProperties) {
                    if ($actionProperties['mode'] == 'LayoutAction') {
						$res[] = array('action' => $actionName, 
                                       'name'   => $actionProperties['name'],
                                       'desc'   => $actionProperties['desc'],
									   'add'	=> $canAdd);
                    }
                }
            } else {
				$res[] = array('add' => $canAdd);
			}
        }
        return $res;
    }

	/**
     * Return wikipedia content
     *
     * @access  private
     * @return  string   output
     */
    function fetch2($name)
	{
		// load document
		$link = "http://en.wikipedia.org" . $name;
		$cleaned_sentence = '';			
		
		// snoopy
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		$snoopy = new Snoopy('CustomPage');
		$snoopy->agent = "Jaws";
		//$cleaned_sentence = $link;
		if($snoopy->submit($link)) {
			//while(list($key,$val) = each($snoopy->headers))
			//	$cleaned_sentence .= $key.": ".$val."<br>\n";
			
			//$cleaned_sentence .= "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
			//$page = $snoopy->results;
			if (strpos($snoopy->results, "<!-- content -->") !== false) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<!-- bodytext -->";
				$delimeterRight = "<!-- /bodytext -->";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$output = substr($inputStr, $posLeft, $posRight-$posLeft);
				//$cleaned_sentence = preg_replace("/  /", " ", $cleaned_sentence);
				//$cleaned_sentence = preg_replace("/ ,/", ",", $cleaned_sentence);
				//$cleaned_sentence = preg_replace("/ \./", ".", $cleaned_sentence);
				//preg_match("/[^.]*./", $cleaned_sentence, $first_sentence);
				//return $first_sentence[0];
				$cleaned_sentence = $output;			
				if (strpos($cleaned_sentence, "<div class=\"dablink") !== false) {
					/*
					$inputStr3 = $output;
					$delimeterLeft3 = "<div class=\"dablink";
					$delimeterRight3 = "</div>";
					$posLeft3=strpos($inputStr3, $delimeterLeft3);
					//$posLeft3+=strlen($delimeterLeft3);
					$posRight3=strpos($inputStr3, $delimeterRight3, $posLeft3);
					$dabbox = substr($inputStr3, $posLeft3, $posRight3+strlen($delimeterRight3));
					$cleaned_sentence = str_replace($dabbox, '', $cleaned_sentence);
					*/
					$cleaned_sentence = preg_replace('|\<div\sclass=\"dablink.*?\</div\>|siu','',$cleaned_sentence);
				}
				$cleaned_sentence = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tbody><tr><td class=\"wikiContent\" width=\"100%\" valign=\"top\" style=\"padding: 10px;\">".preg_replace('|\<table\sclass=\"infobox.*?\</table\>|siu','',$cleaned_sentence)."</td>";
				//if (strpos($output, '<table class="infobox')) {
					//preg_match('|\<table\sclass=\"infobox.*?\</table\>|siu', $output, $matches);
				    $h1tags = preg_match_all("/(<table.*>)(\w.*)(<\/table>)/isxmU",$output,$patterns);
					$res = array();
				    array_push($res,$patterns[0]);
				    array_push($res,count($patterns[0]));
					if($res[1] != 0){
						foreach($res[0] as $key => $val){
							if (strpos($val, 'infobox') !== false) {
								$cleaned_sentence .= '<td width="0%" valign="top">'.$val.'</td>';
							}
						}
					}
				//}

				$cleaned_sentence .= "</tr></tbody></table>";
				$cleaned_sentence = "<style>.wikiContent h2 {margin-top: 1em; margin-bottom: 0.6em; border-bottom:1px solid #999999;} #toc {display: none;} .navbox {display: none;} .infobox {font-size:90%; text-align:left; width:22em; background-color:#F9F9F9; border:1px solid #AAAAAA;} .infobox tbody tr td {line-height:1.2;}</style>".$cleaned_sentence."<div>&nbsp;</div><div style=\"font-size: 80%;\">As a derivative work of GFDL Wikipedia articles, the text returned by this program is itself licensed under the <a href=\"http://www.gnu.org/licenses/fdl.html\" target=\"_blank\">GNU Free Documentation License</a>. The link back to the Wikipedia article from which the text is derived is believed to be sufficient to cover obligations under sections 4B and 4J of the GFDL.</div>";
				$cleaned_sentence = $this->stripbrackets($cleaned_sentence);			
				$cleaned_sentence = str_replace("href=\"", "target=\"_blank\" href=\"", $cleaned_sentence);
				$cleaned_sentence = str_replace("href=\"/wiki", "href=\"http://en.wikipedia.org/wiki", $cleaned_sentence);
				$cleaned_sentence = str_replace("href=\"/w/index.php?", "href=\"http://en.wikipedia.org/w/index.php?", $cleaned_sentence);
				$cleaned_sentence = str_replace(">^</a>", "></a>", $cleaned_sentence);
				$cleaned_sentence = str_replace("white-space: nowrap;", '', $cleaned_sentence);
				$cleaned_sentence = preg_replace('|\>\<sup\>\<i\>\<b\>.\</b\>\</i\>\</sup\>\</a\>|siu','></a>',$cleaned_sentence);
				if (strpos($cleaned_sentence, "<div class=\"printfooter") !== false) {
					$printfooter = "<div class=\"printfooter\">Retrieved from \"<a target=\"_blank\" href=\"".$link."\">".$link."</a>\"</div>";
					/*
					$inputStr3 = $output;
					$delimeterLeft3 = "<div class=\"dablink";
					$delimeterRight3 = "</div>";
					$posLeft3=strpos($inputStr3, $delimeterLeft3);
					//$posLeft3+=strlen($delimeterLeft3);
					$posRight3=strpos($inputStr3, $delimeterRight3, $posLeft3);
					$dabbox = substr($inputStr3, $posLeft3, $posRight3+strlen($delimeterRight3));
					$cleaned_sentence = str_replace($dabbox, '', $cleaned_sentence);
					*/
					$cleaned_sentence = preg_replace('|\<div\sclass=\"printfooter.*?\</div\>|siu',$printfooter,$cleaned_sentence);
				}
				//var_dump($cleaned_sentence);
			}
		} else {
			//$page = _t('CUSTOMPAGE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			//return "";	
		}
		return $cleaned_sentence;
					
		/*
		$ch = curl_init();
		$timeout = 5; // set to zero for no timeout
		curl_setopt ($ch, CURLOPT_URL, $link);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$var = curl_exec($ch);
		curl_close($ch);

		if ($var == false)
		{
			return "";	
		}
		
		$doc = @DOMDocument::loadHTML($var);
		
		// look for bodyContent element
		
		$xpath = new domXPath($doc2);
		$bodyContent = $xpath->query('//div[@id="bodyContent"]/p');
		
		foreach ($bodyContent as $node) {
			//todo .. strip out brackets intelligently
			
			if (preg_match("/:$/", $node->nodeValue)) {
				$firstLink = $xpath->query('//div[@id="bodyContent"]//li/a');
	
				foreach ($firstLink as $node2) {
					$link2 = $node2->getAttribute("href");
					return $this->fetch2($link2);
				}
					
				return "";	
			} else {
				$cleaned_sentence = $this->stripbrackets($node->nodeValue);			
				$cleaned_sentence = preg_replace("/  /", " ", $cleaned_sentence);
				$cleaned_sentence = preg_replace("/ ,/", ",", $cleaned_sentence);
				$cleaned_sentence = preg_replace("/ \./", ".", $cleaned_sentence);
				preg_match("/[^.]*./", $cleaned_sentence, $first_sentence);
				
				return $first_sentence[0];
			}	
		}
		*/
	}
	
	/**
     * Return wikipedia content
     *
     * @access  private
     * @return  string   output
     */
    function fetchWikipedia($word)
	{
		// convert string to Wikipedia URL structure
		$newWord = "";
		
		$first = true;
		
		foreach (explode(" ", $word) as $foundWord) {
			if ($newWord != "" ) {
				$newWord = $newWord . "_";
			}
			
			if ($first) {
				$newWord = $newWord . strtoupper($foundWord[0]) . strtolower(substr($foundWord, 1));
			} else {
				$newWord = $newWord . $foundWord;
			}
			
			$first = false;
		}
	
		
		$reply = $this->fetch2("/wiki/" . $newWord);
		
		if ($reply == '') {
			return "Content not found for keyword: <b>".$word."</b>";
		} else {
			return $reply;
		}
	}
	
		
    /**
     * Return bracket-stripped output
     *
     * @access  private
     * @return  string   output
     */
    function stripbrackets($input)
    {
		$nesting = 0;
		$output = "";
		$len = strlen($input);
		for ($idx = 0; $idx != $len; $idx++) {
			$chr = $input[$idx];
			if ($chr == "(" || $chr == "[") {
				$nesting++;
			}
			
			if ($nesting == 0) {
				$output .= $chr;
			}
			
			if ($chr == ")" || $chr == "]") {
				$nesting--;
			}
		}
		
		return $output;
	}
	
}
