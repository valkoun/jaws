<?PHP
/*
makeban.php - example surfers page to generate banners
for use with the makeban class version 1.1 7/14/2007

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

include('makeban.class.php');

$ban = new makeban();

if($_POST['generatedBanner'] > 0){
	// Generating Banner from selection
	$gBSid = $_POST['generatedBanner'];
	$imageOptions = '&';
	foreach($ban->gBS[$gBSid] as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($value2 != ''){
					$imageOptions .= $key.'['.$key2.']'.'='.str_replace(' ','_',htmlentities($value2)).'&';
				}
			}
			$$key = $value;
		}elseif ($key != 'title'){
			$$key = $value;
			$imageOptions .= $key.'='.str_replace(' ','_',htmlentities($value)).'&';
		}
	}
	unset($imageBanner);
	$backgroundSource = 'generated';
}elseif($_POST['imageBanner'] > 0){
	// Image Banner from Selection
	$iBSid = $_POST['imageBanner'];
	$imageOptions = '&';
	foreach($ban->iBS[$iBSid] as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($value2 != ''){
					$imageOptions .= $key.'['.$key2.']'.'='.str_replace(' ','_',htmlentities($value2)).'&';
				}
			}
			$$key = $value;
		}elseif ($key != 'title'){
			$$key = $value;
			$imageOptions .= $key.'='.str_replace(' ','_',htmlentities($value)).'&';
		}
	}
	unset($_POST['Submit']);
	unset($_POST['formPosted']);
	unset($_POST['backgroundSource']);
	unset($_POST['generatedBanner']);
	unset($_POST['imageXpos']);
	unset($_POST['imageYpos']);
	unset($_POST['primBackgroundColor']);
	unset($_POST['displayGradient']);
	unset($_POST['gradientDirection']);
	unset($_POST['secBackgroundColor']);
	unset($_POST['borderSize']);
	unset($_POST['borderColor']);
	foreach($_POST as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($value2 != ''){
					$imageOptions .= $key.'['.$key2.']'.'='.$value2.'&';
				}
			}
			$$key = $value;
		}else{
			$$key = $value;
			$imageOptions .= $key.'='.$value.'&';
		}
	}
	$backgroundSource = 'image';
}elseif($_POST['formPosted']){
	//Process Posted Data
	$imageOptions = '&';
	unset($_POST['Submit']);
	unset($_POST['formPosted']);
	if(!$_POST['displayGradient']){
		unset($_POST['gradientDirection']);
		unset($_POST['secBackgroundColor']);
	}elseif(!$_POST['gradientDirection']){
		$_POST['gradientDirection'] = 'horizontal';
		$_POST['secBackgroundColor'] = $_POST['primBackgroundColor'];
	}
	foreach($_POST as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($value2 != ''){
					$imageOptions .= $key.'['.$key2.']'.'='.str_replace(' ','_',$value2).'&';
				}
			}
			$$key = $value;
		}else{
			$$key = $value;
			$imageOptions .= $key.'='.$value.'&';
		}
	}
	$backgroundSource = 'generated';
}else{
	$imageXpos = $ban->gBS[0]['imageXpos'];
	$imageYpos = $ban->gBS[0]['imageYpos'];
	$primBackgroundColor = $ban->gBS[0]['primBackgroundColor'];
	$secBackgroundColor = $ban->gBS[0]['secBackgroundColor'];
	$displayGradient = $ban->gBS[0]['displayGradient'];
	$gradientDirection = $ban->gBS[0]['gradientDirection'];
	$borderColor = $ban->gBS[0]['borderColor'];
	$borderSize = $ban->gBS[0]['borderSize'];
	for($x=0;$x<count($ban->gBS[0]);$x++){
		$text[$x] = $ban->gBS[0]['text'][$x];
		$textXpos[$x] = $ban->gBS[0]['textXpos'][$x];
		$textYpos[$x] = $ban->gBS[0]['textYpos'][$x];
		$textAngle[$x] = $ban->gBS[0]['textAngle'][$x];
		$textColor[$x] = $ban->gBS[0]['textColor'][$x];
		$fontSize[$x] = $ban->gBS[0]['fontSize'][$x];
		$font[$x] = $ban->gBS[0]['font'][$x];
		$textShadow[$x] = $ban->gBS[0]['textShadow'][$x];
	}
	$backgroundSource = 'generated';
}

if($_POST['send'] or $_POST['save']){
	if($imageBanner > 0){
		$ban->useImage($file);
	}else{
		$ban->createImage($imageXpos, $imageYpos);
		$ban->addBackground($primBackgroundColor,$secBackgroundColor,$gradientDirection);
		if($borderSize > 0){
			$ban->addBorder($borderColor,$borderSize);
		}
	}
	foreach($text as $key => $value){
		$showText = $value;
		$showXpos = $textXpos[$key];
		$showYpos = $textYpos[$key];
		$showAngle = $textAngle[$key];
		$showColor = $textColor[$key];
		$showFontSize = $fontSize[$key];
		$fontKey = $font[$key];
		$showFont = $ban->fonts[$fontKey]['file'];
		$showShadow = $textShadow[$key];
		$ban->addText($showText,$showXpos,$showYpos,$showAngle,$showColor,$showFontSize,$showFont,$showShadow);
	}
	if($_POST['save']){
		$file_name = '/ban_'.rand();
		$ban->saveImage('jpg',$file_name);
	}else{
		$ban->sendImage();
	}
}

$generatedBackgroundChecked = ($backgroundSource == 'generated') ? 'checked' : '';
$imageBackgroundChecked = ($backgroundSource == 'image') ? 'checked' : '';
$gradientHorizontalChecked = ($gradientDirection == 'horizontal') ? 'checked' : '';
$gradientVerticalChecked = ($gradientDirection == 'vertical') ? 'checked' : '';

?>
<html>
<head>
<title>Make a Banner</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
<table width="100%" border="0" cellspacing="2" cellpadding="2">
  <tr>
    <td align="center"><img name="mybanner" src="index.php?action=IncludeLibrary&path=libraries%2Fmakebanner%2Fshowban.php<?=$imageOptions?>" width="<?=$imageXpos?>" height="<?=$imageYpos?>" alt="Your banner preview"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><form name="form1" method="post" action="index.php?action=IncludeLibrary&path=libraries%2Fmakebanner%2Fmakeban.php">
      <table width="100%" border="0" cellspacing="2" cellpadding="2">
        <tr bgcolor="#CCFFCC">
          <td colspan="8" align="left"> Select a default image template or pre-defined banner (optional)</td>
        </tr>
        <tr>
          <td colspan="8" align="center"><input name="BackgroundSource" type="radio" value="image" <?=$imageBackgroundChecked?> disabled>&nbsp;Image Template:&nbsp;<?=$ban->createImgBanSelect('imageBanner',$imageBanner,'onChange="this.form.submit();"')?>&nbsp;&nbsp;<input type="radio" name="backgroundSource" value="generated" disabled <?=$generatedBackgroundChecked?>>&nbsp;
            Defined Banner:&nbsp;<?=$ban->createGenBanSelect('generatedBanner','onChange="this.form.submit();"')?>&nbsp;(<font color="#FF0000">warning: your changes will be lost</font>)</td>
        </tr>
<?PHP if($generatedBackgroundChecked){ ?>
        <tr>
          <td colspan="8" align="center">Width:&nbsp;<input name="imageXpos" value="<?=$imageXpos?>">&nbsp;&nbsp;Height:&nbsp;<input name="imageYpos" value="<?=$imageYpos?>" onChange="this.form.submit();"></td>
        </tr>
        <tr>
          <td colspan="8">&nbsp;</td>
        </tr>
        <tr bgcolor="#CCFFCC">
          <td colspan="8"> Set background color - check gradient to fade to a secondary color</td>
        </tr>
	<?PHP if($displayGradient){ ?>
        <tr>
          <td colspan="3" align="right">Start Background Color:&nbsp;&nbsp;<?=$ban->createColorSelect('primBackgroundColor',$primBackgroundColor)?>&nbsp;&nbsp;<font style="background-color:#<?=$primBackgroundColor?>">&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
		  <td align="left" colspan="5"><input type="checkbox" name="displayGradient" value="1" onClick="this.form.submit();" checked>&nbsp;Gradient&nbsp;<input type="radio" name="gradientDirection" value="horizontal" <?=$gradientHorizontalChecked?> onClick="this.form.submit();">&nbsp;Horizontal&nbsp;<input type="radio" name="gradientDirection" value="vertical" <?=$gradientVerticalChecked?> onClick="this.form.submit();">&nbsp;Vertical&nbsp;Color:&nbsp;&nbsp;<?=$ban->createColorSelect('secBackgroundColor',$secBackgroundColor)?>&nbsp;&nbsp;<font style="background-color:#<?=$secBackgroundColor?>">&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
        </tr>
	<?PHP }else{ ?>
        <tr>
          <td colspan="3" align="right">Background Color:&nbsp;&nbsp;<?=$ban->createColorSelect('primBackgroundColor',$primBackgroundColor)?>&nbsp;&nbsp;<font style="background-color:#<?=$primBackgroundColor?>">&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
		  <td align="left" colspan="5"><input type="checkbox" name="displayGradient" value="1" onClick="this.form.submit();">&nbsp;Gradient</td>
        </tr>
	<?PHP } ?>
        <tr>
          <td colspan="8">&nbsp;</td>
        </tr>
        <tr bgcolor="#CCFFCC">
          <td colspan="8"> Set border size and color - leave the border size empty for no border</td>
        </tr>
        <tr>
		  <td align="center" colspan="8">Border Size:&nbsp;&nbsp;<input name="borderSize" value="<?=$borderSize?>" size="3" maxlength="4" onChange="this.form.submit();">&nbsp;Color:&nbsp;&nbsp;<?=$ban->createColorSelect('borderColor',$borderColor)?>&nbsp;&nbsp;<font style="background-color:#<?=$borderColor?>">&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
        </tr>
<?PHP }else{ ?>
        <tr>
          <td colspan="8" align="center">Width:&nbsp;<?=$imageXpos?>&nbsp;&nbsp;Height:&nbsp;<?=$imageYpos?></td>
        </tr>
<?PHP } ?>
        <tr>
          <td colspan="8">&nbsp;</td>
        </tr>
        <tr bgcolor="#CCFFCC">
          <td colspan="8"> Modify/Enter text - to remove a line leave the text field empty - to add text complete the last text line</td>
        </tr>
<?PHP 
$counter = 0;
for($x=0;$x<=count($text);$x++){
	if($x == count($text) OR $text[$x] != ''){
		$textTemp = $text[$x];
		$fontSelectTemp = $ban->createFontSelect('font[]', $font[$x]);
		$fontSizeSelectTemp = $ban->createFontSizeSelect('fontSize[]', $fontSize[$x]);
		$textColorSelectTemp = $ban->createColorSelect('textColor[]',$textColor[$x]);
		$textColorTemp = $textColor[$x];
		$textXposTemp = $textXpos[$x];
		$textYposTemp = $textYpos[$x];
		$textAngleTemp = $textAngle[$x];
		$textShadowTemp = $textShadow[$x];
		$textShadowCheckedTemp = ($textShadow[$x] == 1) ? 'checked' : '';
?>
        <tr>
          <td align="center"><input name="text[]" value="<?=$textTemp?>" onChange="this.form.submit();"></td>
          <td align="center"><?=$fontSelectTemp?></td>
          <td align="center"><?=$fontSizeSelectTemp?></td>
          <td align="center"><?=$textColorSelectTemp?>&nbsp;&nbsp;<font style="background-color:#<?=$textColorTemp?>">&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
          <td align="center">x pos:
            <input name="textXpos[]" value="<?=$textXposTemp?>" size="3" maxlength="4" onChange="this.form.submit();"></td>
          <td align="center">y pos:
            <input name="textYpos[]" value="<?=$textYposTemp?>" size="3" maxlength="4" onChange="this.form.submit();"></td>
          <td align="center">angle:
            <input name="textAngle[]" value="<?=$textAngleTemp?>" size="3" maxlength="4" onChange="this.form.submit();"></td>
          <td align="center"><input type="checkbox" name="textShadow[<?=$counter?>]" value="1" <?=$textShadowCheckedTemp?> onClick="this.form.submit();">&nbsp;Shadow</td>
        </tr>
<?PHP
		$counter++ ;
	}
}
?>
        <tr>
          <td colspan="8">&nbsp;</td>
        </tr>
        <tr bgcolor="#CCFFCC">
          <td colspan="8"> Submit your changes - select 'Save' to write or 'Send' to download completed banner</td>
        </tr>
        <tr>
          <td colspan="8" align="center"><input type="hidden" name="formPosted" value="1"><input type="checkbox" name="save" value="1">&nbsp;Save&nbsp;&nbsp;<input type="checkbox" name="send" value="1">&nbsp;Send&nbsp;&nbsp;<input type="submit" name="Submit" value="Submit">&nbsp;<a href="index.php?action=IncludeLibrary&path=libraries%2Fmakebanner%2Fmakeban.php">Start Over</a></td>
        </tr>
      </table>
    </form></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
</body>
</html>
