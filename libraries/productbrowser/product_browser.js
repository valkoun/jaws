if(typeof(AC)=="undefined"){AC={}}AC.ProductBrowser={productSlider:null,sliderVal:0,animationId:false,viewportWidth:796,contentWidth:796,categories:[{id:"pb-cat1",offset:0},{id:"pb-cat2",offset:0.32}],isIPhone:AC.Detector.isiPhone(),iPhoneCategories:[{id:"pb-cat1",offset:0},{id:"pb-cat2",offset:0.32}],arrowScrollAmount:0.24,iPhoneScrollAmount:0.22,iPhoneContainerWidth:684,isSliding:false,lastX:0.32,isMouseDown:false,dif:0,overlap:0,offsetImageWidth:127,sliderOffset:291,offsetContentWidth:-796,clicked:false,startIndex:0,isIpScroll:false,hasIpDragged:false,init:function(c){if(typeof(c.categories)!="undefined"){this.categories=c.categories
}if(typeof(c.imageOverlap)!="undefined"){this.overlap=c.imageOverlap}if(typeof(c.sliderCentering)!="undefined"){this.sliderOffset=c.sliderCentering
}if(typeof(c.initialCategory)!="undefined"){this.startIndex=c.initialCategory}if(typeof(c.arrowScrollAmount)!="undefined"){this.arrowScrollAmount=c.arrowScrollAmount
}if(typeof(c.iPhoneCategories)!="undefined"){this.iPhoneCategories=c.iPhoneCategories
}if(typeof(c.iPhoneScrollAmount)!="undefined"){this.iPhoneScrollAmount=c.iPhoneScrollAmount
}if(typeof(c.iPhoneContainerWidth)!="undefined"){this.iPhoneContainerWidth=c.iPhoneContainerWidth
}$("pb-productslidertrack").style.visibility="visible";$("pb-productbrowsercontainer").style.overflow="hidden";
this.viewportWidth=$("pb-productbrowsercontainer").getWidth();this.offsetImageWidth=$$("#pb-productslider .pb-productimage")[0].getWidth()-this.overlap;
this.contentWidth=this.offsetImageWidth*$$("#pb-productslider .pb-productimage").length;
this.offsetContentWidth=-1*(this.contentWidth-this.viewportWidth);this.productSlider=new Control.Slider("pb-productsliderhandle","pb-productslidertrack",{axis:"horizontal"});
if(AC.ProductBrowser.isIPhone){this.categories=this.iPhoneCategories;this.arrowScrollAmount=this.iPhoneScrollAmount;
$("pb-productslidertrack").style.visibility="hidden";$("pb-leftarrow").style.visibility="hidden";
$("pb-rightarrow").style.visibility="hidden";$("pb-productbrowsercontainer").style.width=this.iPhoneContainerWidth+"px";
var b=document.createElement("div");b.id="pb-iphone-leftarrow";var a=document.createElement("div");
a.id="pb-iphone-rightarrow";$("productbrowser").appendChild(b);$("productbrowser").appendChild(a);
Event.observe($(b),"click",function(){AC.ProductBrowser.left()});Event.observe($(a),"click",function(){AC.ProductBrowser.right()
})}AC.ProductBrowser.animateSlide(this.categories[this.startIndex].offset);this.productSlider.options.onChange=function(e){$("pb-productsliderhandleimage").style.left=$("pb-productsliderhandle").style.left;
if(AC.ProductBrowser.isThrow&&!AC.ProductBrowser.isSliding){AC.ProductBrowser.isSliding=true;
AC.ProductBrowser.isThrow=false;var d=e+AC.ProductBrowser.throwMod;if(d<0){d=0}if(d>1){d=1
}AC.ProductBrowser.animateSlide(d)}else{if(!AC.ProductBrowser.isSliding&&e){AC.ProductBrowser.isSliding=true;
AC.ProductBrowser.animateSlide(e)}}};this.productSlider.options.onSlide=function(e){$("pb-productsliderhandleimage").style.left=$("pb-productsliderhandle").style.left;
if(e&&!AC.ProductBrowser.isSliding){AC.ProductBrowser.isSliding=true;AC.ProductBrowser.isThrow=false;
if(AC.ProductBrowser.isMouseDown){AC.ProductBrowser.dif=e-AC.ProductBrowser.lastX;
AC.ProductBrowser.lastX=e;if(AC.ProductBrowser.dif>0.05){AC.ProductBrowser.isThrow=true;
AC.ProductBrowser.throwMod=0.2}else{if(AC.ProductBrowser.dif<-0.04){AC.ProductBrowser.isThrow=true;
AC.ProductBrowser.throwMod=-0.2}}}var d=AC.ProductBrowser.offsetContentWidth;$("pb-productslider").style.left=d*e+"px";
this.sliderVal=e;AC.ProductBrowser.lastX=e;AC.ProductBrowser.colorCats();AC.ProductBrowser.isSliding=false
}Element.setStyle($("pb-productbrowsercontainer"),{overflow:"hidden"})};Event.observe("pb-productslidertrack","mousedown",function(d){var f=d.offsetX||d.layerX;
if(Event.element(d).id=="pb-productslidertrack"&&f<100){AC.ProductBrowser.animateSlide(0)
}});Event.observe("pb-leftarrow","mousedown",function(){AC.ProductBrowser.left()
});Event.observe("pb-rightarrow","mousedown",function(){AC.ProductBrowser.right()
});Event.observe("pb-productsliderhandle","mousedown",function(){AC.ProductBrowser.isMouseDown=true;
$("pb-productsliderhandle").style.zIndex="5"});Event.observe("pb-productsliderhandle","mouseup",function(){AC.ProductBrowser.isMouseDown=false
});AC.ProductBrowser.categories.each(function(d){Event.observe($(d.id),"mouseup",function(f){AC.ProductBrowser.animateSlide(d.offset)
})})},animateSlide:function(d){if(d>1){d=1}if(d<0){d=0}AC.ProductBrowser.sliderVal=d;
window.clearInterval(AC.ProductBrowser.animationId);var a=AC.ProductBrowser.offsetContentWidth;
var c=a*d;var b=(Math.round(AC.ProductBrowser.viewportWidth-AC.ProductBrowser.sliderOffset)*d);
AC.ProductBrowser.isSliding=true;AC.ProductBrowser.animationId=window.setInterval(function(){var g=parseInt($("pb-productslider").getStyle("left"))||0;
var f=parseInt($("pb-productsliderhandle").getStyle("left"))||0;var e=AC.ProductBrowser.calculateDecel(g,c);
var h=AC.ProductBrowser.calculateDecel(f,b);$("pb-productslider").style.left=e+"px";
$("pb-productsliderhandle").style.left=h+"px";$("pb-productsliderhandleimage").style.left=h+"px";
AC.ProductBrowser.colorCats();if(e==c){window.clearInterval(AC.ProductBrowser.animationId);
AC.ProductBrowser.isSliding=false}},30)},colorCats:function(){var a=parseInt($("pb-productsliderhandle").getStyle("left"))+(($("pb-productsliderhandle").getWidth()-20)/2);
AC.ProductBrowser.categories.each(function(e){var d=parseInt($(e.id).getStyle("left"));
var b=Math.ceil((Math.min(a,d)/Math.max(a,d))*10);$(e.id).className="pb-catclass"+b
})},left:function(){AC.ProductBrowser.animateSlide(AC.ProductBrowser.sliderVal-AC.ProductBrowser.arrowScrollAmount)
},right:function(){AC.ProductBrowser.animateSlide(AC.ProductBrowser.sliderVal+AC.ProductBrowser.arrowScrollAmount)
},calculateDecel:function(c,b){var a=c-Math.floor((c-b)*0.4);if(Math.abs(c-b)<4){return b
}else{return a}}};
