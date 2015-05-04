<?php
/**
 * Rounded PHP, Rounded corners made easy.
 *
 * rounded.php
 *
 * PHP version 5, GD version 2
 *
 * Copyright (C) 2008 Tree Fort LLC
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 * 
 * @category	Rounded PHP
 * @package		<none>
 * @author		Nevada Kent <dev@kingthief.com>
 * @version		1.1
 * @link		http://dev.kingthief.com
 * @link		http://dev.kingthief.com/demos/roundedphp
 * @link		http://www.sourceforge.net/projects/roundedphp
 */

define('JAWS_SCRIPT', 'rounded');
define('BASE_SCRIPT', basename(__FILE__));
define('PATH_SCRIPT', dirname(dirname(dirname(__FILE__))));
define('APP_TYPE',    'web');

// Redirect to the installer if JawsConfig can't be found.
if (!file_exists(PATH_SCRIPT . '/config/JawsConfig.php')) {
    header('Location: ' . PATH_SCRIPT . '/install/index.php');
    exit;
} else {
    require PATH_SCRIPT . '/config/JawsConfig.php';
}

# Required classes
require_once 'Rounded/RGB.php';
require_once 'Rounded/Corner.php';
require_once 'Rounded/Rectangle.php';
require_once 'Rounded/Side.php';

$font_file  = JAWS_PATH . 'libraries/rounded_php/arialbd.ttf' ;
$background_color = '#ffffff' ;
$cache_images = true ;
$cache_folder = JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps";

extract($_GET);

# Options =
#  - Shape: 'c' (or 'corner'), 'r' (or 'rectangle'), 's' (or 'side')
#  - Radius: (integer >= 0)
#  - Width: (integer >= 2)
#  - Height: (integer >= 2)
#  - Foreground Color: (hex code - 3 or 6 char)
#  - Background Color: (hex code - 3 or 6 char)
#  - Border Color: (hex code - 3 or 6 char)
#  - Border Width: (integer >= 0)
#  - Orientation: 'tl' (or 'lt'), 'tr' (or 'rt'), 'bl' (or 'lb'), 'br' (or 'rb')
#  - Side: 't', 'top', 'l', 'left', 'b', 'bottom', 'r', 'right'
#  - Antialias: 1, 0
#  - Format: 'png', 'gif', 'jpg' (or 'jpeg')
#  - Background Transparent: 1, 0
#  - Border Transparent: 1, 0
#  - Foreground Transparent: 1, 0
#  - Transparent Color: (hex code - 3 or 6 char)
#  - Has a Tail: (Used for Info Labels) 1, 0

$shape = isset($shape) ? strval($shape) : (isset($sh) ? strval($sh) : 'c');
$radius = isset($radius) ? intval($radius) : (isset($r) ? intval($r) : 10);
$foreground = isset($foreground) ? strval($foreground) : (isset($fg) ? strval($fg) : 'CCC');
$background = isset($background) ? strval($background) : (isset($bg) ? strval($bg) : 'FFF');
$bordercolor = isset($bordercolor) ? strval($bordercolor) : (isset($bc) ? strval($bc) : '000');
$borderwidth = isset($borderwidth) ? intval($borderwidth) : (isset($bw) ? intval($bw) : 0);
$orientation = isset($orientation) ? strval($orientation) : (isset($o) ? strval($o) : 'tl');
$side = isset($side) ? strval($side) : (isset($si) ? strval($si) : 'top');
$antialias = isset($antialias) ? (bool) intval($antialias) : (isset($aa) ? (bool) intval($aa) : true);
$format = isset($format) ? strval($format) : (isset($f) ? strval($f) : 'png');
$bgtransparent = isset($bgtransparent) ? (bool) intval($bgtransparent) : (isset($bgt) ? (bool) intval($bgt) : false);
$btransparent = isset($btransparent) ? (bool) intval($btransparent) : (isset($bt) ? (bool) intval($bt) : false);
$fgtransparent = isset($fgtransparent) ? (bool) intval($fgtransparent) : (isset($fgt) ? (bool) intval($fgt) : false);
$transparentcolor = isset($transparentcolor) ? strval($transparentcolor) : (isset($tc) ? strval($tc) : NULL);
$tail = isset($tail) ? (bool) intval($tail) : (isset($ta) ? (bool) intval($ta) : false);

switch (strtolower($format)) {
	case 'jpg' :
		$mime_type = 'image/jpg' ;
	case 'jpeg' :
		$mime_type = 'image/jpeg' ;
		$transparentcolor = NULL;
	case 'gif' :
		$mime_type = 'image/gif' ;
		$bgtransparent = false;
		$btransparent = false;
		$fgtransparent = false;
		break;
	case 'png' :
		$mime_type = 'image/png' ;
		$transparentcolor = NULL;
		break;
}

/*
    Dynamic Heading Generator
    By Stewart Rosenberger
    http://www.stewartspeak.com/headings/    

    This script generates PNG images of text, written in
    the font/size that you specify. These PNG images are passed
    back to the browser. Optionally, they can be cached for later use. 
    If a cached image is found, a new image will not be generated,
    and the existing copy will be sent to the browser.

    Additional documentation on PHP's image handling capabilities can
    be found at http://www.php.net/image/    
*/

// check for GD support
if(!function_exists('ImageCreate'))
    fatal_error('Error: Server does not support PHP image generation') ;

// clean up text
if(empty($_GET['text']) && intval($fontsize) != 0)
    fatal_error('Error: No text specified.') ;
    
$transparent_background = isset($bgtransparent) ? (bool) intval($bgtransparent) : (isset($bgt) ? (bool) intval($bgt) : true);
$font_size = isset($fontsize) ? intval($fontsize) : (isset($fs) ? intval($fs) : 12);
$font_color = isset($fontcolor) ? strval($fontcolor) : (isset($fc) ? strval($fc) : '000000');
$text = $_GET['text'];
$subfont_size = isset($subfontsize) ? intval($subfontsize) : (isset($sfs) ? intval($sfs) : (isset($font_size) ? intval($font_size) : 10));
$subtext = $_GET['subtext'] ;
if(get_magic_quotes_gpc()) {
    $text = stripslashes($text) ;
	$subtext = stripslashes($subtext) ;
}
$text = javascript_to_html($text) ;
$subtext = javascript_to_html($subtext) ;

$send_buffer_size = 4096 ;

header('Cache-Control: max-age=3600, must-revalidate');
header('Pragma: cache');
$offset = 48 * 60 * 60;
$expire = "Expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
header($expire);

// look for cached copy, send if it exists
$hash = md5(basename($font_file) . $_SERVER['QUERY_STRING']) ;
$cache_filename = $cache_folder . '/' . $hash ;
if($cache_images && ($file = @fopen($cache_filename,'rb'))) {
    header('Content-type: ' . $mime_type) ;
    while(!feof($file))
        print(($buffer = fread($file,$send_buffer_size))) ;
    fclose($file) ;
    //exit ;
} else {
	// check font availability
	$font_found = is_readable($font_file) ;
	if(!$font_found)
	{
	    fatal_error('Error: The server is missing the specified font.') ;
	}

	// create image
	$background_rgb = hex_to_rgb($foreground) ;
	$font_rgb = hex_to_rgb($font_color) ;
	$dip = get_dip($font_file,$font_size) ;
	$box = @ImageTTFBBox($font_size,0,$font_file,$text) ;
	if (!empty($subtext)) {
		$dip2 = get_dip($font_file,$subfont_size) ;
		$box2 = @ImageTTFBBox($subfont_size,0,$font_file,$subtext) ;

		$boxes = array();
		$boxes[0] = $box[0] < $box2[0] ? $box2[0] : $box[0];
		$boxes[1] = $box[1] < $box2[1] ? $box2[1] : $box[1];
		$boxes[2] = $box[2] < $box2[2] ? $box2[2] : $box[2];
		$boxes[3] = $box[3] < $box2[3] ? $box2[3] : $box[3];
		$boxes[4] = $box[4] < $box2[4] ? $box2[4] : $box[4];
		$boxes[5] = $box[5] < $box2[5] ? $box2[5] : $box[5];

		$dips = $dip < $dip2 ? $dip2 : $dip;

		$width = isset($width) ? intval($width) : (isset($w) ? intval($w) : abs($boxes[2]-$boxes[0]+($radius*2)));
		$height = isset($height) ? intval($height) : (isset($h) ? intval($h) : abs($box[5]-$dip+($box[5]-$dip/15))+abs($box2[5]-$dip2));
	} else {
		$width = isset($width) ? intval($width) : (isset($w) ? intval($w) : abs($box[2]-$box[0]+($radius*2)));
		$height = isset($height) ? intval($height) : (isset($h) ? intval($h) : abs($box[5]-$dip+($box[5]-$dip/15)));
	}

	$params = array('radius'		=> $radius,
					'width'			=> $width,
					'height'		=> $height,
					'foreground'	=> $foreground,
					'background'	=> $background,
					'borderwidth'	=> $borderwidth,
					'bordercolor'	=> $bordercolor,
					'orientation'	=> $orientation,
					'side'			=> $side,
					'antialias'		=> $antialias,
					'bgtransparent'	=> $bgtransparent,
					'btransparent'	=> $btransparent,
					'fgtransparent'	=> $fgtransparent,
					'tail'			=> $tail);

	switch (strtolower($shape)) {
		case 'r' :
		case 'rect' :
		case 'rectangle' :
			$img = Rounded_Rectangle::create($params);
			break;
		case 's' :
		case 'side' :
			$img = Rounded_Side::create($params);
			break;
		case 'c' :
		case 'corner' :
		default :
			$img = Rounded_Corner::create($params);
			break;
	}

	imagesavealpha($img, $fgtransparent || $bgtransparent || ($btransparent && $borderwidth > 0));

	$img2 = @ImageCreate(abs($box[2]-$box[0])+round($radius/2),abs($box[5]-$dip)) ;

	if(!$img || !$box)
	{
	    fatal_error('Error: The server could not create this heading image.') ;
	}

	$background = @ImageColorAllocate($img2,$background_rgb['red'],
	    $background_rgb['green'],$background_rgb['blue']) ;
	$font_color = ImageColorAllocate($img2,$font_rgb['red'],
	    $font_rgb['green'],$font_rgb['blue']) ;   
	//ImageTTFText($img2,$font_size,0,(-$box[0])+$radius,abs($box[5]-$box[3])-$box[1]+round($radius/1.5),
	//    $font_color,$font_file,$text) ;
	ImageTTFText($img2,$font_size,0,-$box[0],abs($box[5]-$box[3])-$box[1],
	    $font_color,$font_file,$text) ;
	imagecopy($img, $img2, round($radius/2), round($radius/2), 0, 0, abs($box[2]-$box[0])+round($radius/2), abs($box[5]-$dip));

	if (!empty($subtext)) {
		$img3 = @ImageCreate(abs($box2[2]-$box2[0]),abs($box2[5]-$dip2)) ;
		$background = @ImageColorAllocate($img3,$background_rgb['red'],
		    $background_rgb['green'],$background_rgb['blue']) ;
		$font_color = ImageColorAllocate($img3,$font_rgb['red'],
		    $font_rgb['green'],$font_rgb['blue']) ;   
		//ImageTTFText($img2,$font_size,0,(-$box[0])+$radius,abs($box[5]-$box[3])-$box[1]+round($radius/1.5),
		//    $font_color,$font_file,$text) ;
		ImageTTFText($img3,$subfont_size,0,-$box2[0],abs($box2[5]-$box2[3])-$box2[1],
		    $font_color,$font_file,$subtext) ;
		//imagecopy($img, $img3, $width-(abs($box2[2]-$box2[0])+round($radius/2)), abs($box[5]-$dip)+round(abs($box2[5]-$dip2)/3)+3, 0, 0, abs($box2[2]-$box2[0]), abs($box2[5]-$dip2));
		imagecopy($img, $img3, round($radius/2), abs($box[5]-$dip)+round(abs($box2[5]-$dip2)/3)+3, 0, 0, abs($box2[2]-$box2[0]), abs($box2[5]-$dip2));
	}

	if (!is_null($transparentcolor) && $transparentcolor) {
		$rgb = new Rounded_RGB($transparentcolor);
		$color = imagecolorallocate($img, $rgb->red, $rgb->green, $rgb->blue);
		imagecolortransparent($img, $color);
	}

	// save copy of image for cache
	if($cache_images)
	{
	    @ImagePNG($img,$cache_filename) ;
	}

	switch (strtolower($format)) {
		case 'jpg' :
		case 'jpeg' :
			header('Content-Type: image/jpeg');
			imagejpeg($img, '', 100);
			break;
		case 'gif' :
			header('Content-Type: image/gif');
			imagegif($img);
			break;
		case 'png' :
		default :
			header('Content-Type: image/png');
			imagepng($img);
			break;
	}
}

/*
	try to determine the "dip" (pixels dropped below baseline) of this
	font for this size.
*/
function get_dip($font,$size)
{
	$test_chars = 'abcdefghijklmnopqrstuvwxyz' .
			      'ABCDEFGHIJKLMNOPQRSTUVWXYZ' .
				  '1234567890' .
				  '!@#$%^&*()\'"\\/;.,`~<>[]{}-+_-=' ;
	$box = @ImageTTFBBox($size,0,$font,$test_chars) ;
	return $box[3] ;
}


/*
    attempt to create an image containing the error message given. 
    if this works, the image is sent to the browser. if not, an error
    is logged, and passed back to the browser as a 500 code instead.
*/
function fatal_error($message)
{
    // send an image
    if(function_exists('ImageCreate'))
    {
        $width = ImageFontWidth(5) * strlen($message) + 10 ;
        $height = ImageFontHeight(5) + 10 ;
		if($image = ImageCreate($width,$height))
        {
            $background = ImageColorAllocate($image,255,255,255) ;
            $text_color = ImageColorAllocate($image,0,0,0) ;
            ImageString($image,5,5,5,$message,$text_color) ;    
            header('Content-type: image/png') ;
            ImagePNG($image) ;
            ImageDestroy($image) ;
            exit ;
        }
    }

    // send 500 code
    header("HTTP/1.0 500 Internal Server Error") ;
    print($message) ;
    exit ;
}


/* 
    decode an HTML hex-code into an array of R,G, and B values.
    accepts these formats: (case insensitive) #ffffff, ffffff, #fff, fff 
*/    
function hex_to_rgb($hex)
{
    // remove '#'
    if(substr($hex,0,1) == '#')
        $hex = substr($hex,1) ;

    // expand short form ('fff') color
    if(strlen($hex) == 3)
    {
        $hex = substr($hex,0,1) . substr($hex,0,1) .
               substr($hex,1,1) . substr($hex,1,1) .
               substr($hex,2,1) . substr($hex,2,1) ;
    }

    if(strlen($hex) != 6)
        fatal_error('Error: Invalid color "'.$hex.'"') ;

    // convert
    $rgb['red'] = hexdec(substr($hex,0,2)) ;
    $rgb['green'] = hexdec(substr($hex,2,2)) ;
    $rgb['blue'] = hexdec(substr($hex,4,2)) ;

    return $rgb ;
}


/*
    convert embedded, javascript unicode characters into embedded HTML
    entities. (e.g. '%u2018' => '&#8216;'). returns the converted string.
*/
function javascript_to_html($text)
{
    $matches = null ;
    preg_match_all('/%u([0-9A-F]{4})/i',$text,$matches) ;
    if(!empty($matches)) for($i=0;$i<sizeof($matches[0]);$i++)
        $text = str_replace($matches[0][$i],
                            '&#'.hexdec($matches[1][$i]).';',$text) ;

    return $text ;
}

?>