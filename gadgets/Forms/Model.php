<?php
/**
 * Forms Gadget
 *
 * @category   GadgetModel
 * @package    Forms
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsModel extends Jaws_Model
{
    var $_Name = 'Forms';
	
    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetForm($id, $language = null)
    {
		$sql = '
            SELECT [id], [sort_order], [title], [sm_description], [description], 
			   [clause], [image], [recipient], [parent], [custom_action], 
			   [fast_url], [active], [ownerid], [created], [updated], [submit_content], [checksum]
            FROM [[forms]]';

        if (is_numeric($id)) {
            $sql .= ' WHERE [id] = {id}';
        } else {
            $sql .= ' WHERE [fast_url] = {id}';
        }

        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_FOUND'), _t('FORMS_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_FOUND'), _t('FORMS_NAME'));
    }
    
    /**
     * Gets an index of all the forms.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetForms($limit = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'sort_order', 'title', 'fast_url', 'created', 'updated', 'active', 'recipient', 'parent', 'custom_action');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('FORMS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [sort_order], [title], [sm_description], [description], 
			   [clause], [image], [recipient], [parent], [custom_action], 
			   [fast_url], [active], [ownerid], [created], [updated], [submit_content], [checksum]
            FROM [[forms]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
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
            SELECT [id], [sort_order], [formid], [title], 
				[itype], [required], [ownerid], [created], [updated], [checksum]
			FROM [[form_questions]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_FOUND'), _t('FORMS_NAME'));
        }

        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_FOUND'), _t('FORMS_NAME'));
    }

    /**
     * Gets a single answer by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetAnswer($id)
    {		
		$sql = '
            SELECT [id], [sort_order], [linkid], [formid], [title], 
			[ownerid], [created], [updated], [checksum]
			FROM [[form_answers]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('FORMS_ERROR_ANSWER_NOT_FOUND'), _t('FORMS_NAME'));
        }

        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('FORMS_ERROR_ANSWER_NOT_FOUND'), _t('FORMS_NAME'));
    }

    /**
     * Returns all posts that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllPostsOfForm($id)
    {
	    $sql  = '
            SELECT [id], [sort_order], [formid], [title], 
				[itype], [required], [ownerid], [created], [updated], [checksum]				
			FROM [[form_questions]] WHERE [formid] = {id}
			ORDER BY [sort_order] ASC, [title] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_FOUND'), _t('FORMS_NAME'));
        }

        return $result;
    }

    /**
     * Gets the users forms by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the forms and false on error
     */
    function GetFormsOfUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [sort_order], [title], [sm_description], [description], 
			   [clause], [image], [recipient], [parent], [custom_action], 
			   [fast_url], [active], [ownerid], [created], [updated], [submit_content], [checksum]
			FROM [[forms]]
            WHERE ([ownerid] = {id})';
		
        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
        }

        return $result;
    }

    /**
     * Gets a user form by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the forms and false on error
     */
    function GetSingleFormByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [id], [sort_order], [title], [sm_description], [description], 
			   [clause], [image], [recipient], [parent], [custom_action], 
			   [fast_url], [active], [ownerid], [created], [updated], [submit_content], [checksum]
			FROM [[forms]]
            WHERE ([ownerid] = {id} AND [id] = {cid})';
		
        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
        }

        return $result;
    }
    
	/**
     * Gets answers of a given post
     *
     * @access  public
     * @param   int     $id  The region ID
     * @return  mixed   Returns an array with the regions and false on error
     */
    function GetAllAnswersOfPost($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [sort_order], [linkid], [formid], [title], 
			[ownerid], [created], [updated], [checksum]
			FROM [[form_answers]] WHERE [linkid] = {id} ORDER BY [sort_order] ASC';
		
        $types = array(
			'integer', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_ANSWERS_NOT_RETRIEVED'), _t('FORMS_NAME'));
        }

        return $result;
    }

	/**
     * Gets all answers of given Form
     *
     * @access  public
     * @param   int     $id  The region ID
     * @return  mixed   Returns an array with the regions and false on error
     */
    function GetAllAnswersOfForm($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [sort_order], [linkid], [formid], [title], 
			[ownerid], [created], [updated], [checksum]
			FROM [[form_answers]] WHERE [formid] = {id} ORDER BY [sort_order] ASC';
		
        $types = array(
			'integer', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_ANSWERS_NOT_RETRIEVED'), _t('FORMS_NAME'));
        }

        return $result;

    }
}
