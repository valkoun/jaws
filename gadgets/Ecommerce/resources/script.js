/**
 * Ecommerce Javascript actions
 *
 * @category   Ajax
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var EcommerceCallback = { 

    deleteorder: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getOrderData();
        }
        showResponse(response);
    }, 
    
	deleteshipping: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getShippingData();
        }
        showResponse(response);
    }, 
    
	deletetaxes: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getTaxesData();
        }
        showResponse(response);
    }, 
    
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('ecommerce_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('ecommerce_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('ecommerce_datagrid'));
            getOrderData();
        }
        showResponse(response);      
    },
    		
    savesettings: function(response) {
		alert(response[0]['css']);
        showResponse(response);
	},

	addembedsite: function(response) {
        toggleNo('embedInfo');
		//parent.parent.hideWorkingNotification();
        showResponse(response);
    }
};

/**
 * Delete a page : function
 */
function deleteOrder(id)
{
    ecommerceAsync.deleteorder(id);
}

/**
 * Delete a page : function
 */
function deleteShipping(id)
{
    ecommerceAsync.deleteshipping(id);
}

/**
 * Delete a page : function
 */
function deleteTaxes(id)
{
    ecommerceAsync.deletetaxes(id);
}

/**
 * Can use massive delete?
 */
function massiveDelete(message) 
{
    var rows = $('ecommerce_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            ecommerceAsync.massivedelete(rows);
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

/**
 * Search for pages and translations
 */
function searchOrders()
{
    updateOrderDatagrid($('status').value, $('search').value, 0, true);
}

/**
 * Search for pages and translations
 */
function searchShipping()
{
    updateShippingDatagrid($('status').value, $('search').value, 0, true);
}

/**
 * Search for pages and translations
 */
function searchTaxes()
{
    updateTaxesDatagrid($('status').value, $('search').value, 0, true);
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
function EcommerceTableDnD() {
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
			sortEcommerceItem(idsStr, newsortStr);
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
function sortEcommerceItem(id, newsort)
{
    ecommerceAsync.sortitem(id, newsort);
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
	//parent.parent.showWorkingNotification();
    // Ugly hack to add gadget from the greybox
    fun = 'ecommerceAsync.addembedsite(\'' + gadget + '\',\'' + url + '\',\'' + linkid + '\',\'' + layout + '\')';
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
 * gets closest match of a city, and inserts into an element : function
 */
function getClosestMatch(value, pid, element, autocomplete, table)
{
	//showWorkingNotification();
	if (!table) {
		table = '';
	}
    var match = ecommerceSync.getclosestmatch(value, pid, table);
	if (!match['value'] || match['value'] === false) {
		$(element).value = '';
		$(autocomplete).innerHTML = '<ul><li><span class="informal">No matches. Please check your spelling, or try more popular terms.</span></li></ul>';
		$(autocomplete).style.display = '';
	} else {
		$(element).value = match['value'];
	}
	//hideWorkingNotification();
}

/**
 * gets regions of a parent, builds XHTML options and inserts into an element : function
 */
function getRegionsOfParent(id, element, child, next, nextChild)
{
	//showWorkingNotification();
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

    var regions = ecommerceSync.getregionsofparent(id, element);
	if (!regions) {
		option = document.createElement('option');
		option.setAttribute('value', '');
		option.appendChild(document.createTextNode('No locations were found')); 
		$(element).appendChild(option);
	} else {
		var nextID = 0;
		option = document.createElement('option');
		option.setAttribute('value', '');
		option.appendChild(document.createTextNode('Select...'));; 
		$(element).appendChild(option);
		// TODO: Add recursive options for cities within regions, etc.
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
	}
	// Get next children
	if (nextID != 0) {
		getRegionsOfParent(nextID, next, nextChild);
	}
	//hideWorkingNotification();
}

/**
 * Save settings
 */
function saveSettings()
{
	//showWorkingNotification();

    var shipfrom_city = '';
    var shipfrom_state = '';
    var shipfrom_zip = '';
	if ($('city') && $('city').value != "") {
		old_city = $('city').value;
		getClosestMatch($('city').value, $('region').value, 'city', 'search_choices', 'country');
		if (document.forms[0].city.value != old_city) {
			if (!confirm("Did you mean \"" + $('city').value + "\" for the city? Click \"Cancel\" to keep " + old_city + ".")){
				$('city').value = old_city;
			}
		}
	}

	if ($('city') && $('city').value != "") {
		shipfrom_city     		= $('city').value;
	}
	if ($('region') && $('region').value != "") {
		shipfrom_state   		= $('region').value;
	}
	if ($('postal_code') && $('postal_code').value != "") {
		shipfrom_zip     		= $('postal_code').value;
	}

	var payment_gateway 		= $('payment_gateway').value;
    var gateway_id   			= $('gateway_id').value;
    var gateway_key   			= $('gateway_key').value;
    var gateway_signature   	= $('gateway_signature').value;
    var gateway_logo     		= $('gateway_logo').value;
	var transaction_percent		= '';
	if ($('transaction_percent')) {
		transaction_percent		= $('transaction_percent').value;
    }
	var transaction_amount     	= '';
	if ($('transaction_amount')) {
		transaction_amount     	= $('transaction_amount').value;
    }
	var transaction_mode     	= '';
	if ($('transaction_mode')) {
		transaction_mode     	= $('transaction_mode').value;
    }
	var checkout_terms     		= $('checkout_terms').value;
	var use_carrier_calculated  = '';
	if ($('use_carrier_calculated')) {
		use_carrier_calculated = $('use_carrier_calculated').value;
	}
    var notify_expiring_freq 	= new Object();
    list = document.getElementsByName('notify_expiring_freq[]')
    var j = 0;
	for (var i=0;i<list.length;i++){
		if(list[i].checked){
	    	notify_expiring_freq[j] = list[i].value;
			j++;
		}
    }
        
    var response = ecommerceSync.savesettings(
		payment_gateway, gateway_id, gateway_key, gateway_signature, gateway_logo, 
		notify_expiring_freq, shipfrom_city, shipfrom_state, shipfrom_zip, 
		use_carrier_calculated, transaction_percent, transaction_amount, transaction_mode, 
		checkout_terms
	);
	
	showResponse(response);
	//hideWorkingNotification();	
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
	if ($(syntactsCategory + '_no_items')) {
		$(syntactsCategory + '_no_items').style.display = 'none';
	}
	if (!base_url) {
		base_url = '';
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
			ifrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&linkid="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor);
		} else {
			ifrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&where="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor);
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
 * Show Full Update
 */
function toggleEcommerceFullUpdate(id)
{
    if ($('news-preview-'+id)) {
		if ($('news-preview-'+id).style.display == 'none') {
			$('news-preview-'+id).style.display = 'inline';
		} else {
			$('news-preview-'+id).style.display = 'none';
		}
    }
    if ($('news-full-'+id)) {
		if ($('news-full-'+id).style.display == 'none') {
			$('news-full-'+id).style.display = 'inline';
		} else {
			$('news-full-'+id).style.display = 'none';
		}
    }
}

/**
 * Show Update Form
 */
function showEcommerceUpdateForm()
{
	//showWorkingNotification();
    $('update-holder').style.display = 'none';
    $('update-buttons').style.display = 'block';
    $('update-area').style.display = 'block';
	$('update-entry').focus();
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideEcommerceUpdateForm()
{
	//showWorkingNotification();
    $('update-holder').style.display = 'block';
    $('update-buttons').style.display = 'none';
    $('update-area').style.display = 'none';
	$('update-entry').value = '';	
	//hideWorkingNotification();
}

/**
 * Saves an Update
 */
function saveEcommerceUpdate(id, comment, title, parent, sharing)
{
	//showWorkingNotification();
    if (!comment) {
		comment = '';
	}
    if (!title) {
		title = '';
	}
    if (!parent) {
		parent = 0;
	}
    if (!sharing) {
		sharing = 'everyone';
	}
	if (comment.length <= 0) {
		if ($('update-entry') && $('update-entry').value.length > 0) {
			comment = $('update-entry').value;
			$('update-entry').value = '';
		}
	}
	response = ecommerceSync.newecommercecomment(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteEcommerceComment('+response['id']+', \'update\');">X</a></div>';
		news_items_html += '	<div class="news-image">'+response['image']+'</div>';
		news_items_html += '	<div class="news-body">';
		news_items_html += '		<div class="news-title">'+response['title']+'</div>';
		news_items_html += '		<div class="news-info"><span class="news-name">'+(response['link'] != '' ? '<a href="'+response['link']+'">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'</span>&nbsp;';
		news_items_html += '		<span class="news-preview" id="news-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="news-message" id="news-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span></div>';
		news_items_html += '		<div class="news-created news-timestamp">'+response['created']+response['activity']+'</div>';
		news_items_html += '		<div class="news-comments" id="news-comments-'+response['id']+'">';
		news_items_html += '		</div>';
		news_items_html += '		<div class="comments-form">';
		news_items_html += '			<div class="comment-holder" id="comment-holder-'+response['id']+'">';
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showEcommerceCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideEcommerceCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveEcommerceReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		$('news-items').innerHTML = news_items_html + $('news-items').innerHTML;
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}	
	//hideUpdateForm();
	//hideWorkingNotification();
}

/**
 * Show All Comments
 */
function toggleEcommerceAllComments(cid)
{
	$$('.comment-hidden-'+cid).each(function(element){element.style.display = 'block';});
    if ($('comments-form-'+cid)) {
		$('comments-form-'+cid).style.display = 'block';
	}
    if ($('news-comments-'+cid)) {
		$('news-comments-'+cid).style.display = 'block';
	}
    if ($('all-comments-'+cid)) {
		$('all-comments-'+cid).style.display = 'none';
	}
}

/**
 * Show Full Comment
 */
function toggleEcommerceFullComment(cid)
{
    if ($('comment-preview-'+cid)) {
		if ($('comment-preview-'+cid).style.display == 'none') {
			$('comment-preview-'+cid).style.display = 'inline';
		} else {
			$('comment-preview-'+cid).style.display = 'none';
		}
    }
    if ($('comment-full-'+cid)) {
		if ($('comment-full-'+cid).style.display == 'none') {
			$('comment-full-'+cid).style.display = 'inline';
		} else {
			$('comment-full-'+cid).style.display = 'none';
		}
    }
}

/**
 * Show Comment Form
 */
function showEcommerceCommentForm(cid)
{
	//showWorkingNotification();
    if ($('comment-holder-'+cid)) {
		$('comment-holder-'+cid).style.display = 'none';
    }
    if ($('comment-area-'+cid)) {
		$('comment-area-'+cid).style.display = 'block';
    }
    if ($('comment-buttons-'+cid)) {
		$('comment-buttons-'+cid).style.display = 'block';
	}
    if ($('comment-entry-'+cid)) {
		$('comment-entry-'+cid).focus();
	}
	$('comment-entry-'+cid).onBlur = function () {
		hideEcommerceCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideEcommerceCommentForm(cid)
{
	//showWorkingNotification();
    if ($('comment-holder-'+cid)) {
		$('comment-holder-'+cid).style.display = 'block';
    }
    if ($('comment-area-'+cid)) {
		$('comment-area-'+cid).style.display = 'none';
    }
    if ($('comment-buttons-'+cid)) {
		$('comment-buttons-'+cid).style.display = 'none';
	}
    if ($('comment-entry-'+cid)) {
		$('comment-entry-'+cid).value = '';
	}
	//hideWorkingNotification();
}

/**
 * Saves a Comment
 */
function saveEcommerceReply(cid, id, parent)
{
	//showWorkingNotification();
	if (!parent) {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = ecommerceSync.newecommercecomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false));
	if (response['css'] == 'notice-message') {
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteEcommerceComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
		news_comments_html += response['image']+'<div class="comment-body"><span class="comment-name">'+(response['link'] != '' ? '<a href="'+response['link']+'" class="comment-name">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'</span>&nbsp;<span class="comment-preview" id="comment-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="comment-message" id="comment-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span>';
		news_comments_html += '</div><div class="comment-created news-timestamp">'+response['created']+'</div>';
		news_comments_html += '</div>';
		$('news-comments-'+parent).innerHTML = $('news-comments-'+parent).innerHTML + news_comments_html;
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
	hideEcommerceCommentForm(parent);
    if ($('all-comments-'+parent)) {
		$('all-comments-'+parent).innerHTML = '<a href="javascript:void(0);" onclick="toggleEcommerceAllComments('+parent+');">View all comments</a>';
	}
	//hideWorkingNotification();
}

/**
 * Delete Comment
 */
function DeleteEcommerceComment(cid, type, parent)
{
	if (!parent) {
		parent = cid;
	}
	if (!type) {
		type = 'update';
	}
	var answer = confirm(confirmCommentDelete);
    if (answer) {
		//showWorkingNotification();
		var response = ecommerceSync.deleteecommercecomment(cid);
		if (response[0]['css'] == 'notice-message') {
			if (type == 'update' && $('news-'+parent)) {
				$('news-'+parent).parentNode.removeChild($('news-'+parent));
			} else if (type == 'reply' && $('comment-'+parent)) {
				$('comment-'+parent).parentNode.removeChild($('comment-'+parent));
			}
		}
		//hideWorkingNotification();
		//showResponse(response);
    }
}

/**
 * Get pages data
 */
function getOrderData(limit)
{
    if (limit == undefined) {
        limit = $('ecommerce_datagrid').getCurrentPage();
    }
    updateOrderDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousOrderValues()
{
    var previousOrderValues = $('ecommerce_datagrid').getPreviousPagerValues();
    getOrderData(previousOrderValues);
    $('ecommerce_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextOrderValues()
{
    var nextOrderValues = $('ecommerce_datagrid').getNextPagerValues();
    getOrderData(nextOrderValues);
    $('ecommerce_datagrid').nextPage();
}

/**
 * Get previous values of pages
 */
function firstOrderValues()
{
    var firstOrderValues = $('ecommerce_datagrid').getFirstPagerValues();
    getOrderData(firstOrderValues);
    $('ecommerce_datagrid').firstPage();
}

/**
 * Get next values of pages
 */
function lastOrderValues()
{
    var lastOrderValues = $('ecommerce_datagrid').getLastPagerValues();
    getOrderData(lastOrderValues);
    $('ecommerce_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateOrderDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('ecommerce_datagrid').objectName = ecommerceSync;
    JawsDataGrid.name = 'ecommerce_datagrid';

    var result = ecommerceSync.searchorders(status, search, limit);
    resetGrid('ecommerce_datagrid', result);
    if (resetCounter) {
        var size = ecommerceSync.sizeofsearch(status, search);
        $('ecommerce_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('ecommerce_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

/**
 * Get pages data
 */
function getShippingData(limit)
{
    if (limit == undefined) {
        limit = $('shipping_datagrid').getCurrentPage();
    }
    updateShippingDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousShippingValues()
{
    var previousShippingValues = $('shipping_datagrid').getPreviousPagerValues();
    getShippingData(previousShippingValues);
    $('shipping_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextShippingValues()
{
    var nextShippingValues = $('shipping_datagrid').getNextPagerValues();
    getShippingData(nextShippingValues);
    $('shipping_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateShippingDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('shipping_datagrid').objectName = ecommerceSync;
    JawsDataGrid.name = 'shipping_datagrid';

    var result = ecommerceSync.searchshippings(status, search, limit);
    resetGrid('shipping_datagrid', result);
    if (resetCounter) {
        var size = ecommerceSync.sizeofsearch1(status, search);
        $('shipping_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('shipping_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

/**
 * Get pages data
 */
function getTaxesData(limit)
{
    if (limit == undefined) {
        limit = $('taxes_datagrid').getCurrentPage();
    }
    updateTaxesDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousTaxesValues()
{
    var previousTaxesValues = $('taxes_datagrid').getPreviousPagerValues();
    getTaxesData(previousTaxesValues);
    $('taxes_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextTaxesValues()
{
    var nextTaxesValues = $('taxes_datagrid').getNextPagerValues();
    getTaxesData(nextTaxesValues);
    $('taxes_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateTaxesDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('taxes_datagrid').objectName = ecommerceSync;
    JawsDataGrid.name = 'taxes_datagrid';

    var result = ecommerceSync.searchtaxes(status, search, limit);
    resetGrid('taxes_datagrid', result);
    if (resetCounter) {
        var size = ecommerceSync.sizeofsearch1(status, search);
        $('taxes_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('taxes_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

var ecommerceAsync = new ecommerceadminajax(EcommerceCallback);
//ecommerceAsync.serverErrorFunc = Jaws_Ajax_ServerError;

var ecommerceSync = new ecommerceadminajax();
//ecommerceSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

var activeRow = 0;
var fileCount = 0;