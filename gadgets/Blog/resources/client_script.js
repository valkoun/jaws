/**
 * Blog Javascript actions
 *
 * @category   Ajax
 * @package    Blog
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2015 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var BlogCallback = { 
    savequickadd: function(response) {
        var exists = false;
		if (response['success']) {
			if (response['addtype'] == 'Scrumy') {
				if (window.top.$('div_' + response['id'])) {
					exists = true;
					//$('syntactsCategory_' + response['id']).parentNode.removeChild($('syntactsCategory_' + response['id']));
				}
				if (window.top.$(response['gadget'] + '_puzzle')) {
					var dItem = window.top.$(response['gadget'] + '_puzzle');
				
					if (window.top.$(response['gadget'] + '_puzzle_no_items')) {
						window.top.$(response['gadget'] + '_puzzle_no_items').style.display = 'none';
					}
					
					var dItemGadget = document.createElement('div');
					dItem.appendChild(dItemGadget);
					dItemGadget.setAttribute('class', "piece " + response['gadget'] + response['status'].toLowerCase().replace(/ /gi, ""));
					dItemGadget.setAttribute('className', "piece " + response['gadget'] + response['status'].toLowerCase().replace(/ /gi, ""));
					dItemGadget.setAttribute('id', 'div_' + response['id'] + '_temp');
					
					var message_type = document.createElement('div');
					message_type.setAttribute('class', "message_type");
					message_type.setAttribute('className', "message_type");
					if (response['message_type'] != '') {
						message_type.appendChild(document.createTextNode(response['message_type']));
					}
					dItemGadget.appendChild(message_type);

					var handle = document.createElement('div');
					handle.setAttribute('class', "handle");
					handle.setAttribute('className', "handle");
					var handleSpan = document.createElement('span');
					handleSpan.setAttribute('class', "handle_title");
					handleSpan.setAttribute('className', "handle_title");
					handleSpan.appendChild(document.createTextNode(response['status'].toUpperCase()));
					handle.appendChild(handleSpan);
					handle.appendChild(document.createTextNode(response['id']));
					dItemGadget.appendChild(handle);
					
					var message = document.createElement('div');
					message.setAttribute('class', "message");
					message.setAttribute('className', "message");
					message.appendChild(document.createTextNode(response['tmessage']));
					dItemGadget.appendChild(message);
					
					if (response['onclick'] != '') {
						message.onclick = function() {
							eval(response['onclick']);
						}
					}
					
					var deleteBtn = document.createElement('a');
					deleteBtn.setAttribute('href', 'javascript:void(0);');
					deleteBtn.setAttribute('class', "delete");
					deleteBtn.setAttribute('className', "delete");
					if (response['delete'] != '') {
						deleteBtn.onclick = function() {
							eval(response['delete']);
						}
					}
					dItemGadget.appendChild(deleteBtn);
					
					if (exists === false) {
						dItem.appendChild(dItemGadget);
					} else {
						dItem.insertBefore(dItemGadget,window.top.$('div_' + response['id']));
						window.top.$('div_' + response['id']).parentNode.removeChild(window.top.$('div_' + response['id']));
					}
					dItemGadget.setAttribute('id', 'div_' + response['id']);
					window.top.Effect.Appear(dItemGadget.id, {duration:1});
					window.top.Sortable.create(response['gadget'] + '_puzzle', {
						tag:'div',overlap:'horizontal',constraint: false, handle: '.handle', only: 'piece',
						onUpdate:function(){
							/*
							info.update('You\'ve made ' + (++moves) + ' move' + (moves>1 ? 's' : ''));
							if (Sortable.sequence('puzzle').join('')=='123456789') {
								info.update('You\'ve solved the puzzle in ' + moves + ' moves!').morph('congrats');
							}
							*/
						}
					});
					//items['main']['item_' + response['id']] = true; 
					//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
				} else {
					alert('Container section not found.');
				}
			} else if (response['addtype'] == 'Comment') {
				var comment_html = '';
				if (response['html'] != '') {
					comment_html = response['html'];
				}
				window.top.saveUpdate(response['id'], comment_html, '', 0, response['sharing'], parent.$('syndication').checked, (parent.$('OwnerID') ? parent.$('OwnerID').value : ''), 'Blog', true, false, response['eaurl'], false);
			} else {
				//$('layout_main').appendChild(document.createTextNode(response['elementbox']));
				// Fragile!, it must be equal to admin_CustomPage_view template
				parent.selectGadget('Blog', 'AddGadget', '', parent.prevLinkID, parent.prevSectionID);
				return true;
				if (window.top.$('syntactsCategory_' + response['id'])) {
					exists = true;
					//$('syntactsCategory_' + response['id']).parentNode.removeChild($('syntactsCategory_' + response['id']));
				}
				
				if (window.top.$('syntactsCategories_section' + response['section_id'] + '_no_items')) {
					window.top.$('syntactsCategories_section' + response['section_id'] + '_no_items').style.display = 'none';
				}
				if (window.top.$('syntactsCategories_section' + response['section_id'] + '_head')) {
					window.top.$('syntactsCategories_section' + response['section_id'] + '_head').style.display = 'block';
					window.top.$('syntactsCategories_section' + response['section_id'] + '_head').style.width = '100%';
					window.top.$('syntactsCategories_section' + response['section_id'] + '_head').width = '100%';
				}

				var tbl = window.top.$('syntactsCategories_section' + response['section_id']);
				var tbod = tbl.getElementsByTagName('tbody');
				var trs = tbl.getElementsByTagName('tr');
				
				var dItem = document.createElement('tr');
				dItem.setAttribute('id', 'syntactsCategory_' + response['id'] + '_temp');
				//dItem.setAttribute('title', response['tactiondesc']);
				dItem.setAttribute('width', '100%');
				dItem.style.cursor = 'move';
				dItem.style.backgroundColor = "#FFEBA0";

				var dItemIcon = document.createElement('td');
				dItem.appendChild(dItemIcon);
				dItemIcon.setAttribute('class', 'syntacts-form-row');
				dItemIcon.setAttribute('className', 'syntacts-form-row');
				var imgIcon = document.createElement('img');
				imgIcon.setAttribute('alt', 'icon');
				imgIcon.setAttribute('src', response['icon']);
				dItemIcon.appendChild(imgIcon);
				
				var dItemGadget = document.createElement('td');
				dItem.appendChild(dItemGadget);
				dItemGadget.setAttribute('class', 'syntacts-form-row');
				dItemGadget.setAttribute('className', 'syntacts-form-row');
				dItemGadget.setAttribute('id', 'gadget-'+response['eaid']);
				dItemGadget.setAttribute('width', '93%');
				dItemGadget.setAttribute('valign', 'top');
				dItemGadget.setAttribute('align', 'left');
				dItemGadget.style.verticalAlign = 'top';
				dItemGadget.style.textAlign = 'left';
				dItemGadget.style.width = '93%';
				var pea = document.createElement('p');
				dItemGadget.appendChild(pea);
				if (response['layout'] == 1) {
					pea.setAttribute('align', 'right');
				} else {
					pea.setAttribute('align', 'left');
				}
				if (response['image_thumb'] != '') {
					var imgThumb = document.createElement('img');
					imgThumb.setAttribute('alt', 'thumb');
					imgThumb.setAttribute('src', response['image_thumb']);
					imgThumb.setAttribute('width', '80');
					if (response['image_thumb'].substring((response['image_thumb'].length-4), response['image_thumb'].length) != '.jpg' && response['image_thumb'].substring((response['image_thumb'].length-5), response['image_thumb'].length) != '.jpeg') {
						imgThumb.setAttribute('height', '80');
					}
					imgThumb.setAttribute('align', 'left');
					imgThumb.style.paddingLeft = '5px';
					imgThumb.style.paddingRight = '5px';
					imgThumb.style.paddingTop = '5px';
					imgThumb.style.paddingBottom = '5px';
					pea.appendChild(imgThumb);
				}
				bea = document.createElement('b');
				bea.appendChild(document.createTextNode(response['tname']));
				pea.appendChild(bea);

				if (response['taction'] != '') {
					brea = document.createElement('br');
					pea.appendChild(brea);

					aea = document.createElement('a');
					aea.setAttribute('href', 'javascript:void(0);');
					aea.setAttribute('id', response['eaid']);
					aea.setAttribute('name', response['eaid']);
					aea.setAttribute('title', response['tactiondesc']);
					aea.appendChild(document.createTextNode(response['taction']+': '));
					pea.appendChild(aea);
				} else {
					brea2 = document.createElement('br');
					pea.appendChild(brea2);
				}
				//pea2 = document.createElement('p');
				//pea2.appendChild(aea);
				pea.appendChild(document.createTextNode(response['tactiondesc']));
				//pea.appendChild(pea2);

				var dItemEdit = document.createElement('td');
				dItem.appendChild(dItemEdit);
				dItemEdit.setAttribute('class', 'syntacts-form-row');
				dItemEdit.setAttribute('className', 'syntacts-form-row');
				var aedit = document.createElement('a');
				//aedit.setAttribute('href', 'javascript:void(0);') 
				aedit.setAttribute('href', 'javascript:editElementAction("'+response['eaurl']+'");') 
				/*
				imgedit = document.createElement('img');
				imgedit.setAttribute('src', "images/ICON_page_edit.gif");
				*/
				aedit.appendChild(document.createTextNode('EDIT'));
				dItemEdit.appendChild(aedit);
				var dItemSpan = dItemEdit.appendChild(document.createElement('span'));
				dItemSpan.innerHTML = '&nbsp;';

				var dItemDelete = document.createElement('td');
				dItem.appendChild(dItemDelete);
				dItemDelete.setAttribute('class', 'syntacts-form-row');
				dItemDelete.setAttribute('className', 'syntacts-form-row');
				var adel = document.createElement('a');
				//adel.setAttribute('href', 'javascript:void(0);') 
				adel.setAttribute('href', 'javascript:deletePost('+response['id']+');') 
				/*
				imgdel = document.createElement('img');
				imgdel.setAttribute('class', 'syntacts-img-button');
				imgdel.setAttribute('className', 'syntacts-img-button');
				imgdel.setAttribute('src', "images/ICON_delete2.gif");
				*/
				adel.appendChild(document.createTextNode('DELETE'));
				dItemDelete.appendChild(adel);
						  
				tbl.setAttribute('width', '100%');
				tbod[0].style.display = 'block';
				if (exists === false) {
					tbod[0].appendChild(dItem);
				} else {
					tbod[0].insertBefore(dItem,window.top.$('syntactsCategory_' + response['id']));
					window.top.$('syntactsCategory_' + response['id']).parentNode.removeChild(window.top.$('syntactsCategory_' + response['id']));
				}
				dItem.setAttribute('id', 'syntactsCategory_' + response['id']);
				window.top.Effect.Appear(window.top.$(dItem), {duration:1});
				var tableDnD = new window.top.BlogTableDnD();
				tableDnD.init(tbl);             
				//items['main']['item_' + response['id']] = true; 
				//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
			}
		}
		//hideWorkingNotification();
		window.top.GB_hide();
        showResponse(response['message']);
    }	
};

// {{{ Function AutoDraft
/**
 * This function is the main idea behind the auto drafting
 * it will get the values of the fields on the form and then
 * pass them to the function AutoDraft in BlogAjax.php
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
    blog.autodraft(gadget, fieldnames, fieldvalues);
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
 * Hide RSS item : function
 */
function hideRss(id, pid, title, published, url)
{
    //selectedCalendar = cid;
	currentAction = 'HideRss';
	var answer = confirm(confirmRssHide);
    if (answer) {
            //showWorkingNotification();
            var response = blogSync.hiderss(pid, title, published, url);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				var old = $('syntactsCategory_'+id).innerHTML;
				$('syntactsCategory_'+id).innerHTML = $('syntactsEdit_'+id).innerHTML;
				$('syntactsEdit_'+id).innerHTML = old;
				//stopAction();
           }
            //hideWorkingNotification();
	        showResponse(response);
    }
}

/**
 * Show RSS item : function
 */
function showRss(id, pid, title, published, url)
{
    //selectedCalendar = cid;
	currentAction = 'HideRss';
	//showWorkingNotification();
	var response = blogSync.showrss(pid, title, published, url);
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		var old = $('syntactsEdit_'+id).innerHTML;
		$('syntactsEdit_'+id).innerHTML = $('syntactsCategory_'+id).innerHTML;
		$('syntactsCategory_'+id).innerHTML = old;
		//stopAction();
   }
	//hideWorkingNotification();
	//showResponse(response);
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
function BlogTableDnD() {
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
	        var currentId = parseInt(rows[i].id.substr((rows[i].id.indexOf("_")+1),rows[i].id.length));
			if (!isNaN(currentId)) {
				idsStr += currentId;
				newsortStr += i;
				if (i<(rows.length-1)) {
					idsStr += ',';
					newsortStr += ',';
				}	
			}
	    }
		if (this.oldidsStr != idsStr) {
			var sortItemTable = "";
			if ($('sortItemTable').value != undefined) {
				sortItemTable = $('sortItemTable').value;
			}
			this.oldidsStr = idsStr;
			sortBlogItem(idsStr, newsortStr, sortItemTable);
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
function sortBlogItem(id, newsort, table)
{
    blog.sortitem(id, newsort, table);
}

/**
 * gets records row of given ID, and inserts into appropriate form elements : function
 */
function prefillForm(id, elements, form_name)
{
	//showWorkingNotification();
	if (document.forms[form_name] && id) {
		var theForm = document.forms[form_name];
		var property = blogSync.getentry(id);
		var response = new Array(1);
		response[0] = new Array();
		if (!property) {
			response[0]['css'] = 'error-message';
			response[0]['message'] = "There was a problem getting the entry details.";
			showResponse(response);
		} else {
			//response[0]['css'] = 'notice-message';
			//response[0]['message'] = 'Total Elements: '+elements.length;
			for (i=0;i<elements.length;i++){
				//response[0]['message'] = response[0]['message'] + ' Element :::: ' + elements[i] + ' Property :::: ' + property[elements[i]];
				var theElement = elements[i].replace(/PREFILL_/g, '');
				if (theForm.elements[elements[i]]) {
					theForm.elements[elements[i]].value = property[theElement];
				}
			}
			//showResponse(response);
		}
	}
	//hideWorkingNotification();
}

/**
 * Add a File directly to a Post : function
 */
function addFileToPost(gadget, table, method, syntactsCategory, linkid, num, width, height, bgc, focus, base_url, types)
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
    if (typeof(base_url) == "undefined") {
		base_url = '';
	}
    if (typeof(types) == "undefined") {
		types = '';
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
			ifrm.setAttribute("src", base_url + "index.php?gadget=FileBrowser&action=account_AddFileToPost&linkid="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor + "&types=" + types);
		} else {
			ifrm.setAttribute("src", base_url + "index.php?gadget=FileBrowser&action=account_AddFileToPost&where="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor + "&types=" + types);
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
	if (nextFiles < 1) {
		nextFiles = 1;
	}
	if (num > 1 && focus === true) {
		docLocation = document.location+'';
		location.href = (docLocation.indexOf('#newImages') > -1 ? docLocation.substr(0, docLocation.indexOf('#newImages')) + '#newImages' + (nextFiles) : docLocation + '#newImages' + (nextFiles));
	}
	//hideWorkingNotification();
}

/**
 * Saves Quick Add form : function
 */
function saveQuickAdd(addtype, method, callback, sharing)
{
	//showWorkingNotification();
    if (typeof(addtype) == "undefined") {
		addtype = 'CustomPage';
	}
    if (typeof(method) == "undefined") {
		method = 'AddEntry';
	}
    if (typeof(callback) == "undefined") {
		callback = '';
	}
    if (typeof(sharing) == "undefined") {
		sharing = 'everyone';
	}
	var params = new Object();
	params["sharing"] = sharing;
	var str = '';
	var elem_check = 0;
	var elem = document.forms[0].elements;
	for(var i = 0; i < elem.length; i++)
	{
		if (elem[i].type == "radio") {
			if (elem[i].checked) {
				params[elem[i].name] = elem[i].value;
			}
		} else if (elem[i].type == "checkbox") {
			if (elem[i].checked) {
				if (typeof(params[elem[i].name])!='object') {
					params[elem[i].name] = new Object();
				}
				params[elem[i].name][elem_check] = elem[i].value;
				elem_check = elem_check + 1;
			}
		} else {
			params[elem[i].name] = elem[i].value;
		}
	} 
	params["summary"] = '';
	params["description"] = '';
	if ($('summary')) {
		params["summary"] = tinyMCE.get('summary').getContent();
	} else if ($('description')) {
		params["description"] = tinyMCE.get('description').getContent();
	}
	blog.savequickadd(addtype, method, params, callback);
	/*
	hideWorkingNotification();
	window.top.GB_hide();
	//return response;
	*/
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
 * Show Full Update
 */
function toggleBlogFullUpdate(id)
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
function showBlogUpdateForm()
{
	//showWorkingNotification();
    $$('#Blog-accountNews .update-holder')[0].style.display = 'none';
    $$('#Blog-accountNews .update-buttons')[0].style.display = 'block';
    $$('#Blog-accountNews .update-area')[0].style.display = 'block';
	$$('#Blog-accountNews .update-entry')[0].focus();	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideBlogUpdateForm()
{
	//showWorkingNotification();
    $$('#Blog-accountNews .update-holder')[0].style.display = 'block';
    $$('#Blog-accountNews .update-buttons')[0].style.display = 'none';
    $$('#Blog-accountNews .update-area')[0].style.display = 'none';
	$$('#Blog-accountNews .update-entry')[0].value = '';	
	//hideWorkingNotification();
}

/**
 * Saves an Update
 */
function saveBlogUpdate(id, comment, title, parent, sharing)
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
		if ($$('#Blog-accountNews .update-entry')[0] && $$('#Blog-accountNews .update-entry')[0].value.length > 0) {
			comment = $$('#Blog-accountNews .update-entry')[0].value;
			$$('#Blog-accountNews .update-entry')[0].value = '';
		}
	}
	response = blogSync.newblogcomment(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteBlogComment('+response['id']+', \'update\');">X</a></div>';
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
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showBlogCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideBlogCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveBlogReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		$('Blog-news-items').innerHTML = news_items_html + $('Blog-news-items').innerHTML;
		if ($$('#Blog-accountNews .news-items .simple-response-msg')[0]) {
			$$('#Blog-accountNews .news-items .simple-response-msg')[0].style.display = 'none';
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
function toggleBlogAllComments(cid)
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
function toggleBlogFullComment(cid)
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
function showBlogCommentForm(cid)
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
		hideBlogCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideBlogCommentForm(cid)
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
function saveBlogReply(cid, id, parent)
{
	//showWorkingNotification();
    if (typeof(parent) == "undefined") {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = blogSync.newblogcomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false));
	if (response['css'] == 'notice-message') {
		if ($('news-'+parent) && $('news-'+parent).down('.total-comments')) {
			var comments_total = $('news-'+parent).down('.total-comments').innerHTML;
			$('news-'+parent).down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)+1) + " comments";
		}
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteBlogComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
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
	hideBlogCommentForm(parent);
    if ($('all-comments-'+parent)) {
		$('all-comments-'+parent).innerHTML = '<a href="javascript:void(0);" onclick="toggleBlogAllComments('+parent+');">View all comments</a>';
	}
	//hideWorkingNotification();
}

/**
 * Delete Comment
 */
function DeleteBlogComment(cid, type, parent)
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
		var response = blogSync.deleteblogcomment(cid);
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

var blog = new blogajax(BlogCallback);
//blog.serverErrorFunc = Jaws_Ajax_ServerError;

var blogSync = new blogajax();
//blogSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;
HTML_AJAX.onError = Jaws_Ajax_ServerError;

var autoDraftDone = false;
var fileCount = 0;
var num = 0;
