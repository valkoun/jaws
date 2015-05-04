/**
 *	Basic Slideshow App
 *	
 *	Tom Doyle by Isotope Communications Ltd, Dec 2008
 *	http://www.tomdoyletalk.com/2008/10/28/simple-image-gallery-slideshow-with-scriptaculous-and-prototype/
 *	
 *	Published [16/12/08]:
 *	http://www.icommunicate.co.uk/articles/all/simple_slide_show_for_prototype_scriptaculous_38/	
 *
 *	Changes: Basically made it an object so that you can run multiple instances and so that
 *			 it doesn't get interfered with by other scripts on the page.
 *	
 *			 Have also added a few things, like "Captions" and the option of changing the
 *			 effects..
 *
 *	Example:
 *			Event.observe(window, 'load', function(){
 *				oMySlides = new iSlideShow({
 *					autostart 	: true		// optional, boolean (default:true)
 *					start		: 0,	 	// optional, slides[start] (default:0) 
 *					wait 		: 4000, 	// optional, milliseconds (default:4s)
 *					slides 		: [
 *						'image-div-a', 
 *						'image-div-b', 
 *						'image-div-c', 
 *						'image-div-d' 
 *					],
 *					counter		: 'counter-div-id', // optional...
 *					caption 	: 'caption-div-id', // optional... 
 *					playButton	: 'PlayButton', 	// optional (default:playButton)
 *					pauseButton	: 'PauseButton', 	// optional (default:PauseButton)
 *				});
 *				oMySlides.startSlideShow();
 *			});
 *
 *			To start the slideshow:
 *				oMySlides.startSlideShow();
 *
 *			To skip forward, back, stop:
 *				oMySlides.goNext();
 *				oMySlides.goPrevious();
 *				oMySlides.stop();
 */

var iSlideShow = new Class.create();

iSlideShow.prototype = {
	
	initialize : function (oArgs){
		this.wait 			= oArgs.wait ? oArgs.wait : 4000;
		this.start 			= oArgs.start ? oArgs.start : 0;
		this.duration		= oArgs.duration ? oArgs.duration : 0.5;
		this.autostart		= (typeof(oArgs.autostart)=='undefined') ? true : oArgs.autostart;
		this.slides 		= oArgs.slides;
		this.srcs 			= oArgs.srcs;
		this.counter		= oArgs.counter;
		this.caption		= oArgs.caption;
		this.playButton		= oArgs.playButton ? oArgs.playButton : 'PlayButton';
		this.pauseButton	= oArgs.pauseButton ? oArgs.pauseButton : 'PauseButton';
		this.iImageId		= this.start;
		this.play1			= null;
		if ( this.slides ) {
			this.numOfImages	= this.slides.length;
			if ( !this.numOfImages ) {
				alert('No slides?');
			}
		}
		if ( this.autostart ) {
			this.startSlideShow();
		} else if ($(this.caption) && $(this.slides[0]) && $(this.slides[0]).down('.image-caption').innerHTML != '') {
			$(this.caption).innerHTML = $(this.slides[0]).down('.image-caption').innerHTML;
		}
	},
	
	// The Fade Function
	swapImage: function (x,y) {		
		if (typeof($$("#"+this.slides[x]+" img")[0]) != 'undefined' && $$("#"+this.slides[x]+" img")[0].src != this.srcs[x]) {
			$$("#"+this.slides[x]+" img")[0].src = this.srcs[x];
			Event.observe($$("#"+this.slides[x]+" img")[0], "load", (function(){
				$(this.slides[x]) && $(this.slides[x]).appear({ duration: this.duration });
				$(this.slides[y]) && $(this.slides[y]).fade({duration: this.duration });
			}).bind(this));
		} else {
			$(this.slides[x]) && $(this.slides[x]).appear({ duration: this.duration });
			$(this.slides[y]) && $(this.slides[y]).fade({duration: this.duration });
		}
	},
	
	// the onload event handler that starts the fading.
	startSlideShow: function () {
		clearInterval(this.play1);
		this.play1 = undefined;
		var imageShow, imageHide;
	
		imageShow = this.iImageId+1;
		imageHide = this.iImageId;
		
		if (imageShow == this.numOfImages) {
			this.swapImage(0,imageHide);	
			this.iImageId = 0;					
		} else {
			this.swapImage(imageShow,imageHide);			
			this.iImageId++;
		}
		
		this.textIn = this.iImageId+1 + ' of ' + this.numOfImages;
		$(this.playButton).hide();
		$(this.pauseButton).appear({ duration: 0});

		this.updatecounter();
		this.play1 = setInterval(this.play.bind(this),this.wait);
									
	},
	
	play: function () {
		
		var imageShow, imageHide;
	
		imageShow = this.iImageId+1;
		imageHide = this.iImageId;
		
		if (imageShow == this.numOfImages) {
			this.swapImage(0,imageHide);	
			this.iImageId = 0;					
		} else {
			this.swapImage(imageShow,imageHide);			
			this.iImageId++;
		}
		
		this.textIn = this.iImageId+1 + ' of ' + this.numOfImages;
		this.updatecounter();
	},
	
	stop: function  () {
		clearInterval(this.play1);				
		$(this.playButton).appear({ duration: 0});
		$(this.pauseButton).hide();
	},
	
	goNext: function () {
		clearInterval(this.play1);
		$(this.playButton).appear({ duration: 0});
		$(this.pauseButton).hide();
		
		var imageShow, imageHide;
	
		imageShow = this.iImageId+1;
		imageHide = this.iImageId;
		
		if (imageShow == this.numOfImages) {
			this.swapImage(0,imageHide);	
			this.iImageId = 0;					
		} else {
			this.swapImage(imageShow,imageHide);			
			this.iImageId++;
		}
	
		this.updatecounter();
	},
	
	goPrevious: function () {
		clearInterval(this.play1);
		$(this.playButton).appear({ duration: 0});
		$(this.pauseButton).hide();
	
		var imageShow, imageHide;
					
		imageShow = this.iImageId-1;
		imageHide = this.iImageId;
		
		if (this.iImageId == 0) {
			this.swapImage(this.numOfImages-1,imageHide);	
			this.iImageId = this.numOfImages-1;		
			
			//alert(NumOfImages-1 + ' and ' + imageHide + ' i=' + i)
						
		} else {
			this.swapImage(imageShow,imageHide);			
			this.iImageId--;
			
			//alert(imageShow + ' and ' + imageHide)
		}
		
		this.updatecounter();
	},
	
	updatecounter: function () {
		var textIn = this.iImageId+1 + ' of ' + this.numOfImages;
		$(this.counter) && ( $(this.counter).innerHTML = textIn );
		if ( $(this.caption) && ( oNewCaption = $(this.slides[this.iImageId]).down('.image-caption') ) ) {
			if (oNewCaption.innerHTML != '' && oNewCaption.innerHTML != '<p></p>') {
				$(this.caption).style.visibility = 'visible';
				$(this.caption).innerHTML = oNewCaption.innerHTML;
			} else {
				$(this.caption).style.visibility = 'hidden';
			}
		}
	}
}
