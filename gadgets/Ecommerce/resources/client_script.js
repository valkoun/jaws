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
		//hideWorkingNotification();
        showResponse(response);
    }, 
    
	deleteshipping: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getShippingData();
        }
		//hideWorkingNotification();
        showResponse(response);
    }, 
    
	deletetaxes: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getTaxesData();
        }
		//hideWorkingNotification();
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
        showResponse(response);
    },

	addembedsite: function(response) {
        toggleNo('embedInfo');
		//parent.parent.hideWorkingNotification();
        showResponse(response);
    }
};

if (!Array.prototype.indexOf)
{
  Array.prototype.indexOf = function(elt /*, from*/)
  {
    var len = this.length >>> 0;

    var from = Number(arguments[1]) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
    if (from < 0)
      from += len;

    for (; from < len; from++)
    {
      if (from in this &&
          this[from] === elt)
        return from;
    }
    return -1;
  };
}

/**
 * Delete a page : function
 */
function deleteOrder(id)
{
	//showWorkingNotification();
    ecommerceAsync.deleteorder(id);
}

/**
 * Delete a page : function
 */
function deleteShipping(id)
{
	//showWorkingNotification();
    ecommerceAsync.deleteshipping(id);
}

/**
 * Delete a page : function
 */
function deleteTaxes(id)
{
	//showWorkingNotification();
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
	//showWorkingNotification();
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
 * Save settings
 */
function saveSettings()
{
	//showWorkingNotification();
    var payment_gateway = $('payment_gateway').value;
    var gateway_id   = $('gateway_id').value;
    var gateway_key   = $('gateway_key').value;
    var gateway_logo     = $('gateway_logo').value;
    var notify_expiring_freq = new Object();
    list = document.getElementsByName('notify_expiring_freq[]')
    var j = 0;
	for (var i=0;i<list.length;i++){
		if(list[i].checked){
	    	notify_expiring_freq[j] = list[i].value;
			j++;
		}
    }
        
    ecommerceAsync.savesettings(payment_gateway, gateway_id, gateway_key, gateway_logo, notify_expiring_freq);
	//hideWorkingNotification();
}

/**
 * gets regions of a parent, builds XHTML options and inserts into an element : function
 */
function getRegionsOfParent(id, element, child, next, nextChild)
{
	//showWorkingNotification();
    if (typeof(child) == "undefined" || child == '') {
		child = null;
	}
    if (typeof(next) == "undefined" || next == '') {
		next = null;
	}
    if (typeof(nextChild) == "undefined" || nextChild == '') {
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
 * gets closest match of a city, and inserts into an element : function
 */
function getClosestMatch(value, pid, element, autocomplete, table)
{
	//showWorkingNotification();
    if (typeof(table) == "undefined") {
		table = '';
	}
    var match = ecommerceSync.getclosestmatch(value, pid, table, false);
	if (!match['value'] || match['value'] === false) {
		$(element).value = '';
		$(autocomplete).innerHTML = '<ul><li class="selected" onclick="$(\''+element+'\').value = \'\'; $(\'search_choices\').style.display = \'none\';"><span class="informal">No matches. Please check your spelling, or try more popular terms.</span></li></ul>';
		$(autocomplete).style.display = '';
	} else {
		$(element).value = match['value'];
	}
	//hideWorkingNotification();
}

/**
 * Add a File directly to a Post : function
 */
function addFileToPost(gadget, table, method, syntactsCategory, linkid, num, width, height, bgc, focus, base_url)
{
	//showWorkingNotification();	
    if (typeof(focus) == "undefined") {
		focus = false;
	}
    if (typeof(width) == "undefined") {
		width = 750;
	}
    if (typeof(height) == "undefined") {
		height = 34;
	}
	if ($(syntactsCategory + '_no_items')) {
		$(syntactsCategory + '_no_items').style.display = 'none';
	}
    if (typeof(base_url) == "undefined") {
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
 * Before Add Item in Cart
 */
function onBeforeAddToCart(item, index, newQuantity, opt_node) {
	response = ecommerceSync.onbeforeaddtocart(item, index, newQuantity, opt_node);
	alert(response);
	
	if (response['message']) {
		if (response['message'] == 'true' && response['url'] && response['url'] != '') {
			location.href = response['url'];
			return true;
		} else if (response['message'] == 'true') {
			return true;
		} else if (response['message'].indexOf('Error') > -1) {
			alert(response['message'].replace(':::', "\n"));
			return false;
		}
	}
	return true;
}

/**
 * After Add Item in Cart
 */
function onAddToCart(item, index) {
	response = ecommerceSync.onaddtocart(item, index);
	alert(response);
	if (response['message']) {
		if (response['message'] == 'true' && response['url'] && response['url'] != '') {
			location.href = response['url'];
			return true;
		} else if (response['message'] == 'true') {
			return true;
		} else if (response['message'].indexOf('Error') > -1) {
			alert(response['message'].replace(':::', "\n"));
			return false;
		}
	}
	return true;
}

/**
 * Post Items in Cart
 */
function postCart(
		items, total_weight, paymentmethod, redirect_to
) {
	//showWorkingNotification();	
	shipfreight = (shipfreight == '' && $("shipfreight") && $("shipfreight").value != '' ? $("shipfreight").value : shipfreight);
	customer_shipfirstname = (customer_shipfirstname == '' && $("customer_shipfirstname") && $("customer_shipfirstname").value != '' ? $("customer_shipfirstname").value : customer_shipfirstname);
	customer_shiplastname = (customer_shiplastname == '' && $("customer_shiplastname") && $("customer_shiplastname").value != '' ? $("customer_shiplastname").value : customer_shiplastname);
	customer_shipaddress = (customer_shipaddress == '' && $("customer_shipaddress") && $("customer_shipaddress").value != '' ? $("customer_shipaddress").value : customer_shipaddress);
	customer_shipcity = (customer_shipcity == '' && $("customer_shipcity") && $("customer_shipcity").value != '' ? $("customer_shipcity").value : customer_shipcity);
	customer_shipregion = (customer_shipregion == '' && $("customer_shipregion") && $("customer_shipregion").value != '' ? $("customer_shipregion").value : customer_shipregion);
	customer_shippostal = (customer_shippostal == '' && $("customer_shippostal") && $("customer_shippostal").value != '' ? $("customer_shippostal").value : customer_shippostal);
	customer_shipcountry = (customer_shipcountry == '' && $("customer_shipcountry") && $("customer_shipcountry").value != '' ? $("customer_shipcountry").value : customer_shipcountry);
	customer_shipaddress2 = (customer_shipaddress2 == '' && $("customer_shipaddress2") && $("customer_shipaddress2").value != '' ? $("customer_shipaddress2").value : customer_shipaddress2);
	customer_firstname = (customer_firstname == '' && $("customer_firstname") && $("customer_firstname").value != '' ? $("customer_firstname").value : customer_firstname);
	customer_middlename = (customer_middlename == '' && $("customer_middlename") && $("customer_middlename").value != '' ? $("customer_middlename").value : customer_middlename);
	customer_lastname = (customer_lastname == '' && $("customer_lastname") && $("customer_lastname").value != '' ? $("customer_lastname").value : customer_lastname);
	customer_suffix = (customer_suffix == '' && $("customer_suffix") && $("customer_suffix").value != '' ? $("customer_suffix").value : customer_suffix);
	customer_address = (customer_address == '' && $("customer_address") && $("customer_address").value != '' ? $("customer_address").value : customer_address);
	customer_address2 = (customer_address2 == '' && $("customer_address2") && $("customer_address2").value != '' ? $("customer_address2").value : customer_address2);
	customer_city = (customer_city == '' && $("customer_city") && $("customer_city").value != '' ? $("customer_city").value : customer_city);
	customer_region = (customer_region == '' && $("customer_region") && $("customer_region").value != '' ? $("customer_region").value : customer_region);
	customer_postal = (customer_postal == '' && $("customer_postal") && $("customer_postal").value != '' ? $("customer_postal").value : customer_postal);
	customer_country = (customer_country == '' && $("customer_country") && $("customer_country").value != '' ? $("customer_country").value : customer_country);
	customer_phone = (customer_phone == '' && $("customer_phone1") && $("customer_phone1").value != '' ? $("customer_phone1").value : (customer_phone == '' && $("customer_phone2") && $("customer_phone2").value != '' ? $("customer_phone2").value : customer_phone));
	cc_creditcardtype = (cc_creditcardtype == '' && $("cc_creditcardtype") && $("cc_creditcardtype").value != '' ? $("cc_creditcardtype").value : cc_creditcardtype);
	cc_acct = (cc_acct == '' && $("cc_acct") && $("cc_acct").value != '' ? $("cc_acct").value : cc_acct);
	cc_expdate_month = (cc_expdate_month == '' && $("cc_expdate_month") && $("cc_expdate_month").value != '' ? $("cc_expdate_month").value : cc_expdate_month);
	cc_expdate_year = (cc_expdate_year == '' && $("cc_expdate_year") && $("cc_expdate_year").value != '' ? $("cc_expdate_year").value : cc_expdate_year);
	cc_cvv2 = (cc_cvv2 == '' && $("cc_cvv2") && $("cc_cvv2").value != '' ? $("cc_cvv2").value : cc_cvv2);
	sales_code = (sales_code == '' && $("coupon_code") && $("coupon_code").value != '' ? $("coupon_code").value : sales_code);
	
	customcheckoutfields = new Object();
    var customcheckouts = document.getElementsByName('customcheckoutfields[]')
	for (var i=0;i<customcheckouts.length;i++){
		customcheckoutfields[customcheckouts[i].id] = customcheckouts[i].value;
    }
	
	/*
	alert(
	'items'+items + "\n" +
	'total_weight'+total_weight + "\n" +
	'paymentmethod'+paymentmethod + "\n" +
	'redirect_to'+redirect_to + "\n" +
	'ship first'+customer_shipfirstname + "\n" +
	'last'+customer_shiplastname + "\n" +
	'address'+customer_shipaddress + "\n" +
	'city'+customer_shipcity + "\n" +
	'region'+customer_shipregion + "\n" +
	'postal'+customer_shippostal + "\n" +
	'country'+customer_shipcountry + "\n" +
	'freight'+shipfreight + "\n" +
	'customer_shipaddress2'+customer_shipaddress2 + "\n" +
	'customer_firstname'+customer_firstname + "\n" +
	'customer_middlename'+customer_middlename + "\n" +
	'customer_lastname'+customer_lastname + "\n" +
	'customer_suffix'+customer_suffix + "\n" +
	'customer_address'+customer_address + "\n" +
	'customer_address2'+customer_address2 + "\n" +
	'customer_city'+customer_city + "\n" +
	'customer_region'+customer_region + "\n" +
	'customer_postal'+customer_postal + "\n" +
	'customer_country'+customer_country + "\n" +
	' cc_creditcardtype'+ cc_creditcardtype + "\n" +
	' cc_acct'+ cc_acct + "\n" +
	' cc_expdate_month'+ cc_expdate_month + "\n" +
	' cc_expdate_year'+ cc_expdate_year + "\n" +
	' cc_cvv2'+ cc_cvv2 + "\n"
	' sales_code'+ sales_code + "\n"
	);
	*/
	
	response = ecommerceSync.postcart(
		items, total_weight, paymentmethod, redirect_to, customer_shipfirstname, customer_shiplastname, 
		customer_shipaddress, customer_shipcity, customer_shipregion, customer_shippostal, customer_shipcountry, 
		shipfreight, customer_shipaddress2, customer_firstname, customer_middlename, customer_lastname, 
		customer_suffix, customer_address, customer_address2, customer_city, customer_region, customer_postal, customer_country,
		cc_creditcardtype, cc_acct, cc_expdate_month, cc_expdate_year,  
		cc_cvv2, customcheckoutfields, customer_phone, sales_code
	);
	if (response['message']) {
		if (response['body'] == 'showCreditCard' || response['body'] == 'showShipping') {
			if (response['body'] == 'showCreditCard') {
				showCreditCard();
			} else if (response['body'] == 'showShipping') {
				showShipping();
			}
			if ($("customer_shipfirstname")) {
				$("customer_shipfirstname").value = customer_shipfirstname;
			}
			if ($("customer_shiplastname")) {
			$("customer_shiplastname").value = customer_shiplastname;
			}
			if ($("customer_shipaddress")) {
			$("customer_shipaddress").value = customer_shipaddress;
			}
			if ($("customer_shipcity")) {
			$("customer_shipcity").value = customer_shipcity;
			}
			if ($("customer_shipregion")) {
			$("customer_shipregion").value = customer_shipregion;
			}
			if ($("customer_shippostal")) {
			$("customer_shippostal").value = customer_shippostal;
			}
			if ($("customer_shipcountry")) {
			$("customer_shipcountry").value = customer_shipcountry;
			}
			if ($("shipfreight")) {
			$("shipfreight").value = shipfreight;
			}
			if ($("customer_shipaddress2")) {
			$("customer_shipaddress2").value = customer_shipaddress2;
			}
			if ($("customer_firstname")) {
			$("customer_firstname").value = customer_firstname;
			}
			if ($("customer_middlename")) {
			$("customer_middlename").value = customer_middlename;
			}
			if ($("customer_lastname")) {
			$("customer_lastname").value = customer_lastname;
			}
			if ($("customer_suffix")) {
			$("customer_suffix").value = customer_suffix;
			}
			if ($("customer_address")) {
			$("customer_address").value = customer_address;
			}
			if ($("customer_address2")) {
			$("customer_address2").value = customer_address2;
			}
			if ($("customer_city")) {
			$("customer_city").value = customer_city;
			}
			if ($("customer_region")) {
			$("customer_region").value = customer_region;
			}
			if ($("customer_postal")) {
			$("customer_postal").value = customer_postal;
			}
			if ($("customer_country")) {
			$("customer_country").value = customer_country;
			}
			if ($("customer_phone1")) {
			$("customer_phone1").value = customer_phone;
			}
			if ($("customer_phone2")) {
			$("customer_phone2").value = customer_phone;
			}
			if ($("cc_creditcardtype")) {
			$("cc_creditcardtype").value = cc_creditcardtype;
			}
			if ($("cc_acct")) {
			$("cc_acct").value = cc_acct;
			}
			if ($("cc_expdate_month")) {
			$("cc_expdate_month").value = cc_expdate_month;
			}
			if ($("cc_expdate_year")) {
			$("cc_expdate_year").value = cc_expdate_year;
			}
			if ($("cc_cvv2")) {
			$("cc_cvv2").value = cc_cvv2;
			}
			if (response['message'] != 'javascript') {
				alert(response['message'].replace(':::', "\n"));
			}
			return true;
		} else if (response['message'] == 'true' && response['url'] && response['url'] != '') {
			if (response['form_submit'] && response['form_submit'] == 'true') {
				simpleCart.empty();
				if ($('ecommerce-cart-dock')) {
					$('ecommerce-cart-dock').style.display = 'none';
				}
			}
			location.href = response['url'];
			return true;
		} else if (response['message'] == 'body' && response['body'] && response['form'] && response['form_submit']) {
			if ($('ecommerce-cart-dock')) {
				$('ecommerce-cart-dock').innerHTML = $('ecommerce-cart-dock').innerHTML + response['body'];
				if (response['form_submit'] == 'true') {
					simpleCart.empty();
					$('ecommerce-cart-dock').style.display = 'none';
				}
			}
			if (response['form_submit'] == 'true') {
				document.forms[response['form']].submit();
			}
		} else {
			alert(response['message'].replace(':::', "\n"));
		}
	}
	showList();
	return true;
	//hideWorkingNotification();
}

/**
 * Get Shipping Select HTML
 */
function getShippingSelect(weight, price, qty, zip, state, country)
{
	//showWorkingNotification();
	if ($('shipfreight_holder')) {
		if ($('shipping_details')) {	
			while ($('shipping_details').firstChild) {
				$('shipping_details').removeChild($('shipping_details').firstChild);
			};
		}
		while ($('shipfreight_holder').firstChild) {
			$('shipfreight_holder').removeChild($('shipfreight_holder').firstChild);
		};
		
		var loading_image = document.createElement('img');
		loading_image.setAttribute('id', 'shipping_loading');
		loading_image.setAttribute('src', '../../../images/loading.gif');
		loading_image.setAttribute('border', '0');
		$('shipfreight_holder').appendChild(loading_image);
		
		var loading_text = document.createElement('span');
		loading_text.setAttribute('id', 'shipping_text');
		loading_text.style.fontSize = '0.8em';
		loading_text.appendChild(document.createTextNode(" Please wait while we get shipping options..."));
		$('shipfreight_holder').appendChild(loading_text);
		
		var shipping_span = document.createElement('span');
		shipping_span.setAttribute('class', 'googlecart-form-details');
		shipping_span.setAttribute('className', 'googlecart-form-details');
		shipping_span.appendChild(document.createTextNode("We can currently only ship to U.S. addresses."));
		$('shipping_details').appendChild(shipping_span);
				
		var selectbox = document.createElement('select');
		selectbox.style.fontSize = '1em';
		selectbox.setAttribute('name', 'shipfreight');
		selectbox.setAttribute('id', 'shipfreight');
		
		var optn1 = document.createElement("option");
		optn1.value = '';
		optn1.text = "Select Method...";
		selectbox.options.add(optn1);
		selectbox.setAttribute('onclick', '');
		selectbox.onclick = function() {
			return true;
		}
				
		var optn;
		
		response = ecommerceSync.getshippingselect(weight, price, qty, zip, state, country);
		if (response) {
			//googlecart.clear();
			if (response.substr(0, 6) == '<input') {
				if ($('shipping_details')) {	
					while ($('shipping_details').firstChild) {
						$('shipping_details').removeChild($('shipping_details').firstChild);
					};
				}
				while ($('shipfreight_holder').firstChild) {
					$('shipfreight_holder').removeChild($('shipfreight_holder').firstChild);
				};
				$('shipfreight_holder').innerHTML = response;
			} else if (response.substr(0, 5) == '<span') {
				if ($('shipping_details')) {	
					while ($('shipping_details').firstChild) {
						$('shipping_details').removeChild($('shipping_details').firstChild);
					};
				}
				while ($('shipfreight_holder').firstChild) {
					$('shipfreight_holder').removeChild($('shipfreight_holder').firstChild);
				};
				if ($('shipping_details')) {	
					var shipping_span = document.createElement('span');
					shipping_span.setAttribute('class', 'googlecart-form-details');
					shipping_span.setAttribute('className', 'googlecart-form-details');
					shipping_span.style.fontWeight = 'bold';
					shipping_span.style.color = '#FF0000';
					shipping_span.innerHTML = response;
					$('shipping_details').appendChild(shipping_span);
				}
				alert(response.substr(49, response.length).replace("</span>", ''));
				$('shipfreight_holder').appendChild(document.createTextNode("Estimated Shipping:"+String.fromCharCode(32,32,32,32,32)));
				selectbox.setAttribute('name', 'shipfreight_click');
				selectbox.setAttribute('id', 'shipfreight_click');
				selectbox.setAttribute('onclick', "getShippingSelect('"+total_weight+"', '"+total_price+"', '"+total_qty+"', $('customer_shippostal').value, $('customer_shipregion').value, $('customer_shipcountry').value);");
				selectbox.onclick = function() {
					getShippingSelect(total_weight, total_price, total_qty, $('customer_shippostal').value, $('customer_shipregion').value, $('customer_shipcountry').value);
				}
				$('shipfreight_holder').appendChild(selectbox);
				//return true;
			} else {
				if ($('shipping_loading')) {
					$('shipping_loading').style.display = 'none';
				}
				if ($('shipping_text')) {
					$('shipping_text').style.display = 'none';
				}
				$('shipfreight_holder').appendChild(document.createTextNode("Estimated Shipping:"+String.fromCharCode(32,32,32,32,32)));
				$('shipfreight_holder').appendChild(selectbox);
				values = response.split('|');
				for (i=0;i<values.length;i++) {
					if (values[i] != '') {
						optn = document.createElement("option");
						optn.value = values[i];
						optn.text = values[i];
						selectbox.options.add(optn);
					}
				}
			}
		} else {
			if ($('shipping_details')) {	
				while ($('shipping_details').firstChild) {
					$('shipping_details').removeChild($('shipping_details').firstChild);
				};
				var shipping_span = document.createElement('span');
				shipping_span.setAttribute('class', 'googlecart-form-details');
				shipping_span.setAttribute('className', 'googlecart-form-details');
				shipping_span.style.fontWeight = 'bold';
				shipping_span.style.color = '#FF0000';
				shipping_span.appendChild(document.createTextNode("Unable to retrieve shipping options."));
				$('shipping_details').appendChild(shipping_span);
			}
			alert("Unable to retrieve shipping options.");
			while ($('shipfreight_holder').firstChild) {
				$('shipfreight_holder').removeChild($('shipfreight_holder').firstChild);
			};
			$('shipfreight_holder').appendChild(document.createTextNode("Estimated Shipping:"+String.fromCharCode(32,32,32,32,32)));
			selectbox.setAttribute('name', 'shipfreight_click');
			selectbox.setAttribute('id', 'shipfreight_click');
			selectbox.setAttribute('onclick', "getShippingSelect('"+total_weight+"', '"+total_price+"', '"+total_qty+"', $('customer_shippostal').value, $('customer_shipregion').value, $('customer_shipcountry').value,"+i+");");
			selectbox.onclick = function() {
				getShippingSelect(total_weight, total_price, total_qty, $('customer_shippostal').value, $('customer_shipregion').value, $('customer_shipcountry').value, i);
			}
			$('shipfreight_holder').appendChild(selectbox);
			//return true;
		}
		if (selectbox.options.length > 1 && selectbox.options.selectedIndex == 0) {
			selectbox.options.selectedIndex = 1;
		}
	} else {
		alert("Unable to retrieve shipping options. Please try again later.");
	}
	//hideWorkingNotification();
	return true;
}

/**
 * Apply Given Sales Code
 */
function applySalesCode(code)
{
	//showWorkingNotification();
	if ($('sales_code_status')) {
		$('sales_code_status').innerHTML = "<img id='sales_code_loader' src='../../../images/loading.gif' border='0' />";
		$$('.simpleCart_total').each(function(element){
			element.innerHTML = simpleCart.toCurrency(simpleCart.total());
		});
		response = ecommerceSync.getsalebycode(code, simpleCart.total());
		if (response['id']) {
			$('sales_code_status').innerHTML = '&nbsp;&nbsp;<span style="color: #008000; font-weight: bold; font-size: small;">Valid</span>';
			/*			
			if ($('sales_code_details')) {
				if (response['discount_amount'] > 0) {
					$('sales_code_details').innerHTML = $('sales_code_details').innerHTML + '<p>A discount of $'+response['discount_amount']+' was applied.</p>';
				} else if (response['discount_percent'] > 0) {
					$('sales_code_details').innerHTML = $('sales_code_details').innerHTML + '<p>A discount of $'+response['discount_percent']+'% was applied.</p>';
				} else if (response['discount_newprice'] > 0) {
					$('sales_code_details').innerHTML = $('sales_code_details').innerHTML + '<p>A new price of $'+response['discount_newprice']+' was applied.</p>';
				}
			}
			*/
			if (response['newprice']) {
				$$('.simpleCart_total').each(function(element){
					element.innerHTML = simpleCart.toCurrency(response['newprice']);
				});
			}
		} else {
			$('sales_code_status').innerHTML = '&nbsp;&nbsp;<span style="color: #FF0000; font-weight: bold; font-size: small;">Invalid</span>';
		}
	}
	//hideWorkingNotification();
	return true;
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
    $$('#Ecommerce-accountNews .update-holder')[0].style.display = 'none';
    $$('#Ecommerce-accountNews .update-buttons')[0].style.display = 'block';
    $$('#Ecommerce-accountNews .update-area')[0].style.display = 'block';
	$$('#Ecommerce-accountNews .update-entry')[0].focus();	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideEcommerceUpdateForm()
{
	//showWorkingNotification();
    $$('#Ecommerce-accountNews .update-holder')[0].style.display = 'block';
    $$('#Ecommerce-accountNews .update-buttons')[0].style.display = 'none';
    $$('#Ecommerce-accountNews .update-area')[0].style.display = 'none';
	$$('#Ecommerce-accountNews .update-entry')[0].value = '';	
	//hideWorkingNotification();
}

/**
 * Saves an Update
 */
function saveEcommerceUpdate(id, comment, title, parent, sharing)
{
	//showWorkingNotification();
    if (typeof(comment) == "undefined") {
		comment = '';
	}
    if (typeof(title) == "undefined") {
		title = '';
	}
    if (typeof(parent) == "undefined") {
		parent = 0;
	}
    if (typeof(sharing) == "undefined") {
		sharing = 'everyone';
	}
	if (comment.length <= 0) {
		if ($$('#Ecommerce-accountNews .update-entry')[0] && $$('#Ecommerce-accountNews .update-entry')[0].value.length > 0) {
			comment = $$('#Ecommerce-accountNews .update-entry')[0].value;
			$$('#Ecommerce-accountNews .update-entry')[0].value = '';
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
		$('Ecommerce-news-items').innerHTML = news_items_html + $('Ecommerce-news-items').innerHTML;
		if ($$('#Ecommerce-accountNews .news-items .simple-response-msg')[0]) {
			$$('#Ecommerce-accountNews .news-items .simple-response-msg')[0].style.display = 'none';
		}
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
    if (typeof(parent) == "undefined") {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = ecommerceSync.newecommercecomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false));
	if (response['css'] == 'notice-message') {
		if ($('news-'+parent) && $('news-'+parent).down('.total-comments')) {
			var comments_total = $('news-'+parent).down('.total-comments').innerHTML;
			$('news-'+parent).down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)+1) + " comments";
		}
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
    if (typeof(parent) == "undefined") {
		parent = cid;
	}
    if (typeof(type) == "undefined") {
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
				if ($('comment-'+parent).up('.news-body').down('.total-comments')) {
					var comments_total = $('comment-'+parent).up('.news-body').down('.total-comments').innerHTML;
					$('comment-'+parent).up('.news-body').down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)-1) + " comments";
				}
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

var ecommerceAsync = new ecommerceajax(EcommerceCallback);
//ecommerceAsync.serverErrorFunc = Jaws_Ajax_ServerError;

var ecommerceSync = new ecommerceajax();
//ecommerceSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

var activeRow = 0;
var fileCount = 0;
