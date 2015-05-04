/**
 * WHMCS Javascript actions
 *
 * @category   Ajax
 * @package    WHMCS
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var WHMCSCallback = { 

	deleteclient: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getClientData();
        }
		hideWorkingNotification();
        showResponse(response);
    }, 
       
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('whmcs_clients_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('whmcs_clients_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('whmcs_clients_datagrid'));
            getClientData();
        }
        showResponse(response);      
    }
    		
};

/**
 * Delete a client : function
 */
function deleteClient(id)
{
	showWorkingNotification();
    whmcsAsync.deleteclient(id);
}

/**
 * Can use massive delete?
 */
function massiveDelete(message) 
{
    var rows = $('whmcs_clients_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            whmcsAsync.massivedelete(rows);
        }
    }
}

/**
 * Search for clients
 */
function searchClient()
{
    updateClientDatagrid($('status').value, $('search').value, 0, true);
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
 * Save settings
 */
function saveSettings()
{
	//showWorkingNotification();
	var whmcs_url 		= $('whmcs_url').value;
    var whmcs_api   	= $('whmcs_api').value;
    var whmcs_auth   	= $('whmcs_auth').value;
        
    var response = whmcsSync.savesettings(
		whmcs_url, whmcs_api, whmcs_auth
	);
	
	showResponse(response);
	//hideWorkingNotification();	
}

/**
 * Get client data
 */
function getClientData(limit)
{
    if (limit == undefined) {
        limit = $('whmcs_client_datagrid').getCurrentPage();
    }
    updateClientDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousClientValues()
{
    var previousClientValues = $('whmcs_client_datagrid').getPreviousPagerValues();
    getClientData(previousClientValues);
    $('whmcs_client_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextClientValues()
{
    var nextClientValues = $('whmcs_client_datagrid').getNextPagerValues();
    getClientData(nextClientValues);
    $('whmcs_client_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateClientDatagrid(status, search, limit, resetCounter)
{
	showWorkingNotification();
    $('whmcs_client_datagrid').objectName = whmcsSync;
    JawsDataGrid.name = 'whmcs_client_datagrid';

    var result = whmcsSync.searchclients(status, search, limit);
    resetGrid('whmcs_client_datagrid', result);
    if (resetCounter) {
        var size = whmcsSync.sizeofsearch(status, search);
        $('whmcs_client_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('whmcs_client_datagrid').updatePageCounter();
    }
	hideWorkingNotification();
}

var whmcsAsync = new whmcsadminajax(WHMCSCallback);
whmcsAsync.serverErrorFunc = Jaws_Ajax_ServerError;
whmcsAsync.onInit = showWorkingNotification;
whmcsAsync.onComplete = hideWorkingNotification;

var whmcsSync = new whmcsadminajax();
whmcsSync.serverErrorFunc = Jaws_Ajax_ServerError;
whmcsSync.onInit = showWorkingNotification;
whmcsSync.onComplete = hideWorkingNotification;

var activeRow = 0;
var fileCount = 0;
var num = 0;
