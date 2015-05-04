/**
 * CustomPage Javascript actions
 *
 * @category   Ajax
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

/**
 * Use async mode, create Callback
 */
var CustomPageCallback = { 
    setregistrykey: function(response) {
        showResponse(response);
    }, 

    deletepost: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    }, 

    deletepage: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getCustomPageData();
        }
        showResponse(response);
    }, 
    
    deletetemplate: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getTemplatesData();
        }
        showResponse(response);
    }, 
    
    massivedelete: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('pages_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('pages_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('pages_datagrid'));
            getCustomPageData();
        }
        showResponse(response);      
    },
    
    autodraft: function(response) {
        customPageShowSimpleResponse(response);
    },

    editelementaction: function(response) {
        showResponse(response);
    },
                
	addembedsite: function(response) {
        toggleNo('embedInfo');
		showResponse(response);
    },
                
	sortitem: function(response) {
        if (response['success']) {
            //$('layout_main').appendChild(document.createTextNode(response['elementbox']));
        }
        showResponse(response['message']);
    },
	
	sortitemsplash: function(response) {
        if (response['success']) {
			//$('layout_main').appendChild(document.createTextNode(response['elementbox']));
        }
        showResponse(response['message']);
    }, 
	
	savequickadd: function(response) {
		var exists = false;
		if (response['success']) {
			if (response['addtype'] == 'Scrumy') {
				/*
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
							//info.update('You\'ve made ' + (++moves) + ' move' + (moves>1 ? 's' : ''));
							//if (Sortable.sequence('puzzle').join('')=='123456789') {
							//	info.update('You\'ve solved the puzzle in ' + moves + ' moves!').morph('congrats');
							//}
						}
					});
					//items['main']['item_' + response['id']] = true; 
					//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
					var wm = window.top.UI.defaultWM;
					var windows = wm.windows();
					windows.first().destroy();
					//GB_hide();
				} else {
					alert('Container section not found.');
				}
				*/
			} else if (response['addtype'] == 'Comment') {
				var comment_html = '';
				if (response['html'] != '') {
					comment_html = response['html'];
				}
				window.top.saveUpdate(response['id'], comment_html, response['tname'], 0, response['sharing'], parent.$('syndication').checked, (parent.$('OwnerID') ? parent.$('OwnerID').value : ''), 'CustomPage', true, false, response['eaurl'], false, response['callback']);
				window.top.usersW.destroy();
			} else if (response['laction'] && response['laction'] != '') {
				var previousSectionID = (window.top.prevSectionID ? window.top.prevSectionID : (parent.prevSectionID ? parent.prevSectionID : prevSectionID));
				var previousLinkID = (window.top.prevLinkID ? window.top.prevLinkID : (parent.prevLinkID ? parent.prevLinkID : prevLinkID));
				if (response['method'] == 'AddPost' || response['method'] == 'EditPost') {
					window.top.saveEditPost(response['id'], response['description'], response['page_gadget'], response['page_action'], response['page_linkid'], response['addtype'], response['method'], previousSectionID);
				} else {
					customPageSelectGadget('CustomPage', response['method'], '', previousLinkID, response['callback'], previousSectionID);
					// TODO: Maybe pass items_on_layout as Object, so it's not a crazy long string...
					window.top.customPageExecuteFunctionByName('pages.addgadget', window, response['lgadget'], response['laction'], response['page_gadget'], response['page_action'], response['page_linkid'], response['items_on_layout']);
				}
			}
		}
	},
	
	addgadget: function(response) {
        if (response['success']) {
			window.top.items_on_layout = response['items_on_layout'];
			
			window.top.$(response['section_name']).insert(response['gadget_html']);
			window.top.$$('#item_' + response['id']+' .item-controls').each(function(element){element.style.visibility = 'visible';});
            window.top.Effect.ScrollTo(window.top.$('item_' + response['id']), {duration:1});
            window.top.items[response['section_name']]['item_' + response['id']] = true; 
            window.top.newdrags[response['id']] = new window.top.Draggable('item_' + response['id'], {revert:true,scroll:window,constraint:false,handle: 'item-move'});
			
        } 
		/*
		if (UI) {
			var wm = UI.defaultWM;
			var windows = wm.windows();
			alert(windows.first().id);
			windows.first().destroy();
			//GB_hide();
		} else {
			var wm = window.top.UI.defaultWM;
			var windows = wm.windows();
			alert(windows.first().id);
			windows.first().destroy();
			//GB_hide();
		}
		*/
		window.top.customPageW.destroy();
		//showResponse(response['message']);
    },

	saveeditpost: function(response) {
        if (response['success'] && typeof(response['id']) != "undefined" ) {
			if (response['section_name'] == 'custom_page-main') {
				window.location.reload();
			}
			window.top.$(response['section_name']).insert(response['gadget_html']);
			window.top.$$('#item_' + response['id']+' .item-controls').each(function(element){element.style.visibility = 'visible';});
            window.top.Effect.ScrollTo(window.top.$('item_' + response['id']), {duration:1});
            window.top.items[response['section_name']]['item_' + response['id']] = true; 
            window.top.newdrags[response['id']] = new window.top.Draggable('item_' + response['id'], {revert:true,scroll:window,constraint:false,handle: 'item-move'});
			new_tinymce_custom_pageposttext_options[response['post_id']] = {
				mode : 'textareas',
				language :'en',
				theme : 'advanced',
				plugins : 'advhr,advimage,advlink,advlist,contextmenu,directionality,example,fullscreen,inlinepopups,insertdatetime,layer,legacyoutput,noneditable,searchreplace,style,tabfocus,table,template,wordcount,xhtmlxtras,',
				theme_advanced_buttons1 : 'bold,italic,strikethrough,underline,|,formatselect,|,justifyleft,justifycenter,justifyright,justifyfull',
				theme_advanced_buttons2 : 'bullist,numlist,|,code,|,undo,redo,|,image,example,unlink,|advhr,advimage,advlink,advlist,contextmenu,directionality,example,fullscreen,inlinepopups,insertdatetime,layer,legacyoutput,noneditable,searchreplace,style,tabfocus,table,template,wordcount,xhtmlxtras',
				theme_advanced_buttons3 : '',
				theme_advanced_toolbar_location : 'top',
				theme_advanced_toolbar_align : 'left',
				theme_advanced_path_location : 'bottom',
				theme_advanced_resizing : true,
				browsers : 'msie,gecko,opera,safari',
				directionality : 'ltr',
				tab_focus : ':prev,:next',
				entity_encoding : 'raw',
				relative_urls : true,
				remove_script_host : false,
				force_p_newlines : true,
				force_br_newlines : false,
				convert_newlines_to_brs : false,
				remove_linebreaks : true,
				nowrap : false,
				fullscreen_new_window : true,
				dialog_type : 'window',
				apply_source_formatting : true,
				inlinepopups_skin: 'tinysimpleblue',
				accessibility_warnings : false,
				verify_css_classes:true,
				extended_valid_elements : 'a[id|class|name|href|target|title|onclick|rel],marquee[scrollamount|id|class],iframe[marginheight|frameborder|scrolling|align|marginwidth|name|id|src|height|width|class],button[class|id|onclick|onmouseover|onmouseout|height|width|value|name|type],input[class|id|onclick|onmouseover|onmouseout|height|width|value|name|type]',
				external_link_list_url : response['external_link_list_url'],
				invalid_elements : '',
				editor_selector : 'custom_page-post-text-'+response['post_id'],
				paste_preprocess : function(pl, o) {
					o.content = strip_tags(o.content,'');
				},
				template_templates : [
					{
					title : "News Crawler",
					src : "data/tinymce/plugins/template/templates/marquee.htm",
					description : "Adds a news crawler (marquee)"
					}
				],
				setup : function(ed) {
					ed.onLoadContent.add(function(ed, cm) {
						if (
							ed.getContent().replace(/\W+/g, '') == 'START_postpbuttonclasstemppostbuttonAddPostToThisSectionbuttonpEND_post' ||
							ed.getContent().replace(/\W+/g, '') == 'pbuttonclasstemppostbuttonAddPostToThisSectionbuttonp' ||
							ed.getContent().replace(/\W+/g, '') == 'buttonclasstemppostbuttonAddPostToThisSectionbutton'
						) {
							ed.setContent('');
						}
					});
					ed.onSaveContent.add(function(ed, cm) {
						if (
							ed.getContent().replace(/\W+/g, '') != 'START_postpbuttonclasstemppostbuttonAddPostToThisSectionbuttonpEND_post' && 
							ed.getContent().replace(/\W+/g, '') != 'pbuttonclasstemppostbuttonAddPostToThisSectionbuttonp' && 
							ed.getContent().replace(/\W+/g, '') != 'buttonclasstemppostbuttonAddPostToThisSectionbutton' && 
							ed.getContent().replace(/\W+/g, '') != ''
						) {
							if (ed.id.substring(0, 22) == 'custom_page-post-text-') {
								var post_el = ed.id.replace(/\-textarea\-inplacericheditor/gi, '');
								if ($(post_el)) {
									if ($(post_el).up('.item')) {
										if ($(post_el).up('.item').hasClassName('item-temp')) {
											$(post_el).up('.item').removeClassName('item-temp');
											$(post_el).up('.item').down('.item-controls').removeClassName('temp-controls');
											var post_section = $(post_el).up('.layout-section').identify();
											if (post_section.substring(0, 12) == 'custom_page-') {
												post_section = post_section.replace(/custom_page\-/gi, '');
											}
											fun = 'pages.saveeditpost(\'\',\'\', ($(\'page_gadget\') ? $(\'page_gadget\').getValue() : null), ($(\'page_action\') ? $(\'page_action\').getValue() : null), ($(\'page_linkid\') ? $(\'page_linkid\').getValue() : null),\'CustomPage\',\'TempPost\',\'' + post_section + '\')';
											setTimeout(fun, 0);
										}
									}
								}
							}
						}
					});
				},
				cleanup_callback : 'myCustomCleanup',
				file_browser_callback : 'jaws_filebrowser_callback',
				content_css : response['content_css_url']
			};
			new_custom_pageposttext_InPlaceRichEditor[response['post_id']] = new Ajax.InPlaceRichEditor($('custom_page-post-text-'+response['post_id']), response['saveeditpost_url'], {}, new_tinymce_custom_pageposttext_options[response['post_id']]);        
		}
		window.top.customPageW.destroy();
		//showResponse(response['message']);
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
    pages.autodraft(gadget, fieldnames, fieldvalues);
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
function customPageSetRegistryKey(value)
{
    pages.setregistrykey('/gadgets/CustomPage/googleanalytics_code', value);
}

function customPageShowEmbedWindow(url, title)
{
	customPageW = new UI.URLWindow({
		//height: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-150,
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
	customPageW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2));
	//customPageW.setZIndex(2147483647);
	customPageW.show(true).focus();
	customPageW.setZIndex(2147483647);
	customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: customPageW.getPosition().left});
	Event.observe(window, "resize", function() {
		customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 650);
}

function addPage(url, title)
{
	customPageW = new UI.URLWindow({
		height: 350,
		width: 220,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	customPageW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2));
	//customPageW.setZIndex(2147483647);
	customPageW.show(true).focus();
	customPageW.setZIndex(2147483647);
	customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: customPageW.getPosition().left});
	Event.observe(window, "resize", function() {
		customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2)});
	});
	//customPageW.setPosition(25, customPageW.getPosition().left);
	//GB_showCenter(title, url, 400, 220);
}

function customPageAddGadget(url, title)
{
	customPageW = new UI.URLWindow({
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
	//customPageW.center();
	customPageW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2));
	customPageW.setZIndex(2147483647);
	customPageW.show(true).focus();
	customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: customPageW.getPosition().left});
	Event.observe(window, "resize", function() {
		customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function insertGadget(url, title, where)
{
	customPageW = new UI.URLWindow({
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
	customPageW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2));
	//customPageW.setZIndex(2147483647);
	customPageW.show(true).focus();
	customPageW.setZIndex(2147483647);
	customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: customPageW.getPosition().left});
	Event.observe(window, "resize", function() {
		customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2)});
	});
	//GB_showCenter(title, url, 510, 950);
}

function insertFile(url, title, where)
{
	if (typeof(where) == "undefined" || where == '') {
		if ($('Image')) {
			where = 'Image';
		} else {
			alert('Insert field could not be found.');
		}
	}
		
	if (typeof(url) == "undefined" || url == '') {
		url = fileBrowserUrl;
	}
	if (url.indexOf('where=') > -1) {
		url = url.replace('&where=', '&where='+where);
	} else {
		url += '&where='+where;
	}
	customPageW = new UI.URLWindow({
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
	customPageW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2));
	//customPageW.setZIndex(2147483647);
	customPageW.show(true).focus();
	customPageW.setZIndex(2147483647);
	customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: customPageW.getPosition().left});
	Event.observe(window, "resize", function() {
		customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2)});
	});
	
	//GB_showCenter(title, url, 510, 950);
}

/**
 * Delete a page : function
 */
function deletePage(id)
{
    pages.deletepage(id);
}

/**
 * Delete a page : function
 */
function customPageDeletePost(id)
{
    //selectedCalendar = cid;
	currentAction = 'DeletePost';
	var answer = confirm(confirmPostDelete);
    if (answer) {
            var response = pagesSync.deletepost(id);
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
					var tableDnD = new CustomPageTableDnD();
					tableDnD.init(tbl);             
				}
				//stopAction();
           }
	        showResponse(response);
    }
}

/**
 * update a section's stacking order : function
 */
function updateStackOrder(id, stack, section_id)
{
    //selectedCalendar = cid;
	currentAction = 'UpdateStackOrder';
	var response = pagesSync.updatestackorder(id, stack);
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		if (stack == 'horizontal') {
			$('section'+ section_id + '_img').innerHTML = "<nobr><img width=\"50\" border=\"0\" src=\"images/btn_stack_vertical_off.jpg\" onmouseover=\"this.src='images/btn_stack_vertical_off_over.jpg'\" onmouseout=\"this.src='images/btn_stack_vertical_off.jpg'\" onclick=\"updateStackOrder(" + id + ", 'vertical', " + section_id + ");\" alt=\"Items in this section are stacked vertically\" style=\"cursor: pointer; cursor: hand;\"><img width=\"50\" border=\"0\" src=\"images/btn_stack_horizontal_on.jpg\" alt=\"Items in this section are stacked horizontally\" style=\"cursor: pointer; cursor: hand;\"></nobr>";
		} else {
			$('section'+ section_id + '_img').innerHTML = "<nobr><img width=\"50\" border=\"0\" src=\"images/btn_stack_vertical_on.jpg\" alt=\"Items in this section are stacked vertically\" style=\"cursor: pointer; cursor: hand;\"><img width=\"50\" border=\"0\" src=\"images/btn_stack_horizontal_off.jpg\" onmouseover=\"this.src='images/btn_stack_horizontal_off_over.jpg'\" onmouseout=\"this.src='images/btn_stack_horizontal_off.jpg'\" onclick=\"updateStackOrder(" + id + ", 'horizontal', " + section_id + ");\" alt=\"Items in this section are stacked horizontally\" style=\"cursor: pointer; cursor: hand;\"></nobr>";
		}
		//stopAction();
   }
	showResponse(response);
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
		var response = pagesSync.hiderss(pid, title, published, url);
		if (response[0]['css'] == 'notice-message') {
			//oldChild = $('syntactsCategory_'+cid);
			//parent.removeChild(oldChild);
			var old = $('syntactsCategory_'+id).innerHTML;
			$('syntactsCategory_'+id).innerHTML = $('syntactsEdit_'+id).innerHTML;
			$('syntactsEdit_'+id).innerHTML = old;
			//stopAction();
		}
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
	var response = pagesSync.showrss(pid, title, published, url);
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		var old = $('syntactsEdit_'+id).innerHTML;
		$('syntactsEdit_'+id).innerHTML = $('syntactsCategory_'+id).innerHTML;
		$('syntactsCategory_'+id).innerHTML = old;
		//stopAction();
	}
	//showResponse(response);
}

/**
 * Delete a splash panel : function
 */
function deleteSplashPanel(id, p)
{
    //selectedCalendar = cid;
	currentAction = 'DeleteSplashPanel';
	var answer = confirm(confirmSplashPanelDelete);
    if (answer) {
		var response = pagesSync.deletesplashpanel(id);
		if (response[0]['css'] == 'notice-message') {
			//oldChild = $('syntactsCategory_'+cid);
			//parent.removeChild(oldChild);
			if (document.getElementById('image'+p+'Info')) {
				document.getElementById('image'+p+'Info').style.display = 'none';
			}
			if (document.getElementById('insert'+p+'HTML')) {
				document.getElementById('insert'+p+'HTML').style.display = '';
			}
			if (document.getElementById('insert'+p+'Media')) {
				document.getElementById('insert'+p+'Media').style.display = 'none';
			}
			if (document.getElementById('image'+p+'Delete')) {
				document.getElementById('image'+p+'Delete').style.display = 'none';
			}
			if (document.getElementById('Image'+p)) {
				document.getElementById('Image'+p).value = '';
			}
			if (document.getElementById('image'+p+'Code')) {
				document.getElementById('image'+p+'Code').value = '';
				document.getElementById('image'+p+'Code').innerHTML = '';
			}
			if (document.getElementById('Image'+p+'ID')) {
				document.getElementById('Image'+p+'ID').value = '';
			}
			//$('syntactsCategory_'+id).style.display = 'none';
			//stopAction();
		}
		showResponse(response);
    }
}

/**
 * Can use massive delete?
 */
function customPageMassiveDelete(message) 
{
    var rows = $('pages_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            pages.massivedelete(rows);
        }
    }
}

/**
 * Search for pages and translations
 */
function searchPage()
{
    updatePagesDatagrid($('status').value, $('search').value, 0, true);
}

/**
 * show Gadget Content
 */
function showGadgetContent()
{
    $('display_form').style.display = 'none';
    $('advanced_form').style.display = 'none';
    $('content_form').style.display = 'block';
}

/**
 * show display Options
 */
function displayOptions()
{
    $('display_form').style.display = 'block';
    $('advanced_form').style.display = 'none';
    $('content_form').style.display = 'none';
}

/**
 * show advanced Options
 */
function advancedOptions()
{
    $('display_form').style.display = 'none';
    $('advanced_form').style.display = 'block';
    $('content_form').style.display = 'none';
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
function customPageGetEventSource(evt) {
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
function CustomPageTableDnD(splash) {
	this.splash = false
	if (splash) {
		table = splashTable;
		this.splash = true;
	}
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
	 	if (this.splash) {
			table = splashTable;
		}
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
			if (this.splash) {	
				sortCustomPageItemSplash(idsStr, newsortStr);
			} else {
				sortCustomPageItem(idsStr, newsortStr);
			}
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
            var target = customPageGetEventSource(ev);
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
function sortCustomPageItem(id, newsort)
{
	pages.sortitem(id, newsort);
}

/**
 * sorts an item : function
 */
function sortCustomPageItemSplash(id, newsort)
{
    pages.sortitemsplash(id, newsort);
}

function customPageCreateNamedElement(type, name) {
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

function customPageAddGadgetToLayout(gadget, action, page_gadget, page_action, page_linkid, section_id)
{   
	if (typeof(page_gadget) == "undefined") {
		page_gadget = ($('page_gadget') ? $('page_gadget').value : null);
	}
	if (typeof(page_action) == "undefined") {
		page_action = ($('page_action') ? $('page_action').value : null);
	}
	if (typeof(page_linkid) == "undefined") {
		page_linkid = ($('page_linkid') ? $('page_linkid').value : null);
	}
	if (typeof(section_id) == "undefined" || section_id == '') {
		section_id = (window.top.prevSectionID ? window.top.prevSectionID : (parent.prevSectionID ? parent.prevSectionID : (prevSectionID ? prevSectionID : 'main')));
	}
	//window.top.showWorkingNotification();
    // Ugly hack to add gadget from the greybox
	// TODO: Maybe pass items_on_layout as Object, so it's not a crazy long string...
	fun = 'pages.addgadget(\'' + gadget + '\',\'' + action + '\',\'' + page_gadget + '\',\'' + page_action + '\',\'' + page_linkid + '\', window.top.items_on_layout, \'' + section_id + '\')';
	setTimeout(fun, 0);
	var wm = window.top.UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

function customPageGetSelectedAction()
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

function customPageEditElementAction(url, gadget, action)
{
	if (gadget && action) {
		var response = pagesSync.getgadgeteditpage(gadget, action);
		if (response['message'][0]['css'] == 'notice-message' && response['url']) {
			window.open(response['url']);
        } else {
			showResponse(response['message']);
		}
	} else {
		customPageW = new UI.URLWindow({
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
		customPageW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2));
		//customPageW.setZIndex(2147483647);
		customPageW.show(true).focus();
		customPageW.setZIndex(2147483647);
		customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: customPageW.getPosition().left});
		Event.observe(window, "resize", function() {
			customPageW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-customPageW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(customPageW.getSize().width/2)});
		});
		//GB_showCenter('Edit Content', url, 510, 950);
	}
}

function customPageSaveElementAction(itemId, action, url, gadget) {
    // Ugly hack to update from the greybox
	fun = 'pages.editelementaction(' + itemId + ',\'' + action['name'] + '\')';
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

	var wm = window.top.UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
	//GB_hide();
}

/**
 * Saves Quick Add form : function
 */
function saveQuickAdd(addtype, method, callback, sharing)
{
	if (typeof(addtype) == "undefined") {
		addtype = 'CustomPage';
	}
	if (typeof(method) == "undefined") {
		method = 'AddPost';
	}
	if (typeof(callback) == "undefined" || callback == '') {
		callback = null;
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
			if (callback !== null) {
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
		if (method.indexOf('Post') > -1) {
			params["description"] = tinyMCE.get('description').getContent();
		} else {
			params["description"] = $('description').value;
		}
	}
	params["callback"] = ''+callback;
	params["items_on_layout"] = window.top.items_on_layout;
	pages.savequickadd(addtype, method, params);
}

var customPageIfrm = new Array();
function customPageGetQuickAddForm(g, method, id, linkid, callback)
{
	if (typeof(callback) == "undefined" || callback == '' || callback == 'null') {
		callback = null;
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
		customPageIfrm[g] = document.createElement("IFRAME");
		customPageIfrm[g].setAttribute('id', 'quick_add_'+g);
		customPageIfrm[g].setAttribute('name', 'quick_add_'+g);
		customPageIfrm[g].setAttribute("src", "admin.php?gadget="+g+"&action=GetQuickAddForm&method="+method+"&id="+id+"&linkid="+linkid);
		customPageIfrm[g].style.width = "100%";
		customPageIfrm[g].style.height = (method == 'AddPost' ? "415px" : "5000px");
		customPageIfrm[g].style.borderWidth = 0+"px";
		customPageIfrm[g].setAttribute('frameborder', '0');
		customPageIfrm[g].setAttribute('scrolling', 'no');
		customPageIfrm[g].setAttribute('allowtransparency', 'true');
		customPageIfrm[g].frameBorder = "0";
		customPageIfrm[g].scrolling = "no";
		$('quick-form').appendChild(customPageIfrm[g]);
	}
	$('save').onclick = function() {
		window.frames['quick_add_'+g].saveQuickAdd('CustomPage', method, callback);
	}
	$('save').style.display = '';
    prevForm = g;
}

function customPageSelectGadget(g, method, id, linkid, callback, section_id)
{
	if (typeof(section_id) == "undefined") {
		section_id = 'main';
	}
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
	prevSectionID = section_id;
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
		customPageGetQuickAddForm('CustomPage', method, id, linkid, callback);
		//$('post-form').style.display = '';
	} else {
		var forms = pagesSync.getquickaddforms(g);
		var actions = pagesSync.getgadgetactions(g, 12, 0);
		var first = null;
		show_link = false;
		link_shown = false; 
		actions_shown = false; 
		actions.each (function(item, arrayIndex) {
			if (item['action'] && item['desc'] && item['name']) {
				if (item['add'] === true) {
					show_link = true;
				}
				if (actions_shown === false) {
					actions_shown = true;
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
									customPageGetQuickAddForm(g, form['method'], id, linkid, callback);
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
				r = customPageCreateNamedElement('input', 'action');
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
							customPageGetQuickAddForm(g, form['method'], id, linkid, callback);
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
		if (actions_shown === true) {
			brlink = document.createElement('br');
			plink = document.createElement('p');
			plink.setAttribute('id', g+'-more-actions');
			plink.appendChild(brlink);
			nlink = document.createElement('a');
			nlink.setAttribute('href', 'javascript:void(0);');
			nlink.onclick = function() {
				customPageMoreActions(g, method, id, linkid, callback, 12, 12);
			}
			nlink.appendChild(document.createTextNode('Show More Actions'));
			plink.appendChild(document.createTextNode(String.fromCharCode(32,32,32)));
			plink.appendChild(nlink);
			brlink2 = document.createElement('br');
			plink.appendChild(brlink2);
			plink.setAttribute('align', 'left');
			plink.style.paddingLeft = '10px';
			plink.style.paddingBottom = '10px';
			plink.style.textAlign = 'left';
			$('actions-list').appendChild(plink);
		}
	}
    prevGadget = g;
}

function customPageMoreActions(g, method, id, linkid, callback, limit, offSet)
{
	if (typeof(limit) == "undefined") {
		limit = null;
	}
	if (typeof(offSet) == "undefined") {
		offSet = false;
	}
	$(g+'-more-actions').parentNode.removeChild($(g+'-more-actions'));
	var actions = pagesSync.getgadgetactions(g, limit, offSet);
	var first = null;
	show_link = false;
	link_shown = false; 
	actions_shown = false; 
	actions.each (function(item, arrayIndex) {
		if (item['action'] && item['desc'] && item['name']) {
			if (actions_shown === false) {
				actions_shown = true;
			}
			li = document.createElement('li');
			r = customPageCreateNamedElement('input', 'action');
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
		}
	});
	if (actions_shown === true) {
		brlink = document.createElement('br');
		plink = document.createElement('p');
		plink.setAttribute('id', g+'-more-actions');
		plink.appendChild(brlink);
		nlink = document.createElement('a');
		nlink.setAttribute('href', 'javascript:void(0);');
		nlink.onclick = function() {
			customPageMoreActions(g, method, id, linkid, callback, limit, (offSet+12));
		}
		nlink.appendChild(document.createTextNode('Show More Actions'));
		plink.appendChild(document.createTextNode(String.fromCharCode(32,32,32)));
		plink.appendChild(nlink);
		brlink2 = document.createElement('br');
		plink.appendChild(brlink2);
		plink.setAttribute('align', 'left');
		plink.style.paddingLeft = '10px';
		plink.style.paddingBottom = '10px';
		plink.style.textAlign = 'left';
		$('actions-list').appendChild(plink);
	}
}

function addUrlToLayout(gadget, url, linkid, layout)
{   
    // Ugly hack to add gadget from the greybox
    fun = 'pages.addembedsite(\'' + gadget + '\',\'' + url + '\',\'' + linkid + '\',\'' + layout + '\')';
    setTimeout(fun, 0);
	var wm = window.top.UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

/**
 * Add a File directly to a Post : function
 */
function customPageAddFileToPost(gadget, table, method, syntactsCategory, linkid, customPageNum, width, height, bgc, focus, base_url)
{
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
	for (n=0; n<customPageNum; n++) {
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
		
		customPageIfrm = document.createElement("IFRAME");
		customPageIfrm.setAttribute('id', 'iframe_' + (customPageFileCount+1));
		if (customPageNum > 1) {
			customPageIfrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&linkid="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (customPageFileCount+1) + "&bc=" + dItem.style.backgroundColor);
		} else {
			customPageIfrm.setAttribute("src", base_url + "admin.php?gadget=FileBrowser&action=AddFileToPost&where="+linkid+"&table="+table+"&method=" + method + "&addtogadget=" + gadget + "&n=" + (customPageFileCount+1) + "&bc=" + dItem.style.backgroundColor);
		}
		customPageIfrm.style.width = width+"px";
		customPageIfrm.style.height = height+"px";
		customPageIfrm.style.borderWidth = 0+"px";
		customPageIfrm.setAttribute('frameborder', '0');
		customPageIfrm.setAttribute('scrolling', 'no');
		customPageIfrm.setAttribute('allowtransparency', 'true');
		customPageIfrm.frameBorder = "0";
		customPageIfrm.scrolling = "no";
		if (n == 0) {
			if (is_table) {
				dItemGadget.innerHTML = '<a name="newImages' + customPageFileCount + '">&nbsp;</a>';
			}
		} 
		if (is_table) {
			dItemGadget.appendChild(customPageIfrm); 		
			/*
			if ($("linkid")) {
				dItemGadget.innerHTML = "<table class=\"tableform\"><tr><td><div><label id=\"file_label\" for=\"file" + customPageFileCount + "\">Image:&nbsp;</label><input type=\"file\" name=\"file" + customPageFileCount + "\" id=\"file" + customPageFileCount + "\" title=\"Filename\" /></div></td></tr></table>";
			} else {
				dItemGadget.innerHTML = "<input type=\"hidden\" name=\"linkid\" id=\"linkid\" value=\"" + linkid + "\" /><input type=\"hidden\" name=\"table\" id=\"table\" value=\"" + table + "\" /><input type=\"hidden\" name=\"addtogadget\" id=\"addtogadget\" value=\"" + gadget + "\" /><input type=\"hidden\" name=\"method\" id=\"method\" value=\"" + method + "\" /><table class=\"tableform\"><tr><td><div><label id=\"file_label\" for=\"file" + customPageFileCount + "\">Image:&nbsp;</label><input type=\"file\" name=\"file" + customPageFileCount + "\" id=\"file" + customPageFileCount + "\" title=\"Filename\" /></div></td></tr></table>";
				//document.getElementById('upload_button').style.display = 'inline';
			}
			*/
			tbod[0].appendChild(dItem);
		} else {
			dItem.appendChild(customPageIfrm); 		
			tbl.appendChild(dItem); 		
		}
		Effect.Appear(dItem.id, {duration:1});
		customPageFileCount++;
		//var tableDnD3 = new GalleryTableDnD();
		//tableDnD3.init(tbl);             
		//items['main']['item_' + response['id']] = true; 
		//newdrags[response['id']] = new Draggable('item_' + response['id'], {revert:true,constraint:true});
	}	
	tbl.setAttribute('width', '100%');
	if (is_table) {
		tbod[0].style.display = 'block';
	}
	nextFiles = customPageFileCount-5;
	if (nextFiles < 0) {
		nextFiles = 0;
	}
	if (customPageNum > 1 && focus === true) {
		docLocation = document.location+'';
		location.href = (docLocation.indexOf('#newImages') > -1 ? docLocation.substr(0, docLocation.indexOf('#newImages')) + '#newImages' + (nextFiles) : docLocation + '#newImages' + (nextFiles));
	}
}

/**
 * Show Full Update
 */
function toggleCustomPageFullUpdate(id)
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
function showCustomPageUpdateForm()
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
function hideCustomPageUpdateForm()
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
function saveCustomPageUpdate(id, comment, title, parent, sharing)
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
	response = pagesSync.newcustompagecomment(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteCustomPageComment('+response['id']+', \'update\');">X</a></div>';
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
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showCustomPageCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideCustomPageCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveCustomPageReply('+response['id']+');">Ok</button></div>';
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
	//hideCustomPageUpdateForm();
	//hideWorkingNotification();
}

/**
 * Show All Comments
 */
function toggleCustomPageAllComments(cid)
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
function toggleCustomPageFullComment(cid)
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
function showCustomPageCommentForm(cid)
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
		hideCustomPageCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideCustomPageCommentForm(cid)
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
function saveCustomPageReply(cid, id, parent)
{
	//showWorkingNotification();
	if (!parent) {
		parent = cid;
	}
    if ($('comment-entry-'+parent) && $('comment-entry-'+parent).value.length > 0) {
		comment = $('comment-entry-'+parent).value;
		$('comment-entry-'+parent).value = '';
	}
	response = pagesSync.newcustompagecomment('', comment, cid, id, '', false, 'everyone', (parent != cid ? true : false));
	if (response['css'] == 'notice-message') {
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteCustomPageComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
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
	hideCustomPageCommentForm(parent);
    if ($('all-comments-'+parent)) {
		$('all-comments-'+parent).innerHTML = '<a href="javascript:void(0);" onclick="toggleCustomPageAllComments('+parent+');">View all comments</a>';
	}
	//hideWorkingNotification();
}

/**
 * Delete Comment
 */
function DeleteCustomPageComment(cid, type, parent)
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
		var response = pagesSync.deletecustompagecomment(cid);
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

function customPageHideGB()
{   
	var wm = window.top.UI.defaultWM;
    var windows = wm.windows();
    windows.first().destroy();
    //GB_hide();
}

/**
 * Get pages data
 */
function getCustomPageData(limit)
{
    if (limit == undefined) {
        limit = $('pages_datagrid').getCurrentPage();
    }
    updatePagesDatagrid($('status').value,
                        $('search').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousCustomPageValues()
{
    var previousCustomPageValues = $('pages_datagrid').getPreviousPagerValues();
    getCustomPageData(previousCustomPageValues);
    $('pages_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextCustomPageValues()
{
    var nextCustomPageValues = $('pages_datagrid').getNextPagerValues();
    getCustomPageData(nextCustomPageValues);
    $('pages_datagrid').nextPage();
}

/**
 * Get first values of pages
 */
function firstCustomPageValues()
{
    var firstCustomPageValues = $('pages_datagrid').getFirstPagerValues();
    getCustomPageData(firstCustomPageValues);
    $('pages_datagrid').firstPage();
}

/**
 * Get last values of pages
 */
function lastCustomPageValues()
{
    var lastCustomPageValues = $('pages_datagrid').getLastPagerValues();
    getCustomPageData(lastCustomPageValues);
    $('pages_datagrid').lastPage();
}

/**
 * Update pages datagrid
 */
function updatePagesDatagrid(status, search, limit, resetCounter)
{
    $('pages_datagrid').objectName = pagesSync;
    JawsDataGrid.name = 'pages_datagrid';

    var result = pagesSync.searchpages(status, search, limit);
    resetGrid('pages_datagrid', result);
    if (resetCounter) {
        var size = pagesSync.sizeofsearch(status, search);
        $('pages_datagrid').rowsSize    = size;
        //$('pages_datagrid').setCurrentPage(0);
        $('pages_datagrid').updatePageCounter();
    }
}

/**
 * Delete a template : function
 */
function deleteTemplate(file)
{
    pages.deletetemplate(file);
}

/**
 * Search for messages
 */
function searchTemplates()
{
    updateTemplatesDatagrid($('gadget_scope').value, $('search_templates').value, 0, true);
}

/**
 * Get templates data
 */
function getTemplatesData(limit)
{
    if (limit == undefined) {
        limit = $('templates_datagrid').getCurrentPage();
    }
    updateTemplatesDatagrid(
						$('gadget_scope').value, 
						$('search_templates').value,
                        limit,
						false);
}

/**
 * Get previous values of pages
 */
function previousTemplatesValues()
{
    var previousTemplatesValues = $('templates_datagrid').getPreviousPagerValues();
    getTemplatesData(previousTemplatesValues);
    $('templates_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextTemplatesValues()
{
    var nextTemplatesValues = $('templates_datagrid').getNextPagerValues();
    getTemplatesData(nextTemplatesValues);
    $('templates_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateTemplatesDatagrid(gadget_scope, search, limit, resetCounter)
{
    $('templates_datagrid').objectName = pagesSync;
    JawsDataGrid.name = 'templates_datagrid';

    var result = pagesSync.searchtemplates(gadget_scope, search, limit);
    resetGrid('templates_datagrid', result);
    if (resetCounter) {
        var size = pagesSync.sizeofsearch1(gadget_scope, search);
        $('templates_datagrid').rowsSize    = size;
        //$('templates_datagrid').setCurrentPage(0);
        $('templates_datagrid').updatePageCounter();
    }
	setTemplatesOnSelect();	
}

/**
 * Set onclick for selected rows
 */
function setTemplatesOnSelect()
{
    var gridTable = $('templates_datagrid');
	var gridBody = gridTable.body;
	var cboxes   = gridBody.getElementsByTagName('input');
	var length   = cboxes.length;
	var realName = gridTable.id + '_checkbox';
	for(var i=0; i<length; i++) {
		//var input     = cboxes[i];
		var className = cboxes[i].getAttribute('class');
		if (className == realName) {
			if ($('layout') && cboxes[i].value != '' && cboxes[i].value == $('layout').value) {
				cboxes[i].checked = true;
			}
			cboxes[i].onclick = function() {
				selectTemplateRow(this);
			};
		}
	}
}

/**
 * Select a template row
 */
function selectTemplateRow(el)
{
	if (el.checked == true) {	
		var ogridTable = $('templates_datagrid');
		var ogridBody = ogridTable.body;
		var ocboxes   = ogridBody.getElementsByTagName('input');
		var olength   = ocboxes.length;
		var orealName = ogridTable.id + '_checkbox';
		for(var n = 0; n < olength; n++) {
			var oinput     = ocboxes[n];
			var oclassName = oinput.getAttribute('class');
			if (oclassName == orealName) {
				/*
				if (oinput.checked === true) {
					oinput.checked = false;
				}
				*/
				oinput.checked = false;
			}
		}
		el.checked = true;
		if ($('layout')) {
			$('layout').value = el.value;
		}
		if ($('layout_row')) {
			$('layout_row').style.display = 'none';
		}
		if ($('pageCol_row')) {
			$('pageCol_row').style.display = 'none';
		}
	} else {
		el.checked = false;
		if ($('layout')) {
			$('layout').value = '';
		}
		if ($('layout_row')) {
			$('layout_row').style.display = '';
		}
		if ($('pageCol_row')) {
			$('pageCol_row').style.display = '';
		}
	}
	
}

/**
 * Show the response but only text, nothing with datagrid.
 * FIXME!
 */
function customPageShowSimpleResponse(message)
{
    if (!autoDraftDone) {
        var actioni   = document.forms[0].elements['action'].value;
        if (actioni == 'AddPage' && message[0]['css'] == 'notice-message') {
            document.forms[0].elements['action'].value = 'SaveEditPage';
            document.forms[0].elements['id'].value     = message[0]['message']['id'];
            message[0]['message'] = message[0]['message']['message'];
        }
        autoDraftDone = true;
    }
    showResponse(message);
}

/**
 * Save EditPost
 */
function saveEditPost(id, content, page_gadget, page_action, page_linkid, addtype, method, section_name)
{
	if (content != prevContent) {
		prevContent = content;
		if (typeof(addtype) == "undefined") {
			addtype = 'CustomPage';
		}
		if (typeof(method) == "undefined") {
			method = 'AddPost';
		}
		if (typeof(page_gadget) == "undefined") {
			page_gadget = null;
		}
		if (typeof(page_action) == "undefined") {
			page_action = null;
		}
		if (typeof(page_linkid) == "undefined") {
			page_linkid = null;
		}
		if (typeof(section_name) == "undefined") {
			section_name = 'main';
		}
		/*
		var params = new Object();
		switch(method) {
			case 'AddPost':
			case 'EditPost':
				var posts = pagesSync.getpost(id);
				posts.each(function(post, arrayIndex){
					for (key in post) {
						if (key == 'id') {
							params['ID'] = id;
						} else {
							params[key] = (key == 'description' ? content : post[key]);
						}
					}
				});
				break;
		}
		*/
		pages.saveeditpost(id, content, page_gadget, page_action, page_linkid, addtype, method, section_name);
	}
}

function customPageExecuteFunctionByName(functionName, context /*, args */) {
    var args = Array.prototype.slice.call(arguments, 2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }
    return context[func].apply(context, args);
}

var pages = new custompageadminajax(CustomPageCallback);
//pages.serverErrorFunc = Jaws_Ajax_ServerError;
var pagesSync = new custompageadminajax();
//pagesSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.onError = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;

var autoDraftDone = false;
var customPageFileCount = 0;
var customPageNum = 0;
var prevGadget = '';
var prevForm = '';
var prevLinkID = null;
var prevSectionID = null;
var prevContent = '';
var show_link = false;
var link_shown = false;
var customPageW;
var fileBrowserUrl = '';
var new_tinymce_custom_pageposttext_options = new Array();
var new_custom_pageposttext_InPlaceRichEditor = new Array();
