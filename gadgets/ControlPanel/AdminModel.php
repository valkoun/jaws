<?php
/**
 * ControlPanel Core Gadget
 *
 * @category   GadgetModel
 * @package    ControlPanel
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
 
ini_set("memory_limit","512M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","300");

class ControlPanelAdminModel extends Jaws_Model
{
    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/ControlPanel/pluggable', 'false');
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
        $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/ControlPanel/DatabaseBackups', 'false');

        return true;
    }
    
	/**
     * Unpacks a data backup (.zip)
     *
     * @access  public
     * @param   string  $filename  Data Backup filename
     * @param   boolean $overwrite Overwrite files (if a file can't be overwriten it
     *                             will be escaped)
     * @return  boolean True if:
     *                   - File could be unpacked
     *                  Jaws_Error if:
     *                   - File could not be unpacked
     */
    function unpackData($filename)
    {
		ignore_user_abort(true); 
        set_time_limit(0);
		//Hm.. file doesn't exist..
        if (!file_exists($filename)) {
            //Maybe its only the archive name
            if (substr(strtolower($filename), -3) != 'zip') {
                //$filename doesn't include the .zip
                $filename.= '.zip';
            }
            $filename = JAWS_DATA . 'db/'.$filename;
        }

		//Get extension
        $ext = end(explode('.', strtolower($filename)));
		if ($ext != 'zip') {
            return new Jaws_Error(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), _t('CONTROLPANEL_NAME'));
        }

        //Ok, we need the archive name with no extension (last 4 digits)
        $archive = substr(basename($filename), 0, -4);
		//We shouldn't use available or downloaded .zip files
        $archive = str_replace(array('.', '/'), '', $archive);
		$archive_schema = $archive.'-schema';
        //archive destination
        $tdest = JAWS_DATA;

		$jaws_data = scandir(JAWS_DATA);
		foreach($jaws_data as $file) {
			if ($file != '.' && $file != '..') {
				if (is_dir(JAWS_DATA . $file) && (substr($file, 0, 5) == date("Y").'-' || substr($file, 0, 5) == (date("Y")-1).'-')) {
					if (!Jaws_Utils::Delete(JAWS_DATA . $file, true, true)) {
						return new Jaws_Error("Can't delete ".JAWS_DATA . $file, _t('CONTROLPANEL_NAME'));
					}				
				}				
			}
		}
		
		/*
		// Ok, archive dir doesn't exists, lets see if we have write-access to data/
		if (!Jaws_Utils::mkdir($tdest)) {
			return new Jaws_Error(_t('CONTROLPANEL_ERROR_BACKUP_NOTCREATED'), _t('CONTROLPANEL_NAME'));
		}
		*/

        /*
		require_once 'File/Archive.php';
		$result = File_Archive::extract($filename . DIRECTORY_SEPARATOR, $tdest);]
		*/
		require_once 'Archive/Zip.php';
		
		$zip = new Archive_Zip($filename);
		$result = $zip->extract(array('add_path' => $tdest .'/'));
		//var_dump($zip->errorInfo(true));
		//var_dump($result);
		$dir = scandir($tdest .'/');
		foreach($dir as $file) {
			//echo '<br />File ::: '.$tdest . '/' . $file;
			//echo '<br />Ext ::: '.substr(strtolower($file), -3);
			if ($file != '.' && $file != '..' && substr(strtolower($file), -3) == 'zip') {
				//$archived_file = str_replace($archive, '', basename($file));
				//echo '<br />New Directory ::: '.$tdest .'/' . substr($archived_file, 1, strlen($archived_file));
				$zip = new Archive_Zip($tdest . '/' . $file);
				$result = $zip->extract(array('add_path' => $tdest .'/'));
				//var_dump($zip->errorInfo(true));
				//var_dump($result);
				if (PEAR::isError($result)) {
					return new Jaws_Error($result->GetMessage(), _t('CONTROLPANEL_NAME'));
				} else {
					if (file_exists($tdest . '/' . $file) && !Jaws_Utils::Delete($tdest . '/' . $file, true, true)) {
						return new Jaws_Error("Can't delete ".$tdest . '/' . $file, _t('CONTROLPANEL_NAME'));
					}
				}
			}
		}
		if (PEAR::isError($result)) {
			return new Jaws_Error($result->GetMessage(), _t('CONTROLPANEL_NAME'));
			//return new Jaws_Error(_t('CONTROLPANEL_ERROR_CANT_READ_BACKUP_DIR'), _t('CONTROLPANEL_NAME'));
        }
		
        // Make sure we don't keep any data/cache/apps|registry|acl|tms stuff
        $path = JAWS_DATA . 'cache';
        if (file_exists($path) && !Jaws_Utils::Delete($path, true, true)) {
            return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
        }
		
		// Create directories
		if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA), _t('CONTROLPANEL_NAME'));
        }
        $new_dirs = array();
        $new_dirs[] = JAWS_DATA. 'cache';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'acl';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'acl'. DIRECTORY_SEPARATOR. 'gadgets';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'acl'. DIRECTORY_SEPARATOR. 'plugins';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'addressprotector';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'apps';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'images';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'registry';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'registry'. DIRECTORY_SEPARATOR. 'gadgets';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'registry'. DIRECTORY_SEPARATOR. 'plugins';
        $new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'tms';
        foreach ($new_dirs as $new_dir) {
            if (!Jaws_Utils::mkdir($new_dir)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('CONTROLPANEL_NAME'));
            }
        }

		// Restore the DB schema
		$dbbackupdir = JAWS_DATA . 'dbbackup/';
		if (!is_dir($dbbackupdir)) {
			return new Jaws_Error(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), _t('CONTROLPANEL_NAME'));
		}


		// DB structure(s)
		if (file_exists($dbbackupdir.$archive_schema.'.xml') && Jaws_Utils::is_writable($dbbackupdir)) {
			// Remove default variables
			$schema_content = file_get_contents($dbbackupdir.$archive_schema.'.xml');
			while (strpos($schema_content, "<default>CURRENT_") !== false) {
				$inputStr = $schema_content;
				$delimeterLeft = "<default>CURRENT_";
				$delimeterRight = "</notnull>";
				$posLeft = strpos($inputStr, $delimeterLeft);
				$posRight = strpos($inputStr, $delimeterRight, $posLeft+strlen($delimeterLeft));
				$default_variable = substr($inputStr, $posLeft, (($posRight-$posLeft)+strlen($delimeterRight)));
				$schema_content = str_replace($default_variable, '<default><default>'."\n".'<notnull>false</notnull>', $schema_content);
			}
			$result = file_put_contents($dbbackupdir.$archive_schema.'.xml', $schema_content);
			if (!$result) {
				return new Jaws_Error("Could not remove variables from ".$archive_schema.".xml", 'SQL Schema', JAWS_ERROR_ERROR);
			}
			$install = $GLOBALS['db']->installSchema($dbbackupdir.$archive_schema.'.xml');
			if (Jaws_Error::IsError($install)) {
				return $install;
			}
		}
		
		// DB data
		if (file_exists($dbbackupdir.$archive.'.xml')) {
			$result = $GLOBALS['db']->installSchema($dbbackupdir.$archive.'.xml', array(), $dbbackupdir.$archive_schema.'.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
		$path = JAWS_DATA . 'dbbackup';
        if (file_exists($path) && !Jaws_Utils::Delete($path, true, true)) {
            return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
        }
		//exit;
		return true;
    }

    /**
     * Makes a .zip of the data directory 
     *
     * @access  public
     * @return  boolean Returns true if:
     *                    - Data exists
     *                    - Data exists and could be packed
     *                  Returns false if:
     *                    - Data doesn't exist
     *                    - Data doesn't exists and couldn't be packed
     */
    function packData($archive, $num = 1)
    {
		ignore_user_abort(true); 
        set_time_limit(0);
        $archive = str_replace(array('.', '/'), '', $archive);
        $archiveSource = JAWS_DATA;
        
        if (!is_dir($archiveSource)) {
            return new Jaws_Error(_t('CONTROLPANEL_ERROR_DATADIR_NOT_FOUND'), _t('CONTROLPANEL_NAME'));
        }

        if (!Jaws_Utils::is_writable(JAWS_DATA . 'db')) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA . 'db'),
                                 _t('CONTROLPANEL_NAME'));
        }
        						
		require_once JAWS_PATH . 'include/Jaws/FileManagement.php';
		$archiveDest = JAWS_DATA . 'db/' . $archive;
		$archived_dirs = array();
		$archived_files = array();
		$archived_files_dirs = array();
		$jaws_data = scandir(JAWS_DATA);
		foreach($jaws_data as $file) {
			if ($file != '.' && $file != '..') {
				if (!is_dir(JAWS_DATA . $file)) {
					if (Jaws_FileManagement::FullCopy(JAWS_DATA . $file, JAWS_DATA . 'db/' . $file)) {
						$archived_files[] = JAWS_DATA . 'db/' . $file;
					}
				} else if (is_dir(JAWS_DATA . $file) && $file != 'db' && $file != 'logs' && $file != 'cache' && $file != 'files') {
					if (substr($file, 0, 5) == date("Y").'-' || substr($file, 0, 5) == (date("Y")-1).'-') {
						if (!Jaws_Utils::Delete(JAWS_DATA . $file, true, true)) {
							return new Jaws_Error("Can't delete ".JAWS_DATA . $file, _t('CONTROLPANEL_NAME'));
						}
					} else {
						$archived_dirs[$file] = JAWS_DATA . $file;
					}
				}				
			}
		}
		$max_chunk_size = 25;
		if (file_exists(JAWS_DATA . 'files')) {
			$size = Jaws_Utils::GetFileSize(JAWS_DATA . 'files', true, $max_chunk_size);
			$filesize = round(($size['filesize'] / 1048576),2);
			$chunks = $size['chunks'];
			foreach ($chunks as $chunk_key => $chunk_val) {
				$chunk_name = str_replace(DIRECTORY_SEPARATOR, '-', str_replace(JAWS_DATA, '', $chunk_key));
				if (count($chunk_val) <= 0) {
					$archived_files_dirs[$chunk_name] = $chunk_key;
				} else {
					$c = 0;
					foreach ($chunk_val as $chunk) {
						$chunk_num = ($c < 10 ? '00'.$c : ($c < 100 ? '0'.$c : $c));
						$archived_files_dirs[$chunk_name.'-'.$chunk_num] = $chunk;
						$c++;
					}
				}
			}
		}

		require_once 'Archive/Zip.php';
		$zip_paths = array();
		$count_archived_total = count($archived_dirs) + count($archived_files_dirs);
		reset($archived_dirs);
		reset($archived_files_dirs);
		
		if ($num == $count_archived_total) {
			// Delete all cache directories
			$path = JAWS_DATA . 'cache/acl';
			if (file_exists($path) && !Jaws_Utils::Delete($path, false)) {
				return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
			}
			
			$path = JAWS_DATA . 'cache/apps';
			if (file_exists($path) && !Jaws_Utils::Delete($path, false)) {
				return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
			}

			$path = JAWS_DATA . 'cache/tms';
			if (file_exists($path) && !Jaws_Utils::Delete($path, false)) {
				return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
			}
			$path = JAWS_DATA . 'cache/registry';
			if (file_exists($path) && !Jaws_Utils::Delete($path, false)) {
				return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
			}

			$path = JAWS_DATA . 'cache';
			if (file_exists($path) && !Jaws_Utils::Delete($path, false)) {
				return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
			}
			
			$path = JAWS_DATA . 'logs';
			if (file_exists($path) && !Jaws_Utils::Delete($path, false)) {
				return new Jaws_Error("Can't delete ".$path, _t('CONTROLPANEL_NAME'));
			}
		
			$new_dirs = array();
			$new_dirs[] = JAWS_DATA. 'logs';
			$new_dirs[] = JAWS_DATA. 'cache';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'acl';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'acl'. DIRECTORY_SEPARATOR. 'gadgets';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'acl'. DIRECTORY_SEPARATOR. 'plugins';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'addressprotector';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'apps';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'images';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'registry';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'registry'. DIRECTORY_SEPARATOR. 'gadgets';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'registry'. DIRECTORY_SEPARATOR. 'plugins';
			$new_dirs[] = JAWS_DATA. 'cache'. DIRECTORY_SEPARATOR. 'tms';
			foreach ($new_dirs as $new_dir) {
				if (!Jaws_Utils::mkdir($new_dir)) {
					return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('CONTROLPANEL_NAME'));
				}
			}
		}
		
		$i = 1;
		foreach ($archived_dirs as $name => $path) {
			$zip_paths[] = $archiveDest.'-'.$name.'.zip';
			if ($num == 1) {
				if ($i == 1) {
					echo "Backing up ".$num." of ".$count_archived_total."<br />\n";
				}
				$zip = new Archive_Zip($archiveDest.'-'.$name.'.zip');
				$res = $zip->create($path, array('remove_path' => JAWS_DATA));
			}
			$i++;
		}
		foreach ($archived_files_dirs as $name => $path) {
			$zip_paths[] = $archiveDest.'-'.$name.'.zip';
			if ($num == $i) {
				echo "Backing up ".$name." (".$num." of ".$count_archived_total.")<br />\n";
				$zip = new Archive_Zip($archiveDest.'-'.$name.'.zip');
				$res = $zip->create($path, array('remove_path' => JAWS_DATA));
			}
			$i++;
		}
		if ($num == $count_archived_total) {
			$zip = new Archive_Zip($archiveDest.'.zip');
			$res = $zip->create(array_merge($zip_paths, $archived_files), array('remove_path' => JAWS_DATA . 'db' . DIRECTORY_SEPARATOR));
			
			/*
			$res = File_Archive::extract(
				File_Archive::filter(
					File_Archive::predNot(
						File_Archive::predEreg('db'. DIRECTORY_SEPARATOR)
					),
					File_Archive::read($archiveSource)
				),
				File_Archive::toArchive(
					$archiveDest,
					File_Archive::toFiles()
				)
			);
			*/		

			if (PEAR::isError($res)) {
				return new Jaws_Error(_t('CONTROLPANEL_ERROR_BACKUP_NOTCREATED'), _t('CONTROLPANEL_NAME'));
			}
						
			// Delete schema files since they were included in the backup
			$archiveBack = JAWS_DATA . 'dbbackup';
			if (file_exists($archiveBack) && !Jaws_Utils::Delete($archiveBack, true, true)) {
				return new Jaws_Error("Can't delete ".$archiveBack, _t('CONTROLPANEL_NAME'));
			}

			Jaws_Utils::chmod($archiveDest);

			// Delete all old backups in db directory
			$dbdir = JAWS_DATA . 'db/';
			if (!is_dir($dbdir)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), RESPONSE_ERROR);
				return false;
			}
			$dir = scandir($dbdir);

			// Which backups to keep?
			$tokeep = array();
			$tokeep[]  = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			for ($i=1; $i<7; $i++) {
				$tokeep[]  = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$i, date("Y")));
			}
			for ($i=0; $i<6; $i++) {
				$tokeep[]  = date("Y-m-d", mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
			}
			
			foreach($dir as $file) {
				if (
					$file != '.' && $file != '..' && ((substr(strtolower($file), -3) == 'zip' && 
					(!in_array(substr($file, 0, 10), $tokeep) || 
					(substr($file, 0, strlen(basename($archiveDest))) == basename($archiveDest) && $file != basename($archiveDest).'.zip'))) || 
					substr(strtolower($file), -3) != 'zip')
				) {
					if (!Jaws_Utils::Delete($dbdir . $file, true, true)) {
						return new Jaws_Error("Can't delete ".$dbdir . $file, _t('CONTROLPANEL_NAME'));
					}
				}
			}
        }
		return ($num == $count_archived_total ? true : ($num+1));
    }
}