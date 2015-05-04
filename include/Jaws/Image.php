<?php
/**
 * Image management features (using GD or ImageMagick)
 *
 * @category   JawsType
 * @category   developer_feature
 * @package    Core
 * @author     Jonathan Hernandez  <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
ini_set("memory_limit","512M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","4M");
ini_set("max_execution_time","300");

class Jaws_Image
{
    /**
     * Get the full thumb path of a given filename
     *
     * @access  public
     * @param   string  $file Name of the file
     * @return  string  The ThumbPath
     */
    function GetThumbPath($file)
    {
        $path = substr($file, 0, strrpos($file, '/'));
        $file = substr($file, strrpos($file, '/'));
        return $path . '/thumb' . $file;
    }

    /**
     * Get the medium path of a given filename
     *
     * @access  public
     * @param   string  $file Name of the file
     * @return  string  The MediumPath
     */
    function GetMediumPath($file)
    {
        $path = substr($file, 0, strrpos($file, '/'));
        $file = substr($file, strrpos($file, '/'));
        return $path . '/medium' . $file;
    }

    /**
     * Get the relative URI of an image
     *
     * @access  public
     * @param   string  $src  Image SRC
     * @return  string  $src with the correct relative URI
     */
    function GetURI($src)
    {
        return $GLOBALS['app']->GetURILocation().$src;
    }

    /**
     * Verify that a given method(ImageMagick or GD) really exists
     *
     * @access  public
     * @param   string  $method Method we want to evaluate
     * @return  mixed   Can be the name of the method to use or Jaws_Error if
     *                  there's no method installed
     */
    function VerifyMethod($method)
    {
        if (Jaws_Error::IsError($method)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_NOMETHOD'));
        }

        switch ($method) {
            case 'ImageMagick':
                if (isset($_ENV['PATH'])) {
                    $path = $_ENV['PATH'];
                } elseif (isset($_SERVER['PATH'])) {
                    $path = $_SERVER['PATH'];
                } else {
                    $path = '/usr/bin:/usr/local/bin:/bin:/usr/X11R6/bin';
                }
                $dirs      = explode(':', $path);
                $exists    = false;
                $dir_count = count($dirs);
                for ($i = 0; $i < $dir_count; $i++) {
                    if (@file_exists($dirs[$i].'/convert')) {
                        $exists = true;
                    }
                }

                if ($exists && function_exists('exec') && function_exists('escapeshellcmd')) {
                    return $method;
                }

                return Jaws_Image::VerifyMethod('GD');
                break;
            case 'GD':
                if (function_exists('gd_info')) {
                    return $method;
                }
                //return Jaws_Image::VerifyMethod('ImageMagick');
                return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_NOMETHOD'));
                break;
        }
    }

    /**
     * Resize an image
     *
     * @access  public
     * @param   string  $source  Photo File
     * @param   string  $destiny Where we want it
     * @param   string  $width   Width of the photo
     * @param   string  $height  Height of the photo
     * @param   string  $method  Method name
     * @param   string  $quality Image quality
     * @return  boolean Returns true if the image was resized without problems
     *                  or Jaws_Error if there were problems.
     */
    function ResizeImage($source, $destiny, $width, $height, $method, $quality)
    {
        // Verify for valid extension
        $ext =  strtolower(substr($source, strrpos($source,'.') + 1));
		$valid_extensions = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($ext, $valid_extensions)) {
            //return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE'));
            return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE').': Extension ('.$ext.') not valid.');
        }
        /*Really, really.. the user is running the library?*/
        $method = Jaws_Image::VerifyMethod($method);
        if (Jaws_Error::IsError($method)) {
            //return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE'));
            return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE').': Method ('.$method.') not valid.');
        }

        switch ($method) {
            case 'ImageMagick':
                $csource  = str_replace(' ', '\ ', escapeshellcmd($source));
                $cdestiny = str_replace(' ', '\ ', escapeshellcmd($destiny));
                switch ($ext) {
                    case 'jpeg':
                    case 'jpg':
						$quality = " -quality ".$quality;
						break;
                    case 'png':
						$quality = " -quality ".(($quality<10) ? 9 : (10 - floor($quality / 10)));
                        break;
                    case 'gif':
						$quality = '';
						break;
				}
                exec('convert'.$quality.' -geometry  '.$width.'x'.$height.' '.$csource.' '.$cdestiny);
                break;
            case 'GD':
                $source_size = getimagesize($source);
                $ratio       =($source_size[0] / $width);
                $h           = round($source_size[1] / $ratio);
                if ($h <= $height) {
                    $w = $width;
                    $destiny_img = imagecreatetruecolor($w, $h);
                } else {
                    $h = $height;
                    $ratio =($source_size[1] / $height);
                    $w = round($source_size[0] / $ratio);
                    $destiny_img = imagecreatetruecolor($w, $h);
                }
                $destiny_img = imagecreatetruecolor($w, $h);
                /*
				if ($ext == 'jpeg') {
                    $ext = 'jpg';
                }
				*/
                switch ($ext) {
                    case 'jpeg':
                    case 'jpg':
                        $img = imagecreatefromjpeg($source);
                        imagecopyresampled($destiny_img, $img,
                                       0, 0, 0, 0,
                                       $w, $h,
                                       $source_size[0], $source_size[1]);
                        imagejpeg($destiny_img, $destiny, $quality);
                        break;
                    case 'png':
                        $quality = ($quality<10)? 9 : (10 - floor($quality / 10));
                        $img = imagecreatefrompng($source);
						imagecolortransparent($destiny_img, imagecolorallocatealpha($destiny_img, 0, 0, 0, 127));
						imagealphablending($destiny_img, false);
						imagesavealpha($destiny_img, true);
						imagecopyresampled($destiny_img, $img,
                                       0, 0, 0, 0,
                                       $w, $h,
                                       $source_size[0], $source_size[1]);
						if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
                            imagepng($destiny_img, $destiny, $quality);
                        } else {
                            imagepng($destiny_img, $destiny);
                        }
                        break;
                    case 'gif':
                        $img = imagecreatefromgif ($source);
						imagecolortransparent($destiny_img, imagecolorallocatealpha($destiny_img, 0, 0, 0, 127));
						imagealphablending($destiny_img, false);
						imagesavealpha($destiny_img, true);
                        imagecopyresampled($destiny_img, $img,
                                       0, 0, 0, 0,
                                       $w, $h,
                                       $source_size[0], $source_size[1]);
                        imagegif ($destiny_img, $destiny);
                        break;
                }
                break;
        }
        if (is_file($destiny)) {
            return true;
        }

        //return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE'));
        return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_RESIZE_IMAGE').': Resized image not found. (Source: '.$source.', destiny: '.$destiny.', width: '.$width.', height: '.$height.', method: '.$method.', quality: '.$quality.')');
    }

    /**
     * Rotate an image
     *
     * @access  public
     * @param   string  $source  Photo File
     * @param   string  $destiny Where we want it
     * @param   string  $degrees Degrees to rotate
     * @param   string  $method  Method name
     * @param   string  $quality Image quality
     * @return  boolean Returns true if the image was rotated without problems
     *                  or Jaws_Error if there were problems.
     */
    function RotateImage($source, $destiny, $degrees, $method, $quality)
    {
        /*Really, really.. the user is running the library?*/
        $method = Jaws_Image::VerifyMethod($method);
        if (Jaws_Error::IsError($method)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_ROTATE_IMAGE'));
        }

        switch ($method) {
            case 'ImageMagick':
                $csource  = str_replace(' ', '\ ', escapeshellcmd($source));
                $cdestiny = str_replace(' ', '\ ', escapeshellcmd($destiny));
                exec('convert -rotate  '.$degrees.' '.$csource.' '.$cdestiny);
                break;
            case 'GD':
                $ext =  strtolower(substr($source, strrpos($source, '.')+1));
                switch ($ext) {
                    case 'jpg':
                        $img = imagecreatefromjpeg($source);
                        $rotate = imagerotate($img, $degrees, 0);
                        imagedestroy($img);
                        imagejpeg($rotate, $destiny, $quality);
                        imagedestroy($rotate);
                        break;
                    case 'png':
                        $quality = ($quality<10)? 9 : (10 - floor($quality / 10));
                        $img = imagecreatefrompng($source);
                        $rotate = imagerotate($img, $degrees, 0);
                        imagedestroy($img);
                        if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
                            imagepng($rotate, $destiny, $quality);
                        } else {
                            imagepng($rotate, $destiny);
                        }
                        imagedestroy($rotate);
                        break;
                    case 'gif':
                        $img = imagecreatefromgif($source);
                        $rotate = imagerotate($img, $degrees, 0);
                        imagedestroy($img);
                        imagegif($rotate, $destiny);
                        imagedestroy($rotate);
                        break;
                }
                break;
        }

        if (is_file($destiny)) {
            return true;
        }

        return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_ROTATE_IMAGE'));
    }


    /**
     * Rotate right given image
     *
     * @param string $filename Path to image file
     * @param string $method   Method name
     * @param string $quality  Image quality
     */
    function RotateRight($filename, $method, $quality)
    {
        $normal = $filename;
        $medium = Jaws_Image::GetMediumPath($filename);
        $thumb =  Jaws_Image::GetThumbPath($filename);

        if ($method !== 'GD') {
            $rotateNormal = Jaws_Image::RotateImage($normal, $normal, 270, $method, $quality);
        } else {
            $rotateNormal = true;
        }
        $rotateMedium = Jaws_Image::RotateImage($medium, $medium, 270, $method, $quality);
        $rotateThumb  = Jaws_Image::RotateImage($thumb, $thumb, 270, $method, $quality);

        if (
            !Jaws_Error::IsError($rotateNormal) &&
            !Jaws_Error::IsError($rotateMedium) &&
            !Jaws_Error::IsError($rotateThumb)
        ) {
            return true;
        }

        return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_ROTATE_TO_RIGHT'), _t('PHOO_NAME'));
    }

    /**
     * Rotate left given image
     * @param string $filename Path to image file
     * @param string $method   Method name
     * @param string $quality  Image quality
     */
    function RotateLeft($filename, $method, $quality)
    {
        $normal = $filename;
        $medium = Jaws_Image::GetMediumPath($filename);
        $thumb =  Jaws_Image::GetThumbPath($filename);

        if ($method !== 'GD') {
            $rotateNormal = Jaws_Image::RotateImage($normal, $normal, 90, $method, $quality);
        } else {
            $rotateNormal = true;
        }
        $rotateMedium = Jaws_Image::RotateImage($medium, $medium, 90, $method, $quality);
        $rotateThumb  = Jaws_Image::RotateImage($thumb, $thumb, 90, $method, $quality);

        if (
            !Jaws_Error::IsError($rotateNormal) &&
            !Jaws_Error::IsError($rotateMedium) &&
            !Jaws_Error::IsError($rotateThumb)
        ) {
            return true;
        }

        return new Jaws_Error(_t('GLOBAL_ERROR_IMAGE_CANT_ROTATE_TO_LEFT'), _t('PHOO_NAME'));
    }

    /**
     * Gets EXIF thumbnail
     *
     * @param   string  $source Image path
     * @param   string  $unkown Unknown image to return if image doesn't have a thumb
     * @return  binary  Exif thumbnail
     */
    function GetEXIFThumbnail($source, $unknown)
    {
        if (strpos($source, '../')) {
            return false;
        }
        $ext       = strtolower(substr($source, strrpos($source,'.')+1));
        $valid_ext = array('jpg', 'jpeg');
        if (in_array($ext, $valid_ext)) {
            if ((function_exists('exif_thumbnail')) && (filesize($source) > 0)) {
                $image = exif_thumbnail($source, $width, $height, $type);
                if ($image !== false) {
                    header('Content-type: ' .image_type_to_mime_type($type));
                    print $image;
                    exit;
                }
            }
        }

        $unknown = $GLOBALS['app']->CheckImage($unknown);
        $ext = strtolower(substr($unknown, strrpos($unknown,'.')+1));
        header('Content-type: image/'.$ext);
        readfile($unknown);
        exit;
    }

    /**
     * Returns width and height of an image.
     *
     * NOTE: If getimagesize doesn't exists it will return the width/height in 100%
     *
     * @access  public
     */
    function GetImageSize($filename)
    {
        $size = array();
        $size['width']  = '100%';
        $size['height'] = '100%';

        if (!file_exists($filename)) {
            return $size;
        }

        if (function_exists('getimagesize')) {
            $info = getimagesize($filename);

            list($size['width'], $size['height']) = getimagesize($filename);
        }

        return $size;
    }

	/**
	 * mixed image_info( file $file [, string $out] )
	 *
	 * Returns information about $file.
	 *
	 * If the second argument is supplied, a string representing that information will be returned.
	 *
	 * Valid values for the second argument are IMAGE_WIDTH, 'width', IMAGE_HEIGHT, 'height', IMAGE_TYPE, 'type',
	 * IMAGE_ATTR, 'attr', IMAGE_BITS, 'bits', IMAGE_CHANNELS, 'channels', IMAGE_MIME, and 'mime'.
	 *
	 * If only the first argument is supplied an array containing all the information is returned,
	 * which will look like the following:
	 *
	 *    [width] => int (width),
	 *    [height] => int (height),
	 *    [type] => string (type),
	 *    [attr] => string (attributes formatted for IMG tags),
	 *    [bits] => int (bits),
	 *    [channels] => int (channels),
	 *    [mime] => string (mime-type)
	 *
	 * Returns false if $file is not a file, no arguments are supplied, $file is not an image, or otherwise fails.
	 *
	 **/
	function image_info($file = null, $out = null) 
	{
		// If $file is not supplied or is not a file, warn the user and return false.
		if (is_null($file) || !is_file($file)) {
			return false;
		}

		// Defines the keys we want instead of 0, 1, 2, 3, 'bits', 'channels', and 'mime'.
		$redefine_keys = array(
			'width',
			'height',
			'type',
			'attr',
			'bits',
			'channels',
			'mime',
		);

		// If $out is supplied, but is not a valid key, nullify it.
		if (!is_null($out) && !in_array($out, $redefine_keys)) $out = null;

		// Assign usefull values for the third index.
		$types = array(
			1 => 'GIF',
			2 => 'JPG',
			3 => 'PNG',
			4 => 'SWF',
			5 => 'PSD',
			6 => 'BMP',
			7 => 'TIFF(intel byte order)',
			8 => 'TIFF(motorola byte order)',
			9 => 'JPC',
			10 => 'JP2',
			11 => 'JPX',
			12 => 'JB2',
			13 => 'SWC',
			14 => 'IFF',
			15 => 'WBMP',
			16 => 'XBM'
		);
		$temp = array();
		$data = array();

		// Get the image info using getimagesize().
		// If $temp fails to populate, warn the user and return false.
		if (!$temp = Jaws_Image::GetImageSize($file)) {
			return false;
		}

		// Get the values returned by getimagesize()
		$temp = array_values($temp);

		// Make an array using values from $redefine_keys as keys and values from $temp as values.
		foreach ($temp AS $k => $v) {
			$data[$redefine_keys[$k]] = $v;
		}

		// Make 'type' usefull.
		$data['type'] = $types[$data['type']];

		// Return the desired information.
		return !is_null($out) ? $data[$out] : $data;    
	}

}