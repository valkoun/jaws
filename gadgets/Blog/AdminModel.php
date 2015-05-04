<?php
/**
 * Blog Gadget
 *
 * @category   GadgetModel
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/Blog/Model.php';

class BlogAdminModel extends BlogModel
{
    var $_Name = 'Blog';
	
	/**
     * Install Blog gadget in Jaws
     *
     * @access  public
     * @return  boolean True on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA));
        }

        $new_dir = JAWS_DATA . 'xml' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('BLOG_NAME'));
        }

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$variables = array();
			$variables['timestamp'] = $GLOBALS['db']->Date();

			$result = $this->installSchema('insert.xml', $variables, 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlogCategory');   // trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlogCategory');	// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlogCategory');	// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlog');   			// trigger an action when we add a ad
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlog');			// trigger an action when we delete a ad
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlog');			// and when we update a ad..
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlogPost');          
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlogPost');          
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlogPost');          
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlogTrackback');   			
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlogTrackback');			
        $GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlogTrackback');			

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        //$GLOBALS['app']->Listener->NewListener('Blog', 'onDeleteUser', 'RemoveUserBlog');
        //$GLOBALS['app']->Listener->NewListener('Blog', 'onUpdateUser', 'UpdateUserBlog');
		$GLOBALS['app']->Listener->NewListener('Blog', 'onAfterEnablingGadget', 'InsertDefaultChecksums');

        // Registry keys
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/columns',                   '1');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/default_view',              'last_entries');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/last_entries_limit',        '20');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/popular_limit',             '10');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/xml_limit',                 '10');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/default_category',          '1');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/allow_comments',            'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/comment_status',            'approved');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/last_comments_limit',       '20');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/last_recentcomments_limit', '20');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/generate_xml',              'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/generate_category_xml',     'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/trackback',                 'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/trackback_status',          'approved');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/plugabble',                 'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/use_antispam',              'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/pingback',                  'true');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  True on success and Jaws_Error otherwise
     */
    function UninstallGadget()
    {
        $tables = array('blog',
                        'blog_trackback',
                        'blog_category',
                        'blog_entrycat');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('BLOG_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onAddBlogCategory');   // trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onDeleteBlogCategory');	// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onUpdateBlogCategory');	// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onAddBlog');   			// trigger an action when we add
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onDeleteBlog');			// trigger an action when we delete
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onUpdateBlog');			// and when we update
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onAddBlogPost');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onUpdateBlogPost');		 
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onDeleteBlogPost');			
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onAddBlogTrackback');   			
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onDeleteBlogTrackback');			
        $GLOBALS['app']->Shouter->DeleteShouter('Blog', 'onUpdateBlogTrackback');			

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        //$GLOBALS['app']->Listener->NewListener('Blog', 'onDeleteUser', 'RemoveUserBlog');
        //$GLOBALS['app']->Listener->NewListener('Blog', 'onUpdateUser', 'UpdateUserBlog');
		$GLOBALS['app']->Listener->DeleteListener('Blog', 'InsertDefaultChecksums');

        // Registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/columns');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/default_view');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/last_entries_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/popular_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/xml_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/default_category');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/allow_comments');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/comment_status');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/last_comments_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/last_recentcomments_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/generate_xml');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/generate_category_xml');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/trackback');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/trackback_status');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/plugabble');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/use_antispam');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Blog/pingback');

        // Recent comments
        require_once JAWS_PATH.'include/Jaws/Comment.php';
        $api = new Jaws_Comment($this->_Name);
        $api->DeleteCommentsOfGadget();

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        
		if (version_compare($old, '0.8.0', '<')) {
            $result = $this->installSchema('0.8.0.xml', '', "0.7.0.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $sql = 'UPDATE [[blog]] SET [publishtime] = [createtime]';
            $result = $GLOBALS['db']->query($sql);
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            // ACL keys
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Blog/ManageTrackbacks', 'false');

            // Registry keys
            $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/trackback_status', 'approved');
        }

        if (version_compare($old, '0.8.1', '<')) {
            $result = $this->installSchema('0.8.1.xml', '', '0.8.0.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        if (version_compare($old, '0.8.2', '<')) {
            // ACL keys
            $GLOBALS['app']->ACL->Set('/ACL/gadgets/Blog/ManageTrackbacks', 'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Blog/PublishEntries', 'false');
        }

        if (version_compare($old, '0.8.3', '<')) {
            // Registry keys
            $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/columns', '1');
        }

        if (version_compare($old, '0.8.4', '<')) {

            $GLOBALS['app']->Registry->NewKey('/gadgets/Blog/popular_limit', '10');
        }
        if (version_compare($old, '0.8.7', '<')) {
			$result = $this->installSchema('0.8.7.xml', '', "0.8.1.xml");
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
			
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlogCategory');   	// trigger an action when we add a parent
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlogCategory');	// trigger an action when we delete a parent
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlogCategory');	// and when we update a parent..
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlog');   			// trigger an action when we add
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlog');			// trigger an action when we delete
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlog');			// and when we update
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlogTrackback');   			
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlogTrackback');			
			$GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlogTrackback');			

			//$GLOBALS['app']->Listener->NewListener('Blog', 'onDeleteUser', 'RemoveUserBlog');
			//$GLOBALS['app']->Listener->NewListener('Blog', 'onUpdateUser', 'UpdateUserBlog');
			$GLOBALS['app']->Listener->NewListener('Blog', 'onAfterEnablingGadget', 'InsertDefaultChecksums');

			// Registry keys
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/columns',                   '1');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/default_view',              'last_entries');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/last_entries_limit',        '20');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/popular_limit',             '10');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/xml_limit',                 '10');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/default_category',          '1');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/allow_comments',            'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/comment_status',            'approved');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/last_comments_limit',       '20');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/last_recentcomments_limit', '20');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/generate_xml',              'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/generate_category_xml',     'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/trackback',                 'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/trackback_status',          'approved');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/plugabble',                 'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/use_antispam',              'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Blog/pingback',                  'true');
        }

		$GLOBALS['app']->Shouter->NewShouter('Blog', 'onAddBlogPost');   		
		$GLOBALS['app']->Shouter->NewShouter('Blog', 'onDeleteBlogPost');		
		$GLOBALS['app']->Shouter->NewShouter('Blog', 'onUpdateBlogPost');		
		
		$result = $this->installSchema('schema.xml', '', "0.8.7.xml");
		if (Jaws_Error::IsError($result)) {
			return $result;
		}
		
		// Insert old "blog.text" as first "blog_posts.description" entry
        
		return true;
    }

    /**
     * Create a new category
     *
     * @access  public
     * @param   string  $name        Category name
     * @param   string  $description Category description
     * @param   string  $fast_url    Category fast url
     * @return  boolean Returns true if Category was successfully added, if not, returns false
     */
    function NewCategory($name, $description, $fast_url, $checksum = '')
    {
        $fast_url = empty($fast_url) ? $name : $fast_url;
        $fast_url = $this->GetRealFastUrl($fast_url, 'blog_category');

		$pages = $this->GetCategories();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum) && $p['checksum'] == $checksum) {					            
					return true;
				}
			}
		}
		
        $params = array();
        $params['name']        = $name;
        $params['description'] = $description;
        $params['fast_url']    = $fast_url;
        $params['checksum']    = $checksum;
        $params['now']         = $GLOBALS['db']->Date();

        $sql = '
            INSERT INTO [[blog_category]]
                ([name], [description], [fast_url], [createtime], [updatetime], [checksum])
            VALUES
                ({name}, {description}, {fast_url}, {now}, {now}, {checksum})';

        $result  = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_NOT_ADDED'), _t('BLOG_NAME'));
        }

        $newid = $GLOBALS['db']->lastInsertID('blog_category', 'id');
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		
		if (empty($checksum)) {
			// Update checksum
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[blog_category]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddBlogCategory', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        
		$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_CATEGORY_ADDED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Update a category entry
     *
     * @access  public
     * @param   int     $cid   ID of category
     * @param   string  $name Name of category
     * @return  boolean Returns true if Category was successfully updated, if not, returns false
     */
    function UpdateCategory($cid, $name, $description, $fast_url)
    {
        $fast_url = empty($fast_url) ? $name : $fast_url;
        $fast_url = $this->GetRealFastUrl($fast_url, 'blog_category', false);

        $params = array();
        $params['id']          = $cid;
        $params['name']        = $name;
        $params['description'] = $description;
        $params['fast_url']    = $fast_url;
        $params['now']         = $GLOBALS['db']->Date();

        $sql = '
            UPDATE [[blog_category]] SET
                [name]        = {name},
                [description] = {description},
                [fast_url]    = {fast_url},
                [updatetime]  = {now}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_NOT_UPDATED'), _t('BLOG_NAME'));
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateBlogCategory', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_category_xml') == 'true') {
            $catAtom = $this->GetCategoryAtomStruct($cid);
            $this->MakeCategoryAtom($cid, $catAtom, true);
            $this->MakeCategoryRSS($cid, $catAtom, true);
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_CATEGORY_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Delete a category entry
     *
     * @access  public
     * @param   int     $id ID of category
     * @return  boolean Returns true if Category was successfully deleted, if not, returns false
     */
    function DeleteCategory($id)
    {
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteBlogCategory', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        
		$params       = array();
        $params['id'] = $id;

        /**
         * Uncomment if you want don't want a category associated with a post
        $sql = "SELECT COUNT([entry_id]) FROM [[blog_entrycat]] WHERE [category_id] = {id}";
        $count = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($count)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), _t('BLOG_NAME'));
        }

        if ($count > 0) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORIES_LINKED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORIES_LINKED'), _t('BLOG_NAME'));
        }
        **/

        $sql = 'DELETE FROM [[blog_entrycat]] WHERE [category_id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), _t('BLOG_NAME'));
        }

        $sql = 'DELETE FROM [[blog_category]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), _t('BLOG_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_CATEGORY_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Add a category to a given entry (in blog_category table)
     *
     * @param   int     $blog_id        Post ID
     * @param   int     $category_id    Category ID
     * @return  boolean     Returns true if everything is ok, else Jaws_Error
     */
    function AddCategoryToEntry($blog_id, $category_id)
    {
        $params = array();
        $params['entry_id']    = (int)$blog_id;
        $params['category_id'] = (int)$category_id;
        $sql = 'INSERT INTO [[blog_entrycat]] VALUES({entry_id}, {category_id})';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORIES_NOT_ADDED'), _t('BLOG_NAME'));
        }
        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_category_xml') == 'true') {
            $catAtom = $this->GetCategoryAtomStruct($category_id);
            if (Jaws_Error::IsError($catAtom)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_XML_NOT_GENERATED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_XML_NOT_GENERATED'), _t('BLOG_NAME'));
            } else {
                $this->MakeCategoryAtom($category_id, $catAtom, true);
                $this->MakeCategoryRSS($category_id, $catAtom, true);
            }
        }
        return true;
    }

    /**
     * Delete category from an entry
     *
     * @param   int     $blog_id        Post ID
     * @param   int     $category_id    Category ID
     * @return  boolean     Returns true if everything is ok, else Jaws_Error
     */
    function DeleteCategoryInEntry($blog_id, $category_id)
    {
        $params = array();
        $params['entry_id']    = (int)$blog_id;
        $params['category_id'] = (int)$category_id;
        $sql = 'DELETE FROM [[blog_entrycat]] WHERE [entry_id] = {entry_id} AND [category_id] = {category_id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_CATEGORIES_NOT_ADDED'), _t('BLOG_NAME'));
        }

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_category_xml') == 'true') {
            $catAtom = $this->GetCategoryAtomStruct($category_id);
            if (Jaws_Error::IsError($catAtom)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORY_XML_NOT_GENERATED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_CATEGORY_XML_NOT_GENERATED'), _t('BLOG_NAME'));
            }

            $this->MakeCategoryAtom($category_id, $catAtom, true);
            $this->MakeCategoryRSS($category_id, $catAtom, true);
        }

        return true;
    }

    /**
     * Get all the main settings of the Blog
     *
     * @access  public
     * @return  array   An array of settings
     */
    function GetSettings()
    {
        $settings = array();
        $settings['default_view']               = $GLOBALS['app']->Registry->Get('/gadgets/Blog/default_view');
        $settings['last_entries_limit']         = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_entries_limit');
        $settings['popular_limit']              = $GLOBALS['app']->Registry->Get('/gadgets/Blog/popular_limit');
        $settings['default_category']           = $GLOBALS['app']->Registry->Get('/gadgets/Blog/default_category');
        $settings['xml_limit']                  = $GLOBALS['app']->Registry->Get('/gadgets/Blog/xml_limit');
        $settings['comments']                   = $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments');
        $settings['trackback']                  = $GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback');
        $settings['trackback_status']           = $GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback_status');
        $settings['last_comments_limit']        = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_comments_limit');
        $settings['last_recentcomments_limit']  = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_recentcomments_limit');
        $settings['comment_status']             = $GLOBALS['app']->Registry->Get('/gadgets/Blog/comment_status');
        $settings['pingback']                   = $GLOBALS['app']->Registry->Get('/gadgets/Blog/pingback');

        return $settings;
    }

    /**
     * Save the main settings of the Blog
     *
     * @access  public
     * @param   string  $view               The default View
     * @param   int     $limit              Limit of entries that blog will show
     * @param   int     $popularLimit       Limit of popular entries
     * @param   int     $commentsLimit      Limit of comments that blog will show
     * @param   int     $recentcommentLimit Limit of recent comments to display
     * @param   string  $category           The default category for blog entries
     * @param   boolean $comments           If comments should appear
     * @param   string  $comment_status     Default comment status
     * @param   boolean $trackback          If Trackback should be used
     * @param   string  $trackback_status   Default trackback status
     * @param   boolean $pingback           If Pingback should be used
     * @return  boolean Return true if settings were saved without problems, if not, returns false
     */
    function SaveSettings($view, $limit, $popularLimit, $commentsLimit, $recentcommentsLimit, $category, 
                          $xml_limit, $comments, $comment_status, $trackback, $trackback_status,
                          $pingback)
    {
        $result = array();
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/default_view', $view);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/last_entries_limit', $limit);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/popular_limit', $popularLimit);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/default_category', $category);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/xml_limit', $xml_limit);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/allow_comments', $comments);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/comment_status', $comment_status);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/trackback', $trackback);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/trackback_status', $trackback_status);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/last_comments_limit', $commentsLimit);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/last_recentcomments_limit', $recentcommentsLimit);
        $result[] = $GLOBALS['app']->Registry->Set('/gadgets/Blog/pingback', $pingback);

        foreach ($result as $r) {
            if (!$r || Jaws_Error::IsError($r)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_SETTINGS_NOT_SAVED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_SETTINGS_NOT_SAVE'), _t('BLOG_NAME'));
            }
        }
        $GLOBALS['app']->Registry->Commit('Blog');
        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Create a new post
     *
     * @access  public
     * @param   int     $user           User ID
     * @param   array   $categories     Array with categories id's
     * @param   string  $title          Title of the entry
     * @param   string  $content        Content of the entry
     * @param   string  $fast_url       FastURL
     * @param   boolean $allow_comments If entry should allow commnets
     * @param   boolean $publish        If entry should be published
     * @param   string  $timestamp      Entry timestamp (optional)
     * @param   boolean $autodraft      Does it comes from an autodraft action?
     * @return  int     Returns the ID of the new post and false on error
     */
    function NewEntry($user, $categories, $title, $summary, $content, $fast_url, $allow_comments, $trackbacks,
                      $publish, $timestamp = null, $autoDraft = false, $checksum = '')
    {
        $fast_url = empty($fast_url) ? $title : $fast_url;
        $fast_url = $this->GetRealFastUrl($fast_url, 'blog', $autoDraft === false);

		$pages = $this->GetEntries();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum) && $p['checksum'] == $checksum) {					            
					return true;
				}
			}
		}
        $params               = array();
        $params['user']       = $user;
        $params['title']      = $title;
        $params['content']    = str_replace("\r\n", "\n", $content);
        $params['summary']    = str_replace("\r\n", "\n", $summary);
        $params['trackbacks'] = $trackbacks;
        $params['publish']    = $GLOBALS['app']->Session->GetPermission('Blog', 'PublishEntries')? $publish : false;
        $params['fast_url']   = $fast_url;
        $params['comments']   = $allow_comments;
        $params['checksum']   = $checksum;

        // Switch out for the MDB2 way
        if (!is_bool($params['comments'])) {
            $params['comments'] = $params['comments'] == '1' ? true : false;
        }

        if (!is_bool($params['publish'])) {
            $params['publish'] = $params['publish'] == '1' ? true : false;
        }

        $date = $GLOBALS['app']->loadDate();
        $params['now'] = $GLOBALS['db']->Date();

        if (!is_null($timestamp) && $date->ValidDBDate($timestamp)) {
            // Maybe we need to not allow crazy dates, e.g. 100 years ago
            $params['publishtime'] = $GLOBALS['app']->UserTime2UTC($timestamp, 'Y-m-d H:i:s');
        } else {
            $params['publishtime'] = $params['now'];
        }

        $sql = '
            INSERT INTO [[blog]]
                ([user_id], [title], [summary], [text], [fast_url], [createtime], [updatetime], [publishtime],
                [trackbacks], [published], [allow_comments], [checksum])
            VALUES
                ({user}, {title}, {summary}, {content}, {fast_url}, {now}, {now}, {publishtime},
                {trackbacks}, {publish}, {comments}, {checksum})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_ADDED'), _t('BLOG_NAME'));
        }

        $sql = 'SELECT MAX([id]) FROM [[blog]] WHERE [title] = {title}';
        $newid = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($newid)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_ADDED'), _t('BLOG_NAME'));
        }
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		
		if (empty($checksum)) {
			// Update checksum
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[blog]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
        if ($newid) {
			// Categories stuff
			if (is_array($categories) && count($categories) > 0) {
				foreach ($categories as $category) {
					$res = $this->AddCategoryToEntry($newid, $category);
					if (Jaws_Error::IsError($res)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_CATEGORIES_NOT_ADDED'), RESPONSE_ERROR);
						return $res;
					}
				}
			}
		}

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/pingback') == 'true') {
            require_once JAWS_PATH . 'include/Jaws/Pingback.php';
            $pback =& Jaws_PingBack::getInstance();
            $pback->sendFromString($GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $newid), false, 'site_url'),
                                   $params['content']);
        }

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_xml') == 'true') {
            $this->MakeAtom(true);
            $this->MakeRSS(true);
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddBlog', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ENTRY_ADDED'), RESPONSE_NOTICE);

        return $newid;
    }

    /**
     * Update an entry
     *
     * @access  public
     * @param   int     $post_id        Post ID
     * @param   int     $categories     Categories array
     * @param   string  $title          Title of the Entry
     * @param   string  $content        Content of the Entry
     * @param   string  $fast_url       FastURL
     * @param   boolean $allow_comments If entry should allow commnets
     * @param   boolean $publish        If entry should be published
     * @param   string  $timestamp      Entry timestamp (optional)
     * @param   boolean $autodraft      Does it comes from an autodraft action?
     * @return  int     Returns the ID of the post and false on error
     */
    function UpdateEntry($post_id, $categories, $title, $summary, $content, $fast_url, $allow_comments, $trackbacks,
                         $publish, $timestamp = null, $autoDraft = false)
    {
        $fast_url = empty($fast_url) ? $title : $fast_url;
        $fast_url = $this->GetRealFastUrl($fast_url, 'blog', false);

        $params               = array();
        $params['title']      = $title;
        $params['content']    = str_replace("\r\n", "\n", $content);
        $params['summary']    = str_replace("\r\n", "\n", $summary);
        $params['trackbacks'] = $trackbacks;
        $params['published']  = $publish;
        $params['comments']   = $allow_comments;
        $params['id']         = $post_id;
        $params['fast_url']   = $fast_url;

        if (!is_bool($params['published'])) {
            $params['published'] = $params['published'] == '1' ? true : false;
        }

        if (!is_bool($params['comments'])) {
            $params['comments'] = $params['comments'] == '1' ? true : false;
        }

        $e = $this->GetEntry($params['id']);
        if (Jaws_Error::IsError($e)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), _t('BLOG_NAME'));
        }

        if ($GLOBALS['app']->Session->GetAttribute('user_id') != $e['user_id']) {
            if (!$GLOBALS['app']->Session->GetPermission('Blog', 'ModifyOthersEntries')) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), _t('BLOG_NAME'));
            }
        }

        if (!$GLOBALS['app']->Session->GetPermission('Blog', 'PublishEntries')) {
            $params['published']  = $e['published'];
        }

        //Current fast url changes?
        if ($e['fast_url'] != $fast_url && $autoDraft === false) {
            $fast_url = $this->GetRealFastUrl($fast_url, 'blog');
            $params['fast_url'] = $fast_url;
        }

        // Switch out for the MDB2 way
        if (!is_bool($params['comments'])) {
            $params['comments'] = $params['comments'] === 1 ? true : false;
        }

        if (!is_bool($params['published'])) {
            $params['published'] = $params['published'] === 1 ? true : false;
        }

        $params['now'] = $GLOBALS['db']->Date();

        $sql = '
            UPDATE [[blog]] SET
                [title] = {title},
                [fast_url] = {fast_url},
                [summary]  = {summary},
                [text] = {content},
                [updatetime] = {now},
                [trackbacks] = {trackbacks},
                [published]  = {published},
                [allow_comments] = {comments}';

        $date = $GLOBALS['app']->loadDate();
        if (!is_null($timestamp) && $date->ValidDBDate($timestamp)) {
            // Maybe we need to not allow crazy dates, e.g. 100 years ago
            $params['publishtime'] = $GLOBALS['app']->UserTime2UTC($timestamp, 'Y-m-d H:i:s');
            $sql .= ', [publishtime] = {publishtime} ';
        }

        $sql .= ' WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);

        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), _t('BLOG_NAME'));
        }

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_xml') == 'true') {
            $this->MakeAtom(true);
            $this->MakeRSS (true);
        }

        if (!is_array($categories)) {
            $categories = array();
        }

        $catAux = array();
        foreach ($e['categories'] as $cat) {
            $catAux[] = $cat['id'];
        }

        foreach ($categories as $category) {
            if (!in_array($category, $catAux)) {
                $this->AddCategoryToEntry($params['id'], $category);
            } else {
                if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_category_xml') == 'true') {
                    $catAtom = $this->GetCategoryAtomStruct($category);
                    $this->MakeCategoryAtom($category, $catAtom, true);
                    $this->MakeCategoryRSS($category, $catAtom, true);
                }
            }
        }

        foreach ($e['categories'] as $k => $v) {
            if (!in_array($v['id'], $categories)) {
                $this->DeleteCategoryInEntry($params['id'], $v['id']);
            }
        }

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/pingback') == 'true') {
            require_once JAWS_PATH . 'include/Jaws/Pingback.php';
            $pback =& Jaws_PingBack::getInstance();
            $pback->sendFromString($GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $params['id']),
                                                                   false, 'site_url'),
                                   $params['content']);
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateBlog', $params['id']);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ENTRY_UPDATED'), RESPONSE_NOTICE);
        return $params['id'];
    }

    /**
     * Delete an entry
     *
     * @access  public
     * @param   int     $post_id The entry ID
     * @return  boolean True if entry was successfully deleted, if not, returns false
     */
    function DeleteEntry($post_id)
    {
        $e = $this->GetEntry($post_id);
        if (Jaws_Error::IsError($e)) {
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_DELETED'), _t('BLOG_NAME'));
        }

        if (
            $GLOBALS['app']->Session->GetAttribute('user_id') != $e['user_id'] &&
            !$GLOBALS['app']->Session->GetPermission('Blog', 'ModifyOthersEntries')
        ) {
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_DELETED'), _t('BLOG_NAME'));
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteBlog', $post_id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

        if (is_array($e['categories']) && count($e['categories']) > 0) {
            foreach ($e['categories'] as $k => $v) {
                $this->DeleteCategoryInEntry($post_id, $v['id']);
            }
        }

        $params = array();
        $params['id'] = $post_id;
        $sql = 'DELETE FROM [[blog]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_DELETED'), _t('BLOG_NAME'));
        }

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/generate_xml') == 'true') {
            $this->MakeAtom(true);
            $this->MakeRSS (true);
        }

        // Remove comment entries..
        $this->DeleteCommentsIn($post_id);

        return true;
    }

    /**
     * Send a trackback to a site
     *
     * @access  public
     * @param   string  $title     Title of the Site
     * @param   string  $excerpt   The Excerpt
     * @param   string  $permalink The Permalink to send
     * @param   array  $to        Where to send the trackback
     */
    function SendTrackback($title, $excerpt, $permalink, $to)
    {
        $title = urlencode(stripslashes($title));
        $excerpt = urlencode(stripslashes($excerpt));
        $blog_name = urlencode(stripslashes($GLOBALS['app']->Registry->Get('/config/site_name')));
        $permalink = urlencode($permalink);

        require_once 'HTTP/Request.php';

        $options = array();
        $timeout = (int)$GLOBALS['app']->Registry->Get('/config/connection_timeout');
        $options['timeout'] = $timeout;
        if ($GLOBALS['app']->Registry->Get('/network/proxy_enabled') == 'true') {
            if ($GLOBALS['app']->Registry->Get('/network/proxy_auth') == 'true') {
                $options['proxy_user'] = $GLOBALS['app']->Registry->Get('/network/proxy_user');
                $options['proxy_pass'] = $GLOBALS['app']->Registry->Get('/network/proxy_pass');
            }
            $options['proxy_host'] = $GLOBALS['app']->Registry->Get('/network/proxy_host');
            $options['proxy_port'] = $GLOBALS['app']->Registry->Get('/network/proxy_port');
        }

        $httpRequest = new HTTP_Request('', $options);
        $httpRequest->setMethod(HTTP_REQUEST_METHOD_POST);
        foreach ($to as $url) {
            $httpRequest->setURL($url);
            $httpRequest->addPostData('title',     $title);
            $httpRequest->addPostData('url',       $permalink);
            $httpRequest->addPostData('blog_name', $blog_name);
            $httpRequest->addPostData('excerpt',   $excerpt);
            $resRequest = $httpRequest->sendRequest();
            $httpRequest->clearPostData();
        }
    }

    /**
     * Get the total number of posts of an user
     *
     * @access  public
     * @return  int
     */
    function TotalOfPosts()
    {
        $sql = '
            SELECT
                COUNT([[blog]].[id])
            FROM [[blog]]
            INNER JOIN [[users]] ON [[blog]].[user_id] = [[users]].[id]';

        $howMany = $GLOBALS['db']->queryOne($sql);

        return Jaws_Error::IsError($howMany) ? 0 : $howMany;
    }

    /**
     * Updates a comment
     *
     * @access  public
     * @param   string  $id         Comment id
     * @param   string  $name       Name of the author
     * @param   string  $title      Title of the comment
     * @param   string  $url        Url of the author
     * @param   string  $email      Email of the author
     * @param   string  $comments   Text of the comment
     * @param   string  $permalink  Permanent link to post
     * @param   string  $status     Comment Status
     * @return  boolean Success/Failure
     */
    function UpdateComment($id, $name, $title, $url, $email, $comments, $permalink, $status)
    {
        require_once JAWS_PATH . 'include/Jaws/Comment.php';

        $params              = array();
        $params['id']        = $id;
        $params['name']      = strip_tags($name);
        $params['title']     = strip_tags($title);
        $params['url']       = strip_tags($url);
        $params['email']     = strip_tags($email);
        $params['comments']  = strip_tags($comments);
        $params['permalink'] = $permalink;
        $params['status']    = $status;

        $api = new Jaws_Comment($this->_Name);
        $res = $api->UpdateComment($params['id'],        $params['name'],
                                   $params['email'],     $params['url'],
                                   $params['title'],     $params['comments'],
                                   $params['permalink'], $params['status']);

        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_UPDATED'), _t('BLOG_NAME'));
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_COMMENT_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Delete a comment
     *
     * @access  public
     * @param   string  $id Comment id
     * @return  boolean Success/Failure
     */
    function DeleteComment($id)
    {
        require_once JAWS_PATH.'include/Jaws/Comment.php';

        $comment = $this->GetComment($id);
        if (Jaws_Error::IsError($comment)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), _t('BLOG_NAME'));
        }

        $api = new Jaws_Comment($this->_Name);
        $res = $api->DeleteComment($id);

        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), _t('BLOG_NAME'));
        }

        if ($comment['status'] == COMMENT_STATUS_APPROVED) {
            $sql = 'UPDATE [[blog]] SET [comments] = [comments] - 1 WHERE [id] = {id}';
            $result = $GLOBALS['db']->query($sql, array('id' => $comment['gadget_reference']));

            if (Jaws_Error::IsError($result)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), _t('BLOG_NAME'));
            }
        }

        return true;
    }

    /**
     * Delete all comments in a given entry
     *
     * @access  public
     * @param   string  $id         Post id.
     * @return  boolean Success/Failure
     */
    function DeleteCommentsIn($id)
    {
        require_once JAWS_PATH.'include/Jaws/Comment.php';
        $api = new Jaws_Comment($this->_Name);
        $res = $api->DeleteCommentsByReference($id);
    }

    /**
     * Mark as different status a comment
     *
     * @access  public
     * @param   array  $ids     Id's of the comments to mark as spam
     * @param   string $status  New status (spam by default)
     */
    function MarkCommentsAs($ids, $status = 'spam')
    {
        if (count($ids) == 0 || empty($status)) {
            return true;
        }

        require_once JAWS_PATH.'include/Jaws/Comment.php';
        $api = new Jaws_Comment($this->_Name);

        // Fix blog comment counter...
        foreach ($ids as $id) {
            $comment = $api->GetComment($id);
            if (($comment['status'] != COMMENT_STATUS_APPROVED) && ($status == COMMENT_STATUS_APPROVED)){
                $sql = 'UPDATE [[blog]] SET [comments] = [comments] + 1 WHERE [id] = {id}';
                $result = $GLOBALS['db']->query($sql, array('id' => $comment['gadget_reference']));
                if (Jaws_Error::IsError($result)) {
                    $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_UPDATED'), RESPONSE_ERROR);
                    return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_UPDATED'), _t('BLOG_NAME'));
                }
            } elseif (($comment['status'] == COMMENT_STATUS_APPROVED) && ($status != COMMENT_STATUS_APPROVED)) {
                $sql = 'UPDATE [[blog]] SET [comments] = [comments] - 1 WHERE [id] = {id}';
                $result = $GLOBALS['db']->query($sql, array('id' => $comment['gadget_reference']));
                if (Jaws_Error::IsError($result)) {
                    $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_UPDATED'), RESPONSE_ERROR);
                    return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_UPDATED'), _t('BLOG_NAME'));
                }
            }
        }

        $api->MarkAs($ids, $status);
        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_COMMENT_MARKED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Mark as different status a trackback
     *
     * @access  public
     * @param   array  $ids     Id's of the trackbacks to mark as spam
     * @param   string $status  New status (spam by default)
     */
    function MarkTrackbacksAs($ids, $status = 'spam')
    {
        if (count($ids) == 0 || empty($status)) {
            return true;
        }

        // Fix blog trackback counter...
        foreach ($ids as $id) {
            $sql = 'UPDATE [[blog_trackback]] SET [status] = {status} WHERE [id] = {id}';
            $result = $GLOBALS['db']->query($sql, array('id' => $id, 'status' => $status));
            if (Jaws_Error::IsError($result)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_TRACKBACK_NOT_UPDATED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_TRACKBACK_NOT_UPDATED'), _t('BLOG_NAME'));
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_TRACKBACK_MARKED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Does a massive comment delete
     *
     * @access  public
     * @param   array   $ids  Ids of comments
     * @return  boolean Success/Failure
     */
    function MassiveCommentDelete($ids)
    {
        if (!is_array($ids)) {
            $ids = func_get_args();
        }

        foreach ($ids as $id) {
            $res = $this->DeleteComment($id);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_COMMENT_NOT_DELETED'), _t('BLOG_NAME'));
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_COMMENT_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Does a massive entry delete
     *
     * @access  public
     * @param   array   $ids  Ids of entries
     * @return  boolean Success/Failure
     */
    function MassiveEntryDelete($ids)
    {
        if (!is_array($ids)) {
            $ids = func_get_args();
        }

        foreach ($ids as $id) {
            $res = $this->DeleteEntry($id);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_DELETED'), _t('BLOG_NAME'));
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ENTRY_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Change status of group of entries ids
     *
     * @access  public
     * @param   array   $ids    Ids of entries
     * @param   string  $status New status
     * @return  array   Response (notice or error)
     */
    function ChangeEntryStatus($ids, $status = '0')
    {
        if (count($ids) == 0) {
            return true;
        }

        foreach ($ids as $id) {
            $sql = 'UPDATE [[blog]] SET [published] = {published} WHERE [id] = {id}';
            $result = $GLOBALS['db']->query($sql, array('id' => $id, 'published' => (bool) $status));
            if (Jaws_Error::IsError($result)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_ENTRY_NOT_UPDATED'), _t('BLOG_NAME'));
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ENTRY_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Does a massive trackback delete
     *
     * @access  public
     * @param   array   $ids  Ids of trackbacks
     * @return  boolean Success/Failure
     */
    function MassiveTrackbackDelete($ids)
    {
        if (!is_array($ids)) {
            $ids = func_get_args();
        }

        foreach ($ids as $id) {
            $res = $this->DeleteTrackback($id);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_TRACKBACK_NOT_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('BLOG_ERROR_TRACKBACK_NOT_DELETED'), _t('BLOG_NAME'));
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_TRACKBACK_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a trackback
     *
     * @param   int     $id     Trackback's ID
     * @return  boolean True if sucess or Jaws_Error on any error
     * @access  public
     */
    function DeleteTrackback($id)
    {
        $params             = array();
        $params['id']       = $id;

        $sql = 'DELETE FROM [[blog_trackback]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('GLOBAL_TRACKBACKS_ERROR_NOT_DELETED'), 'CORE');
        }

        return true;
    }

    /**
     * Gets a list of trackbacks that match a certain filter.
     *
     * See Filter modes for more info
     *
     * @access  public
     * @param   string  $filterMode Which mode should be used to filter
     * @param   string  $filterData Data that will be used in the filter
     * @param   string  $status     Spam status (approved, waiting, spam)
     * @param   mixed   $limit      Limit of data (numeric/boolean: no limit)
     * @return  array   Returns an array with of filtered trackbacks or Jaws_Error on error
     */
    function GetFilteredTrackbacks($filterMode, $filterData, $status, $limit)
    {
        if (
            $filterMode != 'postid' &&
            $filterMode != 'status' &&
            $filterMode != 'ip'
            ) {
            $filterData = '%'.$filterData.'%';
        }

        $params = array();
        $params['filterData'] = $filterData;

        $sql = '
            SELECT
                [id],
                [parent_id],
                [blog_name],
                [url],
                [title],
                [ip],
                [url],
                [status],
                [createtime]
            FROM [[blog_trackback]]';

        $sql_condition = '';
        switch ($filterMode) {
        case 'postid':
            $sql_condition.= ' AND [parent_id] = {filterData}';
            break;
        case 'blog_name':
            $sql_condition.= ' AND [blog_name] LIKE {filterData}';
            break;
        case 'url':
            $sql_condition.= ' AND [url] LIKE {filterData}';
            break;
        case 'title':
            $sql_condition.= ' AND [title] LIKE {filterData}';
            break;
        case 'ip':
            $sql_condition.= ' AND [ip] LIKE {filterData}';
            break;
        case 'excerpt':
            $sql_condition.= ' AND [excerpt] LIKE {filterData}';
            break;
        case 'various':
            $sql_condition.= ' AND ([blog_name] LIKE {filterData}';
            $sql_condition.= ' OR [url] LIKE {filterData}';
            $sql_condition.= ' OR [title] LIKE {filterData}';
            $sql_condition.= ' OR [excerpt] LIKE {filterData})';
            break;
        default:
            if (is_bool($limit)) {
                $limit = false;
                //By default we get the last 20 comments
                $result = $GLOBALS['db']->setLimit('20');
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('GLOBAL_COMMENT_ERROR_GETTING_FILTERED_COMMENTS'), 'CORE');
                }
            }
            break;
        }

        if (in_array($status, array('approved', 'waiting', 'spam'))) {
            $params['status'] = $status;
            $sql.= ' AND [status] = {status}';
        }

        if (is_numeric($limit)) {
            $result = $GLOBALS['db']->setLimit(10, $limit);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('GLOBAL_COMMENT_ERROR_GETTING_FILTERED_COMMENTS'), 'CORE');
            }
        }

        $sql .= (empty($sql_condition)? '' : 'WHERE 1=1 ') . $sql_condition;
        $sql .= ' ORDER BY [createtime] DESC';

        $rows = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($rows)) {
            return new Jaws_Error(_t('GLOBAL_COMMENT_ERROR_GETTING_FILTERED_COMMENTS'), 'CORE');
        }

        return $rows;
    }

    /**
     * Build a new array with filtered data
     *
     * @access  public
     * @param   string  $filterby Filter to use(postid, author, email, url, title, comment)
     * @param   string  $filter   Filter data
     * @param   string  $status   Spam status (approved, waiting, spam)
     * @param   mixed   $limit    Data limit (numeric/boolean)
     * @return  array   Filtered Comments
     */
    function GetTrackbacksDataAsArray($filterby, $filter, $status, $limit)
    {
        $trackbacks = $this->GetFilteredTrackbacks($filterby, $filter, $status, $limit);
        if (Jaws_Error::IsError($trackbacks)) {
            return array();
        }

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $date = $GLOBALS['app']->loadDate();
        $data = array();
        foreach ($trackbacks as $row) {
            $newRow = array();
            $newRow['__KEY__'] = $row['id'];
            $newRow['blog_name']    = '<a href="'.$xss->filter($row['url']).'">'.$xss->filter($row['blog_name']).'</a>';;

            $url = BASE_SCRIPT . '?gadget=Blog&action=ViewTrackback&id='.$row['id'];
            $newRow['title']   = '<a href="'.$url.'">'.$xss->filter($row['title']).'</a>';

            $newRow['created'] = $date->Format($row['createtime']);
            switch($row['status']) {
            case 'approved':
                $newRow['status'] = _t('GLOBAL_STATUS_APPROVED');
                break;
            case 'waiting':
                $newRow['status'] = _t('GLOBAL_STATUS_WAITING');
                break;
            case 'spam':
                $newRow['status'] = _t('GLOBAL_STATUS_SPAM');
                break;
            }

            $link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'), $url, STOCK_EDIT);
            $actions= $link->Get().'&nbsp;';

            $link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                        "javascript: trackbackDelete('".$row['id']."');",
                                        STOCK_DELETE);
            $actions.= $link->Get().'&nbsp;';
            $newRow['actions'] = $actions;

            $data[] = $newRow;
        }
        return $data;
    }

    /**
     * Counts how many trackbacks are with a given filter
     *
     * See Filter modes for more info
     *
     * @access  public
     * @param   string  $filterMode Which mode should be used to filter
     * @param   string  $filterData Data that will be used in the filter
     * @param   string  $status     Spam status (approved, waiting, spam)
     * @return  int   Returns how many trackbacks exists with a given filter
     */
    function HowManyFilteredTrackbacks($filterMode, $filterData, $status)
    {
        if (
            $filterMode != 'postid' &&
            $filterMode != 'status' &&
            $filterMode != 'ip'
            ) {
            $filterData = '%'.$filterData.'%';
        }

        $params = array();
        $params['filterData'] = $filterData;

        $sql = '
            SELECT
                COUNT(*) AS howmany
            FROM [[blog_trackback]]';

        $sql_condition = '';
        switch ($filterMode) {
        case 'postid':
            $sql_condition.= ' AND [parent_id] = {filterData}';
            break;
        case 'blog_name':
            $sql_condition.= ' AND [blog_name] LIKE {filterData}';
            break;
        case 'url':
            $sql_condition.= ' AND [url] LIKE {filterData}';
            break;
        case 'title':
            $sql_condition.= ' AND [title] LIKE {filterData}';
            break;
        case 'ip':
            $sql_condition.= ' AND [ip] LIKE {filterData}';
            break;
        case 'excerpt':
            $sql_condition.= ' AND [excerpt] LIKE {filterData}';
            break;
        case 'various':
            $sql_condition.= ' AND ([blog_name] LIKE {filterData}';
            $sql_condition.= ' OR [url] LIKE {filterData}';
            $sql_condition.= ' OR [title] LIKE {filterData}';
            $sql_condition.= ' OR [excerpt] LIKE {filterData})';
            break;
        default:
            if (is_bool($limit)) {
                $limit = false;
                //By default we get the last 20 comments
                $result = $GLOBALS['db']->setLimit('20');
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('GLOBAL_COMMENT_ERROR_GETTING_FILTERED_COMMENTS'), 'CORE');
                }
            }
            break;
        }

        if ($status != 'various' && (!in_array($status, array('approved', 'waiting', 'spam')))) {
            if ($GLOBALS['app']->Registry->Get('/gadget/blog/trackback_status') == 'waiting') {
                $status = 'waiting';
            } else {
                $status = 'approved';
            }          
        }

        if ($status != 'various') {
            $sql_condition.= ' AND [status] = {status}';
            $params['status'] = $status;
        }

        $sql .= (empty($sql_condition)? '' : 'WHERE 1=1 ') . $sql_condition;

        $howmany = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($rows)) {
            return new Jaws_Error(_t('GLOBAL_COMMENT_ERROR_GETTING_FILTERED_COMMENTS'), 'CORE');
        }

        return $howmany;
    }

    /**
     * Creates a new page.
     *
     * @access  public
     * @param   integer  $sort_order 	The chronological order
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   integer $layout  		The layout mode of the post
     * @param   string $Active  		(Y/N) If the post is published or not
     * @param   integer $OwnerID  		The poster's user ID
     * @param   string $gadget  		The gadget type of content
     * @param   boolean $auto       		If it's auto saved or not
     * @return  ID of entered post 	    Success/failure
     */
    function AddPost($sort_order, $LinkID, $title, $description, $image, $image_width, $image_height, $layout, 
	$active, $OwnerID, $gadget, $url_type = 'imageviewer', $internal_url = '', $external_url = '', 
	$url_target = '_self', $rss_url = null, $image_code = '', $iTime = null, $checksum = '', $auto = false)
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li>');
        
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		
		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
			$url_type = 'imageviewer';
		}
		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		
		$image_code = htmlspecialchars($image_code);
        $rss_url = !empty($rss_url) && !is_null($rss_url) ? $xss->parse($rss_url) : null;
        $url = !empty($url) ? $url : '';
        $url_target = !empty($url_target) ? $xss->parse($url_target) : '';

		if (
			$OwnerID == 0 && 
			$url_type == 'external' && 
			(substr(strtolower(trim($external_url)), 0, 4) == 'http') && 
			strpos(strtolower(trim($external_url)), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim($internal_url)), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else if ($url_type == 'imageviewer') {
			$url = "javascript:void(0);";
		} else if ($gadget == 'text') {
	        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_INVALID_URL'), _t('BLOG_NAME'));
		}

		$sql = "
            INSERT INTO [[blog_posts]]
                ([sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated], [gadget],
				[url], [url_target], [rss_url], [image_code], [checksum])
            VALUES
                ({sort_order}, {LinkID}, {title}, 
				{description}, {image}, {image_width}, {image_height},
				{layout}, {Active}, {ownerid}, {now}, {now}, {gadget},
				{url}, {url_target}, {rss_url}, {image_code}, {checksum})";

		$params               		= array();
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= $xss->parse($title);
		$params['description']   	= str_replace("\r\n", "\n", $description);
		$params['image'] 			= $xss->parse($image);
        $params['image_width'] 		= (int)$image_width;
        $params['image_height'] 	= (int)$image_height;
        $params['layout'] 			= (int)$layout;
		$params['LinkID']         	= (int)$LinkID;
		$params['ownerid']         	= $OwnerID;
        $params['Active'] 			= $xss->parse($active);
        $params['gadget'] 			= $xss->parse($gadget);
        $params['url']				= $url;
		$params['url_target']		= $url_target;
		$params['rss_url']			= $rss_url;
		$params['image_code']   	= ($OwnerID == 0 ? str_replace("\r\n", "\n", $image_code) : '');
        $params['checksum'] 		= $xss->parse($checksum);
		if (is_null($iTime)) {
			$params['now']        	= $GLOBALS['db']->Date();
		} else {
			$params['now']        	= $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($iTime));
		}

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('BLOG_ERROR_POST_NOT_ADDED'), _t('BLOG_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('BLOG_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('blog_posts', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[blog_posts]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddBlogPost', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_POST_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a post.
     *
     * @access  public
     * @param   int     $id             The ID of the post to update.
     * @param   integer  $sort_order 	The chronological order
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   integer $layout  		The layout mode of the post
     * @param   string $Active  		(Y/N) If the post is published or not
     * @param   integer $OwnerID  		The poster's user ID
     * @param   string $gadget  		The gadget type of content
     * @param   boolean $auto       		If it's auto saved or not
     * @return  boolean Success/failure
     */
    function UpdatePost($id, $sort_order, $title, $description, $image, $image_width, $image_height, $layout, 
	$active, $gadget, $url_type = 'imageviewer', $internal_url, $external_url, $url_target = '_self', $rss_url, 
	$image_code, $iTime = null, $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $page = $model->GetPost((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('BLOG_ERROR_POST_NOT_FOUND'), _t('BLOG_NAME'));
        }

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li>');

		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
			$url_type = 'imageviewer';
		}
		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		
		if (
			$page['ownerid'] == 0 && 
			$url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else {
			$url = "javascript:void(0);";
		}

		$sql = '
            UPDATE [[blog_posts]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[description] = {description}, 
				[image] = {image}, 
				[image_width] = {image_width},
				[image_height] = {image_height},
				[layout] = {layout}, 
				[active] = {Active}, 
				[updated] = {now},
				[gadget] = {gadget},
				[url] = {url},
				[url_target] = {url_target},
				[rss_url] = {rss_url},
				[image_code] = {image_code}
			WHERE [id] = {id}';

		
		$image_code = htmlspecialchars($image_code);
        $rss_url = !empty($rss_url) ? $xss->parse($rss_url) : null;
        $url = !empty($url) ? $url : '';
        $url_target = !empty($url_target) ? $xss->parse($url_target) : '';
        $params               	= array();
        $params['id']         	= (int)$id;
        $params['sort_order'] 	= (!is_null($sort_order) ? (int)$sort_order : $page['sort_order']);
        $params['title'] 		= $xss->parse($title);
		$params['description']  = str_replace("\r\n", "\n", $description);
        $params['image'] 		= $xss->parse($image);
        $params['image_width'] 	= (int)$image_width;
        $params['image_height'] = (int)$image_height;
        $params['layout'] 		= (int)$layout;
        $params['Active'] 		= $xss->parse($active);
        $params['gadget'] 		= $xss->parse($gadget);
        $params['url']			= $url;
		$params['url_target']	= $url_target;
		$params['rss_url']		= $rss_url;
		$params['image_code']   = ($page['ownerid'] == 0 ? str_replace("\r\n", "\n", $image_code) : '');
		if (is_null($iTime)) {
			$params['now']  = $GLOBALS['db']->Date();
		} else {
			$params['now']  = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($iTime));
		}
		
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('BLOG_ERROR_POST_NOT_UPDATED'), _t('BLOG_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateBlogPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_POST_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a post
     *
     * @access  public
     * @param   int     $id     The ID of the page to delete.
     * @return  bool    Success/failure
     */
    function DeletePost($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		$parent = $model->GetPost((int)$id);
		if (Jaws_Error::IsError($parent) || !isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('BLOG_ERROR_POST_NOT_DELETED'), _t('BLOG_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteBlogPost', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$sql = 'DELETE FROM [[blog_posts]] WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('BLOG_ERROR_POST_NOT_DELETED'), _t('BLOG_NAME'));
			}
			//$error = new Jaws_Error(_t('BLOG_POST_DELETED'), _t('BLOG_NAME'));

			if ($massive === false) {
				$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_POST_DELETED'), RESPONSE_NOTICE);
			}
			return true;
		}
    }

    /**
     * Re-sorts posts
     *
     * @access  public
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts)
    {
		//$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				//$page = $model->GetPost($pid);
		        //if (Jaws_Error::isError($page)) {
		        //    $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
				//	return false;
		        //} else {
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [[blog_posts]] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }


    /**
     * Edit layout's element action
     * 
     * @access  public
     * @param   int     $id   Item ID
     * @params  string  $action
     * @return  array   Response
     */
    function EditElementAction($id, $action) 
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params           = array();
        $params['id']     = (int)$id;
        $params['action'] = $xss->parse($action);
        $sql = '
            UPDATE [[blog_posts]] SET
                [image] = {action}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateBlogPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        return true;
    }

    /**
     * Get actions of a given gadget
     * 
     * @access  public
     * @param   string  $gadget 
     * @return  array   Array with the actions of the given gadget
     */
    function GetGadgetActions($g)
    { 
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		return $model->GetGadgetActions($g);
    }

	/**
     * Inserts checksums for default (insert.xml) content
     *
     * @access  public
     * @param   string  $gadget   Get gadget name from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultChecksums($gadget)
    {
		if ($gadget == 'Blog') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$parents = $this->GetCategories();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[blog_category]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddBlogCategory', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}

				}
				$posts = $this->GetEntriesIDInCategory($parent['id']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				foreach ($posts as $post) {
					$post1 = $this->GetEntry($post['id']);
					if (Jaws_Error::IsError($post1)) {
						return false;
					}
					if (empty($post1['checksum']) || is_null($post1['checksum']) || strpos($post1['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post1['id'];
						$params['checksum'] 	= $post1['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[blog]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddBlog', $post1['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}

						$posts2 = $model->GetAllPostsOfBlog($post1['id'], true);
						if (Jaws_Error::IsError($posts2)) {
							return false;
						}
						foreach ($posts2 as $post2) {
							if (empty($post2['checksum']) || is_null($post2['checksum']) || strpos($post2['checksum'], ':') === false) {
								$params               	= array();
								$params['id'] 			= $post2['id'];
								$params['checksum'] 	= $post2['id'].':'.$config_key;
								
								$sql = '
									UPDATE [[blog_posts]] SET
										[checksum] = {checksum}
									WHERE [id] = {id}';

								$result = $GLOBALS['db']->query($sql, $params);
								if (Jaws_Error::IsError($result)) {
									$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
									return false;
								}

								// Let everyone know it has been added
								$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
								$res = $GLOBALS['app']->Shouter->Shout('onAddBlogPost', $post2['id']);
								if (Jaws_Error::IsError($res) || !$res) {
									return $res;
								}
							}
						}
					}
				}
			}
		}
		return true;
    }
}