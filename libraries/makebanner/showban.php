<?PHP
/*
showban.php - example script to display the output stream in an html image tag
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

//Initialize the class
if(!$showban = new makeban()){
	die($showban->error);
}

if(!$_GET['imageXpos']){
	$showban->showDefault();
}else{
	foreach($_GET as $key => $value){
		$$key = $value;
	}
	
	$showType = '';
	if($file != ''){
		$showType = $type;
		$showban->useImage($file);
	}else{
		$showban->createImage($imageXpos,$imageYpos);
	
		$showban->addBackground($primBackgroundColor,$secBackgroundColor,$gradientDirection);
	
		if($borderColor != '' AND $borderSize > 0){
			$showban->addBorder($borderColor,$borderSize);
		}
	}

	foreach($text as $key => $value){
		$showText = str_replace('_',' ',html_entity_decode($value));
		$showXpos = $textXpos[$key];
		$showYpos = $textYpos[$key];
		$showAngle = $textAngle[$key];
		$showColor = $textColor[$key];
		$showFontSize = $fontSize[$key];
		$fontKey = $font[$key];
		$showFont = $showban->fonts[$fontKey]['file'];
		$showShadow = $textShadow[$key];
		$showban->addText($showText,$showXpos,$showYpos,$showAngle,$showColor,$showFontSize,$showFont,$showShadow);
	}
	$showban->showImage($showType);
}

?>