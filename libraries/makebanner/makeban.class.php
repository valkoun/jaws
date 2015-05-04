<?PHP
/*
makeban class - generate automated/user generated banners
version 1.1 7/14/2007

Copyright (c) 2007, Wagon Trader (an Oregon USA business)
All rights reserved.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS 
OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR 
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class makeban{

	//*********************************************************
	// Settings
	//*********************************************************

	//The root and folder containing the class and example scripts. Assumes 'makeban', change to fit your needs
	var $folder = '';

	//The folder containing the true type fonts for the script to write text
	var $fontDir = 'fonts';

	//The folder to write saved images to, this must be have write permissions or the file will not be written.
	var $saveDir = 'save';

	//The folder containing the image background templates
	var $templateDir = 'templates';

	//The shadow color
	var $shadowColor = 'C0C0C0';

	var $banner; //Banner Image Resource
	var $color; //Color Array
	var $fonts; //Font Array
	var $fontSizes; //Font Size Array
	var $palleteResolution; //Supported GD Resolution
	var $error; //Error Text
	var $siteRoot; //site document root
	
	
	/*makeban class initialization
	usage: makeban([int imageXpos],[int imageYpos]);
	params: imageXpos = the width of the banner
			imageYpos = the height of the banner

	This method is automatically called when the class is initialized. It checks the available resolutions and
	sets the palleteResolution class variable to high or low. If a problem is found, it sets the class error variable
	with a text response. If the imageXpos and imageYpos params are set, it calls the createImage method to initialize
	the banner. It finally initializes the color array provided in this class.

	retuns:	true if succesfull, false if problem with execution
	*/
	function makeban($imageXPos=468, $imageYpos=60, $init=1){
		
		$this->saveDir = JAWS_DATA . 'cache/images';
		$this->folder = JAWS_PATH . 'libraries/makebanner';
		$this->siteRoot = $this->folder;
		//echo $this->siteRoot.'<br />';
		if(function_exists(imagecreatetruecolor)){
			$this->palleteResolution = 'high';
		}elseif (function_exists(imagecreate)){
			$this->palleteResolution = 'low';
		}else{
			$this->error = 'Problem finding GD Library';
			return(false);
		}
		if ($init){
			$this->initColors();
			$this->initFonts();
			$this->initFontSizes();
			$this->initGBS();
			$this->initIBS();
		}
		$this->createImage($imageXpos, $imageYpos);
		return(true);
	}

	/* method:	showDefault
	usage:	showDefault();
	params:	void
	
	This method will create and stream a banner image using the default class variables from $gBS[0].
	*/
	function showDefault(){
		if(is_resource($this->banner)){
			imagedestroy($this->banner);
		}
		$imageXpos = $this->gBS[0]['imageXpos'];
		$imageYpos = $this->gBS[0]['imageYpos'];
		//var_dump($this->gBS[0]['imageXpos']);
		//var_dump($this->gBS[0]['imageYpos']);
		$this->createImage($imageXpos,$imageYpos);
		$this->addBackground($this->gBS[0]['primBackgroundColor'], $this->gBS[0]['secBackgroundColor'], $this->gBS[0]['gradientDirection']);
		$this->addBorder($this->gBS[0]['borderColor'], $this->gBS[0]['borderSize']);
		for($x=0;$x<count($this->gBS[0]['text']);$x++){
			$fontFile = $this->gBS[0]['font'][$x];
			$this->addText($this->gBS[0]['text'][$x], $this->gBS[0]['textXpos'][$x], $this->gBS[0]['textYpos'][$x], $this->gBS[0]['textAngle'][$x], $this->gBS[0]['textColor'][$x], $this->gBS[0]['fontSize'][$x], $this->fonts[$fontFile]['file'], $this->gBS[0]['textShadow'][$x]);
		}
		$this->showImage();
	}
	
	/* method:	createImage
	usage:	createImage(imageXpos, imageYpos)
	params: imageXpos = the width of the banner
			imageYpos = the height of the banner
	
	This method will initialize the pallete and image dimensions using the palleteResolution class variable set
	when the class was initialized. Used for generated backgrounds.
	
	retuns:	true if succesfull, false if problem with execution
	*/
	function createImage($imageXpos='', $imageYpos=''){
		if (is_null($imageXpos) || is_null($imageYpos)) {
			$this->error = 'Height or width cannot be null';
			return(false);
		}
		$this->imageXpos = $imageXpos;
		$this->imageYpos = $imageYpos;
		switch($this->palleteResolution){
		case 'high':
			//var_dump($imageXpos);
			//var_dump($imageYpos);
			if (!$this->banner = imagecreatetruecolor($imageXpos,$imageYpos)){
				$this->error = 'Failed to create image';
				return(false);
			}
			break;
		case 'low':
			if (!$this->banner = imagecreate($imageXpos,$imageYpos)){
				$this->error = 'Failed to create image';
				return(false);
			}
			break;
		default:
			$this->error = 'Pallete Resolution not set';
			return(false);
		}
		return(true);
	}
	
	/* method useImage
	usage:	useImage(imageBackground);
	params: imageBackground = the background image
	
	This method uses the specified image for the background
	
	retuns:	true if succesfull, false if problem with execution and sets the class error variable
	*/
	function useImage($imageBackground){
		//$fileTypeTemp = explode('.', $imageBackground);
		$imageBackgroundType = '';
		$imageBackgroundType = strtolower(strrchr($imageBackground,"."));
		//if(count($fileTypeTemp > 1)){
		if ($imageBackgroundType != '') {
			//$fileTypePoint = count($fileTypeTemp) - 1;
			//$imageBackgroundType = $fileTypeTemp[$fileTypePoint];
			switch($imageBackgroundType){
			case '.png':
				$this->banner = imagecreatefrompng($this->siteRoot.'/'.$this->templateDir.'/'.$imageBackground);
				imagealphablending($this->banner, true); // setting alpha blending on
				imagesavealpha($this->banner, true); // save alphablending setting (important)
				break;
			case '.gif':
				$this->banner = imagecreatefromgif($this->siteRoot.'/'.$this->templateDir.'/'.$imageBackground);
				imagealphablending($this->banner, true); // setting alpha blending on
				imagesavealpha($this->banner, true); // save alphablending setting (important)
				break;
			default:
				$this->banner = imagecreatefromjpeg($this->siteRoot.'/'.$this->templateDir.'/'.$imageBackground);
			}
		} else {
			$this->error = 'Background image filename is incorrect: '.$this-siteRoot.'/'.$this->templateDir.'/'.$imageBackground;
			return(false);
		}
		return(true);
	}
	
	/* method:	addBackground
	usage:	addBackground(primBackgroundColor, [secBackgroundColor], gradientDirection);
	params:	primBackgroundColor = html color code for the primary background color. Will use the color array in this class to
				determine the RGB values.
			secBackgroundColor = html color code for the secondary background color. Only used as the fade to color if the
				background color is to ba a gradient.
			gradientDirection = 'horizontal' to perform gradient fade from left to right or 'vertical' to perform gradient
				fade from top to bottom.
	
	This method will generate a background using the primBackgroundColor specified. If a secBackgroundColor is set and different
	than the primBackgroundColor, a color gradient will be generated from the primBackgroundColor fading to the secBackground color
	in the direction specified by gradientDirection.
	*/
	function addBackground($primBackgroundColor, $secBackgroundColor='', $gradientDirection='horizontal'){
		
		if (!isset($this->color[$primBackgroundColor])) {
			$textColorArray = $this->_html2rgb($primBackgroundColor);
			if ($textColorArray !== false && isset($textColorArray[0]) && isset($textColorArray[1]) && isset($textColorArray[2])) {
				$this->color[$primBackgroundColor][0] = 'Custom ['.$primBackgroundColor.']';
				$this->color[$primBackgroundColor][1] = $textColorArray[0];
				$this->color[$primBackgroundColor][2] = $textColorArray[1];
				$this->color[$primBackgroundColor][3] = $textColorArray[2];
			}
		}
		if (!isset($this->color[$secBackgroundColor])) {
			$textColorArray = $this->_html2rgb($secBackgroundColor);
			if ($textColorArray !== false && isset($textColorArray[0]) && isset($textColorArray[1]) && isset($textColorArray[2])) {
				$this->color[$secBackgroundColor][0] = 'Custom ['.$secBackgroundColor.']';
				$this->color[$secBackgroundColor][1] = $textColorArray[0];
				$this->color[$secBackgroundColor][2] = $textColorArray[1];
				$this->color[$secBackgroundColor][3] = $textColorArray[2];
			}
		}
		if($this->palleteResolution == 'high' and ($primBackgroundColor != $secBackgroundColor and $secBackgroundColor != '')){
			//Display Gradient
			$numberLoops = ($gradientDirection == 'horizontal') ? $this->imageYpos : $this->imageXpos;
			$nextColorRed = $this->color[$primBackgroundColor][1];
			$nextColorGreen = $this->color[$primBackgroundColor][2];
			$nextColorBlue = $this->color[$primBackgroundColor][3];
			$stepColorRed = ($this->color[$secBackgroundColor][1]-$this->color[$primBackgroundColor][1]) / $numberLoops;
			$stepColorGreen = ($this->color[$secBackgroundColor][2]-$this->color[$primBackgroundColor][2]) / $numberLoops;
			$stepColorBlue = ($this->color[$secBackgroundColor][3]-$this->color[$primBackgroundColor][3]) / $numberLoops;
			for($x=0;$x<$numberLoops;$x++){
				$nextColorRed = $nextColorRed + $stepColorRed;
				$showColorRed = floor($nextColorRed);
				$nextColorGreen = $nextColorGreen + $stepColorGreen;
				$showColorGreen = floor($nextColorGreen);
				$nextColorBlue = $nextColorBlue + $stepColorBlue;
				$showColorBlue = floor($nextColorBlue);
				if($gradientDirection == 'vertical'){
					$colorIdent = imagecolorallocate($this->banner, $showColorRed, $showColorGreen, $showColorBlue);
					imagefilledrectangle($this->banner, $x, 0, $x, $this->imageYpos-1, $colorIdent);
				}else{
					$colorIdent = imagecolorallocate($this->banner, $showColorRed, $showColorGreen, $showColorBlue);
					imagefilledrectangle($this->banner, 0, $x, $this->imageXpos-1, $x+2, $colorIdent);
				}
			}
		}else{
			//Display Solid
			$colorIdent = imagecolorallocate($this->banner, $this->color[$primBackgroundColor][1], $this->color[$primBackgroundColor][2], $this->color[$primBackgroundColor][3]);
			imagefilledrectangle($this->banner, 0, 0, $this->imageXpos-1, $this->imageYpos-1, $colorIdent);
		}
		
	}
	
	/* method:	addBorder
	usage: addBorder(borderColor, [borderSize])
	params:	borderColor = the html color to use for the border. Will use the color array in this class to determine the RGB
				value to use.
			borderSize = The number of pixels to draw the border.
	
	This method will add a border to the generated background using the specified borderColor. By default this border will be
	3 pixels wide, or you may specify the width using the borderSize param.
	*/
	function addBorder($borderColor, $borderSize=3){
		if (!isset($this->color[$borderColor])) {
			$textColorArray = $this->_html2rgb($borderColor);
			if ($textColorArray !== false && isset($textColorArray[0]) && isset($textColorArray[1]) && isset($textColorArray[2])) {
				$this->color[$borderColor][0] = 'Custom ['.$borderColor.']';
				$this->color[$borderColor][1] = $textColorArray[0];
				$this->color[$borderColor][2] = $textColorArray[1];
				$this->color[$borderColor][3] = $textColorArray[2];
			}
		}
		$colorIdent = imagecolorallocate($this->banner, $this->color[$borderColor][1], $this->color[$borderColor][2], $this->color[$borderColor][3]);
		imagerectangle($this->banner,0,0,$this->imageXpos-1,$this->imageYpos-1,$colorIdent);
		imagerectangle($this->banner,0+$borderSize,0+$borderSize,$this->imageXpos-1-$borderSize,$this->imageYpos-1-$borderSize,$colorIdent);
		imagefilltoborder($this->banner,1,1,$colorIdent,$colorIdent);
	}
	
	/* method: addText
	usage: addText(text,[textXpos],[textYpos],[textAngle],[textColor],[fontSize],font,[textShadow]);
	params:	text = the text to write to the banner
			textXpos = the horizontal position to start writing the center of the text
			textYpos = the vertical postion to start writing the text
			textAngle = the angle to write the text. Accepts degrees in a value range from 1-360 and angles counter clockwise.
			textColor = the html color code to use for the text. Will use the color array in this class to dermine the RGB values.
			fontSize = the font size in points (GD2) or pixels (GD1)
			font = the font to write the text
			textShadow = Places a shadow behind the text using the class variable shadowColor specified
	
	This method will add one line of text to the image using the supplied params.
	*/
	function addText($text='No Text',$textXpos=5,$textYpos=5,$textAngle=0,$textColor='000000',$fontSize='10',$font='',$textShadow=false){
		$font = $this->siteRoot.'/'.$this->fontDir.'/'.$font;
		if($textShadow == true){
			//Adding Shadow
			$colorIdent = imagecolorallocate($this->banner, $this->color[$this->shadowColor][1], $this->color[$this->shadowColor][2], $this->color[$this->shadowColor][3]);
			imagettftext($this->banner, $fontSize, (double)$textAngle, (int)$textXpos+2, (int)$textYpos+2, $colorIdent, $font, $text);
		}
		if (!isset($this->color[$textColor])) {
			$textColorArray = $this->_html2rgb($textColor);
			if ($textColorArray !== false && isset($textColorArray[0]) && isset($textColorArray[1]) && isset($textColorArray[2])) {
				$this->color[$textColor][0] = 'Custom ['.$textColor.']';
				$this->color[$textColor][1] = $textColorArray[0];
				$this->color[$textColor][2] = $textColorArray[1];
				$this->color[$textColor][3] = $textColorArray[2];
			}
		}
		$colorIdent = imagecolorallocate($this->banner, $this->color[$textColor][1], $this->color[$textColor][2], $this->color[$textColor][3]);
		imagettftext($this->banner, $fontSize, (double)$textAngle, (int)$textXpos, (int)$textYpos, $colorIdent, $font, $text);
	}

	/* method showImage
	usage:	showImage([showType]);
	params:	showType = Type of image to display (png, gif, jpg) Defaults to jpg for any empty or unknown value.
	
	This method will generate the header and stream the banner's image to the browser.
	*/
	function showImage($showType=''){
		switch($showType){
		case 'png':
			header("Content-type: image/png");
			imagepng($this->banner);
			break;
		case 'gif':
			header("Content-type: image/gif");
			imagegif($this->banner);
			break;
		default:
			header("Content-type: image/jpeg");
			imagejpeg($this->banner);
		}
		imagedestroy($this->banner);
	}
	
	/* method sendImage
	usage:	sendImage([imageName],[imageType]);
	params:	imageName = the name to download the image as
			imageType = Type of image to download as (png, gif, jpg) Defaults to jpg for any empty or unknown value.
	
	This method will generate the headers and stream the banner's image for downloading from the browser.
	*/
	function sendImage($imageName='mybanner',$imageType='jpg'){
		$dispHeader = 'Content-Disposition: attachment; filename='.$name.'.'.$type;
		switch($imageType){
		case 'png':
			header("Content-type: image/png");
			header($dispHeader); 
			imagepng($this->banner);
			break;
		case 'gif':
			header("Content-type: image/gif");
			header($dispHeader); 
			imagegif($this->banner);
			break;
		default:
			header("Content-type: image/jpeg");
			header($dispHeader); 
			imagejpeg($this->banner);
		}
		imagedestroy($this->banner);
	}

	/* method saveImage
	usage:	saveImage([imagerType],[imageName],[imageQuality])
	params:	imageType = Type of image to save as (png, gif, jpg) Defaults to jpg for any empty or unknown value.
			imageName = the full root path and name to save the banner's image as
			imageQuality = the image quality from 1-100 (jpg file only)
	
	This method will save the banner's image as the named file in the specified folder.
	*/
	function saveImage($imageType='jpg',$imageName='',$imageQuality=80){
		if($imageName != ''){
			$fullName = $this->saveDir.'/'.$imageName.'.'.$imageType;
		}else{
			$fullName = $this->saveDir.'/'.'ban_'.rand().'.'.$imageType;
		}
		switch($imageType){
		case 'png':
			imagepng($this->banner,$fullName);
			break;
		case 'gif':
			imagegif($this->banner,$fullName);
			break;
		default:
			imagejpeg($this->banner,$fullName,$imageQuality);
		}
		imagedestroy($this->banner);
	}
	
	/* method createGenBanSelect
	usage:	createGenBanSelect([var_name], [options])
	params:	var_name = Variable name to use for select field, defaults to generatedBanner.
			options = Any options for the select field.
	
	This function generates a selection list of predefined banners using the class array $gBS
	*/
    function createGenBanSelect($var_name='generatedBanner', $options='') {

        $select = '<select name="'.$var_name.'" '.$options.' onChange="this.form.submit();">';
		for($x=0;$x<count($this->gBS);$x++){
			$select .= '<option value="'.$x.'">'.$this->gBS[$x]['title'].'</option>';
		}
		$select .= '</select>';
        return $select;
    }
	
	/* method createImgBanSelect
	usage:	createImgBanSelect([var_name], [value], [options])
	params:	var_name = Variable name to use for select field, defaults to imageBanner.
			value = The value the user has selected.
			options = Any options for the select field.
	
	This function generates a selection list of image templates using the class array $iBS
	*/
	function createImgBanSelect($var_name='imageBanner', $value=0, $options=''){
	
		$select = '<select name="'.$var_name.'" '.$options.' onChange="this.form.submit();">';
		for($x=0;$x<count($this->iBS);$x++){
			$imgBanSelected = ($value == $x) ? 'selected' : '';
			$select .= '<option value="'.$x.'" '.$imgBanSelected.'>'.$this->iBS[$x]['title'].'</option>';
		}
		$select .= '</select>';
        return $select;
	}
	
	/* method createColorSelect
	usage:	createColorSelect([var_name], [value], [options])
	params:	var_name = Variable name to use for select field, defaults to colorSelect.
			value = The value the user has selected.
			options = Any options for the select field.
	
	This function generates a selection list of colors using the class array $color
	*/
	function createColorSelect($var_name='colorSelect', $value=0, $options=''){
	
		$select = '<select name="'.$var_name.'" '.$options.' onChange="this.form.submit();">';
		foreach($this->color as $hex => $key){
			$colorSelected = ($value == $hex) ? 'selected' : '';
			$select .= '<option value="'.$hex.'" '.$colorSelected.'>'.$this->color[$hex][0].'</option>';
		}
		$select .= '</select>';
		return $select;
	}
	
	/* method createFontSelect
	usage:	createFontSelect([var_name], [value], [options])
	params:	var_name = Variable name to use for select field, defaults to font[].
			value = The value the user has selected.
			options = Any options for the select field.
	
	This function generates a selection list of available fonts using the class array $fonts
	*/
	function createFontSelect($var_name='font[]', $value=0, $options=''){
	
		$select = '<select name="'.$var_name.'" '.$options.' onChange="this.form.submit();">';
		for($x=1;$x<=count($this->fonts);$x++){
			$fontSelected = ($value == $x) ? 'selected' : '';
			$select .= '<option value="'.$x.'" '.$fontSelected.'>'.$this->fonts[$x]['name'].'</option>';
		}
		$select .= '</select>';
        return $select;
	}

	/* method createFontSizeSelect
	usage:	createFontSizeSelect([var_name], [value], [options])
	params:	var_name = Variable name to use for select field, defaults to fontSize[].
			value = The value the user has selected.
			options = Any options for the select field.
	
	This function generates a selection list of available fonts using the class array $fontSizes
	*/
	function createFontSizeSelect($var_name='fontSize[]', $value=0, $options=''){
	
		$select = '<select name="'.$var_name.'" '.$options.' onChange="this.form.submit();">';
		for($x=1;$x<=count($this->fontSizes);$x++){
			$fontSizeSelected = ($value == $this->fontSizes[$x]) ? 'selected' : '';
			$select .= '<option value="'.$this->fontSizes[$x].'" '.$fontSizeSelected.'>'.$this->fontSizes[$x].'</option>';
		}
		$select .= '</select>';
        return $select;
	}

	/* method initColors
	usage:	initColor();
	params:	void
	
	This method intializes the colors used by the class. You can add/delete/modify any of these values as long as
	the array [0] is the title, [1] = the red value, [2] = the green value and [3] = the blue value (RGB) to display.
	*/
	function initColors(){

		$this->color['000000'][0] = 'Black';
		$this->color['000000'][1] = 0;
		$this->color['000000'][2] = 0;
		$this->color['000000'][3] = 0;
		
		$this->color['F0F8FF'][0] = 'Alice Blue';
		$this->color['F0F8FF'][1] = 240;
		$this->color['F0F8FF'][2] = 248;
		$this->color['F0F8FF'][3] = 255;
		
		$this->color['FAEBD7'][0] = 'Antique White';
		$this->color['FAEBD7'][1] = 250;
		$this->color['FAEBD7'][2] = 235;
		$this->color['FAEBD7'][3] = 215;
		
		$this->color['00FFFF'][0] = 'Aqua';
		$this->color['00FFFF'][1] = 0;
		$this->color['00FFFF'][2] = 255;
		$this->color['00FFFF'][3] = 255;
		
		$this->color['7FFFD4'][0] = 'Aqua Marine';
		$this->color['7FFFD4'][1] = 127;
		$this->color['7FFFD4'][2] = 255;
		$this->color['7FFFD4'][3] = 212;
		
		$this->color['F0FFFF'][0] = 'Azure';
		$this->color['F0FFFF'][1] = 240;
		$this->color['F0FFFF'][2] = 255;
		$this->color['F0FFFF'][3] = 255;
		
		$this->color['F5F5DC'][0] = 'Beige';
		$this->color['F5F5DC'][1] = 245;
		$this->color['F5F5DC'][2] = 245;
		$this->color['F5F5DC'][3] = 220;
		
		$this->color['FFE4C4'][0] = 'Bisque';
		$this->color['FFE4C4'][1] = 255;
		$this->color['FFE4C4'][2] = 228;
		$this->color['FFE4C4'][3] = 196;
		
		$this->color['FFEBCD'][0] = 'Blanched Almond';
		$this->color['FFEBCD'][1] = 255;
		$this->color['FFEBCD'][2] = 235;
		$this->color['FFEBCD'][3] = 205;
		
		$this->color['0000FF'][0] = 'Blue';
		$this->color['0000FF'][1] = 0;
		$this->color['0000FF'][2] = 0;
		$this->color['0000FF'][3] = 255;
		
		$this->color['8A2BE2'][0] = 'Blue Violet';
		$this->color['8A2BE2'][1] = 138;
		$this->color['8A2BE2'][2] = 43;
		$this->color['8A2BE2'][3] = 226;
		
		$this->color['A52A2A'][0] = 'Brown';
		$this->color['A52A2A'][1] = 165;
		$this->color['A52A2A'][2] = 42;
		$this->color['A52A2A'][3] = 42;
		
		$this->color['DEB887'][0] = 'Burlywood';
		$this->color['DEB887'][1] = 222;
		$this->color['DEB887'][2] = 184;
		$this->color['DEB887'][3] = 135;
		
		$this->color['5F9EA0'][0] = 'Cadet Blue';
		$this->color['5F9EA0'][1] = 95;
		$this->color['5F9EA0'][2] = 158;
		$this->color['5F9EA0'][3] = 160;
		
		$this->color['7FFF00'][0] = 'Chartreuse';
		$this->color['7FFF00'][1] = 127;
		$this->color['7FFF00'][2] = 255;
		$this->color['7FFF00'][3] = 0;
		
		$this->color['D2691E'][0] = 'Chocolate';
		$this->color['D2691E'][1] = 210;
		$this->color['D2691E'][2] = 105;
		$this->color['D2691E'][3] = 30;
		
		$this->color['FF7F50'][0] = 'Coral';
		$this->color['FF7F50'][1] = 255;
		$this->color['FF7F50'][2] = 127;
		$this->color['FF7F50'][3] = 80;
		
		$this->color['6495ED'][0] = 'Cornflower Blue';
		$this->color['6495ED'][1] = 100;
		$this->color['6495ED'][2] = 149;
		$this->color['6495ED'][3] = 237;
		
		$this->color['FFF8DC'][0] = 'Cornsilk';
		$this->color['FFF8DC'][1] = 255;
		$this->color['FFF8DC'][2] = 248;
		$this->color['FFF8DC'][3] = 220;
		
		$this->color['DC143C'][0] = 'Crimson';
		$this->color['DC143C'][1] = 237;
		$this->color['DC143C'][2] = 164;
		$this->color['DC143C'][3] = 61;
		
		$this->color['00FFFF'][0] = 'Cyan';
		$this->color['00FFFF'][1] = 0;
		$this->color['00FFFF'][2] = 255;
		$this->color['00FFFF'][3] = 255;
		
		$this->color['00008B'][0] = 'Dark Blue';
		$this->color['00008B'][1] = 0;
		$this->color['00008B'][2] = 0;
		$this->color['00008B'][3] = 139;
		
		$this->color['008B8B'][0] = 'Dark Cyan';
		$this->color['008B8B'][1] = 0;
		$this->color['008B8B'][2] = 139;
		$this->color['008B8B'][3] = 139;
		
		$this->color['B8860B'][0] = 'Dark Goldenrod';
		$this->color['B8860B'][1] = 184;
		$this->color['B8860B'][2] = 134;
		$this->color['B8860B'][3] = 11;
		
		$this->color['A9A9A9'][0] = 'Dark Gray';
		$this->color['A9A9A9'][1] = 169;
		$this->color['A9A9A9'][2] = 169;
		$this->color['A9A9A9'][3] = 169;
		
		$this->color['006400'][0] = 'Dark Green';
		$this->color['006400'][1] = 0;
		$this->color['006400'][2] = 100;
		$this->color['006400'][3] = 0;
		
		$this->color['BDB76B'][0] = 'Dark Khaki';
		$this->color['BDB76B'][1] = 189;
		$this->color['BDB76B'][2] = 183;
		$this->color['BDB76B'][3] = 107;
		
		$this->color['8B008B'][0] = 'Dark Magenta';
		$this->color['8B008B'][1] = 139;
		$this->color['8B008B'][2] = 0;
		$this->color['8B008B'][3] = 139;
		
		$this->color['556B2F'][0] = 'Dark Olive Green';
		$this->color['556B2F'][1] = 85;
		$this->color['556B2F'][2] = 107;
		$this->color['556B2F'][3] = 47;
		
		$this->color['FF8C00'][0] = 'Dark Orange';
		$this->color['FF8C00'][1] = 255;
		$this->color['FF8C00'][2] = 140;
		$this->color['FF8C00'][3] = 0;
		
		$this->color['9932CC'][0] = 'Dark Orchid';
		$this->color['9932CC'][1] = 153;
		$this->color['9932CC'][2] = 50;
		$this->color['9932CC'][3] = 204;
		
		$this->color['8B0000'][0] = 'Dark Red';
		$this->color['8B0000'][1] = 139;
		$this->color['8B0000'][2] = 0;
		$this->color['8B0000'][3] = 0;
		
		$this->color['E9967A'][0] = 'Dark Salmon';
		$this->color['E9967A'][1] = 233;
		$this->color['E9967A'][2] = 150;
		$this->color['E9967A'][3] = 122;
		
		
		$this->color['8FBC8F'][0] = 'Dark Sea Green';
		$this->color['8FBC8F'][1] = 143;
		$this->color['8FBC8F'][2] = 188;
		$this->color['8FBC8F'][3] = 143;
		
		$this->color['483D8B'][0] = 'Dark Slate Blue';
		$this->color['483D8B'][1] = 72;
		$this->color['483D8B'][2] = 61;
		$this->color['483D8B'][3] = 139;
		
		$this->color['2F4F4F'][0] = 'Dark Slate Gray';
		$this->color['2F4F4F'][1] = 47;
		$this->color['2F4F4F'][2] = 79;
		$this->color['2F4F4F'][3] = 79;
		
		$this->color['00CED1'][0] = 'Dark Turquoise';
		$this->color['00CED1'][1] = 0;
		$this->color['00CED1'][2] = 206;
		$this->color['00CED1'][3] = 209;
		
		$this->color['9400D3'][0] = 'Dark Violet';
		$this->color['9400D3'][1] = 140;
		$this->color['9400D3'][2] = 0;
		$this->color['9400D3'][3] = 211;
		
		$this->color['FF1493'][0] = 'Deep Pink';
		$this->color['FF1493'][1] = 255;
		$this->color['FF1493'][2] = 20;
		$this->color['FF1493'][3] = 147;
		
		$this->color['00BFFF'][0] = 'Deep Sky Blue';
		$this->color['00BFFF'][1] = 0;
		$this->color['00BFFF'][2] = 191;
		$this->color['00BFFF'][3] = 255;
		
		$this->color['696969'][0] = 'Dim Gray';
		$this->color['696969'][1] = 105;
		$this->color['696969'][2] = 105;
		$this->color['696969'][3] = 105;
		
		$this->color['1E90FF'][0] = 'Dodger Blue';
		$this->color['1E90FF'][1] = 30;
		$this->color['1E90FF'][2] = 144;
		$this->color['1E90FF'][3] = 255;
		
		$this->color['B22222'][0] = 'Fire Brick';
		$this->color['B22222'][1] = 178;
		$this->color['B22222'][2] = 34;
		$this->color['B22222'][3] = 34;
		
		$this->color['FFFAF0'][0] = 'Floral White';
		$this->color['FFFAF0'][1] = 255;
		$this->color['FFFAF0'][2] = 250;
		$this->color['FFFAF0'][3] = 240;
		
		$this->color['228B22'][0] = 'Forest Green';
		$this->color['228B22'][1] = 34;
		$this->color['228B22'][2] = 139;
		$this->color['228B22'][3] = 34;
		
		$this->color['FF00FF'][0] = 'Fuscha';
		$this->color['FF00FF'][1] = 255;
		$this->color['FF00FF'][2] = 0;
		$this->color['FF00FF'][3] = 255;
		
		$this->color['DCDCDC'][0] = 'Gainsboro';
		$this->color['DCDCDC'][1] = 220;
		$this->color['DCDCDC'][2] = 220;
		$this->color['DCDCDC'][3] = 220;
		
		$this->color['F8F8FF'][0] = 'Ghost White';
		$this->color['F8F8FF'][1] = 248;
		$this->color['F8F8FF'][2] = 248;
		$this->color['F8F8FF'][3] = 255;
		
		$this->color['FFD700'][0] = 'Gold';
		$this->color['FFD700'][1] = 255;
		$this->color['FFD700'][2] = 215;
		$this->color['FFD700'][3] = 0;
		
		$this->color['DAA520'][0] = 'Goldenrod';
		$this->color['DAA520'][1] = 218;
		$this->color['DAA520'][2] = 165;
		$this->color['DAA520'][3] = 32;
		
		$this->color['808080'][0] = 'Gray';
		$this->color['808080'][1] = 128;
		$this->color['808080'][2] = 128;
		$this->color['808080'][3] = 128;
		
		$this->color['008000'][0] = 'Green';
		$this->color['008000'][1] = 0;
		$this->color['008000'][2] = 128;
		$this->color['008000'][3] = 0;
		
		$this->color['ADFF2F'][0] = 'Green Yellow';
		$this->color['ADFF2F'][1] = 173;
		$this->color['ADFF2F'][2] = 255;
		$this->color['ADFF2F'][3] = 147;
		
		$this->color['F0FFF0'][0] = 'Honey Dew';
		$this->color['F0FFF0'][1] = 240;
		$this->color['F0FFF0'][2] = 255;
		$this->color['F0FFF0'][3] = 240;
		
		$this->color['FF69B4'][0] = 'Hot Pink';
		$this->color['FF69B4'][1] = 255;
		$this->color['FF69B4'][2] = 105;
		$this->color['FF69B4'][3] = 180;
		
		$this->color['CD5C5C'][0] = 'Indian Red';
		$this->color['CD5C5C'][1] = 205;
		$this->color['CD5C5C'][2] = 92;
		$this->color['CD5C5C'][3] = 92;
		
		$this->color['4B0082'][0] = 'Indigo';
		$this->color['4B0082'][1] = 75;
		$this->color['4B0082'][2] = 0;
		$this->color['4B0082'][3] = 130;
		
		$this->color['FFFFF0'][0] = 'Ivory';
		$this->color['FFFFF0'][1] = 255;
		$this->color['FFFFF0'][2] = 255;
		$this->color['FFFFF0'][3] = 240;
		
		$this->color['F0E68C'][0] = 'Khaki';
		$this->color['F0E68C'][1] = 240;
		$this->color['F0E68C'][2] = 230;
		$this->color['F0E68C'][3] = 140;
		
		$this->color['E6E6FA'][0] = 'Lavender';
		$this->color['E6E6FA'][1] = 230;
		$this->color['E6E6FA'][2] = 230;
		$this->color['E6E6FA'][3] = 250;
		
		$this->color['FFF0F5'][0] = 'Lavender Blush';
		$this->color['FFF0F5'][1] = 255;
		$this->color['FFF0F5'][2] = 240;
		$this->color['FFF0F5'][3] = 245;
		
		$this->color['7CFC00'][0] = 'Lawn Green';
		$this->color['7CFC00'][1] = 124;
		$this->color['7CFC00'][2] = 252;
		$this->color['7CFC00'][3] = 0;
		
		$this->color['FFFACD'][0] = 'Lemon Chiffon';
		$this->color['FFFACD'][1] = 255;
		$this->color['FFFACD'][2] = 250;
		$this->color['FFFACD'][3] = 205;
		
		$this->color['ADD8E6'][0] = 'Light Blue';
		$this->color['ADD8E6'][1] = 173;
		$this->color['ADD8E6'][2] = 216;
		$this->color['ADD8E6'][3] = 230;
		
		$this->color['F08080'][0] = 'Light Coral';
		$this->color['F08080'][1] = 240;
		$this->color['F08080'][2] = 128;
		$this->color['F08080'][3] = 128;
		
		$this->color['E0FFFF'][0] = 'Light Cyan';
		$this->color['E0FFFF'][1] = 224;
		$this->color['E0FFFF'][2] = 255;
		$this->color['E0FFFF'][3] = 255;
		
		$this->color['FAFAD2'][0] = 'Light Goldenrod Yellow';
		$this->color['FAFAD2'][1] = 250;
		$this->color['FAFAD2'][2] = 250;
		$this->color['FAFAD2'][3] = 210;
		
		$this->color['D3D3D3'][0] = 'Light Gray';
		$this->color['D3D3D3'][1] = 211;
		$this->color['D3D3D3'][2] = 211;
		$this->color['D3D3D3'][3] = 211;
		
		$this->color['90EE90'][0] = 'Light Green';
		$this->color['90EE90'][1] = 144;
		$this->color['90EE90'][2] = 238;
		$this->color['90EE90'][3] = 144;
		
		$this->color['FFB6C1'][0] = 'Light Pink';
		$this->color['FFB6C1'][1] = 255;
		$this->color['FFB6C1'][2] = 182;
		$this->color['FFB6C1'][3] = 193;
		
		$this->color['FFA07A'][0] = 'Light Salmon';
		$this->color['FFA07A'][1] = 255;
		$this->color['FFA07A'][2] = 160;
		$this->color['FFA07A'][3] = 122;
		
		$this->color['20B2AA'][0] = 'Light Sea Green';
		$this->color['20B2AA'][1] = 32;
		$this->color['20B2AA'][2] = 178;
		$this->color['20B2AA'][3] = 170;
		
		$this->color['87CEFA'][0] = 'Light Sky Blue';
		$this->color['87CEFA'][1] = 135;
		$this->color['87CEFA'][2] = 206;
		$this->color['87CEFA'][3] = 250;
		
		$this->color['778899'][0] = 'Light Slate Gray';
		$this->color['778899'][1] = 119;
		$this->color['778899'][2] = 136;
		$this->color['778899'][3] = 153;
		
		$this->color['B0C4DE'][0] = 'Light Steel Blue';
		$this->color['B0C4DE'][1] = 176;
		$this->color['B0C4DE'][2] = 196;
		$this->color['B0C4DE'][3] = 222;
		
		$this->color['FFFFE0'][0] = 'Light Yellow';
		$this->color['FFFFE0'][1] = 255;
		$this->color['FFFFE0'][2] = 255;
		$this->color['FFFFE0'][3] = 224;
		
		$this->color['00FF00'][0] = 'Lime';
		$this->color['00FF00'][1] = 0;
		$this->color['00FF00'][2] = 255;
		$this->color['00FF00'][3] = 0;
		
		$this->color['32CD32'][0] = 'Lime Green';
		$this->color['32CD32'][1] = 50;
		$this->color['32CD32'][2] = 205;
		$this->color['32CD32'][3] = 50;
		
		$this->color['FAF0E6'][0] = 'Linen';
		$this->color['FAF0E6'][1] = 250;
		$this->color['FAF0E6'][2] = 240;
		$this->color['FAF0E6'][3] = 230;
		
		$this->color['FF00FF'][0] = 'Magenta';
		$this->color['FF00FF'][1] = 128;
		$this->color['FF00FF'][2] = 0;
		$this->color['FF00FF'][3] = 0;
		
		$this->color['800000'][0] = 'Maroon';
		$this->color['800000'][1] = 128;
		$this->color['800000'][2] = 0;
		$this->color['800000'][3] = 0;
		
		$this->color['66CDAA'][0] = 'Medium Aqua Marine';
		$this->color['66CDAA'][1] = 102;
		$this->color['66CDAA'][2] = 205;
		$this->color['66CDAA'][3] = 170;
		
		$this->color['0000CD'][0] = 'Medium Blue';
		$this->color['0000CD'][1] = 0;
		$this->color['0000CD'][2] = 0;
		$this->color['0000CD'][3] = 205;
		
		$this->color['BA55D3'][0] = 'Medium Orchid';
		$this->color['BA55D3'][1] = 186;
		$this->color['BA55D3'][2] = 85;
		$this->color['BA55D3'][3] = 211;
		
		$this->color['9370D8'][0] = 'Medium Purple';
		$this->color['9370D8'][1] = 147;
		$this->color['9370D8'][2] = 112;
		$this->color['9370D8'][3] = 219;
		
		$this->color['3CB371'][0] = 'Medium Sea Green';
		$this->color['3CB371'][1] = 60;
		$this->color['3CB371'][2] = 179;
		$this->color['3CB371'][3] = 113;
		
		$this->color['7B68EE'][0] = 'Medium Slate Blue';
		$this->color['7B68EE'][1] = 123;
		$this->color['7B68EE'][2] = 104;
		$this->color['7B68EE'][3] = 238;
		
		$this->color['00FA9A'][0] = 'Medium Spring Green';
		$this->color['00FA9A'][1] = 0;
		$this->color['00FA9A'][2] = 250;
		$this->color['00FA9A'][3] = 154;
		
		$this->color['48D1CC'][0] = 'Medium Turquoise';
		$this->color['48D1CC'][1] = 79;
		$this->color['48D1CC'][2] = 209;
		$this->color['48D1CC'][3] = 204;
		
		$this->color['C71585'][0] = 'Medium Violet Red';
		$this->color['C71585'][1] = 199;
		$this->color['C71585'][2] = 21;
		$this->color['C71585'][3] = 133;
		
		$this->color['191970'][0] = 'Midnight Blue';
		$this->color['191970'][1] = 25;
		$this->color['191970'][2] = 25;
		$this->color['191970'][3] = 112;
		
		$this->color['F5FFFA'][0] = 'Mint Cream';
		$this->color['F5FFFA'][1] = 245;
		$this->color['F5FFFA'][2] = 255;
		$this->color['F5FFFA'][3] = 250;
		
		$this->color['FFE4E1'][0] = 'Misty Rose';
		$this->color['FFE4E1'][1] = 255;
		$this->color['FFE4E1'][2] = 228;
		$this->color['FFE4E1'][3] = 225;
		
		$this->color['FFE4B5'][0] = 'Moccasin';
		$this->color['FFE4B5'][1] = 255;
		$this->color['FFE4B5'][2] = 228;
		$this->color['FFE4B5'][3] = 181;
		
		$this->color['FFDEAD'][0] = 'Navajo White';
		$this->color['FFDEAD'][1] = 255;
		$this->color['FFDEAD'][2] = 222;
		$this->color['FFDEAD'][3] = 173;
		
		$this->color['000080'][0] = 'Navy';
		$this->color['000080'][1] = 0;
		$this->color['000080'][2] = 0;
		$this->color['000080'][3] = 128;
		
		$this->color['FDF5E6'][0] = 'Old Lace';
		$this->color['FDF5E6'][1] = 253;
		$this->color['FDF5E6'][2] = 245;
		$this->color['FDF5E6'][3] = 230;
		
		$this->color['808000'][0] = 'Olive';
		$this->color['808000'][1] = 128;
		$this->color['808000'][2] = 128;
		$this->color['808000'][3] = 0;
		
		$this->color['6B8E23'][0] = 'Olive Drab';
		$this->color['6B8E23'][1] = 107;
		$this->color['6B8E23'][2] = 142;
		$this->color['6B8E23'][3] = 35;
		
		$this->color['FFA500'][0] = 'Orange';
		$this->color['FFA500'][1] = 255;
		$this->color['FFA500'][2] = 165;
		$this->color['FFA500'][3] = 0;
		
		$this->color['FF4500'][0] = 'Orange Red';
		$this->color['FF4500'][1] = 255;
		$this->color['FF4500'][2] = 69;
		$this->color['FF4500'][3] = 0;
		
		$this->color['DA70D6'][0] = 'Orchid';
		$this->color['DA70D6'][1] = 218;
		$this->color['DA70D6'][2] = 112;
		$this->color['DA70D6'][3] = 214;
		
		$this->color['EEE8AA'][0] = 'Pale Goldenrod';
		$this->color['EEE8AA'][1] = 238;
		$this->color['EEE8AA'][2] = 232;
		$this->color['EEE8AA'][3] = 170;
		
		$this->color['98FB98'][0] = 'Pale Green';
		$this->color['98FB98'][1] = 152;
		$this->color['98FB98'][2] = 251;
		$this->color['98FB98'][3] = 152;
		
		$this->color['AFEEEE'][0] = 'Pale Turquoise';
		$this->color['AFEEEE'][1] = 175;
		$this->color['AFEEEE'][2] = 238;
		$this->color['AFEEEE'][3] = 238;
		
		$this->color['D87093'][0] = 'Pale Violet Red';
		$this->color['D87093'][1] = 219;
		$this->color['D87093'][2] = 112;
		$this->color['D87093'][3] = 147;
		
		$this->color['FFEFD5'][0] = 'Papaya Whip';
		$this->color['FFEFD5'][1] = 255;
		$this->color['FFEFD5'][2] = 239;
		$this->color['FFEFD5'][3] = 213;
		
		$this->color['FFDAB9'][0] = 'Peach Puff';
		$this->color['FFDAB9'][1] = 255;
		$this->color['FFDAB9'][2] = 218;
		$this->color['FFDAB9'][3] = 185;
		
		$this->color['CD853F'][0] = 'Peru';
		$this->color['CD853F'][1] = 205;
		$this->color['CD853F'][2] = 133;
		$this->color['CD853F'][3] = 63;
		
		$this->color['FFC0CB'][0] = 'Pink';
		$this->color['FFC0CB'][1] = 255;
		$this->color['FFC0CB'][2] = 192;
		$this->color['FFC0CB'][3] = 203;
		
		$this->color['DDA0DD'][0] = 'Plum';
		$this->color['DDA0DD'][1] = 221;
		$this->color['DDA0DD'][2] = 160;
		$this->color['DDA0DD'][3] = 221;
		
		$this->color['B0E0E6'][0] = 'Powder Blue';
		$this->color['B0E0E6'][1] = 176;
		$this->color['B0E0E6'][2] = 224;
		$this->color['B0E0E6'][3] = 230;
		
		$this->color['800080'][0] = 'Purple';
		$this->color['800080'][1] = 128;
		$this->color['800080'][2] = 0;
		$this->color['800080'][3] = 128;
		
		$this->color['FF0000'][0] = 'Red';
		$this->color['FF0000'][1] = 255;
		$this->color['FF0000'][2] = 0;
		$this->color['FF0000'][3] = 0;
		
		$this->color['BC8F8F'][0] = 'Rosy Brown';
		$this->color['BC8F8F'][1] = 188;
		$this->color['BC8F8F'][2] = 143;
		$this->color['BC8F8F'][3] = 143;
		
		$this->color['4169E1'][0] = 'Royal Blue';
		$this->color['4169E1'][1] = 65;
		$this->color['4169E1'][2] = 105;
		$this->color['4169E1'][3] = 225;
		
		$this->color['8B4513'][0] = 'Saddle Brown';
		$this->color['8B4513'][1] = 139;
		$this->color['8B4513'][2] = 69;
		$this->color['8B4513'][3] = 19;
		
		$this->color['FA8072'][0] = 'Salmon';
		$this->color['FA8072'][1] = 250;
		$this->color['FA8072'][2] = 128;
		$this->color['FA8072'][3] = 114;
		
		$this->color['F4A460'][0] = 'Sandy Brown';
		$this->color['F4A460'][1] = 244;
		$this->color['F4A460'][2] = 164;
		$this->color['F4A460'][3] = 96;
		
		$this->color['2E8B57'][0] = 'Sea Green';
		$this->color['2E8B57'][1] = 46;
		$this->color['2E8B57'][2] = 139;
		$this->color['2E8B57'][3] = 87;
		
		$this->color['FFF5EE'][0] = 'Sea Shell';
		$this->color['FFF5EE'][1] = 255;
		$this->color['FFF5EE'][2] = 245;
		$this->color['FFF5EE'][3] = 238;
		
		$this->color['A0522D'][0] = 'Sienna';
		$this->color['A0522D'][1] = 160;
		$this->color['A0522D'][2] = 82;
		$this->color['A0522D'][3] = 45;
		
		$this->color['C0C0C0'][0] = 'Silver';
		$this->color['C0C0C0'][1] = 192;
		$this->color['C0C0C0'][2] = 192;
		$this->color['C0C0C0'][3] = 192;
		
		$this->color['87CEEB'][0] = 'Sky Blue';
		$this->color['87CEEB'][1] = 135;
		$this->color['87CEEB'][2] = 206;
		$this->color['87CEEB'][3] = 235;
		
		$this->color['6A5ACD'][0] = 'Slate Blue';
		$this->color['6A5ACD'][1] = 106;
		$this->color['6A5ACD'][2] = 90;
		$this->color['6A5ACD'][3] = 205;
		
		$this->color['708090'][0] = 'Slate Gray';
		$this->color['708090'][1] = 112;
		$this->color['708090'][2] = 128;
		$this->color['708090'][3] = 144;
		
		$this->color['FFFAFA'][0] = 'Snow';
		$this->color['FFFAFA'][1] = 255;
		$this->color['FFFAFA'][2] = 250;
		$this->color['FFFAFA'][3] = 250;
		
		$this->color['00FF7F'][0] = 'Spring Green';
		$this->color['00FF7F'][1] = 0;
		$this->color['00FF7F'][2] = 255;
		$this->color['00FF7F'][3] = 127;
		
		$this->color['4682B4'][0] = 'Steel Blue';
		$this->color['4682B4'][1] = 70;
		$this->color['4682B4'][2] = 130;
		$this->color['4682B4'][3] = 180;
		
		$this->color['D2B48C'][0] = 'Tan';
		$this->color['D2B48C'][1] = 210;
		$this->color['D2B48C'][2] = 180;
		$this->color['D2B48C'][3] = 140;
		
		$this->color['008080'][0] = 'Teal';
		$this->color['008080'][1] = 0;
		$this->color['008080'][2] = 128;
		$this->color['008080'][3] = 128;
		
		$this->color['D8BFD8'][0] = 'Thistle';
		$this->color['D8BFD8'][1] = 216;
		$this->color['D8BFD8'][2] = 191;
		$this->color['D8BFD8'][3] = 216;
		
		$this->color['FF6347'][0] = 'Tomato';
		$this->color['FF6347'][1] = 255;
		$this->color['FF6347'][2] = 99;
		$this->color['FF6347'][3] = 71;
		
		$this->color['40E0D0'][0] = 'Turquoise';
		$this->color['40E0D0'][1] = 64;
		$this->color['40E0D0'][2] = 224;
		$this->color['40E0D0'][3] = 208;
		
		$this->color['EE82EE'][0] = 'Violet';
		$this->color['EE82EE'][1] = 238;
		$this->color['EE82EE'][2] = 130;
		$this->color['EE82EE'][3] = 238;
		
		$this->color['F5DEB3'][0] = 'Wheat';
		$this->color['F5DEB3'][1] = 245;
		$this->color['F5DEB3'][2] = 222;
		$this->color['F5DEB3'][3] = 179;
		
		$this->color['FFFFFF'][0] = 'White';
		$this->color['FFFFFF'][1] = 255;
		$this->color['FFFFFF'][2] = 255;
		$this->color['FFFFFF'][3] = 255;
		
		$this->color['F5F5F5'][0] = 'White Smoke';
		$this->color['F5F5F5'][1] = 245;
		$this->color['F5F5F5'][2] = 245;
		$this->color['F5F5F5'][3] = 245;
		
		$this->color['FFFF00'][0] = 'Yellow';
		$this->color['FFFF00'][1] = 255;
		$this->color['FFFF00'][2] = 255;
		$this->color['FFFF00'][3] = 0;
		
		$this->color['9ACD32'][0] = 'Yellow Green';
		$this->color['9ACD32'][1] = 154;
		$this->color['9ACD32'][2] = 205;
		$this->color['9ACD32'][3] = 50;
	}
	
	/* method initGBS
	usage:	initGBS();
	params:	void
	
	This method intializes the predefined banners. Array[0] is used for the select first line and default banner.
	*/
	function initGBS(){
		$this->gBS[0]['title'] = 'Select Predefined Banner';
		$this->gBS[0]['imageXpos'] = 468;
		$this->gBS[0]['imageYpos'] = 60;
		$this->gBS[0]['primBackgroundColor'] = 'FFE4C4';
		$this->gBS[0]['secBackgroundColor'] = 'D2691E';
		$this->gBS[0]['displayGradient'] = 1;
		$this->gBS[0]['gradientDirection'] = 'horizontal';
		$this->gBS[0]['borderSize'] = 3;
		$this->gBS[0]['borderColor'] = '6495ED';
		$this->gBS[0]['textShadow'][0] = 1;
		$this->gBS[0]['textShadow'][1] = 0;
		$this->gBS[0]['textShadow'][2] = 0;
		$this->gBS[0]['text'][0] = 'Create Your Own Banner!';
		$this->gBS[0]['textColor'][0] = '00008B';
		$this->gBS[0]['font'][0] = 1;
		$this->gBS[0]['fontSize'][0] = 16;
		$this->gBS[0]['textXpos'][0] = 30;
		$this->gBS[0]['textYpos'][0] = 38;
		$this->gBS[0]['textAngle'][0] = 0;
		$this->gBS[0]['text'][1] = 'Multiple Colors';
		$this->gBS[0]['textColor'][1] = '800000';
		$this->gBS[0]['font'][1] = 1;
		$this->gBS[0]['fontSize'][1] = 10;
		$this->gBS[0]['textXpos'][1] = 308;
		$this->gBS[0]['textYpos'][1] = 24;
		$this->gBS[0]['textAngle'][1] = 0;
		$this->gBS[0]['text'][2] = 'Multiple lines of text';
		$this->gBS[0]['textColor'][2] = 'FFFAFA';
		$this->gBS[0]['font'][2] = 1;
		$this->gBS[0]['fontSize'][2] = 10;
		$this->gBS[0]['textXpos'][2] = 300;
		$this->gBS[0]['textYpos'][2] = 42;
		$this->gBS[0]['textAngle'][2] = 0;
		
		$this->gBS[1]['title'] = 'Full Banner (468x60)';
		$this->gBS[1]['imageXpos'] = 468;
		$this->gBS[1]['imageYpos'] = 60;
		$this->gBS[1]['primBackgroundColor'] = 'FFE4C4';
		$this->gBS[1]['secBackgroundColor'] = 'D2691E';
		$this->gBS[1]['displayGradient'] = 1;
		$this->gBS[1]['gradientDirection'] = 'horizontal';
		$this->gBS[1]['borderSize'] = 3;
		$this->gBS[1]['borderColor'] = '6495ED';
		$this->gBS[1]['textShadow'][0] = 1;
		$this->gBS[1]['textShadow'][1] = 0;
		$this->gBS[1]['textShadow'][2] = 0;
		$this->gBS[1]['text'][0] = 'Create Your Own Banner!';
		$this->gBS[1]['textColor'][0] = '00008B';
		$this->gBS[1]['font'][0] = 1;
		$this->gBS[1]['fontSize'][0] = 16;
		$this->gBS[1]['textXpos'][0] = 30;
		$this->gBS[1]['textYpos'][0] = 38;
		$this->gBS[1]['textAngle'][0] = 0;
		$this->gBS[1]['text'][1] = 'Multiple Colors';
		$this->gBS[1]['textColor'][1] = '800000';
		$this->gBS[1]['font'][1] = 1;
		$this->gBS[1]['fontSize'][1] = 10;
		$this->gBS[1]['textXpos'][1] = 308;
		$this->gBS[1]['textYpos'][1] = 24;
		$this->gBS[1]['textAngle'][1] = 0;
		$this->gBS[1]['text'][2] = 'Multiple lines of text';
		$this->gBS[1]['textColor'][2] = 'FFFAFA';
		$this->gBS[1]['font'][2] = 1;
		$this->gBS[1]['fontSize'][2] = 10;
		$this->gBS[1]['textXpos'][2] = 300;
		$this->gBS[1]['textYpos'][2] = 42;
		$this->gBS[1]['textAngle'][2] = 0;
		
		$this->gBS[2]['title'] = 'Half Banner (234x60)';
		$this->gBS[2]['imageXpos'] = 234;
		$this->gBS[2]['imageYpos'] = 60;
		$this->gBS[2]['primBackgroundColor'] = 'FFB6C1';
		$this->gBS[2]['secBackgroundColor'] = 'B0C4DE';
		$this->gBS[2]['displayGradient'] = 1;
		$this->gBS[2]['gradientDirection'] = 'horizontal';
		$this->gBS[2]['borderSize'] = 0;
		$this->gBS[2]['borderColor'] = 'FFB6C1';
		$this->gBS[2]['textShadow'][0] = 0;
		$this->gBS[2]['textShadow'][1] = 0;
		$this->gBS[2]['textShadow'][2] = 0;
		$this->gBS[2]['text'][0] = 'Create Your Own Banner';
		$this->gBS[2]['textColor'][0] = 'FFFFFF';
		$this->gBS[2]['font'][0] = 1;
		$this->gBS[2]['fontSize'][0] = 14;
		$this->gBS[2]['textXpos'][0] = 14;
		$this->gBS[2]['textYpos'][0] = 28;
		$this->gBS[2]['textAngle'][0] = 0;
		$this->gBS[2]['text'][1] = 'Multiple Colors';
		$this->gBS[2]['textColor'][1] = '000080';
		$this->gBS[2]['font'][1] = 1;
		$this->gBS[2]['fontSize'][1] = 10;
		$this->gBS[2]['textXpos'][1] = 14;
		$this->gBS[2]['textYpos'][1] = 50;
		$this->gBS[2]['textAngle'][1] = 0;
		$this->gBS[2]['text'][2] = 'Multiple lines of text';
		$this->gBS[2]['textColor'][2] = '000000';
		$this->gBS[2]['font'][2] = 1;
		$this->gBS[2]['fontSize'][2] = 10;
		$this->gBS[2]['textXpos'][2] = 116;
		$this->gBS[2]['textYpos'][2] = 50;
		$this->gBS[2]['textAngle'][2] = 0;
		
		$this->gBS[3]['title'] = 'Micro Bar (88x31)';
		$this->gBS[3]['imageXpos'] = 88;
		$this->gBS[3]['imageYpos'] = 31;
		$this->gBS[3]['primBackgroundColor'] = 'F5F5F5';
		$this->gBS[3]['secBackgroundColor'] = 'D2B48C';
		$this->gBS[3]['displayGradient'] = 1;
		$this->gBS[3]['gradientDirection'] = 'horizontal';
		$this->gBS[3]['borderSize'] = 0;
		$this->gBS[3]['borderColor'] = 'F5F5DC';
		$this->gBS[3]['textShadow'][0] = 0;
		$this->gBS[3]['textShadow'][1] = 0;
		$this->gBS[3]['textShadow'][2] = 0;
		$this->gBS[3]['text'][0] = 'Create';
		$this->gBS[3]['textColor'][0] = '8A2BE2';
		$this->gBS[3]['font'][0] = 1;
		$this->gBS[3]['fontSize'][0] = 12;
		$this->gBS[3]['textXpos'][0] = 18;
		$this->gBS[3]['textYpos'][0] = 14;
		$this->gBS[3]['textAngle'][0] = 0;
		$this->gBS[3]['text'][1] = 'Banners';
		$this->gBS[3]['textColor'][1] = '8A2BE2';
		$this->gBS[3]['font'][1] = 1;
		$this->gBS[3]['fontSize'][1] = 12;
		$this->gBS[3]['textXpos'][1] = 18;
		$this->gBS[3]['textYpos'][1] = 28;
		$this->gBS[3]['textAngle'][1] = 0;
		$this->gBS[3]['text'][2] = '';
		$this->gBS[3]['textColor'][2] = '000000';
		$this->gBS[3]['font'][2] = 1;
		$this->gBS[3]['fontSize'][2] = 10;
		$this->gBS[3]['textXpos'][2] = 0;
		$this->gBS[3]['textYpos'][2] = 0;
		$this->gBS[3]['textAngle'][2] = 0;
		
		$this->gBS[4]['title'] = 'Button 1 (120x90)';
		$this->gBS[4]['imageXpos'] = 120;
		$this->gBS[4]['imageYpos'] = 90;
		$this->gBS[4]['primBackgroundColor'] = '6495ED';
		$this->gBS[4]['secBackgroundColor'] = '6495ED';
		$this->gBS[4]['displayGradient'] = 0;
		$this->gBS[4]['gradientDirection'] = 'horizontal';
		$this->gBS[4]['borderSize'] = 0;
		$this->gBS[4]['borderColor'] = '6495ED';
		$this->gBS[4]['textShadow'][0] = 0;
		$this->gBS[4]['textShadow'][1] = 0;
		$this->gBS[4]['textShadow'][2] = 0;
		$this->gBS[4]['text'][0] = 'Create A Banner';
		$this->gBS[4]['textColor'][0] = 'FFFFFF';
		$this->gBS[4]['font'][0] = 1;
		$this->gBS[4]['fontSize'][0] = 12;
		$this->gBS[4]['textXpos'][0] = 6;
		$this->gBS[4]['textYpos'][0] = 14;
		$this->gBS[4]['textAngle'][0] = 325;
		$this->gBS[4]['text'][1] = 'Multiple Colors';
		$this->gBS[4]['textColor'][1] = '008000';
		$this->gBS[4]['font'][1] = 1;
		$this->gBS[4]['fontSize'][1] = 10;
		$this->gBS[4]['textXpos'][1] = 32;
		$this->gBS[4]['textYpos'][1] = 18;
		$this->gBS[4]['textAngle'][1] = 0;
		$this->gBS[4]['text'][2] = 'Multiple lines';
		$this->gBS[4]['textColor'][2] = 'FFFFFF';
		$this->gBS[4]['font'][2] = 1;
		$this->gBS[4]['fontSize'][2] = 10;
		$this->gBS[4]['textXpos'][2] = 6;
		$this->gBS[4]['textYpos'][2] = 80;
		$this->gBS[4]['textAngle'][2] = 0;
		
		$this->gBS[5]['title'] = 'Button 2 (120x60)';
		$this->gBS[5]['imageXpos'] = 120;
		$this->gBS[5]['imageYpos'] = 60;
		$this->gBS[5]['primBackgroundColor'] = 'FAEBD7';
		$this->gBS[5]['secBackgroundColor'] = 'FAEBD7';
		$this->gBS[5]['displayGradient'] = 0;
		$this->gBS[5]['gradientDirection'] = 'horizontal';
		$this->gBS[5]['borderSize'] = 2;
		$this->gBS[5]['borderColor'] = 'D2691E';
		$this->gBS[5]['textShadow'][0] = 0;
		$this->gBS[5]['textShadow'][1] = 0;
		$this->gBS[5]['textShadow'][2] = 0;
		$this->gBS[5]['text'][0] = 'Create';
		$this->gBS[5]['textColor'][0] = '000000';
		$this->gBS[5]['font'][0] = 1;
		$this->gBS[5]['fontSize'][0] = 14;
		$this->gBS[5]['textXpos'][0] = 10;
		$this->gBS[5]['textYpos'][0] = 20;
		$this->gBS[5]['textAngle'][0] = 0;
		$this->gBS[5]['text'][1] = 'Your';
		$this->gBS[5]['textColor'][1] = '808080';
		$this->gBS[5]['font'][1] = 1;
		$this->gBS[5]['fontSize'][1] = 14;
		$this->gBS[5]['textXpos'][1] = 34;
		$this->gBS[5]['textYpos'][1] = 37;
		$this->gBS[5]['textAngle'][1] = 0;
		$this->gBS[5]['text'][2] = 'Banner';
		$this->gBS[5]['textColor'][2] = '000000';
		$this->gBS[5]['font'][2] = 1;
		$this->gBS[5]['fontSize'][2] = 14;
		$this->gBS[5]['textXpos'][2] = 50;
		$this->gBS[5]['textYpos'][2] = 54;
		$this->gBS[5]['textAngle'][2] = 0;
		
		$this->gBS[6]['title'] = 'Verticle Banner (120x240)';
		$this->gBS[6]['imageXpos'] = 120;
		$this->gBS[6]['imageYpos'] = 240;
		$this->gBS[6]['primBackgroundColor'] = 'B0E0E6';
		$this->gBS[6]['secBackgroundColor'] = '708090';
		$this->gBS[6]['displayGradient'] = 1;
		$this->gBS[6]['gradientDirection'] = 'vertical';
		$this->gBS[6]['borderSize'] = 0;
		$this->gBS[6]['borderColor'] = 'F5F5DC';
		$this->gBS[6]['textShadow'][0] = 0;
		$this->gBS[6]['textShadow'][1] = 0;
		$this->gBS[6]['textShadow'][2] = 0;
		$this->gBS[6]['text'][0] = 'Create Your Own Banner';
		$this->gBS[6]['textColor'][0] = 'FFFFFF';
		$this->gBS[6]['font'][0] = 1;
		$this->gBS[6]['fontSize'][0] = 16;
		$this->gBS[6]['textXpos'][0] = 100;
		$this->gBS[6]['textYpos'][0] = 5;
		$this->gBS[6]['textAngle'][0] = 270;
		$this->gBS[6]['text'][1] = 'Multiple Colors';
		$this->gBS[6]['textColor'][1] = '800080';
		$this->gBS[6]['font'][1] = 1;
		$this->gBS[6]['fontSize'][1] = 10;
		$this->gBS[6]['textXpos'][1] = 5;
		$this->gBS[6]['textYpos'][1] = 20;
		$this->gBS[6]['textAngle'][1] = 0;
		$this->gBS[6]['text'][2] = 'Multiple lines of text';
		$this->gBS[6]['textColor'][2] = 'FF6347';
		$this->gBS[6]['font'][2] = 1;
		$this->gBS[6]['fontSize'][2] = 16;
		$this->gBS[6]['textXpos'][2] = 60;
		$this->gBS[6]['textYpos'][2] = 210;
		$this->gBS[6]['textAngle'][2] = 90;
		
		$this->gBS[7]['title'] = 'Square Button (125x125)';
		$this->gBS[7]['imageXpos'] = 125;
		$this->gBS[7]['imageYpos'] = 125;
		$this->gBS[7]['primBackgroundColor'] = 'D2691E';
		$this->gBS[7]['secBackgroundColor'] = 'FFE4C4';
		$this->gBS[7]['displayGradient'] = 0;
		$this->gBS[7]['gradientDirection'] = 'horizontal';
		$this->gBS[7]['borderSize'] = 4;
		$this->gBS[7]['borderColor'] = 'B8860B';
		$this->gBS[7]['textShadow'][0] = 0;
		$this->gBS[7]['textShadow'][1] = 0;
		$this->gBS[7]['textShadow'][2] = 0;
		$this->gBS[7]['text'][0] = 'Create';
		$this->gBS[7]['textColor'][0] = 'F0E68C';
		$this->gBS[7]['font'][0] = 1;
		$this->gBS[7]['fontSize'][0] = 16;
		$this->gBS[7]['textXpos'][0] = 30;
		$this->gBS[7]['textYpos'][0] = 38;
		$this->gBS[7]['textAngle'][0] = 0;
		$this->gBS[7]['text'][1] = 'A';
		$this->gBS[7]['textColor'][1] = 'F0E68C';
		$this->gBS[7]['font'][1] = 1;
		$this->gBS[7]['fontSize'][1] = 16;
		$this->gBS[7]['textXpos'][1] = 56;
		$this->gBS[7]['textYpos'][1] = 70;
		$this->gBS[7]['textAngle'][1] = 0;
		$this->gBS[7]['text'][2] = 'Banner';
		$this->gBS[7]['textColor'][2] = 'FFFFFF';
		$this->gBS[7]['font'][2] = 1;
		$this->gBS[7]['fontSize'][2] = 16;
		$this->gBS[7]['textXpos'][2] = 29;
		$this->gBS[7]['textYpos'][2] = 100;
		$this->gBS[7]['textAngle'][2] = 0;
		
		$this->gBS[8]['title'] = 'Leaderboard (728x90)';
		$this->gBS[8]['imageXpos'] = 728;
		$this->gBS[8]['imageYpos'] = 90;
		$this->gBS[8]['primBackgroundColor'] = 'FFFFFF';
		$this->gBS[8]['secBackgroundColor'] = '8A2BE2';
		$this->gBS[8]['displayGradient'] = 1;
		$this->gBS[8]['gradientDirection'] = 'horizontal';
		$this->gBS[8]['borderSize'] = 3;
		$this->gBS[8]['borderColor'] = '8A2BE2';
		$this->gBS[8]['textShadow'][0] = 0;
		$this->gBS[8]['textShadow'][1] = 0;
		$this->gBS[8]['textShadow'][2] = 0;
		$this->gBS[8]['text'][0] = 'Create Your Own Banner To Your Specifications';
		$this->gBS[8]['textColor'][0] = '000000';
		$this->gBS[8]['font'][0] = 1;
		$this->gBS[8]['fontSize'][0] = 20;
		$this->gBS[8]['textXpos'][0] = 90;
		$this->gBS[8]['textYpos'][0] = 28;
		$this->gBS[8]['textAngle'][0] = 0;
		$this->gBS[8]['text'][1] = 'Preview every change until your are satisfied with your results';
		$this->gBS[8]['textColor'][1] = 'A0522D';
		$this->gBS[8]['font'][1] = 1;
		$this->gBS[8]['fontSize'][1] = 14;
		$this->gBS[8]['textXpos'][1] = 120;
		$this->gBS[8]['textYpos'][1] = 50;
		$this->gBS[8]['textAngle'][1] = 0;
		$this->gBS[8]['text'][2] = 'Complete the process and your creation is yours';
		$this->gBS[8]['textColor'][2] = 'FFFAFA';
		$this->gBS[8]['font'][2] = 1;
		$this->gBS[8]['fontSize'][2] = 10;
		$this->gBS[8]['textXpos'][2] = 230;
		$this->gBS[8]['textYpos'][2] = 80;
		$this->gBS[8]['textAngle'][2] = 0;
	
	}

	/* method initIBS
	usage:	initIBS();
	params:	void
	
	This method intializes the background images. Array[0] is used for the select first line with no choice.
	*/
	function initIBS(){
		$this->iBS[0]['title'] = 'Select Image Template';
		
		$this->iBS[1]['title'] = 'Transparent (468x60)';
		$this->iBS[1]['imageXpos'] = 468;
		$this->iBS[1]['imageYpos'] = 60;
		$this->iBS[1]['file'] = 'transparent.png';
		$this->iBS[1]['type'] = 'png';

		$this->iBS[2]['title'] = 'Three Way (468x60)';
		$this->iBS[2]['imageXpos'] = 468;
		$this->iBS[2]['imageYpos'] = 60;
		$this->iBS[2]['file'] = 'threeway.jpg';
		$this->iBS[2]['type'] = 'jpg';
		
		$this->iBS[3]['title'] = 'Lumber (468x60)';
		$this->iBS[3]['imageXpos'] = 468;
		$this->iBS[3]['imageYpos'] = 60;
		$this->iBS[3]['file'] = 'lumber.jpg';
		$this->iBS[3]['type'] = 'jpg';
		
		$this->iBS[4]['title'] = 'Bottom Line (468x60)';
		$this->iBS[4]['imageXpos'] = 468;
		$this->iBS[4]['imageYpos'] = 60;
		$this->iBS[4]['file'] = 'Bottom_Line.gif';
		$this->iBS[4]['type'] = 'gif';

		}

	/* method initFonts
	usage:	initFonts();
	params:	void
	
	This method intializes the fonts used by the class. You can add/delete/modify any of these values, the name is
	the full font name and the file is the file name (case sensitive).
	*/
	function initFonts(){
		$i = 1;
		if ($handle = opendir($this->siteRoot.'/'.$this->fontDir)) { 
			while (false!== ($file = readdir($handle))) { 
				//find the font files
				if ($file != "." && $file != ".." && strtolower(strrchr($file,".")) == '.ttf'){  
					$this->fonts[$i]['name'] = str_replace('_', ' ', ucfirst(substr($file, 0, strpos($file,'.'))));
					$this->fonts[$i]['file'] = $file;
					$i++;
				} 
			} 
			closedir($handle); 
		}
		/*
		$this->fonts[1]['name'] = 'Arial';
		$this->fonts[1]['file'] = 'Arial.ttf';
		
		$this->fonts[2]['name'] = 'Times New Roman';
		$this->fonts[2]['file'] = 'Times.ttf';

		$this->fonts[3]['name'] = 'Verdana';
		$this->fonts[3]['file'] = 'Verdana.ttf';
		*/
	}
	
	/* method initFontSizes
	usage:	initFontSizes();
	params:	void
	
	This method intializes the font sizes used by the class.
	*/
	function initFontSizes(){
		$this->fontSizes[1] = 8;
		$this->fontSizes[2] = 10;
		$this->fontSizes[3] = 12;
		$this->fontSizes[4] = 14;
		$this->fontSizes[5] = 16;
		$this->fontSizes[6] = 20;
		$this->fontSizes[7] = 24;
		$this->fontSizes[8] = 30;
	}

//**************************************************************
// Private Methods - for expansion, not currently implemented
//**************************************************************
	
	/* private method _html2rgb
	
	Returns an array containing the RGB values for any given html color code
	*/
	function _html2rgb($color){
		if ($color[0] == '#'){
			$color = substr($color, 1);
		}
		if (strlen($color) == 6){
			list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
		}elseif(strlen($color) == 3){
			list($r, $g, $b) = array($color[0], $color[1], $color[2]);
		}else{
			return false;
		}
		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);
		return array($r, $g, $b);
	}

	/* private method _rgb2html
	
	Returns the html color code for any given RGB value
	*/
	function _rgb2html($r, $g=-1, $b=-1){
		if (is_array($r) && sizeof($r) == 3){
			list($r, $g, $b) = $r;
		}
		$r = intval($r);
		$g = intval($g);
		$b = intval($b);
		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));
		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;
		return '#'.$color;
	}

}
?>