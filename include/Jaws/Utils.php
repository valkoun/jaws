<?php
/**
 * Some utils functions. Random functions
 *
 * @category   JawsType
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
ini_set("memory_limit","50M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","10M");
ini_set("max_execution_time","300");

define('JAWS_OS_WIN', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
class Jaws_Utils
{
    /**
     * Change the color of a row from a given color
     *
     * @param  string  $color  Original color(so we don't return the same color)
     * @return string  New color
     * @access public
     */
    function RowColor($color)
    {
        if ($color == '#fff') {
            return '#eee';
        }

        return '#fff';
    }

    /**
     * Create a random text
     *
     * @access  public
     * @param   int     $length Random text length
     * @return  string  The random string
     */
    function RandomText($length = 8, $pronounceable = 'unpronounceable', $alphanumeric = 'alphanumeric')
    {
        include_once 'Text/Password.php';
        $word = Text_Password::create($length, $pronounceable, $alphanumeric);
        return $word;
    }

    /**
     * Convert a number in bytes, kilobytes,...
     *
     * @access  public
     * @param   int     $num
     * @return  string  The converted number in string
     */
    function FormatSize($num)
    {
        $unims = array("B", "KB", "MB", "GB", "TB");
        $i = 0;
        while ($num >= 1024) {
            $i++;
            $num = $num/1024;
        }

        return number_format($num, 2). " ". $unims[$i];
    }

    /**
     * Get base url
     *
     * @access  public
     * @param   string  $base_script base script
     * @param   string rel_url relative url
     * @return  string  url of base script
     */
    function getBaseURL($base_script = 'index.php', $rel_url = false)
    {
        static $base_urls_info;
        if (!isset($base_urls_info) || !array_key_exists($base_script, $base_urls_info)) {
            $base_urls_info = array();
            $site_url = array();
            $site_url['scheme'] = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            if (false === strpos($host, ':')) {
                $site_url['host'] = $host;
            } else {
                $site_url['host'] = substr($host, 0, strpos($host, ':'));
                $site_url['port'] = substr($host, strpos($host, ':') + 1);
            }

            $path = strip_tags($_SERVER['PHP_SELF']);
            if (false === stripos($path, $base_script)) {
                $path = strip_tags($_SERVER['SCRIPT_NAME']);
                if (false === stripos($path, $base_script)) {
                    $pInfo = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] : '';
                    $pInfo = (empty($pInfo) && isset($_SERVER['ORIG_PATH_INFO']))? $_SERVER['ORIG_PATH_INFO'] : '';
                    $pInfo = (empty($pInfo) && isset($_ENV['PATH_INFO']))? $_ENV['PATH_INFO'] : '';
                    $pInfo = (empty($pInfo) && isset($_ENV['ORIG_PATH_INFO']))? $_ENV['ORIG_PATH_INFO'] : '';
                    $pInfo = strip_tags($pInfo);
                    if (!empty($pInfo)) {
                        $path = substr($path, 0, strpos($path, $pInfo)+1);
                    }
                }
            }

            $site_url['path'] = substr($path, 0, stripos($path, $base_script)-1);
            $base_urls_info[$base_script] = $site_url;
        } else {
            $site_url = $base_urls_info[$base_script];
        }

        if ($rel_url) {
            return $site_url['path'];
        } else {
            return $site_url['scheme']. '://'.
                   $site_url['host'].
                   (isset($site_url['port'])? (':'.$site_url['port']) : '').
                   $site_url['path'];
        }
    }

    /**
     * Get request url
     *
     * @access  public
     * @param   string  $base_script base script
     * @return  string  get url without base url
     */
    function getRequestURL($base_script = 'index.php')
    {
        static $uri;

        if (!isset($uri)) {
            if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                $uri = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                $uri = $_SERVER['PHP_SELF'] . '?' .$_SERVER['QUERY_STRING'];
            } else {
                $uri = '';
            }

            $rel_base = Jaws_Utils::getBaseURL($base_script, true);
            $uri = htmlspecialchars(urldecode($uri), ENT_NOQUOTES, 'UTF-8');
            $uri = substr($uri, strlen($rel_base) + 1);
        }

        return $uri;
    }

    /**
     * relative between two path
     *
     * @access  public
     * @param   string  $path1 directory/file path
     * @param   string  $path2 directory/file path
     * @return  string  The relative path
     */
    function relative_path($path1, $path2='')
    {
        if (DIRECTORY_SEPARATOR!='/') {
            $path1 = str_replace('\\', '/', $path1);
            $path2 = str_replace('\\', '/', $path2);
        }

        //Remove starting, ending, and double / in paths
        $path1 = trim($path1,'/');
        $path2 = trim($path2,'/');
        while (substr_count($path1, '//')) $path1 = str_replace('//', '/', $path1);
        while (substr_count($path2, '//')) $path2 = str_replace('//', '/', $path2);
        //create arrays
        $arr1 = explode('/', $path1);
        if ($arr1 == array('')) $arr1 = array();
        $arr2 = explode('/', $path2);
        if ($arr2 == array('')) $arr2 = array();
        $size1 = count($arr1);
        $size2 = count($arr2);
        $path='';

        for($i=0; $i<min($size1,$size2); $i++) {
            if ($arr1[$i] != $arr2[$i]) {
                $path = '../'.$path.$arr2[$i].'/';
            }
        }
        if ($size1 > $size2) {
            for ($i = $size2; $i < $size1; $i++) {
                $path = '../'.$path;
            }
        } else {
            if ($size2 > $size1) {
                for ($i = $size1; $i < $size2; $i++) {
                    $path .= $arr2[$i].'/';
                }
            }
        }
        return $path;
    }

    /**
     * is directory writeable?
     *
     * @access  public
     * @param   string  $path directory path
     * @return  boolean True/False
     */
    function is_writable($path)
    {
        clearstatcache();
        if (!file_exists($path)) {
            return false;
        }

        /* Take care of the safe mode limitations if safe_mode=1 */
        if (ini_get('safe_mode')) {
            /* GID check */
            if (ini_get('safe_mode_gid')) {
                if (filegroup($path) == getmygid()) {
                    return (@fileperms($path) & 0020) ? is_writeable($path) : false;
                }
            } else {
                if (fileowner($path) == getmyuid()) {
                    return (@fileperms($path) & 0200) ? is_writeable($path) : false;
                }
            }
        } else {
            return is_writeable($path);
        }

        return false;
    }

    /**
     * Write a string to a file
     * @access  public
     * @see http://www.php.net/file_put_contents
     */
    function file_put_contents($file, $data, $flags = null, $resource_context = null)
    {
        $res = @file_put_contents($file, $data, $flags, $resource_context);
        if ($res !== false) {
            Jaws_Utils::chmod($file);
        }

        return $res;
    }

    /**
     * Change file/directory mode
     *
     * @access  public
     * @param   string  $path file/directory path
     * @param   integer $mode see php chmod() function
     * @return  boolean True/False
     */
    function chmod($path, $mode = null)
    {
        $result = false;
        if (is_null($mode)) {
            $php_as_owner = (function_exists('posix_getuid') && posix_getuid() === fileowner($path));
            $php_as_group = (function_exists('posix_getgid') && posix_getgid() === filegroup($path));
            if (is_dir($path)) {
                $mode = $php_as_owner? 0755 : ($php_as_group? 0775 : 0777);
            } else {
                $mode = $php_as_owner? 0644 : ($php_as_group? 0664 : 0666);
            }
        }

        $mask = umask(0);
        /* Take care of the safe mode limitations if safe_mode=1 */
        if (ini_get('safe_mode')) {
            /* GID check */
            if (ini_get('safe_mode_gid')) {
                if (filegroup($path) == getmygid()) {
                    $result = @chmod($path, $mode);
                }
            } else {
                if (fileowner($path) == getmyuid()) {
                    $result = @chmod($path, $mode);
                }
            }
        } else {
            $result = @chmod($path, $mode);
        }

        umask($mask);
        return $result;
    }

    /**
     * Make directory
     *
     * @access  public
     * @param   string  $path directory path
     * @param   integer $mode see php chmod() function
     * @return  boolean True/False
     */
    function mkdir($path, $recursive = 0, $mode = null)
    {
        $result = true;
        if (!file_exists($path) || !is_dir($path)) {
            if ($recursive && !file_exists(dirname($path))) {
                $recursive--;
                Jaws_Utils::mkdir(dirname($path), $recursive, $mode);
            }
            $result = @mkdir($path);
        }

        if ($result) {
            Jaws_Utils::chmod($path, $mode);
        }

        return $result;
    }

    /**
     * Delete directories and files
     *
     * @access  public
     * @param   boolean $dirs_include
     * @param   boolean $self_include
     * @see http://www.php.net/rmdir & http://www.php.net/unlink
     * @TODO 	Move to Jaws_FileManagement
     */
    function Delete($path, $dirs_include = true, $self_include = true)
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_file($path) || is_link($path)) {
            // unlink can't delete read-only files in windows os
            if (JAWS_OS_WIN) {
                @chmod($path, 0777); 
            }

			/*
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteFiles', $path);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			*/

            return @unlink($path);
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if($file == '.' || $file == '..') {
                continue;
            }

            if (!Jaws_Utils::Delete($path. DIRECTORY_SEPARATOR. $file, $dirs_include)) {
                return false;
            }
        }

        if($dirs_include && $self_include) {			
			/*
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteFiles', $path);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			*/
            
			return @rmdir($path);
        }

        return true;
    }

    /**
     * Upload Files
     *
     * @access  public
     * @param   array   $files        $_FILES array
     * param    string  $dest         destination directory(include end directory separator)
     * param    string  $allowFormats permitted file format
     * param    string  $denyFiles    deny files name list, if uploaded file name in list, then rename it
     * @param   boolean $overwrite    overwite file if exist
     * @param   boolean $move_files   moving or only copying files. this param avail for non-uploaded files
     * @param   integer $max_size     max size of file
     * @return  boolean True/False
     * @TODO 	Move to Jaws_FileManagement
     */
    function UploadFiles(
		$files, $dest, $allowFormats = 'txt,csv,css,jpg,jpeg,gif,png,pdf,
		ai,psd,eps,ps, doc,xls,ppt,rtf,svg,bmp,mp2,mp3,mp4,wmv,swf,flv,
		tiff,tif,wav,flac,aac,qt,wma,ogg,midi,ac3,mov,avi,mpe,mpg,mpga,
		mpeg,raw', $denyFiles = '', $overwrite = true, $move_files = true, 
		$max_size = null, $thumb_size = 200, $medium_size = 950
	) {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        // never overwrite existing files
		$overwrite = false;
		
		if (empty($files) || !is_array($files)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD'),': No file(s)', 'UploadFiles');
        }

        $result = array();
        if (isset($files['name'])) {
            $files = array($files);
        }
		//var_dump($files);
        foreach($files as $key => $file) {
            $filename = isset($file['name']) ? $file['name'] : '';
            $filename = $xss->parse($filename);
			
			if (isset($file['error']) && !empty($file['error'])) {
                $realerror = 'Unknown error';
				switch ($file['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$realerror = "The file exceeds upload_max_filesize.";
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$realerror = "The file exceeds MAX_FILE_SIZE.";
						break;
					case UPLOAD_ERR_PARTIAL:
						$realerror = "The file was only partially uploaded.";
						break;
					case UPLOAD_ERR_NO_FILE:
						$realerror = "No file was uploaded.";
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$realerror = "Missing a temporary folder.";
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$realerror = "Failed to write file to disk.";
						break;
					case UPLOAD_ERR_EXTENSION:
						$realerror = "An extension stopped the file upload. Examining the list of loaded extensions may help.";
						break;
				}
				return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', ': '.$realerror), 'UploadFiles');
            }

            if (empty($file['tmp_name'])) {
                continue;
            }

			$mime_types = array(
				'txt' => 'text/plain',
				'rtf' => 'text/rtf',
				'htm' => 'text/html',
				'html' => 'text/html',
				'css' => 'text/css',
				'js' => 'application/javascript',
				'json' => 'application/json',
				'xml' => 'application/xml',
				'swf' => 'application/x-shockwave-flash',
				'flv' => 'video/x-flv',

				// images
				'png' => 'image/png',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'pjpeg' => 'image/pjpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'bmp' => 'image/bmp',
				'ico' => 'image/vnd.microsoft.icon',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'svg' => 'image/svg+xml',
				'svgz' => 'image/svg+xml',
				'jfif' => "image/pipeg", 
				'ief' => "image/ief",
				'wbmp' => "image/vnd.wap.wbmp", 
				'ras' => "image/x-cmu-raster",
				'pnm' => "image/x-x-portable-anymap",
				'pbm' => "image/x-portable-bitmap",
				'pgm' => "image/x-portable-graymap",
				'ppm' => "image/x-portable-pixmap",
				'rgb' => "image/x-rgb", 
				'xbm' => "image/x-xbitmap", 
				'xpm' => "image/x-xpixmap", 
				'xwd' => "image/x-xwindowdump",

				// archives
				/*
				'zip' => 'application/zip',
				'rar' => 'application/x-rar-compressed',
				'exe' => 'application/x-msdownload',
				'msi' => 'application/x-msdownload',
				'cab' => 'application/vnd.ms-cab-compressed',
				*/

				// audio/video
				'qt' => 'video/quicktime',
				'mov' => 'video/quicktime',
				'wmv' => 'video/x-ms-wmv',
				'wav' => 'audio/x-wav',
				'flac' => 'audio/flac',
				'aac' => 'audio/x-aac',
				'wma' => 'audio/x-ms-wma',
				'ogg' => 'audio/ogg',
				'midi' => 'audio/midi',
				'ac3' => 'audio/ac3',
				'avi' => 'video/x-msvideo',
				'mp2' => 'audio/mpeg',
				'mp3' => 'audio/mpeg',
				'mp4' => 'video/mp4',
				'mpe' => 'video/mpeg',
				'mpeg' => 'video/mpeg',
				'mpg' => 'video/mpeg',
				'mpga' => 'audio/mpeg',
				
				// adobe
				'pdf' => 'application/pdf',
				'psd' => 'image/vnd.adobe.photoshop',
				'ai' => 'application/postscript',
				'eps' => 'application/postscript',
				'ps' => 'application/postscript',

				// ms office
				'doc' => 'application/msword',
				'rtf' => 'application/rtf',
				'xls' => 'application/vnd.ms-excel',
				'csv' => 'application/vnd.ms-excel',
				'ppt' => 'application/vnd.ms-powerpoint',

				// open office
				'odt' => 'application/vnd.oasis.opendocument.text',
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
				'odf' => 'application/vnd.oasis.opendocument.formula',
			);
			
			$img_type = strtolower($file['type']);
			$ext = end(explode('.', strtolower($filename)));
			//var_dump($ext);
			//var_dump($mime_types[$ext]);
			//var_dump($img_type);
			
			// Check the file type is what it says it is
			if (isset($mime_types[$ext])) {
				if ($img_type != $mime_types[$ext] && strpos($img_type, 'image') === false) {
					return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_FORMATS_DONT_MATCH', $img_type, $mime_types[$ext]), 'UploadFiles');
				}
			} else {
				return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_INVALID_FORMAT', $filename), 'UploadFiles');
			}
			
			// Check the file type is allowed
			if (!empty($allowFormats)) {
                $allowFormats = explode(',', $allowFormats);
                if (!in_array($ext, $allowFormats)) {
                    return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_INVALID_FORMAT', $filename), 'UploadFiles');
                }
            }

			// Check the file type is not denied
            if (!empty($denyFiles)) {
                $denyFiles = explode(',', $denyFiles);
				$deniedFiles = array();
				foreach ($denyFiles as $dFile) {
					$deniedFiles[] = (substr($dFile, 0, 1) == '.' ? strtolower(substr($dFile, 1, strlen($dFile))) : strtolower($dFile));
				}
			} else {
                $deniedFiles = array();
            }

			if (in_array($ext, $deniedFiles)) {
				return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_INVALID_FORMAT', $filename), 'UploadFiles');
			}
            
			$filename = strtolower($filename);
			$filename = str_replace(array("#"," ","'",'"',",","__","&","/","\\","?","`","@","!","$","%","^","*","(",")","+","=","[","]","{","}","|",";",":","<",">"),"",$filename);
   
			if (in_array(basename($filename), $deniedFiles) ||
               (!$overwrite && file_exists($dest . $filename)))
            {
                $filename = time() . '_' . $filename;
            }
            $uploadfile = $dest . $filename;
										
			$img_size = $file['size'];
						
			if (is_uploaded_file($file['tmp_name'])) {
				//Checks if file is an *actual* image
				if (strpos(strtolower($img_type), 'image') !== false) {
					$sizelim = true;
					$size = "10000000";
					// image too big?
					if (($sizelim === true) && ((float)$img_size > (float)$size)) {
						return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', ': File too big: '.$filename), 'UploadFiles');
					} else {
						$thumb_path = $dest.'/thumb';
						$thumb_name = $filename;
						$medium_name = $filename;
						$medium_path = $dest.'/medium';

						//Scale image with Constrain proportions
						$img_d = Jaws_Image::image_info($file['tmp_name']);
						$img_height_orig = round($img_d['height']);
						$img_width_orig = round($img_d['width']);
						
						if ( $img_width_orig > $thumb_size || $img_height_orig > $thumb_size ) {
							if ($img_width_orig < $img_height_orig) {
								$thumb_width = ($thumb_size / $img_height_orig) * $img_width_orig;
								$thumb_height = $thumb_size;
							} else {
								$thumb_height = ($thumb_size / $img_width_orig) * $img_height_orig;
								$thumb_width = $thumb_size;
							}
						} else {
							$thumb_width = $img_width_orig;
							$thumb_height = $img_height_orig;
						}
						
						if ( $img_width_orig > $medium_size || $img_height_orig > $medium_size ) {
							if ($img_width_orig < $img_height_orig) {
								$medium_width = ($medium_size / $img_height_orig) * $img_width_orig;
								$medium_height = $medium_size;
							} else {
								$medium_height = ($medium_size / $img_width_orig) * $img_height_orig;
								$medium_width = $medium_size;
							}
						} else {
							$medium_width = $img_width_orig;
							$medium_height = $img_height_orig;
						}
																			
						if (!move_uploaded_file($file['tmp_name'], $uploadfile)) {
							return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', ': Can\'t move temp file: '. $filename), 'UploadFiles');
						} else {
							if (!file_exists($uploadfile)) {
								return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', ': Uploaded file doesn\'t exist: '. $filename), 'UploadFiles');
							} else {
								// Resample
								if (($img_d['height'] > $thumb_size || $img_d['width'] > $thumb_size)) {
									$thumbfile = Jaws_Image::ResizeImage($uploadfile, "$thumb_path/$thumb_name", $thumb_width, $thumb_height, 'ImageMagick', 60);
									/*
									if (Jaws_Error::IsError($thumbfile)) {
										return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE'));
									}
									*/
								}
								// Resample
								if (($img_d['height'] > $medium_size || $img_d['width'] > $medium_size)) {
									$mediumfile = Jaws_Image::ResizeImage($uploadfile, "$medium_path/$medium_name", $medium_width, $medium_height, 'ImageMagick', 60);
									/*
									if (Jaws_Error::IsError($mediumfile)) {
										return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE'));
									}
									*/
								}		
							}
						}
					}
				} else if (!move_uploaded_file($file['tmp_name'], $uploadfile)) {
					return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', ': Can\'t move temp file: '. $filename), 'UploadFiles');
				}
            } else {
                // On windows-systems we can't rename a file to an existing destination,
                // So we first delete destination file
                if (file_exists($uploadfile)) {
                    @unlink($uploadfile);
                }
				$res = $move_files? @rename($file['tmp_name'], $uploadfile) : @copy($file['tmp_name'], $uploadfile);
                if (!$res) {
                    return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', ': Can\'t rename file to existing destination: '.$filename), 'UploadFiles');
				}
            }
			
			// Check if the file has been altered or is corrupted
            if (filesize($uploadfile) != $file['size']) {
                @unlink($uploadfile);
                return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_CORRUPTED', $filename), 'UploadFiles');
            }
			
			if (strpos(strtolower($img_type), 'image') !== false) {
				$uploadfile = Jaws_Image::ResizeImage($uploadfile, $uploadfile, $img_width_orig, $img_height_orig, 'ImageMagick', 70);
            }
			//var_dump($uploadfile);

            Jaws_Utils::chmod($uploadfile);
			/*
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$shouter = $GLOBALS['app']->Shouter->Shout('onUploadFiles', array(
				'filename' => $filename, 'filepath' => $uploadfile, 'filetype' => $img_type, 
				'filesize' => $img_size, 'allowFormats' => $allowFormats, 
				'denyFiles' => $denyFiles, 'overwrite' => $overwrite, 
				'move_files' => $move_files, 'max_size' => $max_size, 
				'thumb_size' => $thumb_size, 'medium_size' => $medium_size)
			);
			if (isset($shouter['filepath']) && !empty($shouter['filepath'])) {
				$filename = $shouter['filepath'];
			}
			*/
			$result[$key] = $filename;
        }

        return $result;
    }

    /**
     * Extract archive Files
     *
     * @access  public
     * @param   array   $files        $_FILES array
     * param    string  $dest         destination directory(include end directory separator)
     * param    string  $extractToDir create separate directory for extracted files
     * @param   boolean $overwrite    overwite directory if exist
     * @param   integer $max_size     max size of file
     * @return  boolean true on success or false on error
     */
    function ExtractFiles($files, $dest, $extractToDir = true, $overwrite = true, $max_size = null)
    {
        if (empty($files) || !is_array($files)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD'), 'ExtractFiles');
        }

        $result = array();
        if (isset($files['name'])) {
            $files = array($files);
        }

        require_once 'File/Archive.php';
        foreach($files as $key => $file) {
            if ((isset($file['error']) && !empty($file['error'])) || !isset($file['name'])) {
                return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_'.$file['error']), 'ExtractFiles');
            }

            if (empty($file['tmp_name'])) {
                continue;
            }

            $ext = strrchr($file['name'], '.');
            $filename = substr($file['name'], 0, -strlen($ext));
            if (false !== stristr($filename, '.tar')) {
                $filename = substr($filename, 0, strrpos($filename, '.'));
                switch ($ext) {
                    case '.gz':
                        $ext = '.tgz';
                        break;

                    case '.bz2':
                    case '.bzip2':
                        $ext = '.tbz';
                        break;

                    default:
                        $ext = '.tar' . $ext;
                }
            }

            $ext = strtolower(substr($ext, 1));
            if (!File_Archive::isKnownExtension($ext)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_INVALID_FORMAT', $file['name']), 'ExtractFiles');
            }

            if ($extractToDir) {
                $dest = $dest . $filename;
            }

            if ($extractToDir && !Jaws_Utils::mkdir($dest)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $dest), _t('ExtractFiles'));
            }

            if (!Jaws_Utils::is_writable($dest)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', $dest));
            }

            $archive = File_Archive::readArchive($ext, $file['tmp_name']);
            if (PEAR::isError($archive)) {
                return new Jaws_Error($archive->getMessage());
            }
            $writer = File_Archive::_convertToWriter($dest);
            $result = $archive->extract($writer);
            if (PEAR::isError($result)) {
                return new Jaws_Error($result->getMessage());
            }

            //@unlink($file['tmp_name']);
        }

        return true;
    }

    /**
     * Get information of remote IP address
     *
     * @access  public
     * @return  array   include proxy and client ip addresses
     */
    function GetRemoteAddress()
    {
        static $proxy, $client;

        if (!isset($proxy) and !isset($client)) {
            if (!empty($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
                $direct = $_SERVER['REMOTE_ADDR'];
            } else if (!empty($_ENV) && isset($_ENV['REMOTE_ADDR'])) {
                $direct = $_ENV['REMOTE_ADDR'];
            } else if (@getenv('REMOTE_ADDR')) {
                $direct = getenv('REMOTE_ADDR');
            }

            $proxy_flags = array('HTTP_CLIENT_IP',
                                 'HTTP_X_FORWARDED_FOR',
                                 'HTTP_X_FORWARDED',
                                 'HTTP_FORWARDED_FOR',
                                 'HTTP_FORWARDED',
                                 'HTTP_VIA',
                                 'HTTP_X_COMING_FROM',
                                 'HTTP_COMING_FROM',
                                );

            $client = '';
            foreach ($proxy_flags as $flag) {
                if (!empty($_SERVER) && isset($_SERVER[$flag])) {
                    $client = $_SERVER[$flag];
                    break;
                } else if (!empty($_ENV) && isset($_ENV[$flag])) {
                    $client = $_ENV[$flag];
                    break;
                } else if (@getenv($flag)) {
                    $client = getenv($flag);
                    break;
                }
            }

            if (empty($client)) {
                $proxy  = '';
                $client = $direct;
            } else {
                $is_ip = preg_match('|^([0-9]{1,3}\.){3,3}[0-9]{1,3}|', $client, $regs);
                $client = $is_ip? $regs[0] : '';
                $proxy  = $direct;
            }

        }

        return array('proxy' => $proxy, 'client' => $client);
    }

    /**
     * Returns an array of languages
     *
     * @access  public
     * @return  array   A list of available languages
     */
    function GetLanguagesList($use_data_lang = true)
    {
        static $langs;
        if (!isset($langs)) {
            $langs = array();
            $langdir = JAWS_PATH . 'languages/';
            $files = @scandir($langdir);
            if ($files !== false) {
                foreach($files as $file) {
                    if ($file{0} != '.'  && is_dir($langdir . $file)) {
                        if (is_file($langdir.$file.'/FullName')) {
                            $fullname = implode('', @file($langdir.$file.'/FullName'));
                            if (!empty($fullname)) {
                                $langs[$file] = $fullname;
                            }
                        }
                    }
                }
                asort($langs);
            }
        }

        if ($use_data_lang) {
            static $dLangs;
            if (!isset($dLangs)) {
                $dLangs = array();
                $langdir = JAWS_DATA . 'languages/';
                $files = @scandir($langdir);
                if ($files !== false) {
                    foreach($files as $file) {
                        if ($file{0} != '.'  && is_dir($langdir . $file)) {
                            if (is_file($langdir.$file.'/FullName')) {
                                $fullname = implode('', @file($langdir.$file.'/FullName'));
                                if (!empty($fullname)) {
                                    $dLangs[$file] = $fullname;
                                }
                            }
                        }
                    }
                }
                $dLangs = array_unique(array_merge($langs, $dLangs));
                asort($dLangs);
            }

            return $dLangs;
        }

        return $langs;
    }

    /**
     * Get a list of the themes the site is running
     *
     * @access  public
     * @return  array   A list of themes(filenames)
     */
    function GetThemesList($include_base_themes = true)
    {
        /**
         * is theme valid?
         */
        if (!function_exists('is_vaild_theme')) {
            function is_vaild_theme(&$item, $key, $path)
            {
                if ($item{0} == '.' ||
                    !is_dir($path . $item) ||
                    !file_exists($path . $item . DIRECTORY_SEPARATOR. 'layout.html'))
                {
                    $item = '';
                }
                return true;
            }
        }

        static $pThemes;
        if (!isset($pThemes)) {
            $theme_path = JAWS_DATA . 'themes'. DIRECTORY_SEPARATOR;
            $pThemes = scandir($theme_path);
            array_walk($pThemes, 'is_vaild_theme', $theme_path);
            $pThemes = array_filter($pThemes);
            sort($pThemes);
        }

        if ($include_base_themes) {
            static $themes;
            if (!isset($themes)) {
                $themes = array();
                if (JAWS_DATA != JAWS_BASE_DATA) {
                    $theme_path = JAWS_BASE_DATA . 'themes'. DIRECTORY_SEPARATOR;
                    $themes = scandir($theme_path);
                    array_walk($themes, 'is_vaild_theme', $theme_path);
                    $themes = array_filter($themes);
                    sort($themes);
                }
                $themes = array_unique(array_merge($pThemes, $themes));
            }

            return $themes;
        }

        return $pThemes;
    }
		
	/**
     * Create a 2D array from a delimited string
     *
     * @param mixed $data 2D array
     * @param string $delimiter Field delimiter
     * @param string $enclosure Field enclosure
     * @param string $newline Line seperator
     * @return
     */
    function split2D($data, $delimiter = "\t", $enclosure = '', $newline = "\n"){
        $pos = $last_pos = -1;
        $end = strlen($data);
        $row = 0;
        $quote_open = false;
        $trim_quote = false;

        $return = array();

        // Create a continuous loop
        for ($i = -1;; ++$i){
            ++$pos;
            // Get the positions
            $comma_pos = strpos($data, $delimiter, $pos);
            if (!empty($enclosure)) {
				$quote_pos = strpos($data, $enclosure, $pos);
            } else {
				$quote_pos = false;
			}
			$newline_pos = strpos($data, $newline, $pos);

            // Which one comes first?
            $pos = min(($comma_pos === false) ? $end : $comma_pos, ($quote_pos === false) ? $end : $quote_pos, ($newline_pos === false) ? $end : $newline_pos);

            // Cache it
            $char = (isset($data[$pos])) ? $data[$pos] : null;
            $done = ($pos == $end);

            // It it a special character?
            if ($done || $char == $delimiter || $char == $newline){

                // Ignore it as we're still in a quote
                if ($quote_open && !$done){
                    continue;
                }

                $length = $pos - ++$last_pos;

                // Is the last thing a quote?
                if ($trim_quote){
                    // Well then get rid of it
                    --$length;
                }

                // Get all the contents of this column
                $return[$row][] = ($length > 0) ? str_replace($enclosure . $enclosure, $enclosure, substr($data, $last_pos, $length)) : '';

                // And we're done
                if ($done){
                    break;
                }

                // Save the last position
                $last_pos = $pos;

                // Next row?
                if ($char == $newline){
                    ++$row;
                }

                $trim_quote = false;
            }
            // Our quote?
            else if ($char == $enclosure){

                // Toggle it
                if ($quote_open == false){
                    // It's an opening quote
                    $quote_open = true;
                    $trim_quote = false;

                    // Trim this opening quote?
                    if ($last_pos + 1 == $pos){
                        ++$last_pos;
                    }

                }
                else {
                    // It's a closing quote
                    $quote_open = false;

                    // Trim the last quote?
                    $trim_quote = true;
                }

            }

        }

        return $return;
    }
	
	function fputcsv2 ($fields = array(), $delimiter = ',', $enclosure = '"', $mysql_null = false) {
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');

		$output = array();
		foreach ($fields as $field) {
			if ($field === null && $mysql_null) {
				$output[] = 'NULL';
				continue;
			}

			$output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? (
				$enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
			) : $field;
		}

		return join($delimiter, $output);
	} 

	function seoShuffle(&$items,$seedstring) {
	   if (!$seedstring) {
		  $seedval = time();
	   } else {
		  if (is_numeric($seedstring)) {
			  $seedval = $seedstring;
		  } else {
			  for($i=0;$i<=strlen($seedstring);$i++) {
				  $seedval += ord($seedstring[$i]);
			  }
		  }
	   }

	   srand($seedval);
	   for ($i = count($items) - 1; $i > 0; $i--) {
		  $j = @rand(0, $i);
		  $tmp = $items[$i];
		  $items[$i] = $items[$j];
		  $items[$j] = $tmp;
	   }
	}
	
    /**
     * Returns an array of Geo Information of Remote IP Address
     *
     * @return mixed array of Geo Location information or false on error
     */
	function GetRemoteGeoLocation(){
		$result = array();
		$remote_info = Jaws_Utils::GetRemoteAddress();
		//var_dump($remote_info);
		if (isset($remote_info['client']) && !empty($remote_info['client'])) {
			$result['client'] = $remote_info['client'];
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			// snoopy
			$snoopy = new Snoopy;
			$snoopy->agent = "Jaws";
			//US,GA,Snellville,33.862999,-84.007599
			$geocode_url = "http://geoip3.maxmind.com/b?l=7mdlfYOfPxDx&i=".$remote_info['client'];
			//echo '<br />Geocoder: '.$geocode_url;
			if($snoopy->fetch($geocode_url)) {
				$csv_content = $snoopy->results;
				$csv_array = explode(',', $csv_content);
				if (isset($csv_array[2]) && !empty($csv_array[2])) {
					//$is_totalResults = false;
					$result['country_name'] = '';
					$result['country_code'] = $csv_array[0];
					if (isset($csv_array[1]) && !empty($csv_array[1])) {
						$result['region'] = $csv_array[1];
					}
					if (isset($csv_array[3]) && !empty($csv_array[3])) {
						$result['latitude'] = (float)$csv_array[3];
					}
					if (isset($csv_array[4]) && !empty($csv_array[4])) {
						$result['longitude'] = (float)$csv_array[4];
					}
					if (isset($csv_array[2]) && !empty($csv_array[2])) {
						$result['city'] = $csv_array[2];
					}
					$result['area_code'] = '';
					$result['postal_code'] = '';
					//break;
				}
			}
			if (!isset($result['city']) || empty($result['city'])) {
				$result['country_name'] = "United States";
				$result['country_code'] = 'US';
				$result['region'] = "District of Columbia";
				$result['latitude'] = '38.9048';
				$result['longitude'] = '-77.0354';
				$result['city'] = "Washington, D.C.";
				$result['area_code'] = '202';
				$result['postal_code'] = '20001';
				$geocode_url = "http://geoip.pidgets.com/?ip=".$remote_info['client']."&format=xml";
				$snoopy = new Snoopy;
				$snoopy->agent = "Jaws";
				if($snoopy->fetch($geocode_url)) {
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
					$xml_content = $snoopy->results;
				
					// XML Parser
					$xml_parser = new XMLParser;
					$xml_result = $xml_parser->parse($xml_content, array("RESULT"));
					/*
					echo '<pre>';
					var_dump($xml_result);
					echo '</pre>';
					exit;
					*/
					for ($i=0;$i<$xml_result[1]; $i++) {
						//$is_totalResults = false;
						if (isset($xml_result[0][$i]['COUNTRY_NAME']) && !empty($xml_result[0][$i]['COUNTRY_NAME']) && isset($xml_result[0][$i]['COUNTRY_CODE']) && !empty($xml_result[0][$i]['COUNTRY_CODE'])) {
							$result['country_name'] = $xml_result[0][$i]['COUNTRY_NAME'];
							$result['country_code'] = $xml_result[0][$i]['COUNTRY_CODE'];
							$result['region'] = 'District of Columbia';
							if (isset($xml_result[0][$i]['REGION']) && !empty($xml_result[0][$i]['REGION'])) {
								$result['region'] = $xml_result[0][$i]['REGION'];
							}
							$result['latitude'] = '38.9048';
							if (isset($xml_result[0][$i]['LATITUDE']) && !empty($xml_result[0][$i]['LATITUDE'])) {
								$result['latitude'] = (float)$xml_result[0][$i]['LATITUDE'];
							}
							$result['longitude'] = '-77.0354';
							if (isset($xml_result[0][$i]['LONGITUDE']) && !empty($xml_result[0][$i]['LONGITUDE'])) {
								$result['longitude'] = (float)$xml_result[0][$i]['LONGITUDE'];
							}
							$result['city'] = 'Washington, D.C.';
							if (isset($xml_result[0][$i]['CITY']) && !empty($xml_result[0][$i]['CITY'])) {
								$result['city'] = $xml_result[0][$i]['CITY'];
							}
							$result['area_code'] = '202';
							if (isset($xml_result[0][$i]['AREA_CODE']) && !empty($xml_result[0][$i]['AREA_CODE'])) {
								$result['area_code'] = $xml_result[0][$i]['AREA_CODE'];
							}
							$result['postal_code'] = '20001';
							if (isset($xml_result[0][$i]['POSTAL_CODE']) && !empty($xml_result[0][$i]['POSTAL_CODE'])) {
								$result['postal_code'] = $xml_result[0][$i]['POSTAL_CODE'];
							}
							break;
						}
					}
				}
			}
		}
		return $result;
	}
    
	/**
     * Returns an array of Geo Information of a Geo address string
     *
     * @return mixed array of Geo Location information or false on error
     */
	function GetGeoLocation($address = ''){
		$result = array();
		if (isset($address) && !empty($address)) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
			// snoopy
			$snoopy = new Snoopy;
			$snoopy->agent = "Jaws";
			$geocode_url = "http://maps.google.com/maps/geo?q=".$address."&output=xml&key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
			//echo '<br />Google Geocoder: '.$geocode_url;
			if($snoopy->fetch($geocode_url)) {
				$xml_content = $snoopy->results;
			
				// XML Parser
				$xml_parser = new XMLParser;
				$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
				/*
				echo '<pre>';
				var_dump($xml_result);
				echo '</pre>';
				exit;
				*/
				for ($i=0;$i<$xml_result[1]; $i++) {
					//$is_totalResults = false;
					if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COUNTRYNAMECODE']) && !empty($xml_result[0][$i]['COUNTRYNAMECODE']) && isset($xml_result[0][$i]['COUNTRYNAME']) && !empty($xml_result[0][$i]['COUNTRYNAME']) && isset($xml_result[0][$i]['COORDINATES'])) {
						$result['city'] = '';
						if (isset($xml_result[0][$i]['LOCALITYNAME']) && !empty($xml_result[0][$i]['LOCALITYNAME'])) {
							$result['city'] = $xml_result[0][$i]['LOCALITYNAME'];
						}
						$result['address'] = '';
						if (isset($xml_result[0][$i]['ADDRESS']) && !empty($xml_result[0][$i]['ADDRESS'])) {
							$result['address'] = $xml_result[0][$i]['ADDRESS'];
						}
						$result['postal_code'] = '';
						if (isset($xml_result[0][$i]['POSTALCODENUMBER']) && !empty($xml_result[0][$i]['POSTALCODENUMBER'])) {
							$result['postal_code'] = $xml_result[0][$i]['POSTALCODENUMBER'];
						}
						$result['country_name'] = '';
						if (isset($xml_result[0][$i]['COUNTRY_NAME']) && !empty($xml_result[0][$i]['COUNTRY_NAME'])) {
							$result['country_name'] = $xml_result[0][$i]['COUNTRY_NAME'];
						}
						$result['country_code'] = $xml_result[0][$i]['COUNTRYNAMECODE'];
						$result['region'] = '';
						if (isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && !empty($xml_result[0][$i]['ADMINISTRATIVEAREANAME'])) {
							$result['region'] = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
						}
						$result['latitude'] = '';
						$result['longitude'] = '';
						if (isset($xml_result[0][$i]['COORDINATES']) && !empty($xml_result[0][$i]['COORDINATES'])) {
							$coordinates = explode(',',$xml_result[0][$i]['COORDINATES']);
							$result['longitude'] = (float)$coordinates[0];
							$result['latitude'] = (float)$coordinates[1];
						}
						$result['area_code'] = '';
					}
				}
			}
		}
		return $result;
	}

    /**
     * Haversine formula for calculating distance (in miles) between two coordinates
     *
     * @param float $l1 latitude1
     * @param float $o1 longitude1
     * @param float $l2 latitude2
     * @param float $o2 longitude2
     * @return
     */
	function haversine ($l1, $o1, $l2, $o2)
	{
		$l1 = deg2rad ($l1);
		$sinl1 = sin ($l1);
		$l2 = deg2rad ($l2);
		$o1 = deg2rad ($o1);
		$o2 = deg2rad ($o2);
					
		return (7926 - 26 * $sinl1) * asin (min (1, 0.707106781186548 * sqrt ((1 - (sin ($l2) * $sinl1) - cos ($l1) * cos ($l2) * cos ($o2 - $o1)))));
	}

	/**
     * Returns an array of information of a URL
     *
     * @return mixed array of information of a URL
     */
	function GetUrlInfo($url = ''){
		$result = array();
		if (isset($url) && !empty($url)) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			// snoopy
			$snoopy = new Snoopy;
			$snoopy->agent = "Jaws";
			$snoopy->read_timeout = 5;
			//if($snoopy->fetchinfo($url) && (string)$snoopy->response_code == '200') {
			if($snoopy->fetchinfo($url)) {
				$result['url'] = $url;
				$result['host'] = $snoopy->host;
				$result['title'] = $snoopy->title;
				$result['bodytext'] = $snoopy->bodytext;
				$result['images'] = $snoopy->images;
				$meta = $snoopy->meta;
				$summary = '';
				$keywords = '';
				foreach ($meta as $m => $v) {
					switch (strtolower($v[0])) {
						case 'description':
							$summary = $v[1];
							break;
						case 'keywords':
							$keywords = $v[1];
							break;
					}
				}
				$result['summary'] = $summary;
				$result['keywords'] = $keywords;
				$main_image = '';
				$result['main_image'] = (isset($result['images'][0]) ? $result['images'][0] : '');
				$embed = '';
				$result['embed'] = $embed;
			}
		}
		return $result;
	}
	
    /**
     * Creates a PDF using mPDF class
     *
     * @param string $template template file
     * @param mixed $save location to save to or false to output
     * @return
     */
	function CreatePDF(
		$template = '', $html = '', $save = false, $mode = '', $format = 'A4', $defaultFontSize = 0, $defaultFont = '', 
		$marginLeft = 15, $marginRight = 15, $marginTop = 15, $marginBottom = 16, $marginHeader = 9, $marginFooter = 9, 
		$orientation = 'P', $header = array(), $HTMLheader = '', $footer = array(), $HTMLfooter = '', 
		$displayMode = 100
	) {
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'mpdf51' . DIRECTORY_SEPARATOR . 'mpdf.php';
		// mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P')			
		$mpdf=new mPDF($mode,$format,$defaultFontSize,$defaultFont,$marginLeft,$marginRight,$marginTop,$marginBottom,$marginHeader,$marginFooter); 
		/*
		$mpdf->showStats = true;
		$mpdf->debug = true;
		*/
		/*
		$mpdf->pagenumPrefix = 'Page ';
		$mpdf->pagenumSuffix = '';
		$mpdf->nbpgPrefix = ' of ';
		$mpdf->nbpgSuffix = ' pages.';
		$header = array(
			'L' => array(
			),
			'C' => array(
			),
			'R' => array(
				'content' => '{PAGENO}{nbpg}',
				'font-family' => 'sans',
				'font-style' => '',
				'font-size' => '9',
			),
			'line' => 1,
		);
		
		$HTMLfooter = '
		<table width="100%" style="border-top: 0.1mm solid #000000; vertical-align: top; font-family: sans; font-size: 9pt; color: #000055;"><tr>
		<td width="50%"></td>
		<td width="50%" align="right">See <a href="http://mpdf1.com/manual/index.php">documentation manual</a> for further details</td>
		</tr></table>
		';
		*/

		if (!count($header) <= 0) {
			$mpdf->SetHeader($header,'O');
		}
		if (!empty($HTMLheader)) {
			$mpdf->SetHTMLHeader($HTMLheader);
		}
		if (!count($footer) <= 0) {
			$mpdf->SetFooter($footer,'O');
		}
		if (!empty($HTMLfooter)) {
			$mpdf->SetHTMLFooter($HTMLfooter);
		}
		$mpdf->SetDisplayMode($displayMode);
		if (empty($html)) {
			if (substr($template, 0, 4) == 'http') {
				// snoopy
				require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy;
				$snoopy->fetchinfo($template);
				if ($snoopy->status == "200") {
					$html = $snoopy->results;
					// Copy all linked CSS files inline
					foreach ($snoopy->css as $css) {
						$css_parts = parse_url($css);
						if (substr(strtolower($css_parts['path']), -4) == '.css') {
							$snoopy2 = new Snoopy;
							$snoopy2->fetch($css);
							if ($snoopy2->status == "200") {
								$html = str_replace('</head>', "\n".'<style type="text/css">'.$snoopy2->results.'</style>'."\n".'</head>', $html);
							}
						}
					}
				}
			} else if (file_exists($template)) {
				$html = file_get_contents($template);
			} else {
				return false;
			}
		}
		if (!empty($html)) {
			$mpdf->WriteHTML($html);
			
			$output = 'I';
			// OUTPUT
			if ($save === false || $save == '') {
				$save = time().'.pdf';
				$mpdf->Output($save, $output); 
				exit;
			} else {
				$ouput = 'F';
			}
			$mpdf->Output($save, $output); 
			exit;
		}
		return false;
	}
}