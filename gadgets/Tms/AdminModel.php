<?php
/**
 * TMS (Theme Management System) Gadget
 *
 * @category   GadgetModel
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'gadgets/Tms/Model.php';

class TmsAdminModel extends TmsModel
{
    /**
     * Installs the gadget
     *
     * @access  public
     * @return  boolean True on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA), _t('TMS_NAME'));
        }

        $new_dirs = array();
        $new_dirs[] = JAWS_DATA. 'themes';
        $new_dirs[] = JAWS_DATA. 'themes'. DIRECTORY_SEPARATOR. 'repository';
        $new_dirs[] = JAWS_DATA. 'themes'. DIRECTORY_SEPARATOR. 'repository'. DIRECTORY_SEPARATOR. 'down';
        $new_dirs[] = JAWS_DATA. 'themes'. DIRECTORY_SEPARATOR. 'repository'. DIRECTORY_SEPARATOR. 'up';
        $new_dirs[] = JAWS_DATA. 'themes'. DIRECTORY_SEPARATOR. 'repository'. DIRECTORY_SEPARATOR. 'images';
        $new_dirs[] = JAWS_DATA. 'xml';
        $new_dirs[] = JAWS_DATA. 'cache';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'tms';
        foreach ($new_dirs as $new_dir) {
            if (!Jaws_Utils::mkdir($new_dir)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('TMS_NAME'));
            }
        }

        //Ok, maybe user has data/themes dir but is not writable, Tms requires that dir
        //to be writable
        if (!Jaws_Utils::is_writable(JAWS_DATA . 'themes')) {
            return new Jaws_Error(_t('TMS_ERROR_DESTINATION_THEMES_NOT_WRITABLE'), _t('TMS_NAME'));
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

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Tms/pluggable',  'false');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Tms/share_mode', 'yes');

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onInstallTheme');   //trigger an action when we install a theme
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUninstallTheme'); //trigger an action when we uninstall a theme
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDisableTheme');   //and when we disable a theme

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
        if (version_compare($old, '0.1.1', '<')) {
            $result = $this->installSchema('schema.xml', '', "$old.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        // ACL keys.
        $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Tms/UploadTheme', 'false');

        return true;
    }

    /**
     * Installs a remote theme 
     *
     * @access  public
     * @param   string  $theme  Theme name
     * @param   int     $rep    Repository's ID
     * @return  boolean Returns true if theme could be installed or Jaws_Error on any error
     */
    function installTheme($theme, $rep)
    {
        $themeInfo = $this->getThemeInfo($theme, $rep);
        if (isset($themeInfo['file']) && !empty($themeInfo['file'])) {
            ///*
			$downloadReturn = $this->downloadTheme($themeInfo['file'], true, true);
            if (Jaws_Error::isError($downloadReturn)) {
                $GLOBALS['app']->Session->PushLastResponse($downloadReturn->getMessage(), RESPONSE_ERROR);
                return $downloadReturn;
            }
            //*/
			$GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_THEME_INSTALLED'), RESPONSE_NOTICE);
            return true;
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_THEME_DOES_NOT_EXIST', $theme), RESPONSE_ERROR);
        return new Jaws_Error(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), _t('TMS_NAME'));
    }

    /**
     * Enables a theme as the default theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @return  boolean Returns true if theme really exists (and is now the default one)
     *                  and Jaws_Error if it doesn't exists
     */
    function enableTheme($theme)
    {
        if ($this->themeExists($theme)) {
            $t = new Jaws_Template();
            $t->Load(JAWS_DATA . 'themes/' . $theme . '/layout.html', false, false);

            // Validate theme
            if (!isset($t->Blocks['layout'])) {
                $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_NO_BLOCK', $theme, 'layout'), RESPONSE_ERROR);
                return new Jaws_Error(_t('TMS_ERROR_NO_BLOCK', $theme, 'layout'), _t('TMS_NAME'));
            }
            if (!isset($t->Blocks['layout']->InnerBlock['head'])) {
                $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_NO_BLOCK', $theme, 'head'), RESPONSE_ERROR);
                return new Jaws_Error(_t('TMS_ERROR_NO_BLOCK', $theme, 'head'), _t('TMS_NAME'));
            }
            if (!isset($t->Blocks['layout']->InnerBlock['main'])) {
                $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_NO_BLOCK', $theme, 'main'), RESPONSE_ERROR);
                return new Jaws_Error(_t('TMS_ERROR_NO_BLOCK', $theme, 'main'), _t('TMS_NAME'));
            }

            // Verify blocks/Reassign gadgets
            $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');
            $sections = $model->GetLayoutSections();

            foreach ($sections as $s) {
                if (!isset($t->Blocks['layout']->InnerBlock[$s['section']])) {
                    if (isset($t->Blocks['layout']->InnerBlock[$s['section'] . '_narrow'])) {
                        $model->MoveSection($s['section'], $s['section'] . '_narrow');
                    } elseif (isset($t->Blocks['layout']->InnerBlock[$s['section'] . '_wide'])) {
                        $model->MoveSection($s['section'], $s['section'] . '_wide');
                    } else {
                        if (strpos($s['section'], '_narrow')) {
                            $clear_section = str_replace('_narrow', '', $s['section']);
                        } else {
                            $clear_section = str_replace('_wide', '', $s['section']);
                        }
                        if (isset($t->Blocks['layout']->InnerBlock[$clear_section])) {
                            $model->MoveSection($s['section'], $clear_section);
                        } else {
                            $model->MoveSection($s['section'], 'main');
                        }
                    }
                }
            }

            $GLOBALS['app']->Registry->Set('/config/theme', $theme);
            $GLOBALS['app']->Registry->Set('/config/layout', 'layout.html');
            $GLOBALS['app']->Registry->Commit('core');
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_THEME_IS_DEFAULT', $theme), RESPONSE_NOTICE);
            return true;
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), _t('TMS_NAME'));
        }
    }

    /**
     * Downloads a theme from an URI and optionally it unpacks the theme (.zip)
     *
     * @access  public
     * @param   string  $uri    URI where the theme is
     * @param   boolean $unpack Unpack theme? (false by default)
     * @param   boolean $delete Delete downloaded file 
     * @return  boolean True if:
     *                    - Theme was downloaded
     *                    - Theme was downloaded and unpacked
     *                    - Theme was downloaded, unpackaged and deleted
     *                  Jaws_Error if:
     *                    - Theme couldn't be downloaded
     *                    - Theme couldn't be downloaded and unpacked
     *                    - Theme couldn't be downloaded, unpacked and deleted
     */
    function downloadTheme($uri, $unpack = false, $delete = false)
    {
        require_once 'HTTP/Request.php';

        $httpRequest = new HTTP_Request($uri);
        $httpRequest->setMethod(HTTP_REQUEST_METHOD_GET);
        $resRequest  = $httpRequest->sendRequest();
        if (PEAR::isError($resRequest) || (int) $httpRequest->getResponseCode() <> 200) {
            return new Jaws_Error(_t('TMS_ERROR_COULD_NOT_DOWNLOAD_THEME'), _t('TMS_NAME'));
        }

        $data = $httpRequest->getResponseBody();
        //Ok, get filename 
        $filename = basename($uri);
        //and save it
        if (!Jaws_Utils::is_writable(JAWS_DATA . 'themes/repository/down')) {
			// Maybe directory is not created? Try to create directory tree...
			$new_dirs = array();
			$new_dirs[] = JAWS_DATA. 'themes';
			$new_dirs[] = JAWS_DATA. 'themes'. DIRECTORY_SEPARATOR. 'repository';
			$new_dirs[] = JAWS_DATA. 'themes'. DIRECTORY_SEPARATOR. 'repository'. DIRECTORY_SEPARATOR. 'down';
			foreach ($new_dirs as $new_dir) {
				if (!file_exists($new_dir)) {
					if (!Jaws_Utils::mkdir($new_dir)) {
						return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('TMS_NAME'));
					}
				}
			}
			if (!Jaws_Utils::is_writable(JAWS_DATA . 'themes/repository/down')) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA . 'themes/repository/down'),
                                 _t('TMS_NAME'));
			}
		}

        $filename = JAWS_DATA . 'themes/repository/down/'.$filename;
        //File exists?
        if (file_exists($filename)) {
            //Delete the last 7 chars (.zip extension)
            $filename   = substr($filename, 0, -3);
            //Find a available filename
            $dirCounter = 1;
            while(true) {
                if (!file_exists($filename . '_' . $dirCounter . '.zip')) {
                    $filename = $filename . '_' . $dirCounter . '.zip';
                    break;
                }
                $dirCounter++;
            }
        }

        $return = file_put_contents($filename, $data);
        if ($return === false) {
            return new Jaws_Error(_t('TMS_ERROR_COULD_NOT_SAVE_THEME'), _t('TMS_NAME'));
        }

        //User asked to unpack it?
        if ($unpack === true) {
            $returnUnpack = $this->unpackTheme($filename, true);
            if ($returnUnpack === true) {
                if ($delete === true) {
                    @unlink($filename);
                }
            }
        }

        return true;
    }

    /**
     * Unpacks a theme (.zip)
     *
     * @access  public
     * @param   string  $filename  Theme filename
     * @param   boolean $overwrite Overwrite files (if a file can't be overwriten it
     *                             will be escaped)
     * @return  boolean True if:
     *                   - File could be unpacked
     *                  Jaws_Error if:
     *                   - File could not be unpacked
     */
    function unpackTheme($filename, $overwrite = false)
    {
        //Hm.. file doesn't exist..
        if (!file_exists($filename)) {
            //Maybe its only the theme name
            if (substr($filename, -3) !== '.zip') {
                //$filename doesn't include the .zip
                $filename.= '.zip';
            }
            $filename = JAWS_DATA . 'themes/repository/down/' . $filename;
        }

        //Get extension
        $ext = end(explode('.', $filename));
        if ($ext !== 'zip') {
            return new Jaws_Error(_t('TMS_ERROR_THEME_SHOULD_BE_ZIP'), _t('TMS_NAME'));
        }

        $filename .= '/';
        //Ok, we need the theme name with no extension (last 7 digits)
        $theme = substr(basename($filename), 0, -3);
        //We shouldn't use available or downloaded .zip files
        $theme = str_replace(array('.', '/'), '', $theme);
        if ($theme == 'repository') {
            return new Jaws_Error(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), _t('TMS_NAME'));
            
        }

        //Theme destination
        $tdest = JAWS_DATA . 'themes/' . $theme;
        //Theme already exists?
        if (is_dir($tdest)) {
            //User asked to overwrite it?
            if ($overwrite === true) {
                if (!Jaws_Utils::is_writable($tdest)) {
                    return new Jaws_Error(_t('TMS_ERROR_DESTINATION_NOT_WRITABLE', $tdest), _t('TMS_NAME'));
                }
            } else {
                return new Jaws_Error(_t('TMS_ERROR_DESTINATION_EXISTS_NOT_OVERWRITE', $theme), _t('TMS_NAME'));
            }
        } else {
            //Ok, theme dir doesn't exists, lets see if we have write-access to themes/
            if (!Jaws_Utils::is_writable(JAWS_DATA . 'themes/')) {
                return new Jaws_Error(_t('TMS_ERROR_DESTINATION_THEMES_NOT_WRITABLE'), _t('TMS_NAME'));
            }
            if (!Jaws_Utils::mkdir($tdest)) {
                return new Jaws_Error(_t('TMS_ERROR_THEME_NOT_CREATED', $theme), _t('TMS_NAME'));
            }
        }

        require_once 'File/Archive.php';
        $result = File_Archive::extract($filename, $tdest);
        if (PEAR::isError($result)) {
            return new Jaws_Error(_t('TMS_ERROR_THEME_COULD_NOT_BE_READED'), _t('TMS_NAME'));
        }

        return true;
    }

    /**
     * Makes a .zip of a theme located in themes/ 
     *
     * @access  public
     * @return  boolean Returns true if:
     *                    - Theme exists
     *                    - Theme exists and could be packed
     *                  Returns false if:
     *                    - Theme doesn't exist
     *                    - Theme doesn't exists and couldn't be packed
     */
    function packTheme($theme)
    {
        $theme = str_replace(array('.', '/'), '', $theme);
        if ($theme == 'repository') {
            return new Jaws_Error(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), _t('TMS_NAME'));
        }

        $themeSource = JAWS_DATA . 'themes/' . $theme;
        
        if (!is_dir($themeSource)) {
            return new Jaws_Error(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), _t('TMS_NAME'));
        }

        if (!Jaws_Utils::is_writable(JAWS_DATA . 'themes/repository/up')) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA . 'themes/repository/up'),
                                 _t('TMS_NAME'));
        }

        $themeDest = JAWS_DATA . 'themes/repository/up/' . $theme . '.zip';
        //If file exists.. delete it
        if (file_exists($themeDest)) {
            unlink($themeDest);
        }

        require_once 'File/Archive.php';
        $res = File_Archive::extract(
                                     File_Archive::read($themeSource),
                                     File_Archive::toArchive(
                                                             $themeDest,
                                                             File_Archive::toFiles()
                                                             )
                                     );

        if (PEAR::isError($res)) {
            return new Jaws_Error(_t('TMS_ERROR_COULD_NOT_PACK_THEME'), _t('TMS_NAME'));
        }

        //Copy image to repository/images
        if (file_exists($themeSource . '/example.png')) {
            @copy($themeSource . '/example.png',
                 JAWS_DATA . 'themes/repository/images/' . $theme . '.png');
            Jaws_Utils::chmod(JAWS_DATA . 'themes/repository/images/' . $theme . '.png');
        }
        Jaws_Utils::chmod($themeDest);

        return true;
    }

    /**
     * Add/Update the authors of a theme
     *
     * @access  public
     * @param   array   $authors      Theme authors (each item should have the name and email 
     *                                indexes)
     * @param   string  $theme        Theme name
     * @param   boolean $update       Update authors?
     * @return  boolean Success/Failure
     */
    function updateThemeAuthors($authors, $theme, $update = false) 
    {
        //Get the theme id..
        $id  = $this->isThemeShared($theme);
        if ($id === false) {
            return false;
        }
        $sql = 'DELETE FROM [[tms_authors]] 
                WHERE [theme_id] = {id}';
        $rs  = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::isError($rs)) {
            return false;
        }
        //Ok, add each author..
        if (is_array($authors)) {
            foreach($authors as $author) {
                $params = array();
                $params['name']     = $author[1];
                $params['email']    = $author[0];
                $params['theme_id'] = $id;
                $sql    = '
                      INSERT INTO [[tms_authors]]
                      VALUES ({theme_id}, {name}, {email})';
                $rs  = $GLOBALS['db']->query($sql, $params);
            }
        }
        return true;
    }

    /**
     * Share a theme 
     *
     * @access  public
     * @param   string  $theme        Theme name to share, if doesn't exists in available/ 
     *                                directory then we try to create it
     * @param   boolean $forceRepack  If we should try to repack the theme if it already exists
     * @return  boolean Returns true if:
     *                   - Theme exists
     *                   - Theme exists (already in a .zip)
     *                   - Theme could be packed
     *                   - Theme exists (in a .zip) and could be added to the DB
     *                  Returns Jaws_Error if:
     *                   - Theme doesn't exists in themes/ and available
     *                   - Theme exists in themes/ but couldn't be packed
     *                   - Theme could not be added to the DB
     */
    function shareTheme($theme, $forceRepack = false)
    {
        $theme = str_replace(array('.', '/'), '', $theme);
        if ($theme == 'repository') {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), _t('TMS_NAME'));
        }

        //Theme should exists in themes/
        $themeSource = JAWS_DATA . 'themes/' . $theme;
        //Theme exists?
        if (!$this->themeExists($theme)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_THEME_DOES_NOT_EXIST', $theme), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), _t('TMS_NAME'));
        }

        $themeDest   = JAWS_DATA . 'themes/repository/up/' . $theme . '.zip';

        $packTheme = false;
        if ($forceRepack == true) {
            $packTheme = true;
        } else {
            if (!file_exists($themeDest)) {
                $forceRepack = true;
            }
        }

        if ($packTheme === true) {
            $resPack = $this->packTheme($theme);
            if (Jaws_Error::isError($resPack)) {
                $GLOBALS['app']->Session->PushLastResponse($resPack->getMessage(), RESPONSE_ERROR);
                return $resPack;
            }
        }

        //Is it shared?
        //Get theme information
        $themeInfo = $this->getThemeInfo($theme);

        //Get pack information
        $params = array();
        $params['theme']       = $theme;
        $params['description'] = (empty($themeInfo['desc']))  ? '' : $themeInfo['desc'];
        $params['now']         = $GLOBALS['db']->Date();
        //Isn't shared
        $executeSql        = false;
        $update            = false;
        if (!$this->isThemeShared($theme)) {
            $executeSql = true;
            $sql = '
                INSERT INTO [[tms_themes]]
                    ([theme], [description], [updatetime])
                VALUES
                    ({theme}, {description}, {now})';
        } else if ($forceRepack === true) {
            $executeSql = true;
            $update     = true;
            $sql = '
                UPDATE [[tms_themes]] SET
                    [description]  = {description},
                    [updatetime]   = {now}
                WHERE
                    [theme] = {theme}';
        }

        if ($executeSql === true) {
            $res  = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
                return new Jaws_Error(_t('TMS_ERROR_CANT_SHARE_THEME'), _t('TMS_NAME'));
            }

            //Update authors..
            $this->updateThemeAuthors($themeInfo['authors'], $theme, $update);
            $this->makeRSS(true);
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_THEME_SHARED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a local theme
     *
     * @access  public
     * @param   string  $theme
     * @return  boolean Returns true if:
     *                   - We have write-access, theme is not the default one and we 
     *                     could delete it
     *                  Returns Jaws_Error if:
     *                   - We didn't have write-access, theme was the default one or 
     *                     we couldn't simple delete it
     */
    function uninstallTheme($theme)
    {
		if (substr(strtolower($theme), 0, 4) == 'http') {
			$return = false;
		} else {	
	        $themeDir = JAWS_DATA . 'themes/' . $theme;
	        $theme = str_replace(array('.', '/'), '', $theme);
	        if ($theme == 'repository') {
	            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), RESPONSE_ERROR);
	            return new Jaws_Error(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), _t('TMS_NAME'));
	        }
	
	        if (!$this->themeExists($theme)) {    
	            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), RESPONSE_ERROR);
	            return new Jaws_Error(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), _t('TMS_NAME'));
	        }
	
	        if (!Jaws_Utils::is_writable($themeDir)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', $themeDir), RESPONSE_ERROR);
	            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', $themeDir), _t('TMS_NAME'));
	        }
	
	        if ($GLOBALS['app']->Registry->Get('/config/theme') == $theme) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_CANT_UNINSTALL_THEME_IS_DEFAULT', $theme), RESPONSE_ERROR);
	            return new Jaws_Error(_t('TMS_ERROR_CANT_UNINSTALL_THEME_IS_DEFAULT', $theme), _t('TMS_NAME'));
	        }
	
	        $return = Jaws_Utils::Delete($themeDir);
        }
		if ($return) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_THEME_UNINSTALLED'), RESPONSE_NOTICE);
            return true;
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_CANT_UNINSTALL_THEME', $theme), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_CANT_UNINSTALL_THEME', $theme), _t('TMS_NAME'));
        }
    }

    /**
     * Un-shares a theme (deletes from DB and repository/up/ directory)
     *
     * @access  public
     * @param   string  $theme        Theme name to un-share
     * @return  boolean Returns true if:
     *                   - Theme could be deleted from DB (nor FS)
     *                  Returns Jaws_Error if:
     *                   - Theme could not be deleted from DB (nor FS)
     */
    function unshareTheme($theme)
    {
        $theme = str_replace(array('.', '/'), '', $theme);
        if ($theme == 'repository') {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_INVALID_THEME_NAME', $theme), _t('TMS_NAME'));
        }

        $themeDest   = JAWS_DATA . 'themes/repository/up/' . $theme . '.zip';

        if (file_exists($themeDest)) {
            if (Jaws_Utils::is_writable($themeDest)) {
                unlink($themeDest);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_SHARED_THEME_UNWRITABLE', $theme), RESPONSE_ERROR);
                return new Jaws_Error(_t('TMS_ERROR_SHARED_THEME_UNWRITABLE', $theme), _t('TMS_NAME'));
            }
        }

        $params            = array();
        $params['theme']   = $theme;
        //Isn't shared
        $sql = '
                 DELETE FROM [[tms_themes]]
                 WHERE
                  [theme] = {theme}';

        $res  = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_CANT_UNSHARE_THEME'), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_CANT_UNSHARE_THEME'), _t('TMS_NAME'));
        }
        $this->makeRSS(true);
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_THEME_UNSHARED'), RESPONSE_NOTICE);

        return true;
    }

    /**
     * Adds a new repository
     *
     * @access  public
     * @param   string  $name   Repository name
     * @param   string  $url    Repository Feed URL
     * @return  boolean Returns true if:
     *                   - Repository was added
     *                  Returns Jaws_Error if:
     *                   - Repository could not be added
     */
    function addRepository($name, $url)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $params         = array();
        $params['name'] = $xss->parse($name);
        $params['url']  = $xss->parse($url);

        $sql = '
           INSERT INTO [[tms_repositories]]
            ([name], [url])
           VALUES
            ({name},{url})';

        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_CANT_ADD_REPOSITORY'), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_CANT_ADD_REPOSITORY'), _t('TMS_NAME'));
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_REPOSITORY_ADDED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Updates a repository
     *
     * @access  public
     * @param   string  $id     Repository ID
     * @param   string  $name   Repository name
     * @param   string  $url    Repository Feed URL
     * @return  boolean Returns true if:
     *                   - Repository was updated
     *                  Returns Jaws_Error if:
     *                   - Repository could not be updated
     */
    function updateRepository($id, $name, $url)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $params         = array();
        $params['id']   = $id;
        $params['name'] = $xss->parse($name);
        $params['url']  = $xss->parse($url);

        $sql = '
           UPDATE [[tms_repositories]]
           SET 
            [name] = {name},
            [url]  = {url}
           WHERE
            [id]   = {id}';

        $res  = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_CANT_UPDATE_REPOSITORY'), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_CANT_UPDATE_REPOSITORY'), _t('TMS_NAME'));
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_REPOSITORY_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a repository
     *
     * @access  public
     * @param   string  $id     Repository ID
     * @return  boolean Returns true if:
     *                   - Repository was deleted
     *                  Returns Jaws_Error if:
     *                   - Repository could not be deleted
     */
    function deleteRepository($id)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $params         = array();
        $params['id']   = $id;
        
        $sql = '
           DELETE FROM [[tms_repositories]]
           WHERE
            [id]  = {id}';

        $res  = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_ERROR_CANT_DELETE_REPOSITORY'), RESPONSE_ERROR);
            return new Jaws_Error(_t('TMS_ERROR_CANT_DELETE_REPOSITORY'), _t('TMS_NAME'));
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_REPOSITORY_DELETED'), RESPONSE_NOTICE);
        return true;
    }

     /**
     * Save gadget settings
     *
     * @access  public
     * @param   string  $shareThemes  Share themes? (a string: true or false)
     * @return  array   Response (notice or error)
     */
    function saveSettings($shareThemes)
    {
        if (in_array($shareThemes, array('yes', 'no'))) {
            $GLOBALS['app']->Registry->Set('/gadgets/Tms/share_mode', $shareThemes);
            $GLOBALS['app']->Registry->Commit('Tms');
        } 
        $GLOBALS['app']->Session->PushLastResponse(_t('TMS_RESPONSE_SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }
}
