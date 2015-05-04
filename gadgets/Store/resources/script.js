/**
 * Store Javascript actions
 *
 * @category   Ajax
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var StoreCallback = { 
    setregistrykey: function(response) {
        showResponse(response);
    }, 
    
	deletepost: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    }, 

    deleteproductparent: function(response) {
        if (response[0]['css'] == 'notice-message') {
 			if (window.location.href.indexOf('gadget=CustomPage') > -1) {
				getCustomPageData();
			} else {
				getProductParentData();
			}
        }
        showResponse(response);
    }, 

    deleteattribute: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getAttributeData();
        }
        showResponse(response);
    }, 

    deleteattributetype: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getAttributeTypeData();
        }
        showResponse(response);
    }, 

    deleteproduct: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    }, 
    
    deletesale: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getSaleData();
        }
        showResponse(response);
    }, 
    
	deletebrand: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getBrandData();
        }
        showResponse(response);
    }, 
   
    deletetemplate: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getTemplatesData(storePrevGadgetscope);
        }
        showResponse(response);
    }, 
	
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('products_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('products_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('products_datagrid'));
            getProductParentData();
        }
        showResponse(response);      
    },
    
    massivedeleteattributes: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('attributes_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('attributes_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('attributes_datagrid'));
            getAttributeData();
        }
        showResponse(response);      
    },

    massivedeleteattributetypes: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('attribute_types_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('attribute_types_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('attribute_types_datagrid'));
            getAttributeTypeData();
        }
        showResponse(response);      
    },
    
	massivedeletesales: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('sales_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('sales_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('sales_datagrid'));
            getSaleData();
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

    autodraft: function(response) {
        showSimpleResponse(response);
    },
        
	sortitem: function(response) {
        if (response['success']) {
            //$('layout_main').appendChild(document.createTextNode(response['elementbox']));
        }
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
				window.top.saveUpdate(response['id'], comment_html, '', 0, response['sharing'], parent.$('syndication').checked, (parent.$('OwnerID') ? parent.$('OwnerID').value : ''), 'Store', true, false, response['eaurl'], false);
			} else {
				window.top.customPageSelectGadget('Store', 'AddGadget', '', window.top.prevLinkID, window.top.prevSectionID);
				return true;
				//$('layout_main').appendChild(document.createTextNode(response['elementbox']));
				// Fragile!, it must be equal to admin_CustomPage_view template
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
 * pass them to the function AutoDraft in StoreAjax.php
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
    store.autodraft(gadget, fieldnames, fieldvalues);
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

function addAttribute(url, title)
{
	storeW = new UI.URLWindow({
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
	//storeW.center();
	storeW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(storeW.getSize().width/2));
	storeW.setZIndex(2147483647);
	storeW.show(true).focus();
	storeW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-storeW.getSize().height-90, left: storeW.getPosition().left});
	Event.observe(window, "resize", function() {
		storeW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-storeW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(storeW.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

/**
 * Delete a product parent : function
 */
function deleteProductParent(id)
{
	store.deleteproductparent(id);
}

/**
 * Delete a product : function
 */
function deleteProduct(id)
{
    //selectedCalendar = cid;
	currentAction = 'DeleteProduct';
	var answer = confirm(confirmProductDelete);
    if (answer) {
            //showWorkingNotification();
            var response = storeSync.deleteproduct(id);
            if (response && response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				$('syntactsCategory_'+id).style.display = 'none';
				//stopAction();
           }
            //hideWorkingNotification();
	        showResponse(response);
    }
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
            //showWorkingNotification();
            var response = storeSync.deletepost(id);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				$('syntactsCategory_'+id).style.display = 'none';
				//stopAction();
           }
            //hideWorkingNotification();
	        showResponse(response);
    }
}

/**
 * Delete a sale : function
 */
function deleteSale(id)
{
	store.deletesale(id);
}

/**
 * Delete a brand : function
 */
function deleteBrand(id)
{
	store.deletebrand(id);
}

/**
 * Delete an attribute type : function
 */
function deleteAttribute(id)
{
	store.deleteattribute(id);
}

/**
 * Delete a page : function
 */
function deletePostAttribute(id)
{
    //selectedCalendar = cid;
	currentAction = 'DeleteAttribute';
	var answer = confirm(confirmAttributeDelete);
    if (answer) {
            //showWorkingNotification();
            var response = storeSync.deleteattribute(id);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				$('syntactsCategoryAnswer_'+id).style.display = 'none';
				//stopAction();
           }
            //hideWorkingNotification();
	        showResponse(response);
    }
}

/**
 * Delete an attribute type : function
 */
function deleteAttributeType(id)
{
	store.deleteattributetype(id);
}

/**
 * Delete an attribute type : function
 */
function deleteTemplate(file, gadgetscope)
{
	storePrevGadgetscope = gadgetscope;
	store.deletetemplate(file);
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
            var response = storeSync.hiderss(pid, title, published, url);
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
	var response = storeSync.showrss(pid, title, published, url);
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

/**
 * Can use massive delete?
 */
function massiveDelete(message) 
{
    var rows = $('products_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            store.massivedelete(rows);
        }
    }
}

/**
 * Can use massive delete?
 */
function massiveDeleteAttributes(message) 
{
    var rows = $('attributes_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            store.massivedeleteamenities(rows);
        }
    }
}

/**
 * Can use massive delete?
 */
function massiveDeleteAttributeTypes(message) 
{
    var rows = $('attribute_types_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            store.massivedeleteattributetypes(rows);
        }
    }
}

/**
 * Can use massive delete?
 */
function massiveDeleteSales(message) 
{
    var rows = $('sales_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            store.massivedeletesales(rows);
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
            store.massivedeletebrands(rows);
        }
    }
}

/**
 * Can use massive delete?
 */
function massiveDeleteTemplates(message) 
{
    var rows = $('templates_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            store.massivedeletetemplates(rows);
        }
    }
}

/**
 * Search for pages and translations
 */
function searchProductParent()
{
    updateProductParentDatagrid($('productparent_status').value, $('productparent_search').value, 0, true);
}

/**
 * Search for pages and translations
 */
function searchAttribute(use_for_select)
{
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
	updateAttributeDatagrid($('attributes_status').value, $('attributes_search').value, 0, true, use_for_select);
}

/**
 * Search for pages and translations
 */
function searchAttributeType()
{
    updateAttributeTypeDatagrid($('attribute_types_status').value, $('attribute_types_search').value, 0, true);
}

/**
 * Search for pages and translations
 */
function searchSale()
{
    updateSaleDatagrid($('sales_status').value, $('sales_search').value, 0, true);
}

/**
 * Search for pages and translations
 */
function searchBrand()
{
    updateBrandDatagrid($('brands_status').value, $('brands_search').value, 0, true);
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
function StoreTableDnD() {
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
			sortStoreItem(idsStr, newsortStr, sortItemTable);
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
function sortStoreItem(id, newsort, table)
{
    store.sortitem(id, newsort, table);
}

/**
 * Creates a new Row for an answer to a form question
 */
function createAnswerRow(count) {
	var background_color = "";
	if (count === 0) {	
		background_color = '#EDF3FE';
	} else if ((count % 2) === 0) { 
		background_color = '#EDF3FE';
	}
	var tbl = $('syntactsCategoriesAnswers');
	var tbod = tbl.getElementsByTagName('tbody');
		
	var dItem = document.createElement('tr');
	dItem.setAttribute('id', 'syntactsCategoryAnswer_NEXT' + count);
	dItem.style.backgroundColor = background_color;
	dItem.setAttribute('noDrag', 'true');
	dItem.setAttribute('noDrop', 'true');

	var dItemGadget = document.createElement('td');
	dItemGadget.setAttribute('class', 'syntacts-form-row');
	dItemGadget.setAttribute('className', 'syntacts-form-row');
	dItemGadget.style.padding = '5px';
	dItemGadget.style.border = '1pt dashed #DDDDDD';
	dItemGadget.innerHTML = "<div style=\"100%\"><a name=\"Name\"><b>Name:</b></a><img src=\"images/stock/help-browser.png\" border=\"0\" style=\"cursor: pointer; cursor: hand;\" title=\"header=[<p style='padding: 1px' align='left'>Help</p>] body=[<p style='padding: 1px' align='left'>This is the title of the Attribute you are adding.</p>] delay=[10] fade=[on] fadespeed=[.2]\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>&nbsp;<input ID=\"AnswerNew"+count+"\" NAME=\"AnswerNew"+count+"\" SIZE=\"60\" VALUE=\"\" onChange=\"if (this.value.length > 0) {document.getElementById('answer"+count+"Delete').style.display = '';} else {document.getElementById('answer"+count+"Delete').style.display = 'none';};\"></b>&nbsp;&nbsp;&nbsp;&nbsp;<a id=\"answer"+count+"Delete\" style=\"display: none;\" href=\"javascript:void(0);\" onclick=\"document.getElementById('AnswerNew"+count+"').value = ''; document.getElementById('AnswerNewAmount"+count+"').value = ''; document.getElementById('AnswerNewPercent"+count+"').value = ''; document.getElementById('AnswerNewPrice"+count+"').value = '';\">Delete</a></div>";
	dItemGadget.innerHTML = dItemGadget.innerHTML + "<div style=\"100%\"><a name=\"Add Amount\"><b>Add Amount:</b></a><img src=\"images/stock/help-browser.png\" border=\"0\" style=\"cursor: pointer; cursor: hand;\" title=\"header=[<p style='padding: 1px' align='left'>Help</p>] body=[<p style='padding: 1px' align='left'>This Attribute will add the amount you enter here to the final price of the product.</p>] delay=[10] fade=[on] fadespeed=[.2]\"><b>&nbsp;<input ID=\"AnswerNewAmount"+count+"\" NAME=\"AnswerNewAmount"+count+"\" SIZE=\"20\" VALUE=\"\" onChange=\"if (this.value.length > 0) {document.getElementById('answer"+count+"Delete').style.display = '';};\"></b></div>";
	dItemGadget.innerHTML = dItemGadget.innerHTML + "<div style=\"100%\"><a name=\"Add Percent\"><b>Add Percent:</b></a><img src=\"images/stock/help-browser.png\" border=\"0\" style=\"cursor: pointer; cursor: hand;\" title=\"header=[<p style='padding: 1px' align='left'>Help</p>] body=[<p style='padding: 1px' align='left'>This Attribute will add the percentage you enter here to the final price of the product.</p>] delay=[10] fade=[on] fadespeed=[.2]\"><b>&nbsp;<input ID=\"AnswerNewPercent"+count+"\" NAME=\"AnswerNewPercent"+count+"\" SIZE=\"20\" VALUE=\"\" onChange=\"if (this.value.length > 0) {document.getElementById('answer"+count+"Delete').style.display = '';};\"></b></div>";
	dItemGadget.innerHTML = dItemGadget.innerHTML + "<div style=\"100%\"><a name=\"New Price\"><b>New Price:</b></a><img src=\"images/stock/help-browser.png\" border=\"0\" style=\"cursor: pointer; cursor: hand;\" title=\"header=[<p style='padding: 1px' align='left'>Help</p>] body=[<p style='padding: 1px' align='left'>This Attribute will set the final price of the product to the amount you enter here.</p>] delay=[10] fade=[on] fadespeed=[.2]\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>&nbsp;<input ID=\"AnswerNewPrice"+count+"\" NAME=\"AnswerNewPrice"+count+"\" SIZE=\"20\" VALUE=\"\" onChange=\"if (this.value.length > 0) {document.getElementById('answer"+count+"Delete').style.display = '';};\"></b></div>";
	dItem.appendChild(dItemGadget);
	
	//$('syntactsCategories').childNodes[1].appendChild(dItem);
	tbod[0].appendChild(dItem);
	Effect.Appear(dItem.id, {duration:1});
	rowCount = count;
}

/**
 * gets regions of a parent, builds XHTML options and inserts into an element : function
 */
function insertRSS(category, fetch_url, override_city, rss_url, ownerid)
{
	//showWorkingNotification();
	/*
	if (!ownerid || ownerid == '') {
		ownerid = null;
	}
    var regions = storeSync.insertrssproducts(category, fetch_url, override_city, rss_url, ownerid, storeNum);
	if (!regions) {
		if ($('insert')) {
			$('insert').innerHTML = 'No products were found to import.';
		}
	} else {
		regions.each (
			function(item, arrayIndex) {
				if (item['title']) {
					//option = document.createElement('div');
					//option.appendChild(document.createTextNode('Importing: '+item['title'])); 
					if ($('insert')) {
						$('insert').innerHTML = item['title'] + '&nbsp;&nbsp;now importing<br />' + $('insert').innerHTML;
					}
					var response = storeSync.insertrssproduct(item, override_city, category, rss_url, ownerid);
					if (response[0]['css'] == 'notice-message') {
						$('insert').innerHTML = 'SUCCESS&nbsp;&nbsp;' + $('insert').innerHTML;
					} else {
						$('insert').innerHTML = 'NOT IMPORTED&nbsp;&nbsp;' + $('insert').innerHTML;
						showResponse(response);
						hideWorkingNotification();
						return false;
					}
					showResponse(response);
				}
				if (item['next_category'] && item['next_fetch_url'] && item['next_rss_url'] && item['next_ownerid']) {
					if (!item['next_override_city']) {
						item['next_override_city'] = '';
					}
					//option = document.createElement('div');
					//option.appendChild(document.createTextNode('Multi-part Feed, now importing from: ' + item['next_fetch_url'])); 
					if ($('insert')) {
						$('insert').innerHTML = '<br />Multi-part Feed, now importing from: ' + item['next_fetch_url'] + $('insert').innerHTML;
					}
					//document.write('<script>insertRSS('+item['next_category']+', "'+item['next_fetch_url']+'", "'+item['next_override_city']+'", "'+item['next_rss_url']+'", '+item['next_ownerid']+');</script>');
					insertRSS(item['next_category'], item['next_fetch_url'], item['next_override_city'], item['next_rss_url'], item['next_ownerid']);
				}
			}
		);
	}
	storeNum++;
	*/
	//hideWorkingNotification();
}	
	
/**
 * Save Settings form : function
 */
function saveSettings()
{
    //selectedCalendar = cid;
	currentAction = 'SaveSettings';
	//showWorkingNotification();
	var response = storeSync.savesettings($('user_post_limit').value, $('user_desc_char_limit').value, $('user_mask_owner_email').value, $('user_price_limit').value, $('randomize').value, $('default_display').value);
	if (response[0]['css'] == 'notice-message') {
	}
	//hideWorkingNotification();
	showResponse(response);
}

/**
 * Add a File directly to a Post : function
 */
function addFileToPost(gadget, table, method, syntactsCategory, linkid, storeNum, width, height, bgc, focus, base_url, types)
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
	for (n=0; n<storeNum; n++) {
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
		ifrm.setAttribute('id', 'iframe_' + (storeFileCount+1));
		if (storeNum > 1) {
			ifrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&linkid="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (storeFileCount+1) + "&bc=" + dItem.style.backgroundColor + "&types=" + types);
		} else {
			ifrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&where="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (storeFileCount+1) + "&bc=" + dItem.style.backgroundColor + "&types=" + types);
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
				dItemGadget.innerHTML = '<a name="newImages' + storeFileCount + '">&nbsp;</a>';
			}
		} 
		if (is_table) {
			dItemGadget.appendChild(ifrm); 		
			/*
			if ($("linkid")) {
				dItemGadget.innerHTML = "<table class=\"tableform\"><tr><td><div><label id=\"file_label\" for=\"file" + storeFileCount + "\">Image:&nbsp;</label><input type=\"file\" name=\"file" + storeFileCount + "\" id=\"file" + storeFileCount + "\" title=\"Filename\" /></div></td></tr></table>";
			} else {
				dItemGadget.innerHTML = "<input type=\"hidden\" name=\"linkid\" id=\"linkid\" value=\"" + linkid + "\" /><input type=\"hidden\" name=\"table\" id=\"table\" value=\"" + table + "\" /><input type=\"hidden\" name=\"addtogadget\" id=\"addtogadget\" value=\"" + gadget + "\" /><input type=\"hidden\" name=\"method\" id=\"method\" value=\"" + method + "\" /><table class=\"tableform\"><tr><td><div><label id=\"file_label\" for=\"file" + storeFileCount + "\">Image:&nbsp;</label><input type=\"file\" name=\"file" + storeFileCount + "\" id=\"file" + storeFileCount + "\" title=\"Filename\" /></div></td></tr></table>";
				//document.getElementById('upload_button').style.display = 'inline';
			}
			*/
			tbod[0].appendChild(dItem);
		} else {
			dItem.appendChild(ifrm); 		
			tbl.appendChild(dItem); 		
		}
		Effect.Appear(dItem.id, {duration:1});
		storeFileCount++;
		//var tableDnD3 = new GalleryTableDnD();
		//tableDnD3.init(tbl);             
		//items['main']['item_' + response['id']] = true; 
		//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
	}	
	tbl.setAttribute('width', '100%');
	if (is_table) {
		tbod[0].style.display = 'block';
	}
	nextFiles = storeFileCount-5;
	if (nextFiles < 1) {
		nextFiles = 1;
	}
	if (storeNum > 1 && focus === true) {
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
	if (!addtype) {
		addtype = 'CustomPage';
	}
	if (!method) {
		method = 'AddProductParent';
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
		if (elem[i].name == 'productparentActive' || elem[i].name == 'Active') {
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
	params["productparentDescription"] = '';
	params["description"] = '';
	if ($('productparentDescription')) {
		params["productparentDescription"] = tinyMCE.get('productparentDescription').getContent();
	} else if ($('description')) {
		params["description"] = tinyMCE.get('description').getContent();
	}
	store.savequickadd(addtype, method, params, callback);
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
function toggleStoreFullUpdate(id)
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
function showStoreUpdateForm()
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
function hideStoreUpdateForm()
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
function saveStoreUpdate(id, comment, title, parent, sharing)
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
	response = storeSync.newstorecomment(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteStoreComment('+response['id']+', \'update\');">X</a></div>';
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
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showStoreCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideStoreCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveStoreReply('+response['id']+');">Ok</button></div>';
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
function toggleStoreAllComments(cid)
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
function toggleStoreFullComment(cid)
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
function showStoreCommentForm(cid)
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
		hideStoreCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideStoreCommentForm(cid)
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
function saveStoreReply(cid, id, parent)
{
	//showWorkingNotification();
	if (!parent) {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = storeSync.newstorecomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false));
	if (response['css'] == 'notice-message') {
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteStoreComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
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
	hideStoreCommentForm(parent);
    if ($('all-comments-'+parent)) {
		$('all-comments-'+parent).innerHTML = '<a href="javascript:void(0);" onclick="toggleStoreAllComments('+parent+');">View all comments</a>';
	}
	//hideWorkingNotification();
}

/**
 * Delete Comment
 */
function DeleteStoreComment(cid, type, parent)
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
		var response = storeSync.deletestorecomment(cid);
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
 * Get pages data
 */
function getProductParentData(limit)
{
    if (limit == undefined) {
        limit = $('products_datagrid').getCurrentPage();
    }
    updateProductParentDatagrid($('productparent_status').value,
                        $('productparent_search').value,
                        limit,
                        false);
}

/**
 * Get pages data
 */
function getAttributeData(limit, use_for_select)
{
	if (typeof(limit) == "undefined") {
        limit = $('attributes_datagrid').getCurrentPage();
    }
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
    updateAttributeDatagrid($('attributes_status').value,
                        $('attributes_search').value,
                        limit,
                        false,
						use_for_select);
}

/**
 * Get pages data
 */
function getAttributeTypeData(limit)
{
    if (limit == undefined) {
        limit = $('attribute_types_datagrid').getCurrentPage();
    }
    updateAttributeTypeDatagrid($('attribute_types_status').value,
                        $('attribute_types_search').value,
                        limit,
                        false);
}

/**
 * Get pages data
 */
function getSaleData(limit)
{
    if (limit == undefined) {
        limit = $('sales_datagrid').getCurrentPage();
    }
    updateSaleDatagrid($('sales_status').value,
                        $('sales_search').value,
                        limit,
                        false);
}

/**
 * Get pages data
 */
function getBrandData(limit)
{
    if (limit == undefined) {
        limit = $('brands_datagrid').getCurrentPage();
    }
    updateBrandDatagrid($('brands_status').value,
                        $('brands_search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousProductValues()
{
    var previousProductValues = $('products_datagrid').getPreviousPagerValues();
    getProductParentData(previousProductValues);
    $('products_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextProductValues()
{
    var nextProductValues = $('products_datagrid').getNextPagerValues();
    getProductParentData(nextProductValues);
    $('products_datagrid').nextPage();
}

/**
 * Get first values of pages
 */
function firstProductValues()
{
    var firstProductValues = $('products_datagrid').getFirstPagerValues();
    getProductParentData(firstProductValues);
    $('products_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastProductValues()
{
    var lastProductValues = $('products_datagrid').getLastPagerValues();
    getProductParentData(lastProductValues);
    $('products_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateProductParentDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('products_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'products_datagrid';

    var result = storeSync.searchproductparents(status, search, limit);
    resetGrid('products_datagrid', result);
    if (resetCounter) {
        var size = storeSync.sizeofsearch(status, search);
        $('products_datagrid').rowsSize    = size;
        //$('products_datagrid').setCurrentPage(0);
        $('products_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

/**
 * Get previous values of pages
 */
function previousAttributeValues(use_for_select)
{
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
    var previousAttributeValues = $('attributes_datagrid').getPreviousPagerValues();
    getAttributeData(previousAttributeValues, use_for_select);
    $('attributes_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextAttributeValues(use_for_select)
{
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
    var nextAttributeValues = $('attributes_datagrid').getNextPagerValues();
    getAttributeData(nextAttributeValues, use_for_select);
    $('attributes_datagrid').nextPage();
}

/**
 * Get first values of pages
 */
function firstAttributeValues(use_for_select)
{
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
    var firstAttributeValues = $('attributes_datagrid').getFirstPagerValues();
    getAttributeData(firstAttributeValues, use_for_select);
    $('attributes_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastAttributeValues(use_for_select)
{
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
    var lastAttributeValues = $('attributes_datagrid').getLastPagerValues();
    getAttributeData(lastAttributeValues, use_for_select);
    $('attributes_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateAttributeDatagrid(status, search, limit, resetCounter, use_for_select)
{
	//showWorkingNotification();
    if (typeof(use_for_select) == "undefined") {
		use_for_select = false;
	}
    $('attributes_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'attributes_datagrid';

    var result = storeSync.searchattributes(search, status, limit, use_for_select);
    resetGrid('attributes_datagrid', result);
    if (resetCounter) {
        var size = storeSync.sizeofsearchattributes(status, search, use_for_select);
        $('attributes_datagrid').rowsSize    = size;
        //$('attributes_datagrid').setCurrentPage(0);
        $('attributes_datagrid').updatePageCounter();
    }
	if (use_for_select === true && $('attribute')) {
		setGridOnSelect('attributes_datagrid', 'attribute', true);	
	}
	//hideWorkingNotification();
}

/**
 * Get previous values of pages
 */
function previousAttributeTypeValues()
{
    var previousAttributeTypeValues = $('attribute_types_datagrid').getPreviousPagerValues();
    getAttributeTypeData(previousAttributeTypeValues);
    $('attribute_types_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextAttributeTypeValues()
{
    var nextAttributeTypeValues = $('attribute_types_datagrid').getNextPagerValues();
    getAttributeTypeData(nextAttributeTypeValues);
    $('attribute_types_datagrid').nextPage();
}

/**
 * Get first values of pages
 */
function firstAttributeTypeValues()
{
    var firstAttributeTypeValues = $('attribute_types_datagrid').getFirstPagerValues();
    getAttributeTypeData(firstAttributeTypeValues);
    $('attribute_types_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastAttributeTypeValues()
{
    var lastAttributeTypeValues = $('attribute_types_datagrid').getLastPagerValues();
    getAttributeTypeData(lastAttributeTypeValues);
    $('attribute_types_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateAttributeTypeDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('attribute_types_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'attribute_types_datagrid';

    var result = storeSync.searchattributetypes(status, search, limit);
    resetGrid('attribute_types_datagrid', result);
    if (resetCounter) {
        var size = storeSync.sizeofsearchattributetypes(status, search);
        $('attribute_types_datagrid').rowsSize    = size;
        //$('attribute_types_datagrid').setCurrentPage(0);
        $('attribute_types_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

/**
 * Get previous values of pages
 */
function previousSaleValues()
{
    var previousSaleValues = $('sales_datagrid').getPreviousPagerValues();
    getSaleData(previousSaleValues);
    $('sales_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextSaleValues()
{
    var nextSaleValues = $('sales_datagrid').getNextPagerValues();
    getSaleData(nextSaleValues);
    $('sales_datagrid').nextPage();
}

/**
 * Get first values of pages
 */
function firstSaleValues()
{
    var firstSaleValues = $('sales_datagrid').getFirstPagerValues();
    getSaleData(firstSaleValues);
    $('sales_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastSaleValues()
{
    var lastSaleValues = $('sales_datagrid').getLastPagerValues();
    getSaleData(lastSaleValues);
    $('sales_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateSaleDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('sales_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'sales_datagrid';

    var result = storeSync.searchsales(search, status, limit);
    resetGrid('sales_datagrid', result);
    if (resetCounter) {
        var size = storeSync.sizeofsearchsales(status, search);
        $('sales_datagrid').rowsSize    = size;
        //$('sales_datagrid').setCurrentPage(0);
        $('sales_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
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
 * Get first values of pages
 */
function firstBrandValues()
{
    var firstBrandValues = $('brands_datagrid').getFirstPagerValues();
    getBrandData(firstBrandValues);
    $('brands_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastBrandValues()
{
    var lastBrandValues = $('brands_datagrid').getLastPagerValues();
    getBrandData(lastBrandValues);
    $('brands_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateBrandDatagrid(status, search, limit, resetCounter)
{
	//showWorkingNotification();
    $('brands_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'brands_datagrid';

    var result = storeSync.searchbrands(search, status, limit);
    resetGrid('brands_datagrid', result);
    if (resetCounter) {
        var size = storeSync.sizeofsearchbrands(status, search);
        $('brands_datagrid').rowsSize    = size;
        //$('brands_datagrid').setCurrentPage(0);
        $('brands_datagrid').updatePageCounter();
    }
	//hideWorkingNotification();
}

/**
 * Search for messages
 */
function searchTemplates(gadgetscope)
{
    updateTemplatesDatagrid(gadgetscope, $('search_templates').value, 0, true);
}

/**
 * Get templates data
 */
function getTemplatesData(limit, gadgetscope)
{
    if (limit == undefined) {
        limit = $('templates_datagrid').getCurrentPage();
    }
    updateTemplatesDatagrid(
						gadgetscope, 
						$('search_templates').value,
                        limit,
						false);
}

/**
 * Get previous values of pages
 */
function previousTemplatesValues(gadgetscope)
{
    var previousTemplatesValues = $('templates_datagrid').getPreviousPagerValues();
    getTemplatesData(previousTemplatesValues, gadgetscope);
    $('templates_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextTemplatesValues(gadgetscope)
{
    var nextTemplatesValues = $('templates_datagrid').getNextPagerValues();
    getTemplatesData(nextTemplatesValues, gadgetscope);
    $('templates_datagrid').nextPage();
}

/**
 * Get first values of pages
 */
function firstTemplatesValues(gadgetscope)
{
    var firstTemplatesValues = $('templates_datagrid').getFirstPagerValues();
    getTemplatesData(firstTemplatesValues, gadgetscope);
    $('templates_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastTemplatesValues(gadgetscope)
{
    var lastTemplatesValues = $('templates_datagrid').getLastPagerValues();
    getTemplatesData(lastTemplatesValues, gadgetscope);
    $('templates_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updateTemplatesDatagrid(gadgetscope, search, limit, resetCounter)
{
    $('templates_datagrid').objectName = storeSync;
    JawsDataGrid.name = 'templates_datagrid';

    var result = storeSync.searchtemplates(gadgetscope, search, limit);
    resetGrid('templates_datagrid', result);
    if (resetCounter) {
        var size = storeSync.sizeofsearchtemplates(gadgetscope, search);
        $('templates_datagrid').rowsSize    = size;
        //$('templates_datagrid').setCurrentPage(0);
        $('templates_datagrid').updatePageCounter();
    }
	setGridOnSelect('templates_datagrid', 'template');	
}

/**
 * Set onclick for selected rows
 */
function setGridOnSelect(grid, target, multiple)
{
	if (typeof(multiple) == "undefined") {
		multiple = false;
	}
    var gridTable = $(grid);
	var gridBody = gridTable.body;
	var cboxes   = gridBody.getElementsByTagName('input');
	var length   = cboxes.length;
	var realName = gridTable.id + '_checkbox';
	var className, cvalues, c, i;
	for(i=0;i<length;i++) {
		//var input     = cboxes[i];
		className = cboxes[i].getAttribute('class');
		if (className == realName) {
			if ($(target)) {
				if (cboxes[i].value != '') {
					if (multiple === true) {
						cvalues = $(target).value.split(',');
						for (c=0;c<cvalues.length;c++) {
							if (cboxes[i].value == cvalues[c]) {
								cboxes[i].checked = true;
							}
						}
					} else {
						if (cboxes[i].value == $(target).value) {
							cboxes[i].checked = true;
						}
					}
				}
				cboxes[i].onclick = function() {
					selectGridRow(grid, target, this, multiple);
				};
			}
		}
	}
}

/**
 * Select a template row
 */
function selectGridRow(grid, target, el, multiple)
{
	if (typeof(multiple) == "undefined") {
		multiple = false;
	}
	if (el.checked == true) {	
		if (multiple === false) {
			var ogridTable = $(grid);
			var ogridBody = ogridTable.body;
			var ocboxes   = ogridBody.getElementsByTagName('input');
			var olength   = ocboxes.length;
			var orealName = ogridTable.id + '_checkbox';
			var oinput, oclassName;
			for(var n = 0; n < olength; n++) {
				oinput     = ocboxes[n];
				oclassName = oinput.getAttribute('class');
				if (oclassName == orealName) {
					oinput.checked = false;
				}
			}
		}
		el.checked = true;
		if ($(target)) {
			if (multiple === true) {
				$(target).value += ($(target).value == '' ? '' : ',')+el.value;
			} else {
				$(target).value = el.value;
			}
		}
	} else {
		el.checked = false;
		if ($(target)) {
			if (multiple === true) {
				var cvalues = $(target).value.split(',');
				for (var c=cvalues.length-1; c>=0; c--) {
					if (cvalues[c] === el.value) {
						cvalues.splice(c, 1);
					}
				}
				$(target).value = cvalues.join(',');
			} else {
				$(target).value = '';
			}
		}
	}
}

/**
 * Show the response but only text, nothing with datagrid.
 * FIXME!
 */
function showSimpleResponse(message)
{
    if (!storeAutoDraftDone) {
        var actioni   = document.forms[0].elements['action'].value;
        if (actioni == 'AddMap' && message[0]['css'] == 'notice-message') {
            //document.forms[0].elements['action'].value = 'SaveEditPage';
            document.forms[0].elements['id'].value     = message[0]['message']['id'];
            message[0]['message'] = message[0]['message']['message'];
        }
        storeAutoDraftDone = true;
    }
    showResponse(message);
}

function storeExecuteFunctionByName(functionName, context /*, args */) {
    var args = Array.prototype.slice.call(arguments, 2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }
    return context[func].apply(context, args);
}

var store = new storeadminajax(StoreCallback);
//store.serverErrorFunc = Jaws_Ajax_ServerError;

var storeSync = new storeadminajax();
//storeSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

var storeAutoDraftDone = false;
var storeFileCount = 0;
var storeNum = 0;
var storeW = 0;
var storePrevGadgetscope = null;
