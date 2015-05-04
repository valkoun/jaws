/**
 * Forms Javascript actions
 *
 * @category   Ajax
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var FormsCallback = { 
    
	deletepost: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    }, 

    deleteform: function(response) {
		hideWorkingNotification();
        if (response[0]['css'] == 'notice-message') {
 			if (window.location.href.indexOf('gadget=CustomPage') > -1) {
				getCustomPageData();
			} else {
				getFormsData();
			}
        }
        showResponse(response);
    }, 
    
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('forms_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('forms_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('forms_datagrid'));
            getFormsData();
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
    },
	
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
				window.top.saveUpdate(response['id'], comment_html, '', 0, response['sharing'], parent.$('syndication').checked, 'Forms', true, false, response['eaurl'], false);
			} else {
				//$('layout_main').appendChild(document.createTextNode(response['elementbox']));
				// Fragile!, it must be equal to admin_CustomPage_view template
				parent.selectGadget('Forms', 'AddGadget', '', parent.prevLinkID, parent.prevSectionID);
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
				var tableDnD = new window.top.StoreTableDnD();
				tableDnD.init(tbl);             
				//items['main']['item_' + response['id']] = true; 
				//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
			}
		}
		window.top.GB_hide();
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
    forms.autodraft(gadget, fieldnames, fieldvalues);
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
function setRegistryKey()
{
	showWorkingNotification();
	var response = formsSync.savesettings($('default_recipient').value, $('site_address').value, $('site_office').value, $('site_tollfree').value, $('site_cell').value, $('site_fax').value);
	showResponse(response);
	hideWorkingNotification();
}

/**
 * Creates a new Row for an answer to a form question
 */
function createAnswerRow(count) {
	var tbl = $('syntactsCategoriesAnswers');
	var tbod = tbl.getElementsByTagName('tbody');
		
	var dItem = document.createElement('tr');
	dItem.setAttribute('id', 'syntactsCategoryAnswer_NEXT' + count);
	dItem.setAttribute('noDrag', 'true');
	dItem.setAttribute('noDrop', 'true');

	var dItemGadget = document.createElement('td');
	dItemGadget.setAttribute('class', 'syntacts-form-row');
	dItemGadget.setAttribute('className', 'syntacts-form-row');
	dItemGadget.innerHTML = "<div style=\"100%\"><b><input ID=\"AnswerNew"+count+"\" NAME=\"AnswerNew"+count+"\" SIZE=\"60\" VALUE=\"\" onChange=\"if (this.value.length > 0) {document.getElementById('answer"+count+"Delete').style.display = '';} else {document.getElementById('answer"+count+"Delete').style.display = 'none';};\"></b><br />&nbsp;&nbsp;&nbsp;&nbsp;<a id=\"answer"+count+"Delete\" style=\"display: none;\" href=\"javascript:void(0);\" onclick=\"document.getElementById('AnswerNew"+count+"').value = '';\">Delete</a></div>";
	dItem.appendChild(dItemGadget);
	
	//$('syntactsCategories').childNodes[1].appendChild(dItem);
	tbod[0].appendChild(dItem);
	Effect.Appear(dItem.id, {duration:1});
	rowCount = count;
}

/**
 * Delete a page : function
 */
function deleteForm(id)
{
	showWorkingNotification();
    forms.deleteform(id);
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
            var response = formsSync.deletepost(id);
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
 * Delete a page : function
 */
function deleteAnswer(id)
{
    //selectedCalendar = cid;
	currentAction = 'DeleteAnswer';
	var answer = confirm(confirmAnswerDelete);
    if (answer) {
            showWorkingNotification();
            var response = formsSync.deleteanswer(id);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				$('syntactsCategoryAnswer_'+id).style.display = 'none';
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
    var rows = $('forms_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            forms.massivedelete(rows);
        }
    }
}

/**
 * Search for pages and translations
 */
function searchForm()
{
    updateFormsDatagrid($('status').value, $('search').value, 0, true);
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
			var sortItemTable = "";
			if ($('sortItemTable').value.length > 0) {
				sortItemTable = $('sortItemTable').value;
			}
			this.oldidsStr = idsStr;
			sortItem(idsStr, newsortStr, sortItemTable);
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
function sortItem(id, newsort, table)
{
	showWorkingNotification();
    forms.sortitem(id, newsort, table);
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
 * Saves Quick Add form : function
 */
function saveQuickAdd(addtype, method, callback, sharing)
{
	showWorkingNotification();
	if (!addtype) {
		addtype = 'CustomPage';
	}
	if (!method) {
		method = 'AddForm';
	}
	if (!callback) {
		callback = '';
	}
	if (!sharing) {
		sharing = 'everyone';
	}
	var params = new Object();
	params["sharing"] = sharing;
	var str = '';
	var elem_check = 0;
	var elem = document.forms[0].elements;
	for(var i = 0; i < elem.length; i++)
	{
		if (elem[i].name == 'Active') {
			if (callback != '') {
				params[elem[i].name] = 'N';
			} else {
				params[elem[i].name] = elem[i].value;
			}
		} else {
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
	} 
	params["description"] = '';
	if ($('description')) {
		params["description"] = tinyMCE.get('description').getContent();
	}
	forms.savequickadd(addtype, method, params, callback);
	/*
	hideWorkingNotification();
	window.top.GB_hide();
	//return response;
	*/
}

/**
 * Show Full Update
 */
function toggleFormsFullUpdate(id)
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
function showFormsUpdateForm()
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
function hideFormsUpdateForm()
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
function saveFormsUpdate(id, comment, title, parent, sharing)
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
	response = formsSync.newformscomment(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteFormsComment('+response['id']+', \'update\');">X</a></div>';
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
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showFormsCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideFormsCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveFormsReply('+response['id']+');">Ok</button></div>';
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
function toggleFormsAllComments(cid)
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
function toggleFormsFullComment(cid)
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
function showFormsCommentForm(cid)
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
		hideFormsCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideFormsCommentForm(cid)
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
function saveFormsReply(cid, id, parent)
{
	//showWorkingNotification();
	if (!parent) {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = formsSync.newformscomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false));
	if (response['css'] == 'notice-message') {
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteFormsComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
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
	hideFormsCommentForm(parent);
    if ($('all-comments-'+parent)) {
		$('all-comments-'+parent).innerHTML = '<a href="javascript:void(0);" onclick="toggleFormsAllComments('+parent+');">View all comments</a>';
	}
	//hideWorkingNotification();
}

/**
 * Delete Comment
 */
function DeleteFormsComment(cid, type, parent)
{
	if (!parent) {
		parent = cid;
	}
	if (!type) {
		type = 'update';
	}
	var answer = confirm(confirmFormsCommentDelete);
    if (answer) {
		//showWorkingNotification();
		var response = formsSync.deleteformscomment(cid);
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
function getFormsData(limit)
{
    if (limit == undefined) {
        limit = $('forms_datagrid').getCurrentPage();
    }
    updateFormsDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousFormsValues()
{
    var previousFormsValues = $('forms_datagrid').getPreviousPagerValues();
    getFormsData(previousFormsValues);
    $('forms_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextFormsValues()
{
    var nextFormsValues = $('forms_datagrid').getNextPagerValues();
    getFormsData(nextFormsValues);
    $('forms_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateFormsDatagrid(status, search, limit, resetCounter)
{
	showWorkingNotification();
    $('forms_datagrid').objectName = formsSync;
    JawsDataGrid.name = 'forms_datagrid';

    var result = formsSync.searchforms(status, search, limit);
    resetGrid('forms_datagrid', result);
    if (resetCounter) {
        var size = formsSync.sizeofsearch(status, search);
        $('forms_datagrid').rowsSize    = size;
        //$('forms_datagrid').setCurrentPage(0);
        $('forms_datagrid').updatePageCounter();
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
        if (actioni == 'AddForm' && message[0]['css'] == 'notice-message') {
            //document.forms[0].elements['action'].value = 'SaveEditPage';
            document.forms[0].elements['id'].value     = message[0]['message']['id'];
            message[0]['message'] = message[0]['message']['message'];
        }
        autoDraftDone = true;
    }
    showResponse(message);
}

var forms = new formsajax(FormsCallback);

var formsSync = new formsajax();
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

var autoDraftDone = false;