function MacStyleDock(node,imageDetails,minimumSize,maximumSize,range){var iconsNode=document.createElement('div');node.appendChild(iconsNode);var reflectedIconsNode=document.createElement('div');node.appendChild(reflectedIconsNode);iconsNode.style.textAlign='center';reflectedIconsNode.style.textAlign='center';iconsNode.style.height=maximumSize+'px';reflectedIconsNode.style.height=maximumSize+'px';var maximumWidth=0;var scale=0;var closeTimeout=null;var closeInterval=null;var openInterval=null;var images=[];var iconNodes=[];var reflectedIconNodes=[];var iconSizes=[];for(var i=0;i<imageDetails.length;i++){iconNodes[i]=document.createElement('img');iconNodes[i].style.position='relative';iconSizes[i]=minimumSize;updateIconProperties(i);iconsNode.appendChild(iconNodes[i]);if(iconNodes[i].addEventListener){iconNodes[i].addEventListener('mousemove',processMouseMove,false);iconNodes[i].addEventListener('mouseout',processMouseOut,false);if(imageDetails[i]&&imageDetails[i].onclick){iconNodes[i].addEventListener('click',imageDetails[i].onclick,false);}}else if(iconNodes[i].attachEvent){iconNodes[i].attachEvent('onmousemove',processMouseMove);iconNodes[i].attachEvent('onmouseout',processMouseOut);if(imageDetails[i]&&imageDetails[i].onclick){iconNodes[i].attachEvent('onclick',imageDetails[i].onclick);}}
if(imageDetails[i]&&imageDetails[i].sizes){for(var j=0;j<imageDetails[i].sizes.length;j++){var image=document.createElement('img');image.setAttribute('src',imageDetails[i].name
+imageDetails[i].sizes[j]
+imageDetails[i].extension);images.push(image);}}}
function updateIconProperties(index){var size=minimumSize+scale*(iconSizes[index]-minimumSize);var sizeIndex=0;if(imageDetails[index]&&imageDetails[index].sizes){while(imageDetails[index].sizes[sizeIndex]<size&&sizeIndex+1<imageDetails[index].sizes.length){sizeIndex++;}}
if(size==maximumSize){iconNodes[index].setAttribute('src',imageDetails[index].name
+maximumSize
+'-full'
+imageDetails[index].extension);}else{if(imageDetails[index]&&imageDetails[index].sizes){iconNodes[index].setAttribute('src',imageDetails[index].name
+imageDetails[index].sizes[sizeIndex]
+imageDetails[index].extension);}}
iconNodes[index].setAttribute('width',size);iconNodes[index].setAttribute('height',size);iconNodes[index].style.marginTop=(maximumSize-size)+'px';}
function processMouseMove(e){window.clearTimeout(closeTimeout);closeTimeout=null;window.clearInterval(closeInterval);closeInterval=null;if(scale!=1&&!openInterval){openInterval=window.setInterval(function(){if(scale<1)scale+=0.125;if(scale>=1){scale=1;window.clearInterval(openInterval);openInterval=null;}
for(var i=0;i<iconNodes.length;i++){updateIconProperties(i);}},20);}
if(!e)e=window.event;var target=e.target||e.srcElement;var index=0;while(iconNodes[index]!=target)index++;var across=(e.layerX||e.offsetX)/iconSizes[index];if(across){var currentWidth=0;for(var i=0;i<iconNodes.length;i++){if(i<index-range||i>index+range){iconSizes[i]=minimumSize;}else if(i==index){iconSizes[i]=maximumSize;}else if(i<index){iconSizes[i]=minimumSize
+Math.round((maximumSize-minimumSize-1)*(Math.cos((i-index-across+1)/range*Math.PI)
+1)
/ 2);

          

          //add the icon size to the current width
currentWidth+=iconSizes[i];}else{iconSizes[i]=minimumSize
+Math.round((maximumSize-minimumSize-1)*(Math.cos((i-index-across)/range*Math.PI)
+1)
/ 2);

          

          //add the icon size to the current width
currentWidth+=iconSizes[i];}}
if(currentWidth>maximumWidth)maximumWidth=currentWidth;if(index>=range&&index<iconSizes.length-range&&currentWidth<maximumWidth){iconSizes[index-range]+=Math.floor((maximumWidth-currentWidth)/2);iconSizes[index+range]+=Math.ceil((maximumWidth-currentWidth)/2);}
for(var i=0;i<iconNodes.length;i++)updateIconProperties(i);}}
function processMouseOut(){if(!closeTimeout&&!closeInterval){closeTimeout=window.setTimeout(function(){closeTimeout=null;if(openInterval){window.clearInterval(openInterval);openInterval=null;}
closeInterval=window.setInterval(function(){if(scale>0)scale-=0.125;if(scale<=0){scale=0;window.clearInterval(closeInterval);closeInterval=null;}
for(var i=0;i<iconNodes.length;i++){updateIconProperties(i);}},20);},100);}}}