/**
 * Social Javascript actions
 *
 * @category   Ajax
 * @package    Social
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var SocialCallback = {
    updatesocial: function(response) {
        showResponse(response);
		hideWorkingNotification();
    }
}

/**
 * Update social
 */
function updateSocial(form)
{
	showWorkingNotification();
	var social_webs = new Object();
	var social_urls = new Object();
	var social_ids = new Object();
	var text = '';
	
	for (i=0; i<form.elements.length; i++) {
		if (form.elements[i].name.indexOf('social_urls') > -1) {
			text = form.elements[i].name.replace('social_urls_', '');
			social_urls[text] = form.elements[i].value;
		} else if (form.elements[i].name.indexOf('social_ids') > -1) {
			text = form.elements[i].name.replace('social_ids_', '');
			if (typeof(social_ids[text]) == "undefined") {
				social_ids[text] = new Object();
			}
			social_ids[text][0] = form.elements[i].value;
		} else if (form.elements[i].name.indexOf('social_id2s') > -1) {
			text = form.elements[i].name.replace('social_id2s_', '');
			if (typeof(social_ids[text]) == "undefined") {
				social_ids[text] = new Object();
			}
			social_ids[text][1] = form.elements[i].value;
		} else if (form.elements[i].name.indexOf('social_id3s') > -1) {
			text = form.elements[i].name.replace('social_id3s_', '');
			if (typeof(social_ids[text]) == "undefined") {
				social_ids[text] = new Object();
			}
			social_ids[text][2] = form.elements[i].value;
		} else if (form.elements[i].name.indexOf('social_webs') > -1) {
			text = form.elements[i].name.replace('social_webs_', '');
			text = text.replace('[]', '');
			social_webs[text] = form.elements[i].checked;
		}
	}

    social.updatesocial(social_webs, social_urls, social_ids);
}

/**
 * Add a File directly to a Post : function
 */
function addFileToPost(gadget, table, method, syntactsCategory, linkid, num, width, height, bgc, focus, base_url, types)
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
	
	if (!types) {
		types = '';
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
			ifrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&linkid="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor + "&types=" + types);
		} else {
			ifrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&where="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (fileCount+1) + "&bc=" + dItem.style.backgroundColor + "&types=" + types);
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

var social = new socialadminajax(SocialCallback);
social.serverErrorFunc = Jaws_Ajax_ServerError;
social.onInit = showWorkingNotification;
social.onComplete = hideWorkingNotification;

var socialSync = new socialadminajax();
socialSync.serverErrorFunc = Jaws_Ajax_ServerError;
socialSync.onInit = showWorkingNotification;
socialSync.onComplete = hideWorkingNotification;

var fileCount = 0;

//social.onInit = showWorkingNotification;
//social.onComplete = hideWorkingNotification;
