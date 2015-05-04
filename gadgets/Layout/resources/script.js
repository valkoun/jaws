/**
 * Layout Javascript actions
 *
 * @category   Ajax
 * @package    Layout
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */


/**
 * Use async mode, create Callback
 */
var LayoutCallback = {
    savecss: function(response) {
        showResponse(response);
		var wm = UI.defaultWM;
    	var windows = wm.windows();
    	windows.first().destroy();
		//GB_hide();
        window.location.reload();
    },

    editelementaction: function(response) {
        showResponse(response);
    },

    changedisplaywhen: function(response) {
        showResponse(response);
    },

    moveelement: function(response) {
        showResponse(response);
    },

    deleteelement: function(response) {
        showResponse(response);
    },

    hideelement: function(response) {
		showResponse(response);
    },

    addgadget: function(response) {
        if (response['success']) {
			window.top.items_on_layout = response['items_on_layout'];
			
            window.top.$(response['section_name']).innerHTML = window.top.$(response['section_name']).innerHTML + response['gadget_html'];
			window.top.$$('#item_' + response['id']+' .item-controls').each(function(element){element.style.visibility = 'visible';});
            window.top.Effect.ScrollTo(window.top.$('item_' + response['id']), {duration:1});
            window.top.items[response['section_name']]['item_' + response['id']] = true; 
            window.top.newdrags[response['id']] = new window.top.Draggable('item_' + response['id'], {revert:true,scroll:window,constraint:false,handle: 'item-move'});
            
        }
		window.top.w.destroy();
    }
}

/**
 * Move items from one side to other
 */
function moveItems(fromSection, toSection)
{
    var fromSectionName = fromSection;
    var toSectionName   = toSection;

    var fromSectionObj   = document.getElementById(fromSectionName);
    var toSectionObj     = document.getElementById(toSectionName);

    var itemsFromSection = $$('#'+fromSectionName + ' .item');

    items[fromSection] = {};
    items[toSection]   = {};
    for(var i=0; i<itemsFromSection.length; i++) {
        var item = itemsFromSection[i];

        fromSectionObj.removeChild(item);
        toSectionObj.appendChild(item);

        items[toSection][item.id] = true;
    }
}

/**
 * Returns the position on the current item and section
 */
function getPositionOfItem(item, section)
{
    item = item.replace('item_', '');
    
    var pos = 1;
    for(key in items[section]) {
        if (typeof(items[section][key]) == 'function') {
            continue;
        }
        var itemName = key.replace('item_', '');
        if (itemName == item) {
            return pos;
        }
        pos++;
    }
    return pos;
}

/**
 * Move an element to another section
 */
function moveElement(element, movedTo)
{
    var comesFrom = element.parentNode;
    var goesTo    = $(movedTo.id.replace('_drop', '').replace('layout_', ''));
    var clone     = element;

    goesTo.appendChild(element);

    var destiny   = document.getElementById(movedTo.id.replace('_drop', '').replace('layout_', ''));
    var emptyDivs = $$('#'+movedTo.id.replace('_drop', '').replace('layout_', '') + ' .layout-message');

    if (emptyDivs.length == 1) {
        destiny.removeChild(emptyDivs[0]);
    }

    movedNewElement = true;
    newEmptyRegion  = goesTo.id;
    // $('log').innerHTML += 'Moved ' + element.id + ' from ' + comesFrom.id + ' to ' + goesTo.id + '<br />';
}

/**
 * Returns true if the total items of a section has changed
 */
function itemMovedOnSameSection(section, serialized)
{
    var sectionName  = section;
    var newItemsSize = serialized.split('&').length;

    var totalItems = 0;
    for(key in items[sectionName]) {
        if (typeof(items[sectionName][key]) == 'function') {
            continue;
        }
        totalItems++;
    }

    if (jawsTrim(serialized) == '') {
        newItemsSize = 0;
    }
    // $('log').innerHTML += ' + ' + section + ' antes tenía: ' + totalItems + ' ahora tiene ' + newItemsSize + '<br />';
    //$('log').innerHTML += ' + serial data: ' + serialized + '<br />';
    return totalItems == newItemsSize;
}

/**
 * Copies the items of a div (all those who have item as classname) to the
 * items section array (items[section])
 */
function rebuildItemsOfSection(section)
{
    var itemsOfSection = $$('#'+section + ' .item');
	items[section] = {};
    for(var i=0; i<itemsOfSection.length; i++) {
		var item = itemsOfSection[i].id;
        items[section][item] = true;
    }
}

/**
 * Returns in an array the item that has been changed, the section (where it is now)
 * and the position that it use
 */
function getAddedChanges()
{
    for(var i=0; i<sections.length; i++) {
        var section       = sections[i];
		var divsOfSection = $$('#' + section + ' .item');
		var totalItems = 0;
		for(key in items[section]) {
			if (typeof(items[section][key]) == 'function') {
				continue;
			}
			totalItems++;
		}
        if (divsOfSection.length > totalItems) {
            for(var j=0; j<divsOfSection.length; j++) {
                var item = divsOfSection[j].id;
                if (items[section][item] == undefined) {
                    return new Array(item, section, j+1);
                }
            }
        }
    }
    return null;
}

/**
 * Returns in an array the item that has changed, the item where the item is and
 * the new position
 */
function getSectionChanges(section)
{
    var divsOfSection = $$('#' + section + ' .item');

    var itemPos       = 1;
    for(var j=0; j<divsOfSection.length; j++) {
        var item    = divsOfSection[j].id;
        var origPos = getPositionOfItem(item, section);
        if (origPos != itemPos) {
            return new Array(item, section, itemPos);
        }
        itemPos++;
    }
    return null;
}

/**
 * Returns in an array the item that has been deleted and the section where it was
 */
function getDeletedChanges(item_added)
{
    for(var i=0; i<sections.length; i++) {
        var section = sections[i];
        if (items[section][item_added] == true) {
            return new Array(item_added, section);
        }
    }
    return null;
}

/**
 * Checks a section, if no items are found then a msg should be displayed
 * in the section
 */
function checkDeletedSection(section)
{
    var divsOfSection = $$('#'+section + ' .item');
    if (divsOfSection.length == 0) {
        var emptyDiv = document.createElement('div');
        emptyDiv.className = 'layout-message';
        $(section).appendChild(emptyDiv);
        emptyDiv.innerHTML = noItemsMsg;
    }
}

/**
 * Deletes an element
 */
function deleteElement(itemId, confirmMsg)
{
    var itemDiv     = $('item_' + itemId);
    var parentDiv   = itemDiv.parentNode;
    var comesFrom   = parentDiv.id;

    var answer = confirm(confirmMsg);
    if (answer) {
        Effect.Fade(itemDiv.id, {duration:1});
        //window.setTimeout('\'parentDiv.removeChild(itemDiv);\'', 800);
		itemDiv.parentNode.removeChild(itemDiv);

        items[comesFrom][itemDiv.id] = null;
        rebuildItemsOfSection(comesFrom);
        checkDeletedSection(comesFrom);
        layoutAsync.deleteelement(itemId);
    }
}

/**
 * Hides an element
 */
function hideElement(itemId, confirmMsg, pageGadget, pageAction, pageId)
{
    var itemDiv     = $('item_' + itemId);
    var parentDiv   = itemDiv.parentNode;
    var comesFrom   = parentDiv.id;

    var answer = confirm(confirmMsg);
    if (answer) {
        Effect.Fade(itemDiv.id, {duration:1});
        //window.setTimeout('\'parentDiv.removeChild(itemDiv);\'', 800);
        itemDiv.parentNode.removeChild(itemDiv);

        items[comesFrom][itemDiv.id] = null;
        rebuildItemsOfSection(comesFrom);
        checkDeletedSection(comesFrom);
        layoutAsync.hideelement(itemId, pageGadget, pageAction, pageId);
    }
}

/**
 * Moves an item on the section or to another section
 */
function moveItemOnSection(element, section, serialized)
{
    if (actionStep > 2) {
        return false;
    }

	var page_gadget = ($('page_gadget') ? $('page_gadget').value : null);
	var page_action = ($('page_action') ? $('page_action').value : null);
	var page_linkid = ($('page_linkid') ? $('page_linkid').value : null);
    var sectionId = element.id;

    if (actionStep == 1) {
        itemActions['to']   = sectionId;
    }

    if (actionStep == 2) {
        itemActions['from'] = sectionId;
    }

    if (itemMovedOnSameSection(section, serialized) && !movedNewElement) {
		var sectionChanges = getSectionChanges(sectionId);
        if (sectionChanges == null) {
            return false;
        }
        actionStep = 4;
        itemActions['from'] = sectionId;
        actionStep = 1;

        //$('log').innerHTML += '*)El item ' + sectionChanges[0] + ' fue cambiado a ' + sectionChanges[1] + ' en la posición: '
        //                   + sectionChanges[2] + ', el serial es: ' + serialized + '<br />';
        //$('log').innerHTML += 'Serial: ' + serialized + '<br />';

        rebuildItemsOfSection(sectionId);
				
		layoutAsync.moveelement(sectionChanges[0].replace('item_', ''),
                                sectionChanges[1].replace('custom_page-', ''),
                                sectionChanges[2],
                                items[sectionId],
								page_gadget,
								page_action,
								page_linkid);
    } else {
        actionStep++;
        if (actionStep >= 3) {
            var addedChanges   = getAddedChanges();
            var deletedChanges = getDeletedChanges(addedChanges[0]);

            // $('log').innerHTML += 'El item ' + addedChanges[0] + ' fue cambiado a ' + addedChanges[1] + ' en la posición: ' 
            //                    + addedChanges[2] + ', el serial es: ' + serialized + '<br />';

            rebuildItemsOfSection(addedChanges[1]);
            rebuildItemsOfSection(deletedChanges[1]);

            actionStep = 1;
            itemActions = new Array();

            movedNewElement = false;
            checkDeletedSection(deletedChanges[1]);
			
            layoutAsync.moveelement(addedChanges[0].replace('item_', ''),
                                    addedChanges[1].replace('custom_page-', ''),
                                    addedChanges[2],
                                    items[addedChanges[1]],
									page_gadget,
									page_action,
									page_linkid);
        }
    }
}

/**
 * Creates a random string (for ids)
 */
function randomString()
{
    var chars  = '0123456789abcdefghijklmnopqrstuvwxyz';
    var length = 8;
    var str    = '';
    for (var i=0; i<length; i++) {
        var num = Math.floor(Math.random() * chars.length);
        str += chars.substring(num, num+1);
    }
    return str;
}

/**
 * Initializes some variables
 */
function initUI()
{
	for(var i=0; i<sections.length; i++) {
		var layoutSection = sections[i];
        var layoutDrop    = sections[i];
		$(layoutSection).addClassName('layout-section');
		objects['sort'][layoutSection] = Sortable.create(layoutSection,
        {
            tag: 'div',
            only: 'item',
			handle: 'item-move',
            scroll: window,
            dropOnEmpty: true,
            revert: true,
            constraint: false,
            onUpdate: function(element) {
                moveItemOnSection(element, layoutSection, Sortable.serialize(layoutSection)); 
            }
        });
        objects['drop'][layoutDrop] = Droppables.add(layoutDrop, {
            accept: 'item',
            hoverclass: 'layout-section-hover',
            overlap: 'horizontal',
            onDrop: function(draggableElement, droppableElement) {
				moveElement(draggableElement, droppableElement);
            }
        });
    }
	$$('.layout-section .item-controls').each(function(element){element.style.visibility = 'visible';});
}

function changeTheme()
{
    $('controls').submit();
}

function changeLayoutMode()
{
    $('controls').submit();
}

function addGadget(url, title)
{
	w = new UI.URLWindow({
		height: 450,
		width: 920,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	//w.center();
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	w.setZIndex(2147483647);
	w.show(true).focus();
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function insertFile(url, title, where)
{
	if (typeof(where) == "undefined" || where == '') {
		if ($('Image')) {
			where = 'Image';
		} else {
			alert('Insert field could not be found.');
		}
	}
		
	if (typeof(url) == "undefined" || url == '') {
		url = fileBrowserUrl;
	}
	if (url.indexOf('where=') > -1) {
		url = url.replace('&where=', '&where='+where);
	} else {
		url += '&where='+where;
	}
	w = new UI.URLWindow({
		height: 450,
		width: 920,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	//w.setZIndex(2147483647);
	w.show(true).focus();
	w.setZIndex(2147483647);
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	
	//GB_showCenter(title, url, 510, 950);
}

function editElementAction(url)
{
	w = new UI.URLWindow({
		height: 450,
		width: 450,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	//w.center();
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	w.setZIndex(2147483647);
	w.show(true).focus();
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function changeDisplayWhen(url)
{
	w = new UI.URLWindow({
		height: 350,
		width: 300,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	//w.center();
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	w.setZIndex(2147483647);
	w.show(true).focus();
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function editCSS(url, title)
{
	w = new UI.URLWindow({
		height: 500,
		width: 600,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	//w.center();
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	w.setZIndex(2147483647);
	w.show(true).focus();
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function createNamedElement(type, name) {
   var element = null;
   // Try the IE way; this fails on standards-compliant browsers
   try {
      element = document.createElement('<'+type+' name="'+name+'">');
   } catch (e) {
   }
   if (!element || element.nodeName != type.toUpperCase()) {
      // Non-IE browser; use canonical method to create named element
      element = document.createElement(type);
      element.name = name;
   }
   return element;
}

var prevGadget = '';
function selectGadget(g)
{
    $('gadget').value = g;

    // Remove all actions 
    while ($('actions-list').firstChild)
    {
        $('actions-list').removeChild($('actions-list').firstChild);
    };

    if ($(prevGadget)) {
        $(prevGadget).setAttribute('class', 'gadget-item'); 
        $(prevGadget).setAttribute('className', 'gadget-item'); 
    }
    $(g).setAttribute('class', 'gadget-item gadget-selected'); 
    $(g).setAttribute('className', 'gadget-item gadget-selected'); 
    var actions = layoutSync.getgadgetactions(g);
    var first = null;
    actions.each (
        function(item, arrayIndex) {
            if (first == null) {
                first = 'action_' + item['action'];
            }
            li = document.createElement('li');
            r = createNamedElement('input', 'action');
            //r = document.createElement('input');
            r.setAttribute('type', 'radio');
            //r.setAttribute('name', 'action');
            r.setAttribute('value', item['action']);
            r.setAttribute('id', 'action_' + item['action']);
            label = document.createElement('label');
            label.setAttribute('for', 'action_' + item['action']);
            label.innerHTML = item['name'] + '<span>' + item['desc'] + '</span>';
            li.appendChild(r); 
            li.appendChild(label); 
            $('actions-list').appendChild(li);
        }
    );
    if (first == null) {
        li = document.createElement('li');
        li.setAttribute('class', 'action-msg');
        li.setAttribute('className', 'action-msg');
        li.appendChild(document.createTextNode(noActionsMsg));
        $('actions-list').appendChild(li);
    } else {
        $(first).checked = true;
    }
    prevGadget = g;
}

function addGadgetToLayout(gadget, action, page_gadget, page_action, page_id, parent_element)
{   
    // Ugly hack to add gadget from the greybox
    fun = 'layoutAsync.addgadget(\'' + gadget + '\',\'' + action + '\',\'' + page_gadget + '\',\'' + page_action + '\',\'' + page_id + '\',\'' + items_on_layout + '\')';
    setTimeout(fun, 0);
    //GB_hide();
}

function saveCSS(data)
{   
    fun = 'layoutAsync.savecss(\'' + escape(data) + '\')';
	setTimeout(fun, 0);
    //document.forms['form1'].submit();
}

function getSelectedAction()
{
    var radioObj = document.forms['form1'].elements['action'];
    if(!radioObj)
        return "";
    var radioLength = radioObj.length;
    if(radioLength == undefined)
        if(radioObj.checked)
            return radioObj.value;
        else
            return "";
    for(var i = 0; i < radioLength; i++) {
        if(radioObj[i].checked) {
            return radioObj[i].value;
        }
    }
    return "";
}

function saveElementAction(itemId, action) {
    // Ugly hack to update from the greybox
    fun = 'layoutAsync.editelementaction(' + itemId + ',\'' + action['name'] + '\')';
    setTimeout(fun, 0);
    $('ea' + itemId).innerHTML = action['title'];
    $('ea' + itemId).parentNode.parentNode.title = action['desc'];
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

function saveChangeDW(itemId, dw) {
    // Ugly hack to update from the greybox
    fun = 'layoutAsync.changedisplaywhen(' + itemId + ',\'' + dw + '\')';
    setTimeout(fun, 0);
    if (dw == '*') {
        $('dw' + itemId).innerHTML = displayAlways;
    } else if (jawsTrim(dw) == '') {
        $('dw' + itemId).innerHTML = displayNever;
    } else if (dw.indexOf('{GADGET:') == -1 && dw.indexOf('{HIDEGADGET:') == -1 ) {
        $('dw' + itemId).innerHTML = dw.replace(/,/g, ', ');
    } else {
        $('dw' + itemId).innerHTML = displayThisPage;
    }
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

var ver = navigator.appVersion;
if (/MSIE 6/i.test(navigator.userAgent)) {
    window.onload=function() {
        window.onscroll = function() {
            var clientHeight = document.documentElement.clientHeight;
            clientHeight = (clientHeight == 0 )? document.body.offsetHeight : clientHeight;
            var scrollTop = document.documentElement.scrollTop;
            scrollTop = (scrollTop == 0 )? (document.body.scrollTop - 4) : scrollTop;
            $('layout-controls').style.top = clientHeight + scrollTop - 64 + "px";
        }
    }
}
var layoutAsync = new layoutadminajax(LayoutCallback);
layoutAsync.serverErrorFunc = Jaws_Ajax_ServerError;
layoutAsync.onInit = showWorkingNotification;
layoutAsync.onComplete = hideWorkingNotification;

var layoutSync  = new layoutadminajax();
layoutSync.serverErrorFunc = Jaws_Ajax_ServerError;
layoutSync.onInit = showWorkingNotification;
layoutSync.onComplete = hideWorkingNotification;

var items = {};
var newdrags = new Array();
var sections = new Array();

var previousMode = null;
var itemTmp = null;

var itemActions = new Array();
var actionStep  = 1;

var currentAction = new Array();

var objects = new Array();
objects['sort'] = new Array();
objects['drop'] = new Array();

var movedNewElement = false;
var newEmptyRegion  = '';

//selectd layout mode
var selectedMode = null;

//Combo colors
var evenColor = '#fff';
var oddColor  = '#edf3fe';

var fileBrowserUrl = '';
var oldMouseStop = new Array();
var oldMouseMove = new Array();
var items_on_layout = '';
