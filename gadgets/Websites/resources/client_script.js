/**
 * Websites Javascript actions
 *
 * @category   Ajax
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var WebsitesCallback = { 
	
	deletewebsiteparent: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getWebsiteParentData();
        }
		hideWorkingNotification();
        showResponse(response);
    }, 
   
    deletewebsite: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getWebsiteData();
        }
		hideWorkingNotification();
        showResponse(response);
    }, 
    
    deletesavedwebsite: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getSavedWebsitesData();
        }
		hideWorkingNotification();
        showResponse(response);
    }, 
    
    deletebrand: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getBrandData();
        }
		hideWorkingNotification();
        showResponse(response);
    }, 
    
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('websites_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('websites_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('websites_datagrid'));
            getWebsiteData();
        }
        showResponse(response);      
    },
    	
    massivedeletesavedwebsites: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('savedwebsites_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('savedwebsites_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('savedwebsites_datagrid'));
            getSavedWebsitesData();
        }
        showResponse(response);      
    },
    	
	massivedeletebrands: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('brands_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('brands_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('brands_datagrid'));
            getBrandData();
        }
        showResponse(response);      
    },

    sortitem: function(response) {
        if (response['success']) {
            //$('layout_main').appendChild(document.createTextNode(response['elementbox']));
			window.location.reload();
        }
		hideWorkingNotification();
        showResponse(response['message']);
    },
	    
	addembedsite: function(response) {
        toggleNo('embedInfo');
		parent.parent.hideWorkingNotification();
        showResponse(response);
    }
};

/**
 * Delete a page : function
 */
function deleteWebsite(id)
{
	showWorkingNotification();
    websitesAsync.deletewebsite(id);
}

/**
 * Delete a page : function
 */
function deleteSavedWebsite(id)
{
	showWorkingNotification();
    websitesAsync.deletesavedwebsite(id);
}

/**
 * Delete a page : function
 */
function deleteWebsiteParent(id)
{
	showWorkingNotification();
    websitesAsync.deletewebsiteparent(id);
}

/**
 * Delete a page : function
 */
function deleteBrand(id)
{
	showWorkingNotification();
    websitesAsync.deletebrand(id);
}

/**
 * Can use massive delete?
 */
function massiveDelete(message) 
{
    var rows = $('websites_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            websitesAsync.massivedelete(rows);
        }
    }
}

/**
 * Can use massive delete?
 */
function massiveDeleteSavedWebsites(message) 
{
    var rows = $('savedwebsites_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            websitesAsync.massivedeletesavedwebsites(rows);
        }
    }
}

/**
 * Can use massive delete?
 */
function massiveDeleteBrands(message) 
{
    var rows = $('brands_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            websitesAsync.massivedeletebrands(rows);
        }
    }
}

function showEmbedWindow(url, title)
{
    /*
	w = new UI.URLWindow({
        height: 450,
		width: 590,
		shadow: true,
        theme: "simplewhite",
        url: url,
		minimize: false});
	w.show(true).focus();
	w.center();
	*/	
    GB_showCenter(title, url, 450, 590);
}

function showPostWindow(url, title)
{
    /*
	w = new UI.URLWindow({
        height: 550,
		width: 750,
		shadow: true,
        theme: "simplewhite",
        url: url,
		minimize: false});
	w.show(true).focus();
	w.center();
	*/	
    GB_showCenter(title, url, 550, 750);
}

/**
 * Search for pages and translations
 */
function searchWebsites()
{
    updateWebsitesDatagrid($('websites_status').value, $('websites_search').value, 0, true, $('websites_id').value);
}

/**
 * Search for pages and translations
 */
function searchSavedWebsites()
{
    updateWebsitesDatagrid($('savedwebsites_status').value, $('savedwebsites_search').value, 0, true);
}

/**
 * show Gadget Content
 */
function showGadgetContent()
{
    $('display_form').style.display = 'none';
    $('advanced_form').style.display = 'none';
    $('gadget_form').style.display = 'block';
}

/**
 * show display Options
 */
function displayOptions()
{
    $('display_form').style.display = 'block';
    $('advanced_form').style.display = 'none';
    $('gadget_form').style.display = 'none';
}

/**
 * show advanced Options
 */
function advancedOptions()
{
    $('display_form').style.display = 'none';
    $('advanced_form').style.display = 'block';
    $('gadget_form').style.display = 'none';
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
};

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
};


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
function WebsitesTableDnD() {
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
			var nodrag = rows[i].getAttribute("NoDrag");
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
    };

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
			sortWebsiteItem(idsStr, newsortStr);
		}
		//$('debug').innerHTML = 'row['+row.id+'] dropped<br>'+debugStr;
    };

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
    };

	/** Get the mouse coordinates from the event (allowing for browser differences) */
    this.mouseCoords = function(ev){
        if(ev.pageX || ev.pageY){
            return {x:ev.pageX, y:ev.pageY};
        }
        return {
            x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:ev.clientY + document.body.scrollTop  - document.body.clientTop
        };
    };

	/** Given a target element and a mouse event, get the mouse offset from that element.
		To do this we need the element's position and the mouse position */
    this.getMouseOffset = function(target, ev){
        ev = ev || window.event;

        var docPos    = this.getPosition(target);
        var mousePos  = this.mouseCoords(ev);
        return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
    };

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
    };

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
	};
}

/**
 * sorts an item : function
 */
function sortWebsiteItem(id, newsort)
{
	showWorkingNotification();
    websitesAsync.sortitem(id, newsort);
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

function addUrlToLayout(gadget, url, linkid, layout)
{   
	parent.parent.showWorkingNotification();
    // Ugly hack to add gadget from the greybox
    fun = 'websitesAsync.addembedsite(\'' + gadget + '\',\'' + url + '\',\'' + linkid + '\',\'' + layout + '\')';
    setTimeout(fun, 0);
    /*
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
	*/
    GB_hide();
}

function hideGB()
{   
    /*
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
	*/
    GB_hide();
}

/**
 * Add a File directly to a Post : function
 */
function addFileToPost(gadget, table, method, syntactsCategory, linkid, num, width, height, bgc, focus, base_url)
{
	//showWorkingNotification();	
	if (!focus) {
		focus = false;
	}
	if (!width) {
		width = 750;
	}
	if (!height) {
		height = 34;
	}
	if (!base_url) {
		base_url = '';
	}
	if ($(syntactsCategory + '_no_items')) {
		$(syntactsCategory + '_no_items').style.display = 'none';
	}
				  
	var tbl = $(syntactsCategory);
	var tbod = tbl.getElementsByTagName('tbody');
	var newDate = new Date;
	var form_id = 0;
	is_table = false;
	if (tbod[0]) {
		is_table = true;
		var trs = tbl.getElementsByTagName('tr');
	}
	for (n=0; n<num; n++) {
		form_id = newDate.getTime();
		
		if (is_table) {
			var dItem = document.createElement('tr');
			dItem.setAttribute('width', '100%');
			dItem.setAttribute('noDrag', 'true');
			dItem.setAttribute('noDrop', 'true');
		} else {
			var dItem = document.createElement('div');
		}
		dItem.setAttribute('id', 'syntactsCategory_' + form_id);
		/*
		if (bgc) {
			dItem.style.backgroundColor = "#"+bgc;
		} else {
			dItem.style.backgroundColor = "#FFEBA0";
		}
		*/
		if (is_table) {
			var dItemGadget = dItem.appendChild(document.createElement('td'));
			dItemGadget.setAttribute('class', 'syntacts-form-row');
			dItemGadget.setAttribute('className', 'syntacts-form-row');
			//dItemGadget.setAttribute('width', '93%');
			//dItemGadget.setAttribute('colspan', '3');
		}
		
		ifrm = document.createElement("IFRAME");
		ifrm.setAttribute('id', 'iframe_' + (fileCount+1));
		if (num > 1) {
			ifrm.setAttribute("src", base_url + "index.php?gadget=FileBrowser&action=account_AddFileToPost&linkid="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor);
		} else {
			ifrm.setAttribute("src", base_url + "index.php?gadget=FileBrowser&action=account_AddFileToPost&where="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor);
		}
		ifrm.style.width = width+"px";
		ifrm.style.height = height+"px";
		ifrm.style.borderWidth = 0+"px";
		ifrm.setAttribute('frameborder', '0');
		ifrm.setAttribute('scrolling', 'no');
		ifrm.setAttribute('allowtransparency', 'true');
		ifrm.frameBorder = "0";
		ifrm.scrolling = "no";
		if (n == 0) {
			if (is_table) {
				dItemGadget.innerHTML = '<a name="newImages' + fileCount + '">&nbsp;</a>';
			}
		} 
		if (is_table) {
			dItemGadget.appendChild(ifrm); 		
			/*
			if ($("linkid")) {
				dItemGadget.innerHTML = "<table class=\"tableform\"><tr><td><div><label id=\"file_label\" for=\"file" + fileCount + "\">Image:&nbsp;</label><input type=\"file\" name=\"file" + fileCount + "\" id=\"file" + fileCount + "\" title=\"Filename\" /></div></td></tr></table>";
			} else {
				dItemGadget.innerHTML = "<input type=\"hidden\" name=\"linkid\" id=\"linkid\" value=\"" + linkid + "\" /><input type=\"hidden\" name=\"table\" id=\"table\" value=\"" + table + "\" /><input type=\"hidden\" name=\"addtogadget\" id=\"addtogadget\" value=\"" + gadget + "\" /><input type=\"hidden\" name=\"method\" id=\"method\" value=\"" + method + "\" /><table class=\"tableform\"><tr><td><div><label id=\"file_label\" for=\"file" + fileCount + "\">Image:&nbsp;</label><input type=\"file\" name=\"file" + fileCount + "\" id=\"file" + fileCount + "\" title=\"Filename\" /></div></td></tr></table>";
				//document.getElementById('upload_button').style.display = 'inline';
			}
			*/
			tbod[0].appendChild(dItem);
		} else {
			dItem.appendChild(ifrm); 		
			tbl.appendChild(dItem); 		
		}
		Effect.Appear(dItem.id, {duration:1});
		fileCount++;
		//var tableDnD3 = new GalleryTableDnD();
		//tableDnD3.init(tbl);             
		//items['main']['item_' + response['id']] = true; 
		//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
	}	
	tbl.setAttribute('width', '100%');
	if (is_table) {
		tbod[0].style.display = 'block';
	}
	nextFiles = fileCount-5;
	if (nextFiles < 0) {
		nextFiles = 0;
	}
	if (num > 1 && focus === true) {
		docLocation = document.location+'';
		location.href = (docLocation.indexOf('#newImages') > -1 ? docLocation.substr(0, docLocation.indexOf('#newImages')) + '#newImages' + (nextFiles) : docLocation + '#newImages' + (nextFiles));
	}
	//hideWorkingNotification();
}

/**
 * Get pages data
 */
function getWebsiteParentData(limit)
{
    if (limit == undefined) {
        limit = $('websiteparents_datagrid').getCurrentPage();
    }
    updateWebsiteParentDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousWebsiteParentValues()
{
    var previousWebsiteParentValues = $('websiteparents_datagrid').getPreviousPagerValues();
    getWebsiteParentData(previousWebsiteParentValues);
    $('websiteparents_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextWebsiteParentValues()
{
    var nextWebsiteParentValues = $('websiteparents_datagrid').getNextPagerValues();
    getWebsiteParentData(nextWebsiteParentValues);
    $('websiteparents_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateWebsiteParentDatagrid(status, search, limit, resetCounter)
{
	showWorkingNotification();
    $('websiteparents_datagrid').objectName = websitesSync;
    JawsDataGrid.name = 'websiteparents_datagrid';

    var result = websitesSync.searchwebsiteparents(status, search, limit);
    resetGrid('websiteparents_datagrid', result);
    if (resetCounter) {
        var size = websitesSync.sizeofsearch(status, search);
        $('websiteparents_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('websiteparents_datagrid').updatePageCounter();
    }
	hideWorkingNotification();
}

/**
 * Get pages data
 */
function getWebsiteData(limit)
{
    if (limit == undefined) {
        limit = $('websites_datagrid').getCurrentPage();
    }
    updateWebsitesDatagrid($('websites_status').value,
                        $('websites_search').value,
                        limit,
                        false, $('websites_id').value);
}

/**
 * Get previous values of pages
 */
function previousWebsiteValues()
{
    var previousWebsiteValues = $('websites_datagrid').getPreviousPagerValues();
    getWebsiteData(previousWebsiteValues);
    $('websites_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextWebsiteValues()
{
    var nextWebsiteValues = $('websites_datagrid').getNextPagerValues();
    getWebsiteData(nextWebsiteValues);
    $('websites_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateWebsitesDatagrid(status, search, limit, resetCounter, pid)
{
	showWorkingNotification();
    $('websites_datagrid').objectName = websitesSync;
    JawsDataGrid.name = 'websites_datagrid';

    var result = websitesSync.searchwebsites(status, search, limit, pid);
    resetGrid('websites_datagrid', result);
    if (resetCounter) {
        var size = websitesSync.sizeofsearch1(status, search, pid);
        $('websites_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('websites_datagrid').updatePageCounter();
    }
	hideWorkingNotification();
}

/**
 * Get pages data
 */
function getSavedWebsitesData(limit)
{
    if (limit == undefined) {
        limit = $('savedwebsites_datagrid').getCurrentPage();
    }
    updateSavedWebsitesDatagrid($('savedwebsites_status').value,
                        $('savedwebsites_search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousSavedWebsitesValues()
{
    var previousSavedWebsitesValues = $('savedwebsites_datagrid').getPreviousPagerValues();
    getSavedWebsitesData(previousSavedWebsitesValues);
    $('savedwebsites_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextSavedWebsitesValues()
{
    var nextSavedWebsitesValues = $('savedwebsites_datagrid').getNextPagerValues();
    getSavedWebsitesData(nextSavedWebsitesValues);
    $('savedwebsites_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateSavedWebsitesDatagrid(status, search, limit, resetCounter)
{
	showWorkingNotification();
    $('savedwebsites_datagrid').objectName = websitesSync;
    JawsDataGrid.name = 'savedwebsites_datagrid';

    var result = websitesSync.searchsavedwebsites(status, search, limit);
    resetGrid('savedwebsites_datagrid', result);
    if (resetCounter) {
        var size = websitesSync.sizeofsearch3(status, search);
        $('savedwebsites_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('savedwebsites_datagrid').updatePageCounter();
    }
	hideWorkingNotification();
}

/**
 * Get previous values of pages
 */
function previousBrandValues()
{
    var previousBrandValues = $('brands_datagrid').getPreviousPagerValues();
    getBrandData(previousBrandValues);
    $('brands_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextBrandValues()
{
    var nextBrandValues = $('brands_datagrid').getNextPagerValues();
    getBrandData(nextBrandValues);
    $('brands_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateBrandDatagrid(status, search, limit, resetCounter)
{
	showWorkingNotification();
    $('brands_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'brands_datagrid';

    var result = websitesSync.searchbrands(search, status, limit);
    resetGrid('brands_datagrid', result);
    if (resetCounter) {
        var size = websitesSync.sizeofsearch2(status, search);
        $('brands_datagrid').rowsSize    = size;
        //$('brands_datagrid').setCurrentPage(0);
        $('brands_datagrid').updatePageCounter();
    }
	hideWorkingNotification();
}

var websitesAsync = new websitesajax(WebsitesCallback);
websitesAsync.serverErrorFunc = Jaws_Ajax_ServerError;
websitesAsync.onInit = showWorkingNotification;
websitesAsync.onComplete = hideWorkingNotification;

var websitesSync = new websitesajax();
websitesSync.serverErrorFunc = Jaws_Ajax_ServerError;
websitesSync.onInit = showWorkingNotification;
websitesSync.onComplete = hideWorkingNotification;

var activeRow = 0;
var fileCount = 0;
var num = 0;
