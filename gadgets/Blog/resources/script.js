/**
 * Blog Javascript actions
 *
 * @category   Ajax
 * @package    Blog
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var BlogCallback = {

    deletecomment: function(response) {
        showResponse(response);
        if (response[0]['css'] == 'notice-message') {
            $('comments_datagrid').deleteItem();
            var limit = $('comments_datagrid').getCurrentPage();
            var formData = getDataOfLCForm();
            updateCommentsDatagrid(limit, formData['filter'],
                                   formData['search'], formData['status'],
                                   true);
        }
    },

    deletecomments: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('comments_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('comments_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('comments_datagrid'));
            var limit = $('comments_datagrid').getCurrentPage();
            var formData = getDataOfLCForm();
            updateCommentsDatagrid(limit, formData['filter'],
                                   formData['search'], formData['status'],
                                   true);
        } else {
            PiwiGrid.multiSelect($('comments_datagrid'));
        }
        showResponse(response);
    },

    deleteentries: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('posts_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('posts_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('posts_datagrid'));
            var limit = $('posts_datagrid').getCurrentPage();
            var formData = getDataOfLEForm();
            updatePostsDatagrid(formData['period'], formData['category'],
                                formData['status'], formData['search'], 0, true);
        } else {
            PiwiGrid.multiSelect($('posts_datagrid'));
        }
        showResponse(response);
    },

    changeentrystatus: function(response) {
        if (response[0]['css'] == 'notice-message') {
            PiwiGrid.multiSelect($('posts_datagrid'));
            resetLEForm();
            var formData = getDataOfLEForm();
            updatePostsDatagrid(formData['period'], formData['category'],
                                formData['status'], formData['search'], 0, true);
        } else {
            PiwiGrid.multiSelect($('posts_datagrid'));
        }
        showResponse(response);
    },

    deletetrackbacks: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('trackbacks_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('trackbacks_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('trackbacks_datagrid'));
            var limit = $('trackbacks_datagrid').getCurrentPage();
            var formData = getDataOfLTBForm();
            updateTrackbacksDatagrid(limit, formData['filter'],
                                   formData['search'], formData['status'],
                                   true);
        } else {
            PiwiGrid.multiSelect($('trackbacks_datagrid'));
        }
        showResponse(response);
    },

    markas: function(response) {
        if (response[0]['css'] == 'notice-message') {
            PiwiGrid.multiSelect($('comments_datagrid'));
            resetLCForm();
            var formData = getDataOfLCForm();
            updateCommentsDatagrid(0,
                                   formData['filter'],
                                   formData['search'],
                                   formData['status'],
                                   true);
        } else {
            PiwiGrid.multiSelect($('comments_datagrid'));
        }
        showResponse(response);
    },

    trackbackmarkas: function(response) {
        if (response[0]['css'] == 'notice-message') {
            PiwiGrid.multiSelect($('trackbacks_datagrid'));
            resetLTBForm();
            var formData = getDataOfLTBForm();
            updateTrackbacksDatagrid(0,
                                   formData['filter'],
                                   formData['search'],
                                   formData['status'],
                                   true);
        } else {
            PiwiGrid.multiSelect($('trackbacks_datagrid'));
        }
        showResponse(response);
    },

    savesettings: function(response) {
        showResponse(response);
    },

    getcategoryform: function(response) {
        fillCatInfoForm(response);
    },

    getcategorycombo: function(response) {
        updateCategoryCombo();
    },

    addcategory: function(response) {
        showResponse(response);
        if (response[0]['css'] == 'notice-message') {
            resetCategoryForms();
        }
    },

    updatecategory: function(response) {
        showResponse(response);
        if (response[0]['css'] == 'notice-message') {
            resetCategoryForms();
        }
    },

    deletecategory: function(response) {
        showResponse(response);
        if (response[0]['css'] == 'notice-message') {
            resetCategoryForms();
        }
    },

    deletepost: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    }, 

    editelementaction: function(response) {
        showResponse(response);
    },
	
    addgadget: function(response) {
        if (response['success']) {
            //$('layout_main').appendChild(document.createTextNode(response['elementbox']));
            // Fragile!, it must be equal to admin_CustomPage_view template
			
			var exists = false;
			if ($('syntactsCategory_' + response['id'])) {
				exists = true;
				//$('syntactsCategory_' + response['id']).parentNode.removeChild($('syntactsCategory_' + response['id']));
            }
			
			if ($('syntactsCategories_no_items')) {
				$('syntactsCategories_no_items').style.display = 'none';
			}
			if ($('syntactsCategories_head')) {
				$('syntactsCategories_head').style.display = 'block';
				$('syntactsCategories_head').style.width = '100%';
				$('syntactsCategories_head').width = '100%';
			}

			var tbl = $('syntactsCategories');
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
				aea.onclick = function() {
					eval(response['eaonclick2']);
				}
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
			dItemGadget.appendChild(pea);

            var dItemEdit = document.createElement('td');
            dItem.appendChild(dItemEdit);
            dItemEdit.setAttribute('class', 'syntacts-form-row');
            dItemEdit.setAttribute('className', 'syntacts-form-row');
            var aedit = document.createElement('a');
            aedit.setAttribute('href', 'javascript:void(0);') 
            aedit.onclick = function() {
                eval(response['eaonclick']);
            }
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
            adel.setAttribute('href', 'javascript:void(0);') 
            adel.onclick = function() {
                eval(response['delete']);
            }
            /*
			imgdel = document.createElement('img');
              imgdel.setAttribute('class', 'syntacts-img-button');
              imgdel.setAttribute('className', 'syntacts-img-button');
              imgdel.setAttribute('src', "images/ICON_delete2.gif");
			*/
			adel.appendChild(document.createTextNode('DELETE'));
            dItemDelete.appendChild(adel);
					  
			//$('syntactsCategories').childNodes[1].appendChild(dItem);
			tbl.setAttribute('width', '100%');
			tbod[0].style.display = 'block';
			if (exists === false) {
				tbod[0].appendChild(dItem);
			} else {
				tbod[0].insertBefore(dItem,$('syntactsCategory_' + response['id']));
				$('syntactsCategory_' + response['id']).parentNode.removeChild($('syntactsCategory_' + response['id']));
			}
			dItem.setAttribute('id', 'syntactsCategory_' + response['id']);
			Effect.Appear(dItem.id, {duration:1});
			var tableDnD = new BlogTableDnD();
			tableDnD.init(tbl);             
			//items['main']['item_' + response['id']] = true; 
			//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
        }
        showResponse(response['message']);
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
				if (parent.parent.parent.$('div_' + response['id'])) {
					exists = true;
					//$('syntactsCategory_' + response['id']).parentNode.removeChild($('syntactsCategory_' + response['id']));
				}
				if (parent.parent.parent.$(response['gadget'] + '_puzzle')) {
					var dItem = parent.parent.parent.$(response['gadget'] + '_puzzle');
				
					if (parent.parent.parent.$(response['gadget'] + '_puzzle_no_items')) {
						parent.parent.parent.$(response['gadget'] + '_puzzle_no_items').style.display = 'none';
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
						dItem.insertBefore(dItemGadget,parent.parent.parent.$('div_' + response['id']));
						parent.parent.parent.$('div_' + response['id']).parentNode.removeChild(parent.parent.parent.$('div_' + response['id']));
					}
					dItemGadget.setAttribute('id', 'div_' + response['id']);
					parent.parent.parent.Effect.Appear(dItemGadget.id, {duration:1});
					parent.parent.parent.Sortable.create(response['gadget'] + '_puzzle', {
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
				parent.parent.parent.saveUpdate(response['id'], comment_html, '', 0, response['sharing'], parent.$('syndication').checked, 'Blog', true, false, response['eaurl'], false);
			} else {
				//$('layout_main').appendChild(document.createTextNode(response['elementbox']));
				// Fragile!, it must be equal to admin_CustomPage_view template
				
				if (parent.parent.parent.$('syntactsCategory_' + response['id'])) {
					exists = true;
					//$('syntactsCategory_' + response['id']).parentNode.removeChild($('syntactsCategory_' + response['id']));
				}
				
				if (parent.parent.parent.$('syntactsCategories_no_items')) {
					parent.parent.parent.$('syntactsCategories_no_items').style.display = 'none';
				}
				if (parent.parent.parent.$('syntactsCategories_head')) {
					parent.parent.parent.$('syntactsCategories_head').style.display = 'block';
					parent.parent.parent.$('syntactsCategories_head').style.width = '100%';
					parent.parent.parent.$('syntactsCategories_head').width = '100%';
				}

				var tbl = parent.parent.parent.$('syntactsCategories');
				var tbod = tbl.getElementsByTagName('tbody');
				var trs = tbl.getElementsByTagName('tr');
				
				var dItem = document.createElement('tr');
				dItem.setAttribute('id', 'syntactsCategory_' + response['id'] + '_temp');
				//dItem.setAttribute('title', response['tactiondesc']);
				dItem.setAttribute('width', '100%');
				dItem.style.cursor = 'move';
				dItem.style.backgroundColor = "#FFEBA0";

				var dItemIcon = document.createElement('td');
				dItemIcon.setAttribute('class', 'syntacts-form-row');
				dItemIcon.setAttribute('className', 'syntacts-form-row');
				var imgIcon = document.createElement('img');
				imgIcon.setAttribute('alt', 'icon');
				imgIcon.setAttribute('src', response['icon']);
				dItemIcon.appendChild(imgIcon);
				dItem.appendChild(dItemIcon);
				
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
				dItemGadget.appendChild(pea);

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
					tbod[0].insertBefore(dItem,parent.parent.parent.$('syntactsCategory_' + response['id']));
					parent.parent.parent.$('syntactsCategory_' + response['id']).parentNode.removeChild(parent.parent.parent.$('syntactsCategory_' + response['id']));
				}
				dItem.setAttribute('id', 'syntactsCategory_' + response['id']);
				parent.parent.parent.Effect.Appear(parent.parent.parent.$(dItem), {duration:1});
				var tableDnD = new parent.parent.parent.BlogTableDnD();
				tableDnD.init(tbl);             
				//items['main']['item_' + response['id']] = true; 
				//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
			}
		}
		//parent.parent.parent.GB_hide();
		var wm = parent.parent.parent.UI.defaultWM;
		var windows = wm.windows();
		windows.first().destroy();

        showResponse(response['message']);
    },
	
    autodraft: function(response) {
        showSimpleResponse(response);
    }
}

function addGadget(url, title)
{
	w = new UI.URLWindow({
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
	//w.center();
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	//w.setZIndex(2147483647);
	w.show(true).focus();
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function insertGadget(url, title)
{
	w = new UI.URLWindow({
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
	w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
	//w.setZIndex(2147483647);
	w.show(true).focus();
	w.setZIndex(2147483647);
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
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
            var response = blogSync.deletepost(id);
            if (response[0]['css'] == 'notice-message') {
				//oldChild = $('syntactsCategory_'+cid);
				//parent.removeChild(oldChild);
				//$('syntactsCategory_'+id).style.display = 'none';
				var tbl = $('syntactsCategory_'+id).parentNode.parentNode;
				var trs = tbl.getElementsByTagName('tr');
				$('syntactsCategory_'+id).parentNode.removeChild($('syntactsCategory_'+id));
				if (trs.length < 3) {
					trs[0].style.display = 'none';
					trs[1].style.display = 'block';
					var tableDnD = new BlogTableDnD();
					tableDnD.init(tbl);             
				}
				//stopAction();
           }
	        showResponse(response);
    }
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
            currenttable.dragObject.getElementsByTagName('td')[1].setAttribute('width', '93%');
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
			var nodrag = rows[i].getAttribute("NoDrag")
			if (nodrag == null || nodrag == "undefined") { //There is no NoDnD attribute on rows I want to drag
				//rows[i].setAttribute("class") = (rows[i].className.indexOf('syntacts-form-row-draggable') > -1 ? '' : ' syntacts-form-row-draggable');
				//rows[i].setAttribute("className") = (rows[i].className.indexOf('syntacts-form-row-draggable') > -1 ? '' : ' syntacts-form-row-draggable');
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
	        var currentId = parseInt(rows[i].id.substr((rows[i].id.indexOf("_")+1),rows[i].id.length));
			if (!isNaN(currentId) && currentId < 1000000000) {
				idsStr += currentId;
				newsortStr += i;
				if (i<(rows.length-1)) {
					idsStr += ',';
					newsortStr += ',';
				}	
			}
	    }
		if (this.oldidsStr != idsStr) {
			this.oldidsStr = idsStr;
			sortBlogItem(idsStr, newsortStr);
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
            if (target.tagName == 'INPUT' || target.tagName == 'A' || target.tagName == 'SELECT' || target.tagName == 'TEXTAREA') return true;
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
function sortBlogItem(id, newsort)
{
	blog.sortitem(id, newsort);
}

function addGadgetToLayout(gadget, action, linkid, section_id)
{   
    // Ugly hack to add gadget from the greybox
	fun = 'blog.addgadget(\'\',\'' + gadget + '\',\'' + action + '\',\'' + linkid + '\',\'' + section_id + '\')';
	setTimeout(fun, 0);
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

function insertGadgetToLayout(gadget, action, where)
{   
    // Ugly hack to add gadget from the greybox
	if (gadget && action) {
		document.forms['GLOBALform'].elements[where].value = 'GADGET:'+gadget+'_ACTION:'+action;
	} else {
		document.forms['GLOBALform'].elements[where].value = '';
	}
	document.forms['GLOBALform'].elements[where].focus;
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

function getSelectedAction()
{
    var radioObj = document.forms['form1'].elements['action'];
    if(!radioObj)
        return "";
    var radioLength = radioObj.length;
    if(radioLength == undefined)
        if(radioObj.checked)
            return radioObj.value;
        else
            return "";
    for(var i = 0; i < radioLength; i++) {
        if(radioObj[i]) {
			if(radioObj[i].checked) {
				return radioObj[i].value;
			}
		}
    }
    return "";
}

function editElementAction(url, gadget, action)
{
	if (gadget && action) {
		var response = blogSync.getgadgeteditpage(gadget, action);
		if (response['message'][0]['css'] == 'notice-message' && response['url']) {
			window.open(response['url']);
        } else {
			showResponse(response['message']);
		}
	} else {
		w = new UI.URLWindow({
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
		w.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2));
		//w.setZIndex(2147483647);
		w.show(true).focus();
		w.setZIndex(2147483647);
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
		Event.observe(window, "resize", function() {
			w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
		});
		//GB_showCenter('Edit Content', url, 510, 950);
	}
}

function saveElementAction(itemId, action, url, gadget) {
    // Ugly hack to update from the greybox
	fun = 'blog.editelementaction(' + itemId + ',\'' + action['name'] + '\')';
	setTimeout(fun, 0);
	
	//alink = $('ea' + itemId);
	//alink.setAttribute('title', action['desc']);
	//alink.innerHTML = action['title'];
	//$('gadget-ea' + itemId).innerHTML = '<b>'+response['tname']+'</b><p>'+alink+': '+response['tactiondesc']+'</p>';

	dItemGadget = $('gadget-ea' + itemId);
	dItemGadget.innerHTML = '';
	
	bea = document.createElement('b');
	bea.appendChild(document.createTextNode(gadget));
	dItemGadget.appendChild(bea);

	brea = document.createElement('br');
	dItemGadget.appendChild(brea);

	aea = document.createElement('a');
	aea.setAttribute('href', 'javascript:void(0);');
	aea.onclick = function() {
		eval(url);
	}
	aea.setAttribute('id', 'ea'+itemId);
	aea.setAttribute('name', 'ea'+itemId);
	aea.setAttribute('title', action['desc']);
	aea.appendChild(document.createTextNode(action['title']));

	pea = document.createElement('p');
	pea.appendChild(aea);
	pea.appendChild(document.createTextNode(': '+action['desc']));
	dItemGadget.appendChild(pea);

	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
	//GB_hide();
}

/**
 * Saves Quick Add form : function
 */
function saveQuickAdd(addtype, method, callback, sharing)
{
	if (!addtype) {
		addtype = 'Blog';
	}
	if (!method) {
		method = 'AddPost';
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
	blog.savequickadd(addtype, method, params, callback);
	/*
	parent.parent.parent.GB_hide();
	//return response;
	*/
}

var ifrm = new Array();
function getQuickAddForm(g, method, id, linkid, callback)
{
	if (!callback) {
		callback = '';
	}
	// Remove all actions 
    while ($('actions-list').firstChild) {
        $('actions-list').removeChild($('actions-list').firstChild);
    };

    //$(g).setAttribute('class', 'gadget-item gadget-selected'); 
    //$(g).setAttribute('className', 'gadget-item gadget-selected'); 
	$('quick-form').style.display = '';
	if ($('add')) {
		$('add').style.display = 'none';
	}
	if ($('quick_add_'+prevForm)) {
		$('quick_add_'+prevForm).style.display = 'none';		
	}
	if ($('quick_add_'+g)) {
		$('quick_add_'+g).style.display = 'block';		
	} else {
		ifrm[g] = document.createElement("IFRAME");
		ifrm[g].setAttribute('id', 'quick_add_'+g);
		ifrm[g].setAttribute('name', 'quick_add_'+g);
		ifrm[g].setAttribute("src", "admin.php?gadget="+g+"&action=GetQuickAddForm&method="+method+"&id="+id+"&linkid="+linkid);
		ifrm[g].style.width = "100%";
		ifrm[g].style.height = "5000px";
		ifrm[g].style.borderWidth = 0+"px";
		ifrm[g].setAttribute('frameborder', '0');
		ifrm[g].setAttribute('scrolling', 'no');
		ifrm[g].setAttribute('allowtransparency', 'true');
		ifrm[g].frameBorder = "0";
		ifrm[g].scrolling = "no";
		$('quick-form').appendChild(ifrm[g]);
	}
	$('save').onclick = function() {
		window.frames['quick_add_'+g].saveQuickAdd('Blog', method, callback);
	}
	$('save').style.display = '';
    prevForm = g;
}

function selectGadget(g, method, id, linkid, callback)
{
    $('gadget').value = g;

	// Remove all actions 
    while ($('actions-list').firstChild) {
        $('actions-list').removeChild($('actions-list').firstChild);
    };

    if ($(prevGadget)) {
        $(prevGadget).setAttribute('class', 'gadget-item'); 
        $(prevGadget).setAttribute('className', 'gadget-item'); 
    }
    if ($(g)) {
		$(g).setAttribute('class', 'gadget-item gadget-selected'); 
		$(g).setAttribute('className', 'gadget-item gadget-selected'); 
	}
	prevLinkID = linkid;
	$('quick-form').style.display = 'none';
	if($('quick_add_cancel')) {
		$('quick_add_cancel').parentNode.removeChild($('quick_add_cancel'));
	}
    if ($('add')) {
		$('add').style.display = (method.substring(0, 6) == 'Update' || method.substring(0, 4) == 'Edit' ? 'none' : '');
	}
    if ($('save')) {
		$('save').style.display = (method.substring(0, 6) == 'Update' || method.substring(0, 4) == 'Edit' ? '' : 'none');
    }
	if (g == 'Text') {
		getQuickAddForm('Blog', method, id, linkid, callback);
		//$('post-form').style.display = '';
	} else {
		var forms = blogSync.getquickaddforms(g);
		$('post-form').style.display = 'none';
		var actions = blogSync.getgadgetactions(g);
		var first = null;
		show_link = false;
		link_shown = false; 
		actions.each (function(item, arrayIndex) {
			if (item['action'] && item['desc'] && item['name']) {
				if (item['add'] === true) {
					show_link = true;
				}
				if (first == null) {
					first = 'action_' + item['action'];
					if (show_link === true) {
						link_shown = true; 
						brlink = document.createElement('br');
						$('actions-list').appendChild(brlink);
						plink = document.createElement('p');
						forms.each (function(form, arrayIndex) {
							if (form['method'] && form['name']) {
								nlink = document.createElement('a');
								nlink.setAttribute('href', 'javascript:void(0);');
								nlink.onclick = function() {
									getQuickAddForm(g, form['method'], id, linkid, callback);
								}
								nlink.appendChild(document.createTextNode('Add '+form['name']));
								plink.appendChild(document.createTextNode(String.fromCharCode(32,32,32)));
								plink.appendChild(nlink);
							}
						});
						brlink2 = document.createElement('br');
						plink.appendChild(brlink2);
						plink.setAttribute('align', 'right');
						plink.style.paddingRight = '10px';
						plink.style.paddingBottom = '10px';
						plink.style.textAlign = 'right';
						$('actions-list').appendChild(plink);
					}
				}
				li = document.createElement('li');
				r = createNamedElement('input', 'action');
				//r = document.createElement('input');
				r.setAttribute('type', 'radio');
				//r.setAttribute('name', 'action');
				r.setAttribute('value', item['action']);
				r.setAttribute('id', 'action_' + item['action']);
				label = document.createElement('label');
				label.setAttribute('for', 'action_' + item['action']);
				label.innerHTML = item['name'] + '<span>' + item['desc'] + '</span>';
				li.appendChild(r); 
				li.appendChild(label); 
				$('actions-list').appendChild(li);
			} else {
				if (item['add'] === true) {
					show_link = true;
				}
			}
		});
		if (first == null) {
			li = document.createElement('li');
			li.setAttribute('class', 'action-msg');
			li.setAttribute('className', 'action-msg');
			li.appendChild(document.createTextNode(noActionsMsg));
			$('actions-list').appendChild(li);
			brlink = document.createElement('br');
			$('actions-list').appendChild(brlink);
			if (link_shown === false && show_link === true) {
				brlink = document.createElement('br');
				$('actions-list').appendChild(brlink);
				plink = document.createElement('p');
				forms.each (function(form, arrayIndex) {
					if (form['method'] && form['name']) {
						nlink = document.createElement('a');
						nlink.setAttribute('href', 'javascript:void(0);');
						nlink.onclick = function() {
							getQuickAddForm(g, form['method'], id, linkid, callback);
						}
						nlink.appendChild(document.createTextNode('Add '+form['name']));
						plink.appendChild(document.createTextNode(String.fromCharCode(32,32,32)));
						plink.appendChild(nlink);
					}
				});
				brlink2 = document.createElement('br');
				plink.appendChild(brlink2);
				plink.setAttribute('align', 'right');
				plink.style.paddingRight = '10px';
				plink.style.paddingBottom = '10px';
				plink.style.textAlign = 'right';
				$('actions-list').appendChild(plink);
			}
		} else {
			$(first).checked = true;
		}
	}
    prevGadget = g;
}

/**
 * Add a File directly to a Post : function
 */
function addFileToPost(gadget, table, method, syntactsCategory, linkid, num, width, height, bgc, focus, base_url)
{
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
}

function hideGB()
{   
	var wm = UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
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
 * Reset ListEntries form
 */
function resetLEForm()
{
    var form = document.forms['ListEntries'];
    form.elements['show'].value     = '';
    form.elements['category'].value = '';
    form.elements['status'].value   = '';
    form.elements['search'].value   = '';
}

/**
 * Reset ListComments form
 */
function resetLCForm()
{
    var form = document.forms['ListComments'];
    form.elements['filterby'].value = '';
    form.elements['filter'].value   = '';
    form.elements['status'].value   = 'approved';
}

/**
 * Reset ListTrackbacks form
 */
function resetLTBForm()
{
    var form = document.forms['ListTrackbacks'];
    form.elements['filterby'].value = '';
    form.elements['filter'].value   = '';
    form.elements['status'].value   = 'various';
}

/**
 * Get data of the form ListEntries form
 */
function getDataOfLEForm()
{
    var form = document.forms['ListEntries'];

    var data = new Array();

    data['period']   = form.elements['show'].value;
    data['category'] = form.elements['category'].value;
    data['status']   = form.elements['status'].value;
    data['search']   = form.elements['search'].value;

    return data;
}

/**
 * Get data of the form ListComments form
 */
function getDataOfLCForm()
{
    var form = document.forms['ListComments'];

    var data = new Array();

    data['filter']   = form.elements['filterby'].value;
    data['search']   = form.elements['filter'].value;
    data['status']   = form.elements['status'].value;

    return data;
}

/**
 * Get data of the form ListTrackbacks form
 */
function getDataOfLTBForm()
{
    var form = document.forms['ListTrackbacks'];

    var data = new Array();

    data['filter']   = form.elements['filterby'].value;
    data['search']   = form.elements['filter'].value;
    data['status']   = form.elements['status'].value;

    return data;
}

/**
 * Prepare the preview
 */
function parseText(form)
{
    var title   = form.elements['title'].value;
    //var content = getEditorValue('text_block');
    var content = form.elements['text_block'].value;
    content = blogSync.parsetext(content);

    var preview = document.getElementById('preview');
    preview.style.display = 'block';

    var titlePreview   = document.getElementById('previewTitle');
    var contentPreview = document.getElementById('previewContent');

    titlePreview.innerHTML   = title;
    contentPreview.innerHTML = content;
}

/**
 * Delete a comment
 */
function deleteComment(id)
{
    blog.deletecomment(id);
}

/**
 * search for a post
 */
function searchPost()
{
    var formData = getDataOfLEForm();
    updatePostsDatagrid(formData['period'], formData['category'],
                        formData['status'], formData['search'], 0, true);

    return false;
}

/**
 * search for a comment
 */
function searchComment()
{
    var formData = getDataOfLCForm();
    updateCommentsDatagrid(0, formData['filter'], formData['search'], formData['status'], true);
    return false;
}

/**
 * search for a trackback
 */
function searchTrackback()
{
    var formData = getDataOfLTBForm();
    updateTrackbacksDatagrid(0, formData['filter'], formData['search'], formData['status'], true);
    return false;
}

/**
 * Update post datagrid
 */
function updatePostsDatagrid(period, cat, status, search, limit, resetCounter)
{
    var result = blogSync.searchposts(period, cat, status, search, limit);
    resetGrid('posts_datagrid', result);
    if (resetCounter) {
        var size = blogSync.sizeofsearch(period, cat, status, search);
        $('posts_datagrid').rowsSize    = size;
        $('posts_datagrid').setCurrentPage(0);
        $('posts_datagrid').updatePageCounter();
    }
}

/**
 * Get posts data
 */
function getData(limit)
{
    switch($('action').value) {
    case 'ListEntries':
        if (limit == undefined) {
            limit = $('posts_datagrid').getCurrentPage();
        }
        var formData = getDataOfLEForm();
        updatePostsDatagrid(formData['period'], formData['category'],
                            formData['status'], formData['search'],
                            limit, false);
        break;
    case 'ManageComments':
        if (limit == undefined) {
            limit = $('comments_datagrid').getCurrentPage();
        }
        var formData = getDataOfLCForm();
        updateCommentsDatagrid(limit, formData['filter'],
                               formData['search'], formData['status'],
                               false);
        break;
    case 'ManageTrackbacks':
        if (limit == undefined) {
            limit = $('trackbacks_datagrid').getCurrentPage();
        }
        var formData = getDataOfLTBForm();
        updateTrackbacksDatagrid(limit, formData['filter'],
                                 formData['search'], formData['status'],
                                 false);
        break;
    }
}

/**
 * Get previous values of posts or comments
 */
function previousValues()
{
    switch($('action').value) {
    case 'ListEntries':
        var previousValues = $('posts_datagrid').getPreviousPagerValues();
        getData(previousValues);
        $('posts_datagrid').previousPage();
        break;
    case 'ManageComments':
        var previousValues = $('comments_datagrid').getPreviousPagerValues();
        getData(previousValues);
        $('comments_datagrid').previousPage();
        break;
    case 'ManageTrackbacks':
        var previousValues = $('trackbacks_datagrid').getPreviousPagerValues();
        getData(previousValues);
        $('trackbacks_datagrid').previousPage();
        break;
    }
}

/**
 * Get next values of posts or comments
 */
function nextValues()
{
    switch($('action').value) {
    case 'ListEntries':
        var nextValues = $('posts_datagrid').getNextPagerValues();
        getData(nextValues);
        $('posts_datagrid').nextPage();
        break;
    case 'ManageComments':
        var nextValues = $('comments_datagrid').getNextPagerValues();
        getData(nextValues);
        $('comments_datagrid').nextPage();
        break;
    case 'ManageTrackbacks':
        var nextValues = $('trackbacks_datagrid').getNextPagerValues();
        getData(nextValues);
        $('trackbacks_datagrid').nextPage();
        break;
    }
}

/**
 * Update comments datagrid
 */
function updateCommentsDatagrid(limit, filter, search, status, resetCounter)
{
    result = blogSync.searchcomments(limit, filter, search, status);
    resetGrid('comments_datagrid', result);
    if (resetCounter) {
        var size = blogSync.sizeofcommentssearch(filter, search, status);
        $('comments_datagrid').rowsSize    = size;
        $('comments_datagrid').setCurrentPage(0);
        $('comments_datagrid').updatePageCounter();
    }
}

/**
 * Update trackbacks datagrid
 */
function updateTrackbacksDatagrid(limit, filter, search, status, resetCounter)
{
    result = blogSync.searchtrackbacks(limit, filter, search, status);
    resetGrid('trackbacks_datagrid', result);
    if (resetCounter) {
        var size = blogSync.sizeoftrackbackssearch(filter, search, status);
        $('trackbacks_datagrid').rowsSize    = size;
        $('trackbacks_datagrid').setCurrentPage(0);
        $('trackbacks_datagrid').updatePageCounter();
    }
}

/**
 * Delete comment
 */
function commentDelete(row_id)
{
    var confirmation = confirm(deleteConfirm);
    if (confirmation) {
        blog.deletecomments(row_id);
    }
}

/**
 * Delete trackback
 */
function trackbackDelete(row_id)
{
    var confirmation = confirm(deleteConfirm);
    if (confirmation) {
        blog.deletetrackbacks(row_id);
    }
}

/**
 * Executes an action on comments
 */
function commentDGAction(combo)
{
    var rows = $('comments_datagrid').getSelectedRows();
    var selectedRows = false;
    if (rows.length > 0) {
        selectedRows = true;
    }

     if (combo.value == 'delete') {
        if (selectedRows) {
            var confirmation = confirm(deleteConfirm);
            if (confirmation) {
                blog.deletecomments(rows);
            }
        }
    } else if (combo.value != '') {
        if (selectedRows) {
            blog.markas(rows, combo.value);
        }
    }
}

/**
 * Executes an action on trackbacks
 */
function trackbackDGAction(combo)
{
    var rows = $('trackbacks_datagrid').getSelectedRows();
    var selectedRows = false;
    if (rows.length > 0) {
        selectedRows = true;
    }

     if (combo.value == 'delete') {
        if (selectedRows) {
            var confirmation = confirm(deleteConfirm);
            if (confirmation) {
                blog.deletetrackbacks(rows);
            }
        }
    } else if (combo.value != '') {
        if (selectedRows) {
            blog.trackbackmarkas(rows, combo.value);
        }
    }
}

/**
 * Executes an action on trackbacks
 */
function entryDGAction(combo)
{
    var rows = $('posts_datagrid').getSelectedRows();
    var selectedRows = false;
    if (rows.length > 0) {
        selectedRows = true;
    }

     if (combo.value == 'delete') {
        if (selectedRows) {
            var confirmation = confirm(deleteConfirm);
            if (confirmation) {
                blog.deleteentries(rows);
            }
        }
    } else if (combo.value != '') {
        if (selectedRows) {
            blog.changeentrystatus(rows, combo.value);
        }
    }
}

/**
 * Update the blog settings
 */
function updateSettings(form)
{
    var defaultView      = form.elements['default_view'].value;
    var lastEntries      = form.elements['last_entries_limit'].value;
    var popularLimit     = form.elements['popular_limit'].value;
    var lastComments     = form.elements['last_comments_limit'].value;
    var recentComments   = form.elements['last_recentcomments_limit'].value;
    var defaultCat       = form.elements['default_category'].value;
    var xmlLimit         = form.elements['xml_limit'].value;
    var comments         = form.elements['comments'].value;
    var comment_status   = form.elements['comment_status'].value;
    var trackback        = form.elements['trackback'].value;
    var trackback_status = form.elements['trackback_status'].value;
    var pingback         = form.elements['pingback'].value;

    blog.savesettings(defaultView, lastEntries, popularLimit, lastComments, recentComments, defaultCat, 
                      xmlLimit, comments, comment_status, trackback, trackback_status,
                      pingback);
}

/**
 * Edit the category
 */
function editCategory(id)
{
    blog.getcategoryform('editcategory', id);
}

/**
 * Reset the category Info Form values
 */
function resetCategoryForm()
{
    blog.getcategoryform('new', 0);
    $('category_id').selectedIndex = -1;
}

/**
 * Update the category combo (the big one)
 */
function updateCategoryCombo(content)
{
    var form   = document.getElementById('categoriesComboTable');
    form.innerHTML = content;
}

/**
 * Get the big combo
 */
function resetCategoryForms()
{
    var categoryCombo = blogSync.getcategorycombo();
    updateCategoryCombo(categoryCombo);

    var catInfo = document.getElementById('catinfoform');
    catInfo.innerHTML = blogSync.getcategoryform('new', 0);
}

/**
 * Save the info of a category, updating or adding.
 */
function saveCategory(form)
{
    action = form.elements['action'].value;

    if (action == 'AddCategory') {
        blog.addcategory(form.elements['name'].value,
                         form.elements['description'].value,
                         form.elements['fast_url'].value);
    } else {
        blog.updatecategory(form.elements['catid'].value,
                            form.elements['name'].value,
                            form.elements['description'].value,
                            form.elements['fast_url'].value);
    }
}

/**
 * Fill the Category Info Form
 */
function fillCatInfoForm(content)
{
    var catInfo = document.getElementById('catinfoform');
    catInfo.innerHTML = content;
}

/**
 * Delete category
 */
function deleteCategory(form)
{
    var id = form.elements['catid'].value;
    blog.deletecategory(id);
}

/**
 * Create a new category
 */
function newCategory()
{
    resetCategoryForm();
}

/**
 * Just the mother function that will make sure that auto drafting is running
 * and is being run every ~ 120 seconds (2 minutes).
 *
 * @see AutoDraft();
 */
function startAutoDrafting()
{
    var title          = $('title').value;
    var fasturl        = $('fasturl').value;
    var allow_comments = $('allow_comments').checked;
    var trackbacks     = $('trackback_to').value;
    var published      = $('published').value;
    var summary        = getEditorValue('summary_block');
    //var content        = getEditorValue('text_block');
    var content        = $('text_block').value;

    if (jawsTrim(title) != '' && (jawsTrim(summary) != '' || jawsTrim(content) != '')) {
        var timestamp = null;
        if ($('edit_timestamp').checked) {
            timestamp = $('pubdate').value;
        }

        var categoriesNode = $('categories').getElementsByTagName('input');
        var categories     = new Object();
        var catCounter     = 0;
        for(var i = 0; i < categoriesNode.length; i++) {
            if (categoriesNode[i].checked) {
                categories[catCounter] = categoriesNode[i].value;
                catCounter++;
            }
        }

        var id = '';
        var actioni = $('action').value;

        switch (actioni) {
            case 'SaveNewEntry':
                id = 'NEW';
                break;
            case 'SaveEditEntry':
                id = $('id').value;
                break;
        }

        blog.autodraft(id, categories, title, summary, content, fasturl, allow_comments, trackbacks, published, timestamp);
    }
    setTimeout('startAutoDrafting();', 120000);
}

/**
 * Auto Draft response
 */
function showSimpleResponse(message)
{
    if (!autoDraftDone) {
        var actioni   = $('action').value;
        if (actioni == 'SaveNewEntry' && message[0]['css'] == 'notice-message') {
            $('published').value = '0';
            $('id').value        = message[0]['message']['id'];
            $('action').value    = 'SaveEditEntry';
            message[0]['message'] = message[0]['message']['message'];
        }
        autoDraftDone = true;
    }
    showResponse(message);
}

/**
 * Toggle advanced
 */
function toggleAdvanced(checked)
{
    if (checked) {
        $('advanced').style.display = 'block';
    } else {
        $('advanced').style.display = 'none';
    }
}

/**
 * Toggle update publication time
 */
function toggleUpdate(checked)
{
    if (checked) {
        $('pubdate').disabled = false;
        $('pubdate_button').disabled = false;
    } else {
        $('pubdate').disabled = true;
        $('pubdate_button').disabled = true;
    }
}

var blog = new blogadminajax(BlogCallback);
/*
blog.serverErrorFunc = Jaws_Ajax_ServerError;
blog.onInit = showWorkingNotification;
blog.onComplete = hideWorkingNotification;
*/
var blogSync = new blogadminajax();
/*
blogSync.serverErrorFunc = Jaws_Ajax_ServerError;
blogSync.onInit = showWorkingNotification;
blogSync.onComplete = hideWorkingNotification;
*/
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

var firstFetch = true;
var autoDraftDone = false;
var fileCount = 0;
var num = 0;
var prevGadget = '';
var prevForm = '';
var prevLinkID = null;
var show_link = false;
var link_shown = false;
var w;
