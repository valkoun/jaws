/**
 * Calendar Javascript actions
 *
 * @category   Ajax
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
/**
 * Use async mode, create Callback
 */
var CalendarCallback = {
    deleteevent: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getEvents();
			stopAction();
        }
        showResponse(response);
    },

    deletecalendar: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getCalendarData();
            stopAction();
        }
        showResponse(response);
    },
	
	addembedsite: function(response) {
        toggleNo('embedInfo');
        showResponse(response);
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
				window.top.saveUpdate('', comment_html, '', 0, response['sharing'], parent.$('syndication').checked, (parent.$('OwnerID') ? parent.$('OwnerID').value : ''), 'Calendar', true, false, response['eaurl'], false);
			} else {
				//$('layout_main').appendChild(document.createTextNode(response['elementbox']));
				// Fragile!, it must be equal to admin_CustomPage_view template
				parent.selectGadget('Calendar', 'AddGadget', '', parent.prevLinkID, parent.prevSectionID);
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
				var tableDnD = new window.top.CalendarTableDnD();
				tableDnD.init(tbl);             
				//items['main']['item_' + response['id']] = true; 
				//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
			}
		}
		//hideWorkingNotification();
		window.top.GB_hide();
        showResponse(response['message']);
    }	
}

function showEmbedWindow(url, title)
{
    /*
	w = new UI.URLWindow({
        height: 550,
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
 * Get events list
 */
function getEvents()
{
    calendarSync.getevents();
}


/**
 * Add event
 */
function addEvent(
	cid, title, startdate, enddate, iTimeHr, iTimeMin, iTimeSuffix, eTimeHr, 
	eTimeMin, eTimeSuffix, sm_description, description, host, image, alink, 
	alinkTitle, alinkType, isRecurring, active
){
    //selectedEvent = eid;
	currentAction = 'AddEvent';
	//showWorkingNotification();
	var response = calendarSync.addevent(
		cid, title, startdate, enddate, iTimeHr, iTimeMin, iTimeSuffix, eTimeHr, eTimeMin, 
		eTimeSuffix, sm_description, description, host, image, alink, alinkTitle, alinkType, 
		isRecurring, active
	);
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		/*
		for (var i=0; i < 42; i++) {
			if ($('Event'+eid+'-'+i)) {
				$('Event'+eid+'-'+i).style.display = 'none';
			}
		}
		*/
		jQuery('#fullcalendar').fullCalendar('refetchEvents');
		stopAction();
   }
   
	//hideWorkingNotification(); 
	showResponse(response);
}

/**
 * Change event delta
 */
function updateEventDelta(id, startDayDelta, startMinuteDelta, endDayDelta, endMinuteDelta, allDay)
{
    //selectedEvent = eid;
	currentAction = 'UpdateEventDelta';
	//showWorkingNotification();
	var response = calendarSync.updateeventdelta(id, startDayDelta, startMinuteDelta, endDayDelta, endMinuteDelta, allDay);
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		/*
		for (var i=0; i < 42; i++) {
			if ($('Event'+eid+'-'+i)) {
				$('Event'+eid+'-'+i).style.display = 'none';
			}
		}
		*/
		jQuery('#fullcalendar').fullCalendar('refetchEvents');
		stopAction();
   }
   
	//hideWorkingNotification(); 
	showResponse(response);
}

/**
 * Delete event
 */
function deleteEvent(eid)
{
    //selectedEvent = eid;
	currentAction = 'DeleteEvent';
	var answer = confirm(confirmEventDelete);
    if (answer) {
            //showWorkingNotification();
            var response = calendarSync.deleteevent(eid);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				/*
				for (var i=0; i < 42; i++) {
					if ($('Event'+eid+'-'+i)) {
						$('Event'+eid+'-'+i).style.display = 'none';
					}
				}
				*/
				jQuery('#fullcalendar').fullCalendar('refetchEvents');
				stopAction();
           }
		   
            //hideWorkingNotification(); 
			showResponse(response);
    }
}

/**
 * Delete a calendar : function
 */
function deleteCalendar(id)
{
	//showWorkingNotification();
	calendarAsync.deletecalendar(id);
	//hideWorkingNotification();
}

function addUrlToLayout(gadget, url, linkid, layout)
{   
    // Ugly hack to add gadget from the greybox
    fun = 'calendarAsync.addembedsite(\'' + gadget + '\',\'' + url + '\',\'' + linkid + '\',\'' + layout + '\')';
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
 * Can use massive delete?
 */
function massiveDelete(message) 
{
    var rows = $('calendar_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            calendarAsync.massivedelete(rows);
        }
    }
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
    if (typeof(base_url) == "undefined") {
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
		//var tableDnD3 = new CalendarTableDnD();
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
 * Saves Quick Add form : function
 */
function saveQuickAdd(addtype, method, callback, sharing)
{
	//showWorkingNotification();
    if (typeof(addtype) == "undefined") {
		addtype = 'CustomPage';
	}
    if (typeof(method) == "undefined") {
		method = 'AddCalendar';
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
	params["Description"] = '';
	if ($('Description')) {
		params["Description"] = tinyMCE.get('Description').getContent();
	}
	calendarAsync.savequickadd(addtype, method, params, callback);
	/*
	hideWorkingNotification();
	window.top.GB_hide();
	//return response;
	*/
}

/**
 * Show Full Update
 */
function toggleCalendarFullUpdate(id)
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
function showCalendarUpdateForm()
{
	//showWorkingNotification();
    $$('#Calendar-accountNews .update-holder')[0].style.display = 'none';
    $$('#Calendar-accountNews .update-buttons')[0].style.display = 'block';
    $$('#Calendar-accountNews .update-area')[0].style.display = 'block';
	$$('#Calendar-accountNews .update-entry')[0].focus();	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideCalendarUpdateForm()
{
	//showWorkingNotification();
    $$('#Calendar-accountNews .update-holder')[0].style.display = 'block';
    $$('#Calendar-accountNews .update-buttons')[0].style.display = 'none';
    $$('#Calendar-accountNews .update-area')[0].style.display = 'none';
	$$('#Calendar-accountNews .update-entry')[0].value = '';	
	//hideWorkingNotification();
}

/**
 * Saves an Update
 */
function saveCalendarUpdate(id, comment, title, parent, sharing)
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
		if ($$('#Calendar-accountNews .update-entry')[0] && $$('#Calendar-accountNews .update-entry')[0].value.length > 0) {
			comment = $$('#Calendar-accountNews .update-entry')[0].value;
			$$('#Calendar-accountNews .update-entry')[0].value = '';
		}
	}
	response = calendarSync.newcalendarcomment(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteCalendarComment('+response['id']+', \'update\');">X</a></div>';
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
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showCalendarCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideCalendarCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveCalendarReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		$('Calendar-news-items').innerHTML = news_items_html + $('Calendar-news-items').innerHTML;
		if ($$('#Calendar-accountNews .news-items .simple-response-msg')[0]) {
			$$('#Calendar-accountNews .news-items .simple-response-msg')[0].style.display = 'none';
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
function toggleCalendarAllComments(cid)
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
function toggleCalendarFullComment(cid)
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
function showCalendarCommentForm(cid)
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
		hideCalendarCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideCalendarCommentForm(cid)
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
function saveCalendarReply(cid, id, parent)
{
	//showWorkingNotification();
    if (typeof(parent) == "undefined") {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = calendarSync.newcalendarcomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false), parent);
	if (response['css'] == 'notice-message') {
		if ($('news-'+parent) && $('news-'+parent).down('.total-comments')) {
			var comments_total = $('news-'+parent).down('.total-comments').innerHTML;
			$('news-'+parent).down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)+1) + " comments";
		}
		$$('#news-full-'+parent+' .event-button').each(function(element){
			element.setAttribute('class', element.className.replace(" button-disabled", ''));
			element.setAttribute('className', element.className.replace(" button-disabled", ''));
		});
		$('news-full-'+parent).down('.join-event-button').onclick = function() {
			$('comment-entry-'+parent).value = 'is attending.'; saveCalendarReply(cid, id, parent);
		};
		$('news-full-'+parent).down('.maybe-event-button').onclick = function() {
			$('comment-entry-'+parent).value = 'might be attending.'; saveCalendarReply(cid, id, parent);
		};
		$('news-full-'+parent).down('.decline-event-button').onclick = function() {
			$('comment-entry-'+parent).value = 'is not attending.'; saveCalendarReply(cid, id, parent);
		};
		var rsvp = false;
		if (comment.substring(0, 12) == 'is attending') {
			rsvp = true;
			$('news-full-'+parent).down('.join-event-button').setAttribute('class', $('news-full-'+parent).down('.join-event-button').className+" button-disabled");
			$('news-full-'+parent).down('.join-event-button').setAttribute('className', $('news-full-'+parent).down('.join-event-button').className+" button-disabled");
			$('news-full-'+parent).down('.join-event-button').onclick = function() {return false};
		} else if (comment.substring(0, 18) == 'might be attending') {
			rsvp = true;
			$('news-full-'+parent).down('.maybe-event-button').setAttribute('class', $('news-full-'+parent).down('.maybe-event-button').className+" button-disabled");
			$('news-full-'+parent).down('.maybe-event-button').setAttribute('className', $('news-full-'+parent).down('.maybe-event-button').className+" button-disabled");
			$('news-full-'+parent).down('.maybe-event-button').onclick = function() {return false};
		} else if (comment.substring(0, 16) == 'is not attending') {
			rsvp = true;
			$('news-full-'+parent).down('.decline-event-button').setAttribute('class', $('news-full-'+parent).down('.decline-event-button').className+" button-disabled");
			$('news-full-'+parent).down('.decline-event-button').setAttribute('className', $('news-full-'+parent).down('.decline-event-button').className+" button-disabled");
			$('news-full-'+parent).down('.decline-event-button').onclick = function() {return false};
		}
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		if (rsvp === false) {
			news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteCalendarComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
		}
		news_comments_html += response['image']+'<div class="comment-body"><span class="comment-name">'+(response['link'] != '' ? '<a href="'+response['link']+'" class="comment-name">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'</span>&nbsp;<span class="comment-preview" id="comment-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="comment-message" id="comment-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span>';
		news_comments_html += '</div><div class="comment-created news-timestamp">'+response['created']+'</div>';
		news_comments_html += '</div>';
		$('news-comments-'+parent).innerHTML = $('news-comments-'+parent).innerHTML + news_comments_html;
		if (response['delete_ids']) {
			response['delete_ids'].each (
				function(item, arrayIndex) {
					var deleted = calendarSync.deletecalendarcomment(item['id']);
					if (deleted[0]['css'] == 'notice-message') {
						if ($('comment-reply'+item['id'])) {
							if ($('comment-reply'+item['id']).up('.news-body').down('.total-comments')) {
								var comments_total = $('comment-reply'+item['id']).up('.news-body').down('.total-comments').innerHTML;
								$('comment-reply'+item['id']).up('.news-body').down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)-1) + " comments";
							}
							$('comment-reply'+item['id']).parentNode.removeChild($('comment-reply'+item['id']));
						} else if ($('comment-'+item['id'])) {
							if ($('comment-'+item['id']).up('.news-body').down('.total-comments')) {
								var comments_total = $('comment-'+item['id']).up('.news-body').down('.total-comments').innerHTML;
								$('comment-'+item['id']).up('.news-body').down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)-1) + " comments";
							}
							$('comment-'+item['id']).parentNode.removeChild($('comment-'+item['id']));
						}
					}
			});
		}
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
	hideCalendarCommentForm(parent);
	//hideWorkingNotification();
}

/**
 * Delete Comment
 */
function DeleteCalendarComment(cid, type, parent)
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
		var response = calendarSync.deletecalendarcomment(cid);
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
 * TODO: Add sort options from URL fragment hash
 * TODO: Add pagination
 * Shows more messages
 */
function showMoreCalendarComments(public_items, id, interactive, element, append)
{
	//showWorkingNotification();
	/*
	var more_old_html = '';
	if ($(gadget+'-more-items')) {
		more_old_html = $(gadget+'-more-items').innerHTML;
		$(gadget+'-more-items').innerHTML = '<img src="../../../images/loading.gif" border="0" align="left" style="padding-right: 5px;" />' + more_old_html;
	}
	*/
    if (typeof(public_items) == "undefined") {
		public_items = false;
	}
    if (typeof(id) == "undefined") {
		id = null;
	}
    if (typeof(interactive) == "undefined") {
		interactive = true;
	}
    if (typeof(element) == "undefined") {
		element = 'Calendar-news-items';
	}
    if (typeof(append) == "undefined") {
		append = true;
	}
	/*
	if (window.location.hash.indexOf('#pane=') > -1) {
		default_pane = window.location.hash.replace('#pane=','');
	}
	*/
	response = calendarSync.showmorecalendarcomments(public_items, id, interactive);
	if (response['css'] == 'notice-message') {
		if ($(element)) {
			$(element).innerHTML = (append === true ? $(element).innerHTML + response['comments_html'] : response['comments_html']);
		}
		/*
		if ($('Calendar-more-items')) {
			$('Calendar-more-items').innerHTML = more_old_html;
			if (response['items_on_layout'] == messages_on_layout[gadget]) {
				$('Calendar-more-items').style.display = 'none';
			}
		}
		*/
		//messages_on_layout[gadget] = response['items_on_layout'];
		//messages_limit[gadget] = response['items_limit'];
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
}

/**
 * Search for pages and translations
 */
function searchCalendars()
{
    updateCalendarDatagrid($('status').value, $('search').value, 0, true);
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
function getCalendarData(limit)
{
    if (limit == undefined) {
        limit = $('calendar_datagrid').getCurrentPage();
    }
    updateCalendarDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousCalendarValues()
{
    var previousCalendarValues = $('calendar_datagrid').getPreviousPagerValues();
    getCalendarData(previousCalendarValues);
    $('calendar_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextCalendarValues()
{
    var nextCalendarValues = $('calendar_datagrid').getNextPagerValues();
    getCalendarData(nextCalendarValues);
    $('calendar_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateCalendarDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('calendar_datagrid').objectName = calendarSync;
    JawsDataGrid.name = 'calendar_datagrid';

    var result = calendarSync.searchcalendars(status, search, limit);
    resetGrid('calendar_datagrid', result);
    if (resetCounter) {
        var size = calendarSync.sizeofsearch(status, search);
        $('calendar_datagrid').rowsSize    = size;
        //$('calendar_datagrid').setCurrentPage(0);
        $('calendar_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

/**
 * Stops doing a certain action
 */
function stopAction()
{
	selectedEvent = null;
	selectedCalendar = null;
	currentAction = null;
}

var calendarAsync = new calendarajax(CalendarCallback);
//calendarAsync.serverErrorFunc = Jaws_Ajax_ServerError;

var calendarSync  = new calendarajax();
//calendarSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

//Current user
var selectedEvent = null;
//current group
var selectedCalendar = null;
var syntactsData = null;
//Which action are we runing?
var currentAction = null;
var fileCount = 0;
var num = 0;
