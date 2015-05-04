/*
Use in <HEAD>:

<!--[if lt IE 7]>
<script type="text/javascript" src="ie-png-fix.js"></script>
<![endif]-->

*/
/*
// this code maybe use in future
if (navigator.platform == "Win32" && navigator.appName == "Microsoft Internet Explorer" && window.attachEvent) {
    window.attachEvent("onload", loadPngs);
}

function loadPngs() {
    var elements = document.all;
    for(var i = 0; i < elements.length; i++) {
        if ((elements[i].style.backgroundImage.match(/\.png/i)) ||
            (elements[i].tagName.toUpperCase()=='IMG' && elements[i].src.match(/\.png/i))) {
            fixpngTransparency(elements[i]);
        }
    }
}
*/
function fixpngTransparency(element) {
    if (element.onpropertychange) return;
    if ((element.style.backgroundImage.match(/\.png/i)) || (element.src.match(/\.png/i))) {
        element.onpropertychange = null;
        if(element.style.backgroundImage) {
            bgImg = element.style.backgroundImage;
            element.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" +
                                    bgImg.substring(4, bgImg-1) + "', sizingMethod='crop')";
            element.style.backgroundImage = 'url(images/blank.gif)';
        } else {
            element.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" +
                                    element.src + "', sizingMethod='crop')";
            element.src = 'images/blank.gif';
        }
        element.onpropertychange = propertyChanged;
    }
}

function propertyChanged() {
    if (window.event.propertyName == "style.backgroundImage" || window.event.propertyName == "src") {
        fixpngTransparency(window.event.srcElement);
    }
}

function fixOnFocus(element, focus_class) {
    element.onfocus = function () {
        this.className += ' ' + focus_class;
    }
    element.onblur = function () {
        this.className = this.className.replace(focus_class, '');
    }
}

function fixOnMouseOver(element, mouseover_class) {
    element.onmouseover = function () {
        this.className += ' ' + mouseover_class;
    }
    element.onmouseout = function () {
        this.className = this.className.replace(mouseover_class, '');
    }
}
/*
function fixOnMouseDown(element, mousedown_class) {
    element.onmousedown = function () {
        this.className += ' ' + mousedown_class;
    }
    element.onmouseup = function () {
        this.className = this.className.replace(mousedown_class, '');
    }
}
*/