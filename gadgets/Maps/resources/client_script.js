/**
 * Maps Javascript actions
 *
 * @category   Ajax
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var MapsCallback = { 
    setregistrykey: function(response) {
		hideWorkingNotification();
        showResponse(response);
    }, 
    
	deletepost: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    }, 

    deletemap: function(response) {
		hideWorkingNotification();
        if (response[0]['css'] == 'notice-message') {
            getData();
        }
        showResponse(response);
    }, 
    
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('maps_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('maps_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('maps_datagrid'));
            getData();
        }
        showResponse(response);      
    },
    
    autodraft: function(response) {
        showSimpleResponse(response);
    },
        
	sortitem: function(response) {
        if (response['success']) {
            //$('layout_main').appendChild(document.createTextNode(response['elementbox']));
        }
		hideWorkingNotification();
        showResponse(response['message']);
    }
};

// {{{ Function AutoDraft
/**
 * This function is the main idea behind the auto drafting
 * it will get the values of the fields on the form and then
 * pass them to the function AutoDraft in CustomPageAjax.php
 * and also output a nice message at the end :-)
 */
function AutoDraft(gadget, fieldnames, fieldvalues)
{
    // FIXME: temporary disable auto draft
    return;
    /**
	var title     = document.forms[0].elements['title'].value;
    var fasturl   = document.forms[0].elements['fast_url'].value;
    var language  = document.forms[0].elements['language'].value;
    var published = document.forms[0].elements['published'].value;
    var showtitle = document.forms[0].elements['show_title'].value;
    var actioni   = document.forms[0].elements['action'].value;
    var id        = '';

    switch (actioni) {
        case 'AddPage':
            id = 'NEW';
            break;
        case 'SaveEditPage':
            id = document.forms[0].elements['id'].value;
            break;
    }
    var content   = getEditorValue('content');
	*/
    maps.autodraft(gadget, fieldnames, fieldvalues);
    setTimeout('startAutoDrafting();', 120000);
}
// }}}
// {{{ Function startAutoDrafting
/**
 * Just the mother function that will make sure that auto drafting is running
 * and is being run every ~ 120 seconds (2 minutes).
 *
 * @see AutoDraft();
 */
function startAutoDrafting() 
{
    AutoDraft();
}
// }}}

/**
 * Saves a key
 */
function setRegistryKey(value)
{
	showWorkingNotification();
    maps.setregistrykey('/gadgets/Maps/googlemaps_key', value);
}

/**
 * Delete a page : function
 */
function deleteMap(id)
{
	showWorkingNotification();
    maps.deletemap(id);
}

/**
 * Delete a page : function
 */
function deletePost(id)
{
    //selectedCalendar = cid;
	currentAction = 'DeletePost';
	var answer = confirm(confirmPostDelete);
    if (answer) {
            showWorkingNotification();
            var response = mapsSync.deletepost(id);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				$('syntactsCategory_'+id).style.display = 'none';
				//stopAction();
           }
            hideWorkingNotification();
	        showResponse(response);
    }
}

/**
 * Can use massive delete?
 */
function massiveDelete(message) 
{
    var rows = $('maps_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            maps.massivedelete(rows);
        }
    }
}

/**
 * Search for pages and translations
 */
function searchMap()
{
    updatePagesDatagrid($('status').value, $('search').value, 0, true);
}

/**
 * Gets map markers : function
 */
function getMapMarkers(url, address, title) 
{
    if (!url) {
		url = '';
	}
    if (!address) {
		address = '';
	}
    if (!title) {
		title = '';
	}
    var response = mapsSync.getmapmarkers(url, address, title);
	return response;
}

/** Keep hold of the current table being dragged */
var currenttable = null;

/** Capture the onmousemove so that we can see if a row from the current
 *  table if any is being dragged.
 * @param ev the event (for Firefox and Safari, otherwise we use window.event for IE)
 */
document.onmousemove = function(ev){
    if (currenttable && currenttable.dragObject) {
        ev   = ev || window.event;
        var mousePos = currenttable.mouseCoords(ev);
        var y = mousePos.y - currenttable.mouseOffset.y;
        if (y != currenttable.oldY) {
            // work out if we're going up or down...
            var movingDown = y > currenttable.oldY;
            // update the old value
            currenttable.oldY = y;
            // update the style to show we're dragging
            currenttable.dragObject.style.backgroundColor = "#eee";
            // If we're over a row then move the dragged row to there so that the user sees the
            // effect dynamically
            var currentRow = currenttable.findDropTargetRow(y);
            if (currentRow) {
                if (movingDown && currenttable.dragObject != currentRow) {
                    currenttable.dragObject.parentNode.insertBefore(currenttable.dragObject, currentRow.nextSibling);
                } else if (! movingDown && currenttable.dragObject != currentRow) {
                    currenttable.dragObject.parentNode.insertBefore(currenttable.dragObject, currentRow);
                }
            }
        }

        return false;
    }
}

// Similarly for the mouseup
document.onmouseup   = function(ev){
    if (currenttable && currenttable.dragObject) {
        var droppedRow = currenttable.dragObject;
        // If we have a dragObject, then we need to release it,
        // The row will already have been moved to the right place so we just reset stuff
        droppedRow.style.backgroundColor = 'transparent';
        currenttable.dragObject   = null;
        // And then call the onDrop method in case anyone wants to do any post processing
        currenttable.onDrop(currenttable.table, droppedRow);
        currenttable = null; // let go of the table too
    }
}


/** get the source element from an event in a way that works for IE and Firefox and Safari
 * @param evt the source event for Firefox (but not IE--IE uses window.event) */
function getEventSource(evt) {
    if (window.event) {
        evt = window.event; // For IE
        return evt.srcElement;
    } else {
        return evt.target; // For Firefox
    }
}

/**
 * Encapsulate table Drag and Drop in a class. We'll have this as a Singleton
 * so we don't get scoping problems.
 */
function TableDnD() {
	/** Keep track of old sort string */
	this.oldidsStr = "";	
    /** Keep hold of the current drag object if any */
    this.dragObject = null;
    /** The current mouse offset */
    this.mouseOffset = null;
    /** The current table */
    this.table = null;
    /** Remember the old value of Y so that we don't do too much processing */
    this.oldY = 0;

    /** Initialise the drag and drop by capturing mouse move events */
    this.init = function(table) {
        this.table = table;
        var rows = table.tBodies[0].rows; //getElementsByTagName("tr")
        for (var i=0; i<rows.length; i++) {
			// John Tarr: added to ignore rows that I've added the NoDnD attribute to (Category and Header rows)
			var nodrag = rows[i].getAttribute("NoDrag")
			if (nodrag == null || nodrag == "undefined") { //There is no NoDnD attribute on rows I want to drag
				this.makeDraggable(rows[i]);
			}
	        var currentId = parseInt(rows[i].id.substr((rows[i].id.indexOf("_")+1),rows[i].id.length));
			if (!isNaN(currentId)) {
				this.oldidsStr += currentId;
				if (i<(rows.length-1)) {
					this.oldidsStr += ',';
				}	
			}
        }
    }

    /** This function is called when you drop a row, so redefine it in your code
        to do whatever you want, for example use Ajax to update the server */
    this.onDrop = function(table, row) {
        // Do nothing for now
		var rows = this.table.tBodies[0].rows;
	    //var debugStr = "rows now: ";
	    var idsStr = "";
	    var newsortStr = "";
	    for (var i=0; i<rows.length; i++) {
	        //debugStr += rows[i].id+"["+parseInt(rows[i].id.substr((rows[i].id.indexOf("_")+1),rows[i].id.length))+"] ";
	        idsStr += parseInt(rows[i].id.substr((rows[i].id.indexOf("_")+1),rows[i].id.length));
	        newsortStr += i;
			if (i<rows.length) {
				idsStr += ',';
				newsortStr += ',';
			}			
	    }
		if (this.oldidsStr != idsStr) {
			this.oldidsStr = idsStr;
			sortItem(idsStr, newsortStr);
		}
		//$('debug').innerHTML = 'row['+row.id+'] dropped<br>'+debugStr;
    }

	/** Get the position of an element by going up the DOM tree and adding up all the offsets */
    this.getPosition = function(e){
        var left = 0;
        var top  = 0;
		/** Safari fix -- thanks to Luis Chato for this! */
		if (e.offsetHeight == 0) {
			/** Safari 2 doesn't correctly grab the offsetTop of a table row
			    this is detailed here:
			    http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
			    the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
			    note that firefox will return a text node as a first child, so designing a more thorough
			    solution may need to take that into account, for now this seems to work in firefox, safari, ie */
			e = e.firstChild; // a table cell
		}

        while (e.offsetParent){
            left += e.offsetLeft;
            top  += e.offsetTop;
            e     = e.offsetParent;
        }

        left += e.offsetLeft;
        top  += e.offsetTop;

        return {x:left, y:top};
    }

	/** Get the mouse coordinates from the event (allowing for browser differences) */
    this.mouseCoords = function(ev){
        if(ev.pageX || ev.pageY){
            return {x:ev.pageX, y:ev.pageY};
        }
        return {
            x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:ev.clientY + document.body.scrollTop  - document.body.clientTop
        };
    }

	/** Given a target element and a mouse event, get the mouse offset from that element.
		To do this we need the element's position and the mouse position */
    this.getMouseOffset = function(target, ev){
        ev = ev || window.event;

        var docPos    = this.getPosition(target);
        var mousePos  = this.mouseCoords(ev);
        return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
    }

	/** Take an item and add an onmousedown method so that we can make it draggable */
    this.makeDraggable = function(item) {
        if(!item) return;
        var self = this; // Keep the context of the TableDnd inside the function
        item.onmousedown = function(ev) {
            // Need to check to see if we are an input or not, if we are an input, then
            // return true to allow normal processing
            var target = getEventSource(ev);
            if (target.tagName == 'INPUT' || target.tagName == 'A' || target.tagName == 'SELECT') return true;
            currenttable = self;
            self.dragObject  = this;
            self.mouseOffset = self.getMouseOffset(this, ev);
            return false;
        }
        item.style.cursor = "move";
    }

    /** We're only worried about the y position really, because we can only move rows up and down */
    this.findDropTargetRow = function(y) {
        var rows = this.table.tBodies[0].rows;
		for (var i=0; i<rows.length; i++) {
			var row = rows[i];
			// John Tarr added to ignore rows that I've added the NoDnD attribute to (Header rows)
			var nodrop = row.getAttribute("NoDrop");
			if (nodrop == null || nodrop == "undefined") {  //There is no NoDnD attribute on rows I want to drag
				var rowY    = this.getPosition(row).y;
				var rowHeight = parseInt(row.offsetHeight)/2;
				if (row.offsetHeight == 0) {
					rowY = this.getPosition(row.firstChild).y;
					rowHeight = parseInt(row.firstChild.offsetHeight)/2;
				}
				// Because we always have to insert before, we need to offset the height a bit
				if ((y > rowY - rowHeight) && (y < (rowY + rowHeight))) {
					// that's the row we're over
					return row;
				}
			}
		}
		return null;
	}
}

/**
 * sorts an item : function
 */
function sortItem(id, newsort)
{
	showWorkingNotification();
    maps.sortitem(id, newsort);
}

/**
 * gets regions of a parent, builds XHTML options and inserts into an element : function
 */
function getRegionsOfParent(id, element, child, next, nextChild)
{
	showWorkingNotification();
	if (!child || child == '') {
		child = null;
	}
	if (!next || next == '') {
		next = null;
	}
	if (!nextChild || nextChild == '') {
		nextChild = null;
	}
    // Remove all actions 
    while ($(element).firstChild)
    {
        $(element).removeChild($(element).firstChild);
    };

    var regions = mapsSync.getregionsofparent(id, element);
	if (!regions) {
		option = document.createElement('option');
		option.setAttribute('value', '');
		option.appendChild(document.createTextNode('No locations were found')); 
		$(element).appendChild(option);
	} else {
		var nextID = 0;
		//if (regions['type'] == 'html') {
		//	$(element).innerHTML = "<option value=''>Select..</option>\n"+regions['data'];			
		//} else {
			option = document.createElement('option');
			option.setAttribute('value', '');
			option.appendChild(document.createTextNode('Select...'));; 
			$(element).appendChild(option);
			regions.each (
				function(item, arrayIndex) {
					option = document.createElement('option');
					if (element == 'city') {
						option.setAttribute('value', item['region']);
					} else {
						option.setAttribute('value', item['id']);
					}
					option.appendChild(document.createTextNode(item['region']));; 
					if (child == item['region'] || child == item['id']) {
						option.setAttribute('selected', 'selected');
						// Get next children of this ID
						if (next != null && nextChild != null) {
							nextID = item['region'];
						}
					}
					$(element).appendChild(option);
				}
			);
		//}
		//if (child == item['region'] || child == item['id']) {
		//	option.setAttribute('selected', 'selected');
		//}
		// Get next children
		if (nextID != 0) {
			getRegionsOfParent(nextID, next, nextChild);
		}
	}
	hideWorkingNotification();
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

/**
 * Get pages data
 */
function getData(limit)
{
    if (limit == undefined) {
        limit = $('maps_datagrid').getCurrentPage();
    }
    updatePagesDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousValues()
{
    var previousValues = $('maps_datagrid').getPreviousPagerValues();
    getData(previousValues);
    $('maps_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextValues()
{
    var nextValues = $('maps_datagrid').getNextPagerValues();
    getData(nextValues);
    $('maps_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updatePagesDatagrid(status, search, limit, resetCounter)
{
	showWorkingNotification();
    $('maps_datagrid').objectName = mapsSync;
    JawsDataGrid.name = 'maps_datagrid';

    var result = mapsSync.searchmaps(status, search, limit);
    resetGrid('maps_datagrid', result);
    if (resetCounter) {
        var size = mapsSync.sizeofsearch(status, search);
        $('maps_datagrid').rowsSize    = size;
        //$('maps_datagrid').setCurrentPage(0);
        $('maps_datagrid').updatePageCounter();
    }
	hideWorkingNotification();
}

/**
 * Show the response but only text, nothing with datagrid.
 * FIXME!
 */
function showSimpleResponse(message)
{
    if (!autoDraftDone) {
        var actioni   = document.forms[0].elements['action'].value;
        if (actioni == 'AddMap' && message[0]['css'] == 'notice-message') {
            //document.forms[0].elements['action'].value = 'SaveEditPage';
            document.forms[0].elements['id'].value     = message[0]['message']['id'];
            message[0]['message'] = message[0]['message']['message'];
        }
        autoDraftDone = true;
    }
    showResponse(message);
}

var maps = new mapsajax(MapsCallback);
maps.serverErrorFunc = Jaws_Ajax_ServerError;
maps.onInit = showWorkingNotification;
maps.onComplete = hideWorkingNotification;

var mapsSync = new mapsajax();
mapsSync.serverErrorFunc = Jaws_Ajax_ServerError;
mapsSync.onInit = showWorkingNotification;
mapsSync.onComplete = hideWorkingNotification;

var autoDraftDone = false;
