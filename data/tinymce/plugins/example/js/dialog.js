tinyMCEPopup.requireLangPack();

var ExampleDialog = {
	preInit : function() {
		var url;

		if (url = tinyMCEPopup.getParam("external_link_list_url"))
			document.write('<script language="javascript" type="text/javascript" src="' + tinyMCEPopup.editor.documentBaseURI.toAbsolute(url) + '"></script>');
	},

	init : function() {
		var f = document.forms[0]

		// Get the selected contents as text and place it in the input
		f.someval.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		
		this.fillTargetList('target_list');
		this.fillFileList('link_list', 'tinyMCELinkList');
		
		if (e = tinyMCEPopup.editor.dom.getParent(tinyMCEPopup.editor.selection.getNode(), 'A')) {
			f.href.value = tinyMCEPopup.editor.dom.getAttrib(e, 'href');
			f.linktitle.value = tinyMCEPopup.editor.dom.getAttrib(e, 'title');
			f.insert.value = tinyMCEPopup.editor.getLang('update');
			selectByValue(f, 'link_list', f.href.value);
			selectByValue(f, 'target_list', tinyMCEPopup.editor.dom.getAttrib(e, 'target'));
		}
	},

	insert : function() {
		// Insert the contents from the input into the document		
		tinyMCEPopup.restoreSelection();
		e = tinyMCEPopup.editor.dom.getParent(tinyMCEPopup.editor.selection.getNode(), 'A');
	
		// Remove element if there is no href
		if (!document.forms[0].href.value) {
			if (e) {
				tinyMCEPopup.execCommand("mceBeginUndoLevel");
				b = tinyMCEPopup.editor.selection.getBookmark();
				tinyMCEPopup.editor.dom.remove(e, 1);
				tinyMCEPopup.editor.selection.moveToBookmark(b);
				tinyMCEPopup.execCommand("mceEndUndoLevel");
				tinyMCEPopup.close();
				return;
			}
		}

		tinyMCEPopup.execCommand("mceBeginUndoLevel");

		// Create new anchor elements
		if (e == null) {
			tinyMCEPopup.execCommand("CreateLink", false, "#mce_temp_url#", {skip_undo : 1});

			tinymce.each(tinyMCEPopup.editor.dom.select("a"), function(n) {
				if (tinyMCEPopup.editor.dom.getAttrib(n, 'href') == '#mce_temp_url#') {
					e = n;

					tinyMCEPopup.editor.dom.setAttribs(e, {
						href : document.forms[0].href.value,
						title : document.forms[0].linktitle.value,
						target : document.forms[0].target_list ? document.forms[0].target_list.options[document.forms[0].target_list.selectedIndex].value : null, 
						'class' : null
					});
				}
			});
			
			// Insert the contents from the input into the document
			var href = document.forms[0].href.value;
			var link = document.forms[0].link_list ? document.forms[0].link_list.options[document.forms[0].link_list.selectedIndex].value : null;
			var title = document.forms[0].linktitle.value;
			var target = document.forms[0].target_list ? document.forms[0].target_list.options[document.forms[0].target_list.selectedIndex].value : null;
			hyperlink = (link ? link : href);
			somevalue = document.forms[0].someval.value;
			hypertext = (somevalue ? somevalue : hyperlink);
			tinyMCEPopup.editor.execCommand('mceInsertContent', false, '<a href="'+hyperlink+'" title="'+title+'" target="'+target+'">'+hypertext+'</a>');
		} else {
			tinyMCEPopup.editor.dom.setAttribs(e, {
				href : document.forms[0].href.value,
				title : document.forms[0].linktitle.value,
				target : document.forms[0].target_list ? document.forms[0].target_list.options[document.forms[0].target_list.selectedIndex].value : null, 
				'class' : null
			});
		}

		// Don't move caret if selection was image
		if (e && (e.childNodes.length != 1 || e.firstChild.nodeName != 'IMG')) {
			tinyMCEPopup.editor.focus();
			tinyMCEPopup.editor.selection.select(e);
			tinyMCEPopup.editor.selection.collapse(0);
			tinyMCEPopup.storeSelection();
		}

		tinyMCEPopup.execCommand("mceEndUndoLevel");
		
		//tinyMCEPopup.editor.execCommand('mceInsertContent', false, document.forms[0].href.value);
		tinyMCEPopup.close();
	},

	fillFileList : function(id, l) {
		var dom = tinyMCEPopup.dom, lst = dom.get(id), v, cl;

		l = window[l];

		if (l && l.length > 0) {
			lst.options[lst.options.length] = new Option('', '');

			tinymce.each(l, function(o) {
				lst.options[lst.options.length] = new Option(o[0], o[1]);
			});
		} else
			dom.remove(dom.getParent(id, 'tr'));
	},

	fillTargetList : function(id) {
		var dom = tinyMCEPopup.dom, lst = dom.get(id), v;

		lst.options[lst.options.length] = new Option('Not set', '');
		lst.options[lst.options.length] = new Option('Open in same window', '_self');
		lst.options[lst.options.length] = new Option('Open in new window', '_blank');

		/*
		if (v = tinyMCEPopup.getParam('theme_advanced_link_targets')) {
			tinymce.each(v.split(','), function(v) {
				v = v.split('=');
				lst.options[lst.options.length] = new Option(v[0], v[1]);
			});
		}
		*/
	}
	
};

ExampleDialog.preInit();
tinyMCEPopup.onInit.add(ExampleDialog.init, ExampleDialog);
