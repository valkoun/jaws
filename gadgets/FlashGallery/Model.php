<?php
/**
 * FlashGallery Gadget
 *
 * @category   GadgetModel
 * @package    FlashGallery
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryModel extends Jaws_Model
{
    var $_Name = 'FlashGallery';

    /**
     * Gets a single gallery by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the gallery to get.
     * @return  array   An array containing the gallery information, or false if no gallery could be loaded.
     */
    function GetFlashGallery($id)
    {
        $sql = '
            SELECT [id], [type], [url], [title], [aspect_ratio], [width],
				[custom_width], [timer], [fadetime], [columns], 
				[order], [show_text], [text_pos], [lock_label], [textbar], 
				[textbar_height], [textbar_alpha], [show_buttons], [button_pos], 
				[overlay_image], [allow_fullscreen], [text_move], [image_move], 
				[image_offsetx], [image_offsety], [load_immediately], [background_color],
				[looping], [textbar_color], [background_image], [ownerid], [active], 
				[created], [updated], [height], [custom_height], [watermark_image], [checksum]
            FROM [[flashgalleries]]
			WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'integer', 'integer',
			'text', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'integer', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
    }

    /**
     * Gets an index of all the galleries.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the GALLERIES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either GALLERIES_ASC or GALLERIES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetFlashGalleries($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'title', 'type', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('FLASHGALLERY_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'title';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [type], [url], [title], [aspect_ratio], [width],
				[custom_width], [timer], [fadetime], [columns], 
				[order], [show_text], [text_pos], [lock_label], [textbar], 
				[textbar_height], [textbar_alpha], [show_buttons], [button_pos], 
				[overlay_image], [allow_fullscreen], [text_move], [image_move], 
				[image_offsetx], [image_offsety], [load_immediately], [background_color], 
				[looping], [textbar_color], [background_image], [ownerid], [active], 
				[created], [updated], [height], [custom_height], [watermark_image], [checksum]
            FROM [[flashgalleries]]
			";
		$params  = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
			$sql .= " WHERE [ownerid] = {owner_id}";
		} else {
			$sql .= " WHERE [ownerid] = 0";
		}
		
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERIES_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERIES_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'integer', 'integer',
			'text', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERIES_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
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
    function GetPost($id)
    {
        $sql = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [url], [url_target], 
				[active], [ownerid], [created], [updated], [checksum] 
			FROM [[flashgalleries_posts]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
        }

        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
    }

    /**
     * Returns all posts that belongs to a gallery
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetPostsOfFlashGallery($id)
    {
	    $sql  = 'SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [url], [url_target], [active], 
				[ownerid], [created], [updated], [checksum] 
			FROM [[flashgalleries_posts]] WHERE [linkid] = {id}
			ORDER BY [sort_order] ASC, [title] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'integer', 
			'timestamp', 'timestamp', 'text'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
        }

        return $result;
    }
		
    /**
     * Gets a single gallery by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @param   int     $gid  The gallery ID
     * @return  mixed   Returns an array with the gallery info and false on error
     */
    function GetSingleFlashGalleryByUserID($id, $gid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['gid'] = $gid;
		
		$sql = '
            SELECT [id], [type], [url], [title], [aspect_ratio], [width],
				[custom_width], [timer], [fadetime], [columns], 
				[order], [show_text], [text_pos], [lock_label], [textbar], 
				[textbar_height], [textbar_alpha], [show_buttons], [button_pos], 
				[overlay_image], [allow_fullscreen], [text_move], [image_move], 
				[image_offsetx], [image_offsety], [load_immediately], [background_color], 
				[looping], [textbar_color], [background_image], [ownerid], [active], 
				[created], [updated], [height], [custom_height], [watermark_image], [checksum]
			FROM [[flashgalleries]]
            WHERE ([id] = {gid} AND [ownerid] = {id})';
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'integer', 'integer',
			'text', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
        }

        return $result;
    }

    /**
     * Gets the users galleries by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the pages and false on error
     */
    function GetFlashGalleryOfUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [type], [url], [title], [aspect_ratio], [width],
				[custom_width], [timer], [fadetime], [columns], 
				[order], [show_text], [text_pos], [lock_label], [textbar], 
				[textbar_height], [textbar_alpha], [show_buttons], [button_pos], 
				[overlay_image], [allow_fullscreen], [text_move], [image_move], 
				[image_offsetx], [image_offsety], [load_immediately], [background_color], 
				[looping], [textbar_color], [background_image], [ownerid], [active], 
				[created], [updated], [height], [custom_height], [watermark_image], [checksum]
			FROM [[flashgalleries]]
            WHERE ([ownerid] = {id})';
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'integer', 'integer',
			'text', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERIES_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
        }

        return $result;
    }
	
}
