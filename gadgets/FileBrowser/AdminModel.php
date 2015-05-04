<?php
/**
 * Filebrowser Admin Gadget
 *
 * @category   GadgetModel
 * @package    FileBrowser
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/FileBrowser/Model.php';

class FileBrowserAdminModel extends FileBrowserModel
{
    /**
     * Install the gadget
     *
     * @access  public
     * @return  boolean True on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA));
        }

        $new_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('FILEBROWSER_NAME'));
        }

        $new_css_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR;
		if (!Jaws_Utils::mkdir($new_css_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_css_dir), _t('FILEBROWSER_NAME'));
        }

		$new_thumb_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR .'thumb';
		if (!Jaws_Utils::mkdir($new_thumb_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_thumb_dir), _t('FILEBROWSER_NAME'));
		}
		$new_medium_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR .'medium';
		if (!Jaws_Utils::mkdir($new_medium_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_medium_dir), _t('FILEBROWSER_NAME'));
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
		
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUploadFiles');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onCreateDir');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteFiles');
        $GLOBALS['app']->Shouter->NewShouter('FileBrowser', 'onFileBrowserCreateDir');
        $GLOBALS['app']->Shouter->NewShouter('FileBrowser', 'onFileBrowserDelete');
        $GLOBALS['app']->Shouter->NewShouter('FileBrowser', 'onFileBrowserRename');
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->NewListener('FileBrowser', 'onAddUser', 'CreateUserDir');
		$GLOBALS['app']->Listener->NewListener('FileBrowser', 'onDeleteUser', 'RemoveUserDir');

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/black_list', '.htaccess,.exe.,.dll,.php,.sh,.bash,.csh,.ssh,.py,.asp,.js,.jsb,.meta,.pl,.cgi,.scr,.msi,.vbs,.bat,.com,.pif,.cmd,.vxd,.cpl,.phtml,.php3,.php4,.php5,.phps,.mht,.mhtml');
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/frontend_avail', 'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/root_dir', 'files');
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/virtual_links', 'false');
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/order_type', 'filename, false');
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/views_limit', '0');
		if (!in_array('FileBrowser', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'FileBrowser');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',FileBrowser');
			}
		}

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
    	$result = $GLOBALS['db']->dropTable('filebrowser');
        if (Jaws_Error::IsError($result)) {
            $gName  = _t('FILEBROWSER_NAME');
            $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
            $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
            return new Jaws_Error($errMsg, $gName);
        }

		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onUploadFiles');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onCreateDir');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onDeleteFiles');
        $GLOBALS['app']->Shouter->DeleteShouter('FileBrowser', 'onFileBrowserCreateDir');
        $GLOBALS['app']->Shouter->DeleteShouter('FileBrowser', 'onFileBrowserDelete');
        $GLOBALS['app']->Shouter->DeleteShouter('FileBrowser', 'onFileBrowserRename');

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('FileBrowser', 'CreateUserDir');
        $GLOBALS['app']->Listener->DeleteListener('FileBrowser', 'RemoveUserDir');
        
		//registry keys.
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/FileBrowser/black_list');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/FileBrowser/frontend_avail');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/FileBrowser/root_dir');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/FileBrowser/virtual_links');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/FileBrowser/order_type');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/FileBrowser/views_limit');
		if (in_array('FileBrowser', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'FileBrowser') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',FileBrowser', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}

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
        if (version_compare($old, '0.7.0', '<')) {
            $result = $GLOBALS['db']->dropTable('filebrowser_communities');
            if (Jaws_Error::IsError($result)) {
                // do nothing
            }

            // Registry keys.
        	$GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/black_list', '.htaccess,.exe.,.dll,.php,.sh,.bash,.csh,.ssh,.py,.asp,.js,.jsb,.meta,.pl,.cgi,.scr,.msi,.vbs,.bat,.com,.pif,.cmd,.vxd,.cpl,.phtml,.php3,.php4,.php5,.phps,.mht,.mhtml');
            $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/frontend_avail', 'true');
			
			if (!in_array('FileBrowser', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
				if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
					$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'FileBrowser');
				} else {
					$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',FileBrowser');
				}
			}        
        }

        if (version_compare($old, '0.7.1', '<')) {
            $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/root_dir', 'files');
        }

        if (version_compare($old, '0.8.0', '<')) {
            $result = $this->installSchema('schema.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            // ACL keys
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/FileBrowser/ManageFiles',       'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/FileBrowser/UploadFiles',       'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/FileBrowser/ManageDirectories', 'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/FileBrowser/OutputAccess',      'true');
            /*
			$GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/AddFile');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/RenameFile');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/DeleteFile');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/AddDir');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/RenameDir');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/DeleteDir');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FileBrowser/ShareDir');
			*/
			
            //Registry key
            $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/virtual_links', 'false');
            $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/order_type', 'filename, false');

        }

        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUploadFiles');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onCreateDir');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteFiles');
        $GLOBALS['app']->Shouter->NewShouter('FileBrowser', 'onFileBrowserCreateDir');
        $GLOBALS['app']->Shouter->NewShouter('FileBrowser', 'onFileBrowserDelete');
        $GLOBALS['app']->Shouter->NewShouter('FileBrowser', 'onFileBrowserRename');
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->NewListener('FileBrowser', 'onAddUser', 'CreateUserDir');
		$GLOBALS['app']->Listener->NewListener('FileBrowser', 'onDeleteUser', 'RemoveUserDir');

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/root_dir', 'files');
        $GLOBALS['app']->Registry->NewKey('/gadgets/FileBrowser/views_limit', '0');

        return true;
    }

    /**
     * Add/Update file or directory information
     *
     * @access  public
     * @param   string  $path File|Directory path
     * @param   string  $file File|Directory name
     * @return  array   A list of properties of files and directories of a certain path and Jaws_Error on failure
     */
    function UpdateDBFileInfo($path, $file, $title, $description, $fast_url, $oldname = '')
    {
        if (!empty($path) && $path != '/') {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        } else {
            $path = '';
        }
        $path = str_replace('..', '', $path);

        $date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $fast_url = empty($fast_url) ? $title : $fast_url;

        $params = array();
        $params['path']        = $xss->parse($path);
        $params['file']        = $xss->parse($file);
        $params['oldname']     = empty($oldname)? $params['file'] : $xss->parse($oldname);
        $params['title']       = empty($title)? $params['file'] : $xss->parse($title);
        $params['description'] = $xss->parse($description);
        $params['now']         = $GLOBALS['db']->Date();

        $dbFile = $this->DBFileInfo($path, $params['oldname']);
        if (Jaws_Error::IsError($dbFile)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if (array_key_exists('id', $dbFile)) {
            // Update
            $fast_url = $this->GetRealFastUrl($fast_url, 'filebrowser', false);
            $params['fast_url'] = $xss->parse($fast_url);

            $sql = '
                UPDATE [[filebrowser]] SET
                    [path]         = {path},
                    [filename]     = {file},
                    [title]        = {title},
                    [description]  = {description},
                    [fast_url]     = {fast_url},
                    [updatetime]   = {now}
                WHERE
                    [path]     = {path}
                  AND
                    [filename] = {oldname}';

            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }

            $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_FILE_UPDATED', $file), RESPONSE_NOTICE);
        } else {
            //Insert
            $fast_url = $this->GetRealFastUrl($fast_url, 'filebrowser');
            $params['fast_url'] = $xss->parse($fast_url);

            $sql = '
                INSERT INTO [[filebrowser]]
                    ([path], [filename], [title], [description], [fast_url], [createtime], [updatetime])
                VALUES
                    ({path}, {file}, {title}, {description}, {fast_url}, {now}, {now})';

            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }

            $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_FILE_ADDED', $file), RESPONSE_NOTICE);
        }

        return true;
    }

    /**
     * Delete file or directory information
     *
     * @access  public
     * @param   string  $path File|Directory path
     * @param   string  $file File|Directory name
     * @return  boolean True/False
     */
    function DeleteDBFileInfo($path, $file)
    {
        if (!empty($path) && $path != '/') {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        } else {
            $path = '';
        }
        $path = str_replace('..', '', $path);

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params = array();
        $params['path'] = $xss->parse($path);
        $params['file'] = $xss->parse($file);

        $sql = '
            DELETE FROM [[filebrowser]]
            WHERE [path] = {path} AND [filename] = {file}';

        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_FILE_DELETED', $file), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Creates a directory
     *
     * @access  public
     * @param   string  $path     Where to create it
     * @param   string  $dir_name Which name
     * @return  boolean Returns true if the directory was created, if not, returns Jaws_Error
     */
    function MakeDir($path, $dir_name)
    {
        if (!empty($path) && $path != '/') {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        } else {
            $path = '';
        }
        $path = str_replace('..', '', $path);

        $dir = $this->GetFileBrowserRootDir() . $path . '/' . $dir_name;

        require_once 'File/Util.php';
        $realpath = File_Util::realpath($dir);
        $blackList = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/black_list'));
        $blackList = array_map('strtolower', $blackList);

        if (!File_Util::pathInRoot($realpath, $this->GetFileBrowserRootDir()) ||
            in_array(strtolower(basename($realpath)), $blackList) ||
            !Jaws_Utils::mkdir($realpath))
        {
            $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_ERROR_CANT_CREATE_DIRECTORY', $realpath), RESPONSE_ERROR);
            return false;
        }

		$new_thumb_dir = $realpath . DIRECTORY_SEPARATOR .'thumb';
		if (!Jaws_Utils::mkdir($new_thumb_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_thumb_dir), _t('FILEBROWSER_NAME'));
		}
		$new_medium_dir = $realpath . DIRECTORY_SEPARATOR .'medium';
		if (!Jaws_Utils::mkdir($new_medium_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_medium_dir), _t('FILEBROWSER_NAME'));
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onFileBrowserCreateDir', array('path' => $path, 'dir_name' => $realpath));
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        
        return true;
    }

    /**
     * Deletes a file or directory
     *
     * @access  public
     * @param   string  $path     Where is it
     * @param   string  $filename The name of the file
     * @return  boolean Returns true if file/directory was deleted without problems, if not, returns Jaws_Error
     */
    function Delete($path, $filename, $root_dir = null)
    {
        if (is_null($root_dir)) {
			$root_dir = $this->GetFileBrowserRootDir();
		}
		if (!empty($path) && $path != '/') {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        } else {
            $path = '';
        }
        $path = str_replace('..', '', $path);

        $file = $path . ((empty($path)? '': DIRECTORY_SEPARATOR)) . $filename;
        $filename = $root_dir . DIRECTORY_SEPARATOR . $file;
        $blackList = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/black_list'));
        $blackList = array_map('strtolower', $blackList);

        require_once 'File/Util.php';
        $realpath = File_Util::realpath($filename);
        if (!File_Util::pathInRoot($realpath, $root_dir) ||
            in_array(strtolower(basename($filename)), $blackList))
        {
            $msgError = is_dir($filename)? _t('FILEBROWSER_ERROR_CANT_DELETE_DIR', $file):
                                           _t('FILEBROWSER_ERROR_CANT_DELETE_FILE', $file);
            $GLOBALS['app']->Session->PushLastResponse($msgError, RESPONSE_ERROR);
            return false;
        }

        if (is_file($filename)) {
            $return = @unlink($filename);
            if (!$return) {
                $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_ERROR_CANT_DELETE_FILE', $file), RESPONSE_ERROR);
                return false;
            }
        } elseif (is_dir($filename)) {
            require_once JAWS_PATH . 'include/Jaws/FileManagement.php';
            $return = Jaws_FileManagement::FullRemoval($filename);
            if ($return) {
                $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_ERROR_CANT_DELETE_DIR', $file), RESPONSE_ERROR);
                return false;
            }
        }

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onFileBrowserDelete', array('path' => $path, 'filename' => $filename));
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        
		return true;
    }

    /**
     * Rename files and directories.
     *
     * @param   string  $path    Directory path to file
     * @param   string  $old     Filename to rename
     * @param   string  $new     New Filename
     * @access  public
     * @return  boolean 	Returns file if file/directory was renamed without problems, if not, returns Jaws_Error
     */
    function Rename($path, $old, $new)
    {
        if (!empty($path) && $path != '/') {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        } else {
            $path = '';
        }
        $path = str_replace('..', '', $path);
        $oldfile = $this->GetFileBrowserRootDir() . $path . '/' . $old;
        $newfile = $this->GetFileBrowserRootDir() . $path . '/' . $new;
		
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		//$old_thumb = JAWS_DATA . 'files'. DIRECTORY_SEPARATOR . $old_thumb;
        //$new_thumb = JAWS_DATA . 'files'. DIRECTORY_SEPARATOR . $new_thumb;
		$old_thumb = Jaws_Image::GetThumbPath($oldfile);
		$new_thumb = Jaws_Image::GetThumbPath($newfile);
		$old_medium = Jaws_Image::GetMediumPath($oldfile);
		$new_medium = Jaws_Image::GetMediumPath($newfile);

        require_once 'File/Util.php';
        $oldfile = File_Util::realpath($oldfile);
        $newfile = File_Util::realpath($newfile);
       	if (!is_null($old_thumb) && !is_null($new_thumb)) {
			$old_thumb = File_Util::realpath($old_thumb);
			$new_thumb = File_Util::realpath($new_thumb);
		}
        if (!is_null($old_medium) && !is_null($new_medium)) {
			$old_medium = File_Util::realpath($old_medium);
			$new_medium = File_Util::realpath($new_medium);
		}        
		$blackList = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/black_list'));
        $blackList = array_map('strtolower', $blackList);

        if (!File_Util::pathInRoot($oldfile, $this->GetFileBrowserRootDir()) ||
            !File_Util::pathInRoot($newfile, $this->GetFileBrowserRootDir()) ||
            in_array(strtolower(basename($oldfile)), $blackList) ||
            in_array(strtolower(basename($newfile)), $blackList))
        {
            $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_ERROR_CANT_RENAME', $oldfile, $newfile), RESPONSE_ERROR);
            return false;
        }

        $return = @rename($oldfile, $newfile);
        if ($return) {
	        if (!is_null($old_thumb) && !is_null($new_thumb)) {
				if (file_exists($old_thumb) && !is_dir($old_thumb)) {
					$return_thumb = @rename($old_thumb, $new_thumb);
					if (!$return_thumb) {
				        $msgError = _t('FILEBROWSER_ERROR_CANT_RENAME', $old_thumb, $new_thumb);
				        $GLOBALS['app']->Session->PushLastResponse($msgError, RESPONSE_ERROR);
				        return new Jaws_Error($msgError, _t('FILEBROWSER_NAME'));
					}
				}
			}
			
	        if (!is_null($old_medium) && !is_null($new_medium)) {
				if (file_exists($old_medium) && !is_dir($old_medium)) {
					$return_thumb = @rename($old_medium, $new_medium);
					if (!$return_thumb) {
				        $msgError = _t('FILEBROWSER_ERROR_CANT_RENAME', $old_medium, $new_medium);
				        $GLOBALS['app']->Session->PushLastResponse($msgError, RESPONSE_ERROR);
				        return new Jaws_Error($msgError, _t('FILEBROWSER_NAME'));
					}
				}
			}
			
			// Update all file references
			// FIXME: Use Event Listeners
			$gadgetInputArray = array();
			$gadgetInputArray['Calendar'] = array('calendarparent' => array('calendarparentImage'), 'calendar' => array('image'));
			$gadgetInputArray['CustomPage'] = array('pages' => array('image'), 'pages_posts' => array('image'), 'splash_panels' => array('image'));
			$gadgetInputArray['FlashGalleries'] = array('flashgalleries' => array('overlay_image', 'background_image'), 'flashgalleries_posts' => array('image'));
			$gadgetInputArray['Forms'] = array('forms' => array('image'));
			$gadgetInputArray['Maps'] = array('maps_locations' => array('image'));
			$gadgetInputArray['Properties'] = array('propertyparent' => array('propertyparentImage'), 'property' => array('image'), 'property_posts' => array('image'));
			$gadgetInputArray['Store'] = array('productparent' => array('productparentImage'), 'product' => array('image'), 'product_posts' => array('image'));
			$gadgetInputArray['Users'] = array('users' => array('image', 'logo'));
			
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$params                = array();
			$params['inputFilename']  = $oldfile;
			$params['newFilename']  = $newfile;
			foreach($gadgetInputArray as $inputKey => $inputValue) {
				//echo '<br />'.$inputKey;
				$deleteCache = false;
				foreach($inputValue as $table => $fields) {
					foreach($fields as $field) {
						$sql = '
							SELECT ['.$field.'] 
							FROM [['.$table.']] 
							WHERE ['.$field.'] = {inputFilename}';
						$types = array('text');
						$results = $GLOBALS['db']->queryAll($sql, $params, $types);
						if (Jaws_Error::IsError($results)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
						foreach($results as $result) {
							if (!empty($result["$field"])) {
								$deleteCache = true;
								$sql = '
									UPDATE [['.$table.']] SET
										['.$field.']       = {newFilename}
									WHERE ['.$field.'] = {inputFilename}';
								$res = $GLOBALS['db']->query($sql, $params);
								if (Jaws_Error::IsError($res)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								}
							}
						}
						//$GLOBALS['app']->Session->PushLastResponse('UPDATE [['.$table.']] SET ['.$field.'] = '.$newfile.' WHERE ['.$field.'] = '.$oldfile, RESPONSE_ERROR);
						//echo '<br />'.$table.':::'.$field;
					}
				}
				if ($deleteCache === true) {
					if (!$GLOBALS['app']->deleteSyntactsCacheFile(array($inputKey))) {
						Jaws_Error::Fatal("Cache file couldn't be deleted");
					}
				}
			}
				
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onFileBrowserRename', array('old' => $oldfile, 'new' => $newfile));
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
            $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_RENAMED', $oldfile, $newfile), RESPONSE_NOTICE);
            return true;
        }

        $msgError = _t('FILEBROWSER_ERROR_CANT_RENAME', $oldfile, $newfile);
        $GLOBALS['app']->Session->PushLastResponse($msgError, RESPONSE_ERROR);
        return false;
    }

    /**
     * Users (members) get their own directories.
     *
     * @category  feature
     * @access  public
     * @param   string  $param user id/directory name
     * @return  boolean Returns true if the directory was created, if not, returns Jaws_Error
     */
    function CreateUserDir($param)
    {
		$result = $this->MakeDir('users', $param);
		if (Jaws_Error::IsError($result)) {
			return $result;
		}
		$result1 = $this->MakeDir('users/'.$param, 'thumb');
		if (Jaws_Error::IsError($result1)) {
			return $result1;
		}
		$result2 = $this->MakeDir('users/'.$param, 'medium');
		if (Jaws_Error::IsError($result2)) {
			return $result2;
		}
		return true;
	}

    /**
     * Removes user directory
     *
     * @access  public
     * @param   string  $param user id/directory name
     * @return  boolean Returns true if the directory was removed, if not, returns Jaws_Error
     */
    function RemoveUserDir($param)
    {
		$path = 'users' . DIRECTORY_SEPARATOR;
		$result = $this->Delete($path, $param);
		return $result;
	}

}