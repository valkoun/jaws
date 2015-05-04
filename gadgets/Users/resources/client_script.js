/**
 * Users Javascript actions
 *
 * @category   Ajax
 * @package    Users
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * Use async mode, create Callback
 */
var UsersCallback = {
    adduser: function(response) {
        if (response[0]['css'] == 'notice-message') {
            //$('user_search_entry').value = '';
            getUsers();
            stopAction();
        }
        showResponse(response);
    },

    updateuser: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getUsers();
        }
        //stopAction();
        showResponse(response);
    },

    updatemyaccount: function(response) {
        if (response[0]['message'].indexOf('URL') > -1) {
			//window.location.href=response[0]['message'].substr(5,response[0]['message'].length);
			window.location.reload();
		} else {
			showResponse(response);
		}
    },

    deleteuser: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getUsers();
            stopAction();
        }
        showResponse(response);
    },

    addgroup: function(response) {
        if (response['message'][0]['css'] == 'notice-message') {
            if (response['id'] != '') {
				stopAction();
				editGroup(response['id']);
            }
        }
        showResponse(response['message']);
    },

    updategroup: function(response) {
        if (response['message'][0]['css'] == 'notice-message') {
            if (response['id'] != '') {
				stopAction();
				editGroup(response['id']);
            }
        }
        showResponse(response['message']);
    },

    deletegroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            location.href = 'index.php?gadget=Users&action=DefaultAction#pane=Groups';
			if ($$('#groups-items #group-'+selectedGroup)[0]) {
				$$('#groups-items #group-'+selectedGroup)[0].parentNode.removeChild($$('#groups-items #group-'+selectedGroup)[0]);
			}
        }
        showResponse(response);
    },

    saveuseracl: function(response) {
        showResponse(response);
    },

    savegroupacl: function(response) {
        showResponse(response);
    },

    resetuseracl: function(response) {
        if (response[0]['css'] == 'notice-message') {
            manageUserACL();
        }
        showResponse(response);
    },

    resetgroupacl: function(response) {
        if (response[0]['css'] == 'notice-message') {
            manageGroupACL();
        }
        showResponse(response);
    },

    adduserstogroup: function(response) {
        showResponse(response);
    },

    addusertogroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            location.href = 'index.php?gadget=Users&action=DefaultAction';
        }
        showResponse(response);
    }, 

    deleteuserfromgroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            location.href = 'index.php?gadget=Users&action=DefaultAction';
        }
        showResponse(response['message']);
    },
    saveadvancedoptions: function(response) {
        showResponse(response);
    },

    updatepersonalinfo: function(response) {
        showResponse(response);
    },

    savesettings: function(response) {
        showResponse(response);
    },

    deleteembed: function(response) {
		if (response[0]['message'] == "User not logged in.") {
			showResponse(response);
			//window.location.href=response[0]['message'].substr(5,response[0]['message'].length);
			window.location.reload();
		} else {
	        searchXML($('search').value);
	        //showResponse(response);
		}
	},
    	
    newupdate: function(response) {
        if (response[0]['css'] == 'notice-message') {
            //hideUpdateForm();
        }
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
					var wm = window.top.UI.defaultWM;
					var windows = wm.windows();
					windows.first().destroy();
					//GB_hide();
				} else {
					alert('Container section not found.');
				}
				
			} else if (response['addtype'] == 'Comment') {
				var comment_html = '';
				if (response['html'] != '') {
					comment_html = response['html'];
				}
				window.top.saveUpdate(response['id'], comment_html, response['tname'], 0, response['sharing'], parent.$('syndication').checked, (parent.$('OwnerID') ? parent.$('OwnerID').value : ''), 'Users', true, false, response['eaurl'], false, response['callback']);
				window.top.usersW.destroy();
			} else {
				if (response['laction'] && response['laction'] != '') {
					// TODO: Maybe pass items_on_layout as Object, so it's not a crazy long string...
					usersExecuteFunctionByName('usersAsync.addgadget', window, response['lgadget'], response['laction'], response['page_gadget'], response['page_action'], response['page_linkid'], response['items_on_layout']);
				}
			}
		}
	}

};

function addUpdate(url, title)
{
	w = new UI.URLWindow({
		height: 450,
		width: 750,
		shadow: true,
		theme: "simpleblue",
		url: url,
		minimize: false,
		maximize: false,
		close: 'destroy',
		resizable: true
	});
	//w.setZIndex(2147483647);
	w.show(true).focus().center();
	w.setZIndex(2147483647);
	w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: w.getPosition().left});
	Event.observe(window, "resize", function() {
		w.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-w.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(w.getSize().width/2)});
	});
	//GB_showCenter(title, url, 350, 820);
}

function getSelectedAction(form)
{
    var radioObj = document.forms[form].elements['action'];
    if(!radioObj) {
        return "";
	}
    var radioLength = radioObj.length;
    if(radioLength == undefined) {
        if(radioObj.checked) {
            return radioObj.value;
        } else {
            return "";
		}
	}
    for(var i = 0; i < radioLength; i++) {
        if(radioObj[i]) {
			if(radioObj[i].checked) {
				return radioObj[i].value;
			}
		}
    }
    return "";
}

var ifrm = new Array();
function getQuickAddForm(g, method, id, linkid, section_id, callback, query)
{
	if (typeof(callback) == "undefined" || callback == '') {
		callback = null;
	}
    if (typeof(query) == "undefined") {
		query = '';
	}
	// Remove all actions 
    while ($('actions-list').firstChild)
    {
        $('actions-list').removeChild($('actions-list').firstChild);
    };

    //$(g).setAttribute('class', 'gadget-item gadget-selected'); 
    //$(g).setAttribute('className', 'gadget-item gadget-selected'); 
	$('quick-form').style.display = '';
	if ($('add')) {
		$('add').style.display = 'none';
	}
	if (ifrm[prevGadget]) {
		ifrm[prevGadget].style.display = 'none';		
	}
	if (ifrm[g]) {
		ifrm[g].style.display = '';
	} else {
		ifrm[g] = document.createElement("IFRAME");
		ifrm[g].setAttribute('id', 'quick_add_'+g);
		ifrm[g].setAttribute('name', 'quick_add_'+g);
		ifrm[g].setAttribute("src", "index.php?gadget="+g+"&action=account_GetQuickAddForm&method="+method+"&id="+id+"&linkid="+linkid+"&section_id="+section_id+query);
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
		window.frames['quick_add_'+g].saveQuickAdd('Comment', method, callback, $('sharing').value, $('syndication').value, ($('OwnerID') ? $('OwnerID').value : null));
	}
	$('save').style.display = '';
}

/**
 * Saves Quick Add form : function
 */
function saveQuickAdd(addtype, method, callback, sharing)
{
	if (typeof(addtype) == "undefined") {
		addtype = 'Users';
	}
	if (typeof(method) == "undefined") {
		method = 'AddGroup';
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
	usersAsync.savequickadd(addtype, method, params);
}

var usersIfrm = new Array();
function usersGetQuickAddForm(g, method, id, linkid, callback)
{
	if (typeof(callback) == "undefined" || callback == '') {
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
		usersIfrm[g] = document.createElement("IFRAME");
		usersIfrm[g].setAttribute('id', 'quick_add_'+g);
		usersIfrm[g].setAttribute('name', 'quick_add_'+g);
		usersIfrm[g].setAttribute("src", "index.php?gadget="+g+"&action=account_GetQuickAddForm&method="+method+"&id="+id+"&linkid="+linkid);
		usersIfrm[g].style.width = "100%";
		usersIfrm[g].style.height = "5000px";
		usersIfrm[g].style.borderWidth = 0+"px";
		usersIfrm[g].setAttribute('frameborder', '0');
		usersIfrm[g].setAttribute('scrolling', 'no');
		usersIfrm[g].setAttribute('allowtransparency', 'true');
		usersIfrm[g].frameBorder = "0";
		usersIfrm[g].scrolling = "no";
		$('quick-form').appendChild(usersIfrm[g]);
	}
	$('save').onclick = function() {
		window.frames['quick_add_'+g].saveQuickAdd('Users', method, callback);
	}
	$('save').style.display = '';
    prevForm = g;
}

function selectGadget(g, method, id, linkid, section_id, callback, custom_function, query)
{
	if (typeof(callback) == "undefined" || callback == '') {
		callback = null;
	}
    if (typeof(custom_function) == "undefined") {
		custom_function = null;
	}
    if (typeof(query) == "undefined") {
		query = '';
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
	if ($('share')) {
		$('share').style.display = '';
	}
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
    if (custom_function !== null) {
		custom_function(); 
	} else {
		var forms = usersSync.getquickaddforms(g, true);
		$('post-form').style.display = 'none';
		show_link = false;
		link_shown = false; 
		form_size = forms.size();
		if (form_size > 1) {
			brlink = document.createElement('br');
			$('actions-list').appendChild(brlink);
			plink = document.createElement('p');
		}
		forms.each (function(form, arrayIndex) {
			if (form['method'] && form['name']) {
				if (form_size > 1) {
					nlink = document.createElement('a');
					nlink.setAttribute('href', 'javascript:void(0);');
					nlink.onclick = function() {
						getQuickAddForm(g, form['method'], id, linkid, section_id, callback, query);
					}
					nlink.appendChild(document.createTextNode('Add '+form['name']));
					plink.appendChild(document.createTextNode(String.fromCharCode(32,32,32)));
					plink.appendChild(nlink);
				} else {
					getQuickAddForm(g, form['method'], id, linkid, section_id, callback, query);
				}
			}
		});
		if (form_size > 1) {
			brlink2 = document.createElement('br');
			plink.appendChild(brlink2);
			plink.setAttribute('align', 'right');
			plink.style.paddingRight = '10px';
			plink.style.paddingBottom = '10px';
			plink.style.textAlign = 'right';
			$('actions-list').appendChild(plink);
		}
	}
	$('save').style.display = '';
    prevGadget = g;
	prevMethod = method;
	prevId = id;
	prevLinkid = linkid;
	prevSectionid = section_id;
	prevCallback = callback;
	prevCustom_function = custom_function;
	prevQuery = query;
}

/**
 * Search user function
 */
function searchUser()
{
    getUsers();
}

/**
 * Get users list
 */
function getUsers()
{
    stopAction();
    $('users_combo').length = 0;
    userList  = usersSync.getusers(showAll, null);
    if (userList != false) {
        var combo = $('users_combo');
        combo.options.length = 0;
        var i = 0;
        userList.each(function(value, index) {
            var op = new Option(value['nickname'], value['id']);
            op.setAttribute('title', value['username']);
            if (i % 2 == 0) {
                op.style.backgroundColor = evenColor;
            } else {
                op.style.backgroundColor = oddColor;
            }
            combo.options[combo.options.length] = op;
            if (selectedUser == value['id']) {
                combo.selectedIndex = i;
            }
            i++;
        });
    }
}

/**
 * Get groups list
 */
function getGroups()
{
    $('groups_combo').length = 0;
    var groupList = usersSync.getgroups();
    if (groupList) {
        var combo = $('groups_combo');
        var i = 0;
        groupList.each(function(value, index) {
            var op = new Option(value['title'], value['id']);
            if (i % 2 == 0) {
                op.style.backgroundColor = evenColor;
            } else {
                op.style.backgroundColor = oddColor;
            }
            combo.options[combo.options.length] = op;
            if (selectedGroup == value['id']) {
                combo.selectedIndex = i;
            }
            i++;
        });
    }
}

/**
 * Saves data / changes
 */
function saveUser()
{
    if (currentAction == 'AdvancedUserOptions') {
        usersAsync.saveadvancedoptions(selectedUser,
                                       $('user_language').value,
                                       $('user_theme').value,
                                       $('user_editor').value,
                                       $('user_timezone').value,
                                       $('notification').value,
                                       $('allow_comments').value);
    } else if (currentAction == 'PersonalInformation') {
        usersAsync.updatepersonalinfo(selectedUser,
                                      $('fname').value,
                                      $('lname').value,
                                      $('gender').value,
                                      $('dob_year').value,
                                      $('dob_month').value,
                                      $('dob_day').value,
                                      $('url').value,
                                      $('company').value,
                                      $('address').value,
                                      $('address2').value,
                                      $('city').value,
                                      $('country').value,
                                      $('region').value,
                                      $('postal').value,
                                      $('phone').value,
                                      $('office').value,
                                      $('tollfree').value,
                                      $('fax').value,
                                      $('merchant_id').value,
                                      $('description').value,
                                      $('logo').value,
                                      $('keywords').value,
                                      $('company_type').value);
    } else {
        if ($('pass1').value != $('pass2').value) {
            alert(wrongPassword);
            return false;
        }

        if (jawsTrim($('username').value) == '' ||
            jawsTrim($('nickname').value)     == '' ||
            jawsTrim($('email').value)    == '') {
            alert(incompleteUserFields);
            return false;
        }

        if ($('exponent')) {
            setMaxDigits(128);
            var pub_key = new RSAPublicKey($('exponent').value, $('modulus').value, 128);
            var password = encryptedString(pub_key, $('pass1').value);
        } else {
            var password = $('pass1').value;
        }

        if(selectedUser == null) {
            if (jawsTrim($('pass1').value) == '') {
                alert(incompleteUserFields);
                return false;
            }

            usersAsync.adduser($('username').value,
			   password,
			   $('nickname').value,
			   $('email').value,
			   $('user_group').value,
			   $('user_type').value,
			   $('enabled').value);
        } else {
            usersAsync.updateuser(selectedUser,
			  $('username').value,
			  password,
			  $('nickname').value,
			  $('email').value,
			  $('user_type').value,
			  $('enabled').value);
        }
    }
    stopAction();
}

/**
 * Saves data / changes on the group's form
 */
function saveGroup()
{
    if (currentAction == 'ManageUserGroups') {
        addUserstoGroup();
    } else {
        if (jawsTrim($('title').value) == '') {
            alert(incompleteGroupFields);
            return false;
        }

        if (selectedGroup == null) {
            usersAsync.addgroup($('name').value,
                                $('title').value,
                                $('description').value, 
								$('founder').value);
        } else {
            usersAsync.updategroup(selectedGroup,
                                  $('name').value,
                                  $('title').value,
                                  $('description').value,
								  $('founder').value);
        }
    }
    //stopAction();
}

/**
 * Saves the users ACL
 */
function saveACL()
{
    var inputs = $('work_area').getElementsByTagName('input');
    var keys   = new Object();
    for(var i=0; i<inputs.length; i++) {
        if (inputs[i].changed) {
            keys[inputs[i].value] = inputs[i].checked;
        }
    }
    usersAsync.saveuseracl(selectedUser, keys);
}

/**
 * Add users to a group
 */
function addUserstoGroup()
{
    var inputs  = $('work_area').getElementsByTagName('input');
    var keys    = new Object();
    var counter = 0;
    for (var i=0; i<inputs.length; i++) {
        if (inputs[i].name.indexOf('group_users') == -1) {
            continue;
        }

        if (inputs[i].checked) {
            keys[counter] = inputs[i].value;
            counter++;
        }
    }
    usersAsync.adduserstogroup(selectedGroup, keys);
}

/**
 * Add single user to a group
 */
function addUserToGroup(gid, uid, status)
{
    usersAsync.addusertogroup(gid, uid, status);
}

/**
 * Delete user from a group
 */
function deleteUserFromGroup(gid, uid)
{
    usersAsync.deleteuserfromgroup(gid, uid);
}

/**
 * Delete user
 */
function deleteUser()
{
    if (typeof(confirmUserDelete) == "undefined") {
		confirmUserDelete = "Are you sure you want to delete this user?";
	}
    var answer = confirm(confirmUserDelete);
    if (answer) {
        usersAsync.deleteuser(selectedUser);
    }
}

/**
 * Delete group
 */
function deleteGroup(gid)
{
	if (typeof(gid) != "undefined") {
		selectedGroup = gid;
	}
    if (typeof(confirmGroupDelete) == "undefined") {
		confirmGroupDelete = "Are you sure you want to delete this group?";
	}
	var answer = confirm(confirmGroupDelete);
    if (answer) {
        usersAsync.deletegroup(selectedGroup);
    }
}

/**
 * Save the group ACL keys
 */
function saveGroupACL()
{
    var inputs = $('work_area').getElementsByTagName('input');
    var keys   = new Object();
    for(var i=0; i<inputs.length; i++) {
        if (inputs[i].changed) {
            keys[inputs[i].value] = inputs[i].checked;
        }
    }
    usersAsync.savegroupacl(selectedGroup, keys);
}

/**
 * Reset the ACL keys of current user
 */
function resetUserACL()
{
    if (typeof(confirmResetACL) == "undefined") {
		confirmResetACL = "Are you sure you want to reset the ACLs?";
	}
    var answer = confirm(confirmResetACL);
    if (answer) {
        usersAsync.resetuseracl(selectedUser);
    }
}

/**
 * Reset the ACL keys of current group
 */
function resetGroupACL()
{
    if (typeof(confirmResetACL) == "undefined") {
		confirmResetACL = "Are you sure you want to reset the ACLs?";
	}
    var answer = confirm(confirmResetACL);
    if (answer) {
        usersAsync.resetgroupacl(selectedGroup);
    }
}

/**
 * Add user - FORM
 */
function addUser()
{
	if (cacheForm == null) {
        cacheForm = usersSync.getuserform();
    }

    $('cancel_action').style.display = 'block';
    $('save_user').style.display = 'block';
    $('add_user').style.display = 'none';
    //$('users_combo').disabled = true;
    $('work_area').innerHTML = cacheForm;
    $('user_group').parentNode.style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
    selectedUser = null;

    var groups = usersSync.getgroups();
    if (groups != null) {
        while($('user_group').options.length != 0) {
            $('user_group').options[0] = null;
        }

        var op = new Option();
        op.id = '-';
        op.text = noGroup;
        $('user_group').options[$('user_group').options.length] = op;

        var i = 1;
        groups.each(function(value, index) {
            op = new Option();
            op.value = value['id'];
            op.text = value['title'];
            if (i % 2 == 0) {
                op.style.backgroundColor = evenColor;
            } else {
                op.style.backgroundColor = oddColor;
            }
            $('user_group').options[$('user_group').options.length] = op;
            i++;
        });
        $('user_group').parentNode.style.display = 'block';
    }
    currentAction = 'AddUser';
}

/**
 * Add group
 */
function addGroup()
{
	if (cacheForm == null) {
        cacheForm = usersSync.getgroupform();
    }
	if ($('cancel_action')) {
		$('cancel_action').style.display = 'block';
    }
	if ($('save_group')) {
		$('save_group').style.display = 'block';
    }
	if ($('add_group')) {
		$('add_group').style.display = 'none';
    }
	//$('groups_combo').disabled = true;
    $('work_area').innerHTML = cacheForm;
    $('right_menu').style.visibility = 'hidden';

    selectedGroup = null;
    currentAction = 'AddGroup';
}

/**
 * Edit user
 */
function editUser(uid)
{
    if (uid == 0) return;
    if (cacheForm == null) {
        cacheForm = usersSync.getuserform();
    }

    currentAction = 'EditUser';

    $('cancel_action').style.display = 'block';
    $('save_user').style.display = 'block';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'block';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('delete_user').style.display = 'block';
    $('add_user').style.display = 'none';
    $('work_area').innerHTML = cacheForm;
    $('user_group').parentNode.style.display = 'none';
    $('right_menu').style.visibility = 'visible';

    selectedUser = uid;
    var uInfo = usersSync.getuser(selectedUser);

    $('user_avatar').src = uInfo['image'];
    $('username').value  = uInfo['username'];
    $('nickname').value     = uInfo['nickname'];
    $('email').value     = uInfo['email'];
    $('userInfo_action').value = 'EditUser';

    if (uInfo['user_type'] == 0) {
        $('user_type').selectedIndex = 0;
    } else if (uInfo['user_type'] == 1) {
        //First value is super-admin or just admin?
        if ($('user_type').options[0].value == 0) { //super-admin
            $('user_type').selectedIndex = 1; //Then we should check the next one (which is admin)
        } else {
            $('user_type').selectedIndex = 0; //First is only admin (no super-admin, check this)
        }
    } else {
        $('user_type').selectedIndex = ($('user_type').options.length-1);
    }
    $('enabled').value = uInfo['enabled'];
    currentAction = 'EditUser';
}

/**
 * Edit group
 */
function editGroup(guid)
{
    if (guid == 0) return;
    if (cacheForm == null) {
        cacheForm = usersSync.getgroupform();
    }

    if ($('cancel_action')) {
		$('cancel_action').style.display = 'block';
    }
    if ($('save_group')) {
		$('save_group').style.display = 'block';
    }
	if ($('save_acl')) {
        $('manage_acl').style.display = 'block';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('add_usergroups').style.display = 'block';
	if ($('delete_group')) {
		$('delete_group').style.display = 'block';
    }
	if ($('add_group')) {
		$('add_group').style.display = 'none';
    }
	$('work_area').innerHTML = cacheForm;
    $('right_menu').style.visibility = 'visible';

    selectedGroup = guid;
    currentAction = 'EditGroup';
    
	var gInfo = usersSync.getgroup(selectedGroup);
    $('name').value        	= gInfo['name'];
    $('title').value       	= gInfo['title'];
    $('description').value 	= gInfo['description'];
    $('founder').value 		= gInfo['founder'];
}

/**
 * Minimize Gadget Pane
 */
function minPane(g, uid)
{
	currentAction = 'MinimizePane';
	var response = usersSync.updateusersgadgets(uid, g, 'minimized');
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		if ($(g+'_pane')) {
			$(g+'_pane').style.display = 'none';
		}
		if ($(g+'_button1')) {
			$(g+'_button1').innerHTML = "<img border=\"0\" src=\"images/btn_paneMax_on.png\" name=\"Expand\" alt=\"Expand\" title=\"Expand\" onClick=\"maxPane('"+g+"', "+uid+");\" onMouseover=\"this.src='images/btn_paneMax_off.png'\" onMouseOut=\"this.src='images/btn_paneMax_on.png'\" />";
		} else if ($(g+'_button2')) {
			$(g+'_button2').innerHTML = "<img border=\"0\" src=\"images/btn_paneMax_on.png\" name=\"Expand\" alt=\"Expand\" title=\"Expand\" onClick=\"maxPane('"+g+"', "+uid+");\" onMouseover=\"this.src='images/btn_paneMax_off.png'\" onMouseOut=\"this.src='images/btn_paneMax_on.png'\" />";
		}
		stopAction();
	} else if (response[0]['message'] == "User not logged in.") {
		showResponse(response);
		//window.location.href=response[0]['message'].substr(5,response[0]['message'].length);
		window.location.reload();
	} else {
		showResponse(response);
	}
}

/**
 * Maximize Gadget Pane
 */
function maxPane(g, uid)
{
	currentAction = 'MaximizePane';
	var response = usersSync.updateusersgadgets(uid, g, 'maximized');
	if (response[0]['css'] == 'notice-message') {
		//oldChild = $('syntactsCategory_'+cid);
		//parent.removeChild(oldChild);
		if ($(g+'_pane')) {
			$(g+'_pane').style.display = 'inline';
		}
		if ($(g+'_button2')) {
			$(g+'_button2').innerHTML = "<img border=\"0\" src=\"images/btn_paneMin_on.png\" name=\"Collapse\" alt=\"Collapse\" title=\"Collapse\" onClick=\"minPane('"+g+"', "+uid+");\" onMouseover=\"this.src='images/btn_paneMin_off.png'\" onMouseOut=\"this.src='images/btn_paneMin_on.png'\" />";
		} else if ($(g+'_button1')) {
			$(g+'_button1').innerHTML = "<img border=\"0\" src=\"images/btn_paneMin_on.png\" name=\"Collapse\" alt=\"Collapse\" title=\"Collapse\" onClick=\"minPane('"+g+"', "+uid+");\" onMouseover=\"this.src='images/btn_paneMin_off.png'\" onMouseOut=\"this.src='images/btn_paneMin_on.png'\" />";
		}
		stopAction();
	} else if (response[0]['message'] == "User not logged in.") {
		showResponse(response);
		//window.location.href=response[0]['message'].substr(5,response[0]['message'].length);
		window.location.reload();
	} else {
		showResponse(response);
	}
}

/**
 * Delete subscription
 */
function deleteSubscription(g, sid)
{
	currentAction = 'DeleteSubscription';
	if (eval('confirm'+g+'Delete')) { 
		var answer = confirm(eval('confirm'+g+'Delete'));
	    if (answer) {
			var response = usersSync.deletesubscription(g, sid);
			if (response[0]['css'] == 'notice-message') {
				$(g+'_syntactsCategory_'+sid).style.display = 'none';
				stopAction();
			} else if (response[0]['message'] == "User not logged in.") {
				showResponse(response);
				//window.location.href=response[0]['message'].substr(5,response[0]['message'].length);
				window.location.reload();
			}
			showResponse(response);
	    }
	}
}

/**
 * Delete embedded gadget
 */
function deleteEmbed(url, gadget_url)
{
    var answer = confirm(confirmEmbedDelete);
    if (answer) {
        usersAsync.deleteembed(url, gadget_url);
    }
}

/**
 * Search embed XML function
 */
function searchXML(s, gadget_url)
{
	// Remove all actions 
	while ($('actions-list').firstChild)
	{
		$('actions-list').removeChild($('actions-list').firstChild);
	};
	if ($('gadget').value) {
		var actions = usersSync.getgadgetactions($('gadget').value,s,gadget_url);
		var first = null;
		actions.each (
			function(item, arrayIndex) {
				if (item['message'] == "User not logged in.") {
					parent.parent.window.location.reload();
				}
				if (first == null) {
					first = item['id'];
				}
				li = document.createElement('li');
				r = createNamedElement('input', 'action');
				//r = document.createElement('input');
				r.setAttribute('type', 'radio');
				//r.setAttribute('name', 'action');
				r.setAttribute('value', item['url']);
				r.setAttribute('id', item['id']);
				r.checked = item['checked'];
				label = document.createElement('label');
				label.setAttribute('for', item['id']);
				if (item['checked'] == true) {
					label.innerHTML = item['name'] + '<br /><span style="display: inline;">' + item['url'] + '</span>&nbsp;';
					aea = document.createElement('a');
					aea.setAttribute('href', 'javascript:void(0);');
					aea.onclick = function() {
						deleteEmbed(item['url'], gadget_url);
					}
		            aea.setAttribute('id', 'a_' + item['id']);
		            aea.setAttribute('name', 'a_' + item['name']);
		            aea.setAttribute('title', 'Un-Embed Gadget on this URL');
		            aea.appendChild(document.createTextNode('Un-embed'));
					label.appendChild(aea);
				} else {
					label.innerHTML = item['name'] + '<span>' + item['url'] + '</span>';
				}
				li.appendChild(r); 
				li.appendChild(label); 
				$('actions-list').appendChild(li);
			}
		);
		if (first == null) {
			li = document.createElement('li');
			li.setAttribute('class', 'action-msg');
			li.setAttribute('className', 'action-msg');
			li.appendChild(document.createTextNode(noActionsMsg));
			$('actions-list').appendChild(li);
		} else {
			$(first).checked = true;
		}
	}
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

function selectSite(g)
{
	// Remove all actions 
	while ($('actions-list').firstChild)
	{
		$('actions-list').removeChild($('actions-list').firstChild);
	};

    if (g == 'manual') {
		$('search-entry').style.display = 'none';
		$('gadget-actions').style.display = 'none';
		$('layout').style.display = 'none';
		$('add').style.display = 'none';
		$('embed-manual').style.display = 'block';
	} else {
		$('embed-manual').style.display = 'none';
		$('search-entry').style.display = 'block';
		$('gadget-actions').style.display = 'block';
		$('add').style.display = '';
		$('layout').style.display = 'block';
		$('gadget').value = g;
	}
	if ($(prevSite) && prevSite != g) {
		$(prevSite).setAttribute('class', 'gadget-item'); 
		$(prevSite).setAttribute('className', 'gadget-item'); 
	}
	$(g).setAttribute('class', 'gadget-item gadget-selected'); 
	$(g).setAttribute('className', 'gadget-item gadget-selected'); 

	prevSite = g;
}

function selectPane(g)
{
	$$('#pane-content .simple-response-msg').each(function(element){
		element.style.display = 'none';
	});
	if (paneRequest !== null) {
		paneRequest.abort();
	}
	if ($(g) && typeof(paneArray[g]) != "undefined") {
		//showWorkingNotification();

		// Remove all actions 
		if ($('pane-content') && $('pane-list')) {
			//$('pane-content').innerHTML = '';	

			if ($(prevPane)) {
				if ($('pane-'+prevPane)) {
					$('pane-'+prevPane).style.display = 'none';
				}
				$(prevPane).setAttribute('class', 'pane-item'); 
				$(prevPane).setAttribute('className', 'pane-item'); 
			} else {
				if ($('Users')) {
					if ($('pane-Users')) {
						$('pane-Users').style.display = 'none'; 
					}
					$('Users').setAttribute('class', 'pane-item'); 
					$('Users').setAttribute('className', 'pane-item'); 
				}
			}
			$(g).setAttribute('class', 'pane-item pane-selected'); 
			$(g).setAttribute('className', 'pane-item pane-selected'); 
			if ($('pane-'+g).innerHTML == '') {
				/*
				$('pane-list').setStyle({
					backgroundImage: 'url(images/loading.gif)',
					backgroundPosition: 'right 0',
					backgroundRepeat: 'no-repeat',
				});
				*/
				paneRequest = new Ajax.Request('index.php?gadget='+paneArray[g]['gadgetrealname']+'&action='+paneArray[g]['method']+paneArray[g]['params'], {
					method: 'get',
					onCreate: function() {
						showWorkingNotification();
					},
					onSuccess: function(transport) {
						$('pane-'+g).update(transport.responseText);
						$$(".menu_li_item").each(function(element){
							checkSubMenus(element);
							Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\""+$(element).down(".ul_sub_menu").className+"\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : (element.hasClassName("menu_li_pane_item") ? $('pane-list').innerHTML : "")), {
								className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
								showOn: "mouseover",
								hideTrigger: "tip",
								hideOn: "mouseout",
								stem: false,
								delay: false,
								tipJoint: [ "center", "top" ],
								target: element,
								showEffect: "appear",
								offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != '' && $$("html")[0].style.marginTop != '0px' ? parseFloat($$("html")[0].style.marginTop.replace('px', '')) : 0)) ]
							});
						});
						$('pane-'+g).style.display = 'block';
						/*
						$('pane-list').setStyle({
							backgroundImage: 'none',
						});
						*/
						if (Ajax.activeRequestCount < 0) {
							Ajax.activeRequestCount = 0;
						}
						hideWorkingNotification();
					}
				});	
			} else {
				$('pane-'+g).style.display = 'block';
			}
			prevPane = g;
			location.hash = '#pane='+prevPane;
			var form_action = '';
			for (f=0; f<document.forms.length; f++) {
				form_action = document.forms[f].action+'';
				form_action = (document.forms[f].action != '' ? form_action.substring(0,form_action.indexOf('#'))+location.hash: window.location);
				document.forms[f].setAttribute('action', form_action);
			}
		}
		//hideWorkingNotification();
	}
}

function selectLetter(g)
{
	if ($('letter-'+g)) {
		$('letter-'+g).setAttribute('class', 'letter letter-selected'); 
		$('letter-'+g).setAttribute('className', 'letter letter-selected'); 
	}
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
 * Show Comment Sharing UI
 */
var w1 = null;
function commentSharing()
{
	// Remove all actions 
    while ($('actions-list').firstChild)
    {
        $('actions-list').removeChild($('actions-list').firstChild);
    };

    //$(g).setAttribute('class', 'gadget-item gadget-selected'); 
    //$(g).setAttribute('className', 'gadget-item gadget-selected'); 
	if ($('share')) {
		$('share').style.display = 'none';
	}
	$('post-form').style.display = 'none';
	$('quick-form').style.display = '';
	var g = 'Sharing';
	if ($('add')) {
		$('add').style.display = 'none';
	}
	if (ifrm[prevGadget]) {
		ifrm[prevGadget].style.display = 'none';		
	}
	if (ifrm[g]) {
		ifrm[g].style.display = '';
	} else {
		ifrm[g] = document.createElement("IFRAME");
		ifrm[g].setAttribute('id', 'quick_add_'+g);
		ifrm[g].setAttribute('name', 'quick_add_'+g);
		ifrm[g].setAttribute("src", "index.php?gadget=Users&action=account_ShareComment");
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
		window.frames['quick_add_'+g].saveCommentSharing();
	}
	$('save').style.display = '';
}

/**
 * Set Comment Sharing
 */
function setCommentSharing(share)
{
	if (parent.$('sharing')) {
		if (typeof(share) == "undefined") {
			if (parent.$('sharing').value != '') {
				if (parent.$('sharing').value.substring(0, 6) == 'users:' || parent.$('sharing').value.substring(0, 7) == 'groups:') {
					share = 'specific';
				} else {
					share = parent.$('sharing').value;
				}
			} else {
				share = 'everyone';
			}
		}
		if ($('share-user')) {
			$('share-user').parentNode.removeChild($('share-user'));
		}
		if ($('share-syndication')) {
			$('share-syndication').parentNode.removeChild($('share-syndication'));
		}
		if ($('share-button')) {
			$('share-button').parentNode.removeChild($('share-button'));
		}
			
		$$('.gadget-item').each(function(element){element.setAttribute('class', 'gadget-item');element.setAttribute('className', 'gadget-item');});
		
		if ($(share)) {
			$(share).setAttribute('class', $(share).className + ' gadget-selected gadget-checked');
			$(share).setAttribute('className', $(share).className + ' gadget-selected gadget-checked');
		}
		
		//parent.w1.morph({height: 305});
		if (share == 'specific') {
			//parent.w1.morph({height: 305});
			if (parent.$('sharing').value.substring(0, 7) == 'groups:') {
				shareGroupsString = parent.$('sharing').value.replace('groups:', '').split(',');
			}
			if (parent.$('sharing').value.substring(0, 7) == 'users:') {
				shareUsersString = parent.$('sharing').value.replace('users:', '').split(',');
			}
			shareCounter = 0;
			var div = document.createElement('DIV');
			div.setAttribute('id', 'share-user');
			div.style.paddingTop = '5px';
			div.style.paddingLeft = '45px';
			div.style.height = '160px';
			div.style.width = '500px';
			div.style.maxHeight = '160px';
			div.style.maxWidth = '500px';
			div.style.overflow = 'auto';
			$('gadget-list').appendChild(div);
			if (parent.$('sharing').value == '' || parent.$('sharing').value == 'everyone' || parent.$('sharing').value == 'friends') {
				parent.$('sharing').value = '';
			}
			var keys = new Array();
			if (friendList === null) {
				friendList = usersSync.getfriendsofuser();
			}
			if (friendList) {
				keys['Users'] = new Array();
				keys['Users']['name'] = 'Users';
				keys['Users']['selectAll'] = true;
				keys['Users']['selectNone'] = true;
				friendList.each(function(value, index) {
					var value_checked = false;
					var value_id_string = value['id']+'';
					for (i=0;i<shareUsersString.length;i++) {
						if (value_id_string == shareUsersString[i]) {
							value_checked = true;
						}
					}
					keys['Users'][shareCounter] = new Array();
					keys['Users'][shareCounter]['desc'] = value['realname'];
					keys['Users'][shareCounter]['value'] = value_checked;
					keys['Users'][shareCounter]['name'] = 'share_user_'+value_id_string;
					shareCounter++;
				});
			}
			var groupList = usersSync.getgroupsofuser();
			if (groupList != false) {
				keys['Groups'] = new Array();
				keys['Groups']['name'] = 'Groups';
				keys['Groups']['selectAll'] = true;
				keys['Groups']['selectNone'] = true;
				groupList.each(function(value, index) {
					var value_checked = false;
					var value_id_string = value['id']+'';
					for (i=0;i<shareGroupsString.length;i++) {
						if (value_id_string == shareGroupsString[i]) {
							value_checked = true;
						}
					}
					keys['Groups'][shareCounter] = new Array();
					keys['Groups'][shareCounter]['desc'] = value['realname'];
					keys['Groups'][shareCounter]['value'] = value_checked;
					keys['Groups'][shareCounter]['name'] = 'share_group_'+value_id_string;
					shareCounter++;
				});
			}
			$('share-user').innerHTML = convertToTree(keys);	
		} else {
			if (parent.$('sharing').value == '') {
				parent.$('sharing').value = share;
			}
		
		/*
		} else {
			shareCounter = 0;
			var div = document.createElement('DIV');
			div.setAttribute('id', 'share-syndication');
			div.style.paddingTop = '5px';
			div.style.paddingLeft = '45px';
			div.style.height = '85px';
			div.style.maxHeight = '85px';
			div.style.overflow = 'auto';
			$('gadget-list').appendChild(div);
			var keys = new Array();
			socialList = usersSync.getsocialsharingofuser();
			if (socialList) {
				keys['Syndication'] = new Array();
				socialList.each(function(value, index) {
					keys['Syndication'][shareCounter] = new Array();
					keys['Syndication'][shareCounter]['desc'] = "Post to "+value['realname'];
					keys['Syndication'][shareCounter]['value'] = false;
					keys['Syndication'][shareCounter]['name'] = 'share_syndication_'+value['id'];
					shareCounter++;
				});
				$('share-syndication').innerHTML = convertToTree(keys);	
			}
			emailList = usersSync.getemailsharingofuser();
			emailCounter = 0;
			if (emailList) {
				var keys = new Array();
				prevPermissionsMsg = permissionsMsg;
				permissionsMsg = "Send E-mail to Contacts:";
				keys['Email'] = new Array();
				socialList.each(function(value, index) {
					keys['Email'][emailCounter] = new Array();
					keys['Email'][emailCounter]['desc'] = value['realname'];
					keys['Email'][emailCounter]['value'] = false;
					keys['Email'][emailCounter]['name'] = 'share_email_'+value['id'];
					emailCounter++;
				});
				$('share-syndication').innerHTML = $('share-syndication').innerHTML + convertToTree(keys);	
				permissionsMsg = prevPermissionsMsg;
			} else {
				input = addShareEmail('share-syndication');
				$('share-syndication').appendChild(input);
			}
			parent.$('sharing').value = share;
		*/
		}
	}
	return false;
}

/**
 * Add E-mail input to share
 */
function addShareEmail(parent)
{
	var input = document.createElement('INPUT');
	input.setAttribute('name', 'add_email_'+emailCounter);
	input.setAttribute('value', "Add Another E-mail");
	input.defaultValue = "Add Another E-mail";
	input.onfocus = function() {
		if(this.defaultValue==this.value){
			this.value='';
			this.style.color='#000000';
		}else if(this.value==''){
			this.value=this.defaultValue;
			this.style.color='#999999';
		}
	};
	input.onblur = function() {
		if(this.defaultValue==this.value){
			this.value='';
			this.style.color='#000000';
		}else if(this.value==''){
			this.value=this.defaultValue;
			this.style.color='#999999';
		}
	};
	input.style.color = '#999999';
	input.style.minWidth = '180px';
	input.style.marginLeft = '5px';
	emailCounter++;
	input.onclick = function() {
		new_input = addShareEmail(parent);
		$(parent).appendChild(new_input);
	};
	return input;
}

/**
 * Share Comment with user
 */
function saveCommentSharing()
{
	//parent.showWorkingNotification();
	if (parent.$('sharing')) {
		if (parent.$('sharing').value == 'everyone' || parent.$('sharing').value == 'friends') {	
			/*
			var inputs = $('share-syndication').getElementsByTagName('input');
			shareCounter = 0;
			parent.$('syndication').value = '';
			for (var i=0; i<inputs.length; i++) {
				if (inputs[i].value.indexOf('share_syndication') >= 0) {
					if (inputs[i].checked) {
						parent.$('syndication').value += (shareCounter > 0 ? ','+inputs[i].value.replace('share_syndication_', '') : inputs[i].value.replace('share_syndication_', ''));
						shareCounter++;
					} 
				} else {
					continue;
				}
			}
			var inputs = $('share-syndication').getElementsByTagName('input');
			shareCounter = 0;
			parent.$('email').value = '';
			for (var i=0; i<inputs.length; i++) {
				if (inputs[i].value.indexOf('share_email') >= 0) {
					if (inputs[i].checked) {
						email = inputs[i].value.replace('share_email_', '');
						email = email.replace('__DOT__', '.');
						email = email.replace('__AT__', '@');
						parent.$('email').value += (shareCounter > 0 ? ','+email : email);
						shareCounter++;
					} 
				} else {
					continue;
				}
			}
			*/
		} else if (parent.$('sharing').value != 'friends') {
			parent.$('sharing').value = '';
			sharingUsers = 'users:';
			sharingGroups = 'groups:';
			var inputs = $('share-user').getElementsByTagName('input');
			shareCounter = 0;
			for (var i=0; i<inputs.length; i++) {
				if (inputs[i].value.indexOf('share_user') >= 0) {
					if (inputs[i].checked) {
						sharingUsers += (shareCounter > 0 ? ','+inputs[i].value.replace('share_user_', '') : inputs[i].value.replace('share_user_', ''));
						shareCounter++;
					} 
				} else if (inputs[i].value.indexOf('share_group') >= 0) {
					if (inputs[i].checked) {
						sharingUsers = '';
						sharingGroups += (shareCounter > 0 ? ','+inputs[i].value.replace('share_group_', '') : inputs[i].value.replace('share_group_', ''));
						shareCounter++;
						/*
						var userGroupList = usersSync.getusersofgroup(inputs[i].value.replace('share_group_', ''));
						if (userGroupList != false) {
							userGroupList.each(function(value, index) {
								parent.$('sharing').value += (shareCounter > 0 ? ','+value['id'] : value['id']);
								shareCounter++;
							});
						}
						*/
					}		
				} else {
					continue;
				}
			}
			parent.$('sharing').value = sharingUsers+sharingGroups;
			if (shareCounter == 0) {
				parent.$('sharing').value = 'everyone';
			}
		}
	}
	parent.selectGadget(parent.prevGadget, parent.prevMethod, parent.prevId, parent.prevLinkid, parent.prevSectionid, parent.prevCallback, parent.prevCustom_function, parent.prevQuery);
	if (parent.$('share')) {
		parent.$('share').style.display = '';
	}
	//parent.hideWorkingNotification();
	//parent.w1.destroy();
	return false;
}

/**
 * Show Full Merchant Description
 */
function toggleFullMerchantDescription()
{
	if ($('merchant-desc-preview')) {
		if ($('merchant-desc-preview').style.display == 'none') {
			$('merchant-desc-preview').style.display = 'inline';
		} else {
			$('merchant-desc-preview').style.display = 'none';
		}
    }
    if ($('merchant-desc-full')) {
		if ($('merchant-desc-full').style.display == 'none') {
			$('merchant-desc-full').style.display = 'inline';
		} else {
			$('merchant-desc-full').style.display = 'none';
		}
		/*
		w1 = new UI.Window({
		  theme: 'simpleblue',
		  height: 350,
		  width: 600,
		  shadow: true,
		  minimize: false,
		  maximize: false,
		  close: 'destroy',
		  resizable: false,
		  draggable: true
		});
		w1.setContent('<div style="text-align: left; padding: 20px;" class="merchant-description">'+$('merchant-desc-full').innerHTML+'</div>');
		w1.setZIndex(2147483647);
		//w1.adapt.bind(w1).delay(0.3);
		w1.show().focus();
		w1.center();
		*/
    }
}

/**
 * Show Full Update
 */
function toggleFullUpdate(id)
{
    if ($('news-preview-'+id)) {
		if ($('news-preview-'+id).style.display == 'none') {
			$('news-preview-'+id).style.display = 'block';
		} else {
			$('news-preview-'+id).style.display = 'none';
		}
    }
    if ($('news-full-'+id)) {
		if ($('news-full-'+id).style.display == 'none') {
			$('news-full-'+id).style.display = 'block';
		} else {
			$('news-full-'+id).style.display = 'none';
		}
    }
}

/**
 * Show Update Form
 */
function showUpdateForm()
{
	//showWorkingNotification();
    $$('#Users-accountNews .update-holder')[0].style.display = 'none';
    $$('#Users-accountNews .update-buttons')[0].style.display = 'block';
    $$('#Users-accountNews .update-area')[0].style.display = 'block';
	$$('#Users-accountNews .update-entry')[0].focus();	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideUpdateForm()
{
	//showWorkingNotification();
    $$('#Users-accountNews .update-holder')[0].style.display = 'block';
    $$('#Users-accountNews .update-buttons')[0].style.display = 'none';
    $$('#Users-accountNews .update-area')[0].style.display = 'none';
	$$('#Users-accountNews .update-entry')[0].value = '';	
	//hideWorkingNotification();
}

/**
 * Saves an Update
 */
function saveUpdate(id, comment, title, parent, sharing, syndication, OwnerID, gadget, auto, save, permalink, mail, callback)
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
    if (typeof(OwnerID) == "undefined" || OwnerID === null) {
		OwnerID = '';
	}
    if (typeof(syndication) == "undefined" || syndication === null) {
		syndication = false;
		if ($('syndication')) {
			syndication = $('syndication').checked;
		}
	}
    if (typeof(gadget) == "undefined") {
		gadget = 'Users';
	}
    if (typeof(auto) == "undefined") {
		auto = false;
	}
    if (typeof(save) == "undefined") {
		save = true;
	}
    if (typeof(permalink) == "undefined") {
		permalink = '';
	}
    if (typeof(mail) == "undefined") {
		mail = true;
	}
    if (typeof(callback) == "undefined" || callback == '') {
		callback = null;
	}
    if (typeof(prevPane) == "undefined" || prevPane == '') {
		prevPane = 'Users';
	}
	if (comment.length <= 0) {
		if (
			$$('#Users-accountNews #Users-update-entry .update-entry') && 
			$$('#Users-accountNews #Users-update-entry .update-entry')[0] && 
			$$('#Users-accountNews #Users-update-entry .update-entry')[0].value.length > 0
		) {
			comment = $$('#Users-accountNews #Users-update-entry .update-entry')[0].value;
			$$('#Users-accountNews #Users-update-entry .update-entry')[0].value = '';
		}
	}
	response = usersSync.newcomment(title, comment, parent, id, OwnerID, '', false, sharing, gadget, auto, save, permalink, mail);
	if (response['css'] == 'notice-message') {
		/*
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteComment('+response['id']+', \'update\');">X</a></div>';
		news_items_html += '	<div class="news-image">'+response['image']+'</div>';
		news_items_html += '	<div class="news-body">';
		news_items_html += '		<div class="news-title">'+response['title']+'</div>';
		news_items_html += '		<div class="news-info"><span class="news-name">'+(response['link'] != '' ? '<a href="'+response['link']+'">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'<span class="news-preactivity">'+(typeof(response['preactivity']) != "undefined" ? response['preactivity'] : '')+'</span></span>&nbsp;';
		news_items_html += '		<div class="news-preview" id="news-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</div>';
		news_items_html += '		<div class="news-message" id="news-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</div></div>';
		news_items_html += '		<div class="news-created news-timestamp">'+response['created']+response['activity']+'</div>';
		news_items_html += '		<div class="news-comments" id="news-comments-'+response['id']+'">';
		news_items_html += '		</div>';
		news_items_html += '		<div class="comments-form">';
		news_items_html += '			<div class="comment-holder" id="comment-holder-'+response['id']+'">';
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		if ($('news-'+gadget+'-items')) {
			$('news-'+gadget+'-items').innerHTML = news_items_html + $('news-'+gadget+'-items').innerHTML;
		}
		if ($(prevPane+'-news-items')) {
			$(prevPane+'-news-items').innerHTML = news_items_html + $(prevPane+'-news-items').innerHTML;
		}
		*/
		//$('news-items').innerHTML = news_items_html + $('news-items').innerHTML;
		if (syndication === true) {
			var rpx_comment = "posted on "+window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
			if (response['comment'] != '') {
				rpx_comment = "posted "+'"'+response['comment']+'"';
			}
			
			var permalink = response['permalink'];
			var title = window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
			if (response['title'] != '') {
				title = response['title'];
			}
			syndicateRPX(rpx_comment, permalink, title);
		}
		if ($$('#'+prevPane+'-accountNews .news-items .simple-response-msg')[0]) {
			$$('#'+prevPane+'-accountNews .news-items .simple-response-msg')[0].style.display = 'none';
		}
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}	
	if (callback !== null) {
		callback();
	}
	if ($('shareButton')) {
		window.location.reload();
	}
	if ($('users-directory')) {
		window.location.href = 'index.php?gadget=Users&action=DefaultAction';
	}
	//hideUpdateForm();
	//hideWorkingNotification();
}

/**
 * Saves a status Update
 */
function saveStatus(id, comment, title, parent, sharing, syndication, OwnerID)
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
    if (typeof(OwnerID) == "undefined") {
		OwnerID = '';
	}
    if (typeof(syndication) == "undefined") {
		syndication = false;
		if ($('syndication')) {
			syndication = $('syndication').checked;
		}
	}
	if (comment.length <= 0) {
		if ($('update-entry') && $('update-entry').value.length > 0) {
			comment = $('update-entry').value;
			$('update-entry').value = '';
		}
	}
	response = usersSync.newstatus(title, comment, parent, id, OwnerID, '', false, sharing);
	if (response['css'] == 'notice-message') {
		/*
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteComment('+response['id']+', \'update\');">X</a></div>';
		news_items_html += '	<div class="news-image">'+response['image']+'</div>';
		news_items_html += '	<div class="news-body">';
		news_items_html += '		<div class="news-title">'+response['title']+'</div>';
		news_items_html += '		<div class="news-info"><span class="news-name">'+(response['link'] != '' ? '<a href="'+response['link']+'">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'<span class="news-preactivity">'+(typeof(response['preactivity']) != "undefined" ? response['preactivity'] : '')+'</span></span>&nbsp;';
		news_items_html += '		<span class="news-preview" id="news-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="news-message" id="news-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span></div>';
		news_items_html += '		<div class="news-created news-timestamp">'+response['created']+response['activity']+'</div>';
		news_items_html += '		<div class="news-comments" id="news-comments-'+response['id']+'">';
		news_items_html += '		</div>';
		news_items_html += '		<div class="comments-form">';
		news_items_html += '			<div class="comment-holder" id="comment-holder-'+response['id']+'">';
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		if ($(prevPane+'-news-items')) {
			$(prevPane+'-news-items').innerHTML = news_items_html + $(prevPane+'-news-items').innerHTML;
		}
		*/
		//$('news-items').innerHTML = news_items_html + $('news-items').innerHTML;
		
		if (syndication === true) {
			var rpx_comment = "posted on "+window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
			if (response['comment'] != '') {
				rpx_comment = "posted "+'"'+response['comment']+'"';
			}
			
			var permalink = response['permalink'];
			var title = window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
			if (response['title'] != '') {
				title = response['title'];
			}
			syndicateRPX(rpx_comment, permalink, title);
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
 * Saves a status Update
 */
function savePhoto(
	id, comment, title, parent, sharing, syndication, OwnerID, image, 
	url_type, internal_url, external_url, url_target, gadget
) {
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
    if (typeof(OwnerID) == "undefined") {
		OwnerID = '';
	}
    if (typeof(image) == "undefined") {
		image = '';
	}
    if (typeof(url_type) == "undefined") {
		url_type = 'imageviewer';
	}
    if (typeof(internal_url) == "undefined") {
		internal_url = 'javascript:void(0);';
	}
    if (typeof(external_url) == "undefined") {
		external_url = '';
	}
    if (typeof(url_target) == "undefined") {
		url_target = '_self';
	}
    if (typeof(gadget) == "undefined") {
		gadget = 'Users';
	}
    if (typeof(syndication) == "undefined") {
		syndication = false;
		if ($('syndication')) {
			syndication = $('syndication').checked;
		}
	}
	if (comment.length <= 0) {
		if ($('update-entry') && $('update-entry').value.length > 0) {
			comment = $('update-entry').value;
			$('update-entry').value = '';
		}
	}
	response = usersSync.newphoto(
		title, comment, parent, id, OwnerID, '', false, sharing, 
		image, url_type, internal_url, external_url, url_target, gadget
	);
	if (response['css'] == 'notice-message') {
		/*
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteComment('+response['id']+', \'update\');">X</a></div>';
		news_items_html += '	<div class="news-image">'+response['image']+'</div>';
		news_items_html += '	<div class="news-body">';
		news_items_html += '		<div class="news-title">'+response['title']+'</div>';
		news_items_html += '		<div class="news-info"><span class="news-name">'+(response['link'] != '' ? '<a href="'+response['link']+'">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'<span class="news-preactivity">'+(typeof(response['preactivity']) != "undefined" ? response['preactivity'] : '')+'</span></span>&nbsp;';
		news_items_html += '		<span class="news-preview" id="news-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="news-message" id="news-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span></div>';
		news_items_html += '		<div class="news-created news-timestamp">'+response['created']+response['activity']+'</div>';
		news_items_html += '		<div class="news-comments" id="news-comments-'+response['id']+'">';
		news_items_html += '		</div>';
		news_items_html += '		<div class="comments-form">';
		news_items_html += '			<div class="comment-holder" id="comment-holder-'+response['id']+'">';
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		if ($(prevPane+'-news-items')) {
			$(prevPane+'-news-items').innerHTML = news_items_html + $(prevPane+'-news-items').innerHTML;
		}
		*/
		//$('news-items').innerHTML = news_items_html + $('news-items').innerHTML;
		
		if (syndication === true) {
			var rpx_comment = "posted on "+window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
			if (response['comment'] != '') {
				rpx_comment = "posted "+'"'+response['comment']+'"';
			}
			
			var permalink = response['permalink'];
			var title = window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
			if (response['title'] != '') {
				title = response['title'];
			}
			syndicateRPX(rpx_comment, permalink, title);
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
function toggleAllComments(cid)
{
	$$('.comment-hidden-'+cid).each(function(element){element.style.display = 'block';});
    if ($('all-comments-'+cid)) {
		$('all-comments-'+cid).style.display = 'none';
	}
}

/**
 * Show Full Comment
 */
function toggleFullComment(cid)
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
function showCommentForm(cid)
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
		hideCommentForm();
	};
	
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideCommentForm(cid)
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
function saveReply(parent, id, parentname)
{
    var writeIt = true;
	if (typeof(parentname) == "undefined") {
		parentname = parent;
		writeIt = false;
	}
    if ($('comment-entry-'+parentname) && $('comment-entry-'+parentname).value.length > 0) {
		comment = $('comment-entry-'+parentname).value;
		$('comment-entry-'+parentname).value = '';
	}
	response = usersSync.newcomment('', comment, parent, id, id, '', false);
	if (response['css'] == 'notice-message') {
		if (writeIt === true && $('comment-'+response['id']) == undefined) {
			if ($('news-'+parentname) && $('news-'+parentname).down('.total-comments')) {
				var comments_total = $('news-'+parentname).down('.total-comments').innerHTML;
				$('news-'+parentname).down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)+1) + " comments";
			}
			news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
			news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
			news_comments_html += response['image']+'<div class="comment-body"><span class="comment-name">'+(response['link'] != '' ? '<a href="'+response['link']+'" class="comment-name">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'</span>&nbsp;<span class="comment-preview" id="comment-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="comment-message" id="comment-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span>';
			news_comments_html += '</div><div class="comment-created news-timestamp">'+response['created']+'</div>';
			news_comments_html += '</div>';
			$('news-comments-'+parentname).innerHTML = $('news-comments-'+parentname).innerHTML + news_comments_html;
		}
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
	hideCommentForm(parentname);
    if ($('all-comments-'+parentname)) {
		$('all-comments-'+parentname).innerHTML = '<a href="javascript:void(0);" onclick="toggleAllComments('+response['id']+');">View all comments</a>';
	}
}

/**
 * Delete Comment
 */
function DeleteComment(parent, type, parentname)
{
    if (typeof(parentname) == "undefined") {
		parentname = parent;
	}
    if (typeof(type) == "undefined") {
		type = 'update';
	}
    if (typeof(confirmCommentDelete) == "undefined") {
		confirmCommentDelete = "Are you sure you want to delete this?";
	}
	var answer = confirm(confirmCommentDelete);
    if (answer) {
		//showWorkingNotification();
		var response = usersSync.deletecomment(parent);
		if (response[0]['css'] == 'notice-message') {
			if (type == 'update' && $('news-'+parentname)) {
				$('news-'+parentname).parentNode.removeChild($('news-'+parentname));
			} else if (type == 'reply' && $('comment-'+parentname)) {
				if ($('comment-'+parentname).up('.news-body').down('.total-comments')) {
					var comments_total = $('comment-'+parentname).up('.news-body').down('.total-comments').innerHTML;
					$('comment-'+parentname).up('.news-body').down('.total-comments').innerHTML = (parseInt(comments_total.replace(" comments", ''), 10)-1) + " comments";
				}
				$('comment-'+parentname).parentNode.removeChild($('comment-'+parentname));
			}
		}
		//hideWorkingNotification();
		//showResponse(response);
    }
}

/**
 * TODO: Add sort options from URL querystrings
 * Shows more messages
 */
function showMoreComments(gadget, public_items, id, interactive, method)
{
	//showWorkingNotification();
	var more_old_html = '';
	if ($(gadget+'-more-items')) {
		more_old_html = $(gadget+'-more-items').innerHTML;
		$(gadget+'-more-items').innerHTML = '<img src="../../../images/loading.gif" border="0" align="left" style="padding-right: 5px;" />' + more_old_html;
	}
    if (typeof(gadget) == "undefined") {
		gadget = 'Users';
	}
    if (typeof(public_items) == "undefined") {
		public_items = false;
	}
    if (typeof(id) == "undefined") {
		id = null;
	}
    if (typeof(interactive) == "undefined") {
		interactive = true;
	}
	/*
	if (window.location.hash.indexOf('#pane=') > -1) {
		default_pane = window.location.hash.replace('#pane=','');
	}
	*/
	var temp_messages = '';
	var i = 0;
	if ($(gadget+'-news-items')) {
		$$('#'+gadget+'-news-items .news-item').each(function(element){
			temp_messages += (temp_messages != '' ? ',' : '')+'_'+element.id.replace('news-', '');
			i++;
		});
	}
	if (typeof(total_messages[gadget]) == "undefined") {
		total_messages[gadget] = i;
	}
	if (typeof(messages_limit[gadget]) == "undefined") {
		messages_limit[gadget] = (5*i);
	}
	temp_messages += (temp_messages != '' ? ',' : '')+'_total'+gadget+'_'+(messages_limit[gadget]);
	if (typeof(messages_on_layout[gadget]) == "undefined") {
		messages_on_layout[gadget] = temp_messages;
	}
	
	response = usersSync.showmorecomments(gadget, messages_on_layout[gadget], public_items, id, interactive, method, (messages_limit[gadget]+5));
	if (response['css'] == 'notice-message') {
		if ($(gadget+'-news-items')) {
			$(gadget+'-news-items').innerHTML = $(gadget+'-news-items').innerHTML + response['comments_html'];
		}
		if ($(gadget+'-more-items')) {
			$(gadget+'-more-items').innerHTML = more_old_html;
			if (response['items_on_layout'] == messages_on_layout[gadget]) {
				$(gadget+'-more-items').style.display = 'none';
			}
		}
		messages_on_layout[gadget] = response['items_on_layout'];
		messages_limit[gadget] = response['items_limit'];
		$$(".menu_li_item").each(function(element){
			checkSubMenus(element);
			Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\""+$(element).down(".ul_sub_menu").className+"\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : ""), {
				className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
				showOn: "mouseover",
				hideTrigger: "tip",
				hideOn: "mouseout",
				stem: false,
				delay: false,
				tipJoint: [ "center", "top" ],
				target: element,
				showEffect: "appear",
				offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != '' && $$("html")[0].style.marginTop != '0px' ? parseFloat($$("html")[0].style.marginTop.replace('px', '')) : 0)) ]
			});
		});
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
}

/**
 * TODO: Add sort options from URL querystrings
 * Shows more messages
 */
function showMoreRecommendations(gadget, public_items, id, method)
{
	//showWorkingNotification();
	var more_old_html = '';
	if ($(gadget+'-more-recommendations')) {
		more_old_html = $(gadget+'-more-recommendations').innerHTML;
		$(gadget+'-more-recommendations').innerHTML = '<img src="../../../images/loading.gif" border="0" align="left" style="padding-right: 5px;" />' + more_old_html;
	}
    if (typeof(gadget) == "undefined") {
		gadget = 'Users';
	}
    if (typeof(public_items) == "undefined") {
		public_items = false;
	}
    if (typeof(id) == "undefined") {
		id = null;
	}
    if (typeof(method) == "undefined") {
		method = 'GetRecommendations';
	}
	/*
	if (window.location.hash.indexOf('#pane=') > -1) {
		default_pane = window.location.hash.replace('#pane=','');
	}
	*/
	response = usersSync.showmorerecommendations(gadget, recommendations_on_layout[gadget], public_items, id, method, (recommendations_limit[gadget]+5));
	if (response['css'] == 'notice-message') {
		if ($(gadget+'-recommendations-items')) {
			$(gadget+'-recommendations-items').innerHTML = $(gadget+'-recommendations-items').innerHTML + response['recommendations_html'];
		}
		if ($(gadget+'-more-recommendations')) {
			$(gadget+'-more-recommendations').innerHTML = more_old_html;
			if (response['items_on_layout'] == recommendations_on_layout[gadget]) {
				$(gadget+'-more-recommendations').style.display = 'none';
			}
		}
		recommendations_on_layout[gadget] = response['items_on_layout'];
		recommendations_limit[gadget] = response['items_limit'];
		$$(".menu_li_item").each(function(element){
			checkSubMenus(element);
			Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\""+$(element).down(".ul_sub_menu").className+"\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : ""), {
				className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
				showOn: "mouseover",
				hideTrigger: "tip",
				hideOn: "mouseout",
				stem: false,
				delay: false,
				tipJoint: [ "center", "top" ],
				target: element,
				showEffect: "appear",
				offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != '' && $$("html")[0].style.marginTop != '0px' ? parseFloat($$("html")[0].style.marginTop.replace('px', '')) : 0)) ]
			});
		});
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
}

/**
 * Shows more messages
 */
function showMoreFriends(gid, public_items, limit, offSet, uid, mode, searchkeyword, searchfilters, searchhoods, searchletter)
{
	//showWorkingNotification();
    if (typeof(gid) == "undefined") {
		gid = null;
	}
    if (typeof(public_items) == "undefined") {
		public_items = true;
	}
    if (typeof(uid) == "undefined") {
		uid = null;
	}
    if (typeof(mode) == "undefined") {
		mode = 'directory';
	}
    if (typeof(limit) == "undefined") {
		limit = null;
	}
    if (typeof(offSet) == "undefined") {
		offSet = null;
	}
    if (typeof(searchkeyword) == "undefined") {
		searchkeyword = '';
	}
    if (typeof(searchfilters) == "undefined") {
		searchfilters = '';
	}
    if (typeof(searchhoods) == "undefined") {
		searchhoods = '';
	}
    if (typeof(searchletter) == "undefined") {
		searchletter = '';
	}
	var more_old_html = '';
	var gadget = 'Friends';
	var temp_messages = '';
	var i = 0;
	switch (mode) {
		case 'dropdown':
			gadget = 'FriendsDropdown';
			if (typeof(messages_on_layout[gadget]) == "undefined") {
				messages_on_layout[gadget] = new Array();
			} else if (typeof(messages_on_layout[gadget][uid]) != "undefined" && $$('.user_sub_menu_'+uid)[0]) {
				$$('.user_sub_menu_'+uid).each(function(element){
					if (!element.innerHTML.indexOf(messages_on_layout[gadget]) > (-1)) {
						element.innerHTML = element.innerHTML + messages_on_layout[gadget];
					}
				});
				return true;
			}
			if ($('user-menu-'+uid)) {
				$$('#user-menu-'+uid+' .user-menu-'+uid).each(function(element){
					temp_messages += (temp_messages != '' ? ',' : '')+'_'+element.id.replace('user-menu-', '');
				});
			}
			messages_on_layout[gadget][uid] = '';
			if (typeof(total_messages[gadget]) == "undefined") {
				total_messages[gadget] = new Array();
			}
			total_messages[gadget][uid] = offSet;
			if (typeof(messages_limit[gadget]) == "undefined") {
				messages_limit[gadget] = new Array();
			}
			messages_limit[gadget][uid] = limit;
			response = usersSync.showmorefriends(
				public_items, gid, messages_limit[gadget][uid], total_messages[gadget][uid], 
				uid, mode, searchkeyword, searchfilters, searchhoods, searchletter
			);
			break;
		default:
			if ($(gadget+'-more-items')) {
				more_old_html = $(gadget+'-more-items').innerHTML;
				$(gadget+'-more-items').innerHTML = '<img src="../../../images/loading.gif" border="0" align="left" style="padding-right: 5px;" />' + more_old_html;
			}
			if ($('users-directory') && $$('#users-directory .user-item') && $$('#users-directory .user-item')[0]) {
				$$('#users-directory .user-item').each(function(element){
					temp_messages += (temp_messages != '' ? ',' : '')+'_'+element.id.replace('user-', '');
					i++;
				});
			}
			if (typeof(total_messages[gadget]) == "undefined") {
				total_messages[gadget] = offSet;
			}
			if (typeof(messages_limit[gadget]) == "undefined") {
				messages_limit[gadget] = limit;
			}
			temp_messages += (temp_messages != '' ? ',' : '')+'_total'+gadget+'_'+(messages_limit[gadget]);
			if (typeof(messages_on_layout[gadget]) == "undefined") {
				messages_on_layout[gadget] = temp_messages;
			}
			response = usersSync.showmorefriends(
				public_items, gid, messages_limit[gadget], (total_messages[gadget]+limit), 
				uid, mode, searchkeyword, searchfilters, searchhoods, searchletter
			);
			break;
	}
	/*
	if (window.location.hash.indexOf('#pane=') > -1) {
		default_pane = window.location.hash.replace('#pane=','');
	}
	*/
	if (response['css'] == 'notice-message') {
		switch (mode) {
			case 'dropdown':
				if ($$('.user_sub_menu_'+uid)[0]) {
					$$('.user_sub_menu_'+uid).each(function(element){
						element.innerHTML = element.innerHTML + response['friends_html'];
					});
				}
				total_messages[gadget][uid] = limit;
				messages_on_layout[gadget][uid] = response['friends_html'];
				messages_limit[gadget][uid] = response['items_limit'];
				break;
			default:
				if ($(gadget+'-more-items')) {
					$(gadget+'-more-items').innerHTML = more_old_html;
					$$('#'+gadget+'-more-items a').each(function(element){
						element.onclick = function() {
							showMoreFriends(gid, public_items, limit, (total_messages[gadget]+limit), uid, 'directory', searchkeyword, searchfilters, searchhoods, searchletter)
						};
					});
					if (response['items_on_layout'] == messages_on_layout[gadget]) {
						$(gadget+'-more-items').style.display = 'none';
					}
				}
				if ($('users-items')) {
					$('users-items').innerHTML = $('users-items').innerHTML + response['friends_html'];
				}
				$$(".menu_li_item").each(function(element){
					checkSubMenus(element);
					Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\""+$(element).down(".ul_sub_menu").className+"\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : ""), {
						className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
						showOn: "mouseover",
						hideTrigger: "tip",
						hideOn: "mouseout",
						stem: false,
						delay: false,
						tipJoint: [ "center", "top" ],
						target: element,
						showEffect: "appear",
						offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != '' && $$("html")[0].style.marginTop != '0px' ? parseFloat($$("html")[0].style.marginTop.replace('px', '')) : 0)) ]
					});
				});
				messages_on_layout[gadget] = response['items_on_layout'];
				messages_limit[gadget] = response['items_limit'];
				break;
		}
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
}

/**
 * Shows more groups
 */
function showMoreGroups(public_items, limit, offSet, uid, mode, searchkeyword, searchfilters, searchhoods, searchletter)
{
	//showWorkingNotification();
    if (typeof(public_items) == "undefined") {
		public_items = true;
	}
    if (typeof(uid) == "undefined") {
		uid = null;
	}
    if (typeof(mode) == "undefined") {
		mode = 'directory';
	}
    if (typeof(limit) == "undefined") {
		limit = null;
	}
    if (typeof(offSet) == "undefined") {
		offSet = 0;
	}
    if (typeof(searchkeyword) == "undefined") {
		searchkeyword = '';
	}
    if (typeof(searchfilters) == "undefined") {
		searchfilters = '';
	}
    if (typeof(searchhoods) == "undefined") {
		searchhoods = '';
	}
    if (typeof(searchletter) == "undefined") {
		searchletter = '';
	}
	var more_old_html = '';
	var gadget = 'Groups';
	var temp_messages = '';
	var i = 0;
	switch (mode) {
		case 'dropdown':
			gadget = 'GroupsDropdown';
			if (typeof(messages_on_layout[gadget]) == "undefined") {
				messages_on_layout[gadget] = new Array();
			} if (typeof(messages_on_layout[gadget][uid]) != "undefined" && $$('.user_sub_menu_'+uid)[0]) {
				$$('.user_sub_menu_'+uid).each(function(element){
					if (!element.innerHTML.indexOf(messages_on_layout[gadget]) > (-1)) {
						element.innerHTML = element.innerHTML + messages_on_layout[gadget];
					}
				});
				return true;
			}
			if ($('user-menu-'+uid)) {
				$$('#user-menu-'+uid+' .group-menu-'+uid).each(function(element){
					temp_messages += (temp_messages != '' ? ',' : '')+'_'+element.id.replace('group-menu-', '');
				});
			}
			messages_on_layout[gadget][uid] = '';
			if (typeof(total_messages[gadget]) == "undefined") {
				total_messages[gadget] = new Array();
			}
			total_messages[gadget][uid] = offSet;
			if (typeof(messages_limit[gadget]) == "undefined") {
				messages_limit[gadget] = new Array();
			}
			messages_limit[gadget][uid] = limit;
			response = usersSync.showmoregroups(
				public_items, messages_limit[gadget][uid], total_messages[gadget][uid], 
				uid, mode, searchkeyword, searchfilters, searchhoods, searchletter
			);
			break;
		default:
			if ($(gadget+'-more-items')) {
				more_old_html = $(gadget+'-more-items').innerHTML;
				$(gadget+'-more-items').innerHTML = '<img src="../../../images/loading.gif" border="0" align="left" style="padding-right: 5px;" />' + more_old_html;
			}
			if ($('groups-directory') && $$('#groups-directory .user-item') && $$('#groups-directory .user-item')[0]) {
				$$('#groups-directory .user-item').each(function(element){
					temp_messages += (temp_messages != '' ? ',' : '')+'_'+element.id.replace('group-', '');
					i++;
				});
			}
			if (typeof(total_messages[gadget]) == "undefined") {
				total_messages[gadget] = offSet;
			}
			if (typeof(messages_limit[gadget]) == "undefined") {
				messages_limit[gadget] = limit;
			}
			temp_messages += (temp_messages != '' ? ',' : '')+'_total'+gadget+'_'+(messages_limit[gadget]);
			if (typeof(messages_on_layout[gadget]) == "undefined") {
				messages_on_layout[gadget] = temp_messages;
			}
			response = usersSync.showmoregroups(
				public_items, messages_limit[gadget], (total_messages[gadget]+limit), 
				uid, mode, searchkeyword, searchfilters, searchhoods, searchletter
			);
			break;
	}
	/*
	if (window.location.hash.indexOf('#pane=') > -1) {
		default_pane = window.location.hash.replace('#pane=','');
	}
	*/
	if (response['css'] == 'notice-message') {
		switch (mode) {
			case 'dropdown':
				if ($$('.user_sub_menu_'+uid)[0]) {
					$$('.user_sub_menu_'+uid).each(function(element){
						element.innerHTML = element.innerHTML + response['groups_html'];
					});
				}
				total_messages[gadget][uid] = limit;
				messages_on_layout[gadget][uid] = response['groups_html'];
				messages_limit[gadget][uid] = response['items_limit'];
				break;
			default:
				if ($(gadget+'-more-items')) {
					$(gadget+'-more-items').innerHTML = more_old_html;
					$$('#'+gadget+'-more-items a').each(function(element){
						element.onclick = function() {
							showMoreGroups(public_items, limit, (total_messages[gadget]+limit), uid, 'directory', searchkeyword, searchfilters, searchhoods, searchletter)
						};
					});
					if (response['items_on_layout'] == messages_on_layout[gadget]) {
						$(gadget+'-more-items').style.display = 'none';
					}
				}
				if ($('groups-items')) {
					$('groups-items').innerHTML = $('groups-items').innerHTML + response['groups_html'];
				}
				$$(".menu_li_item").each(function(element){
					checkSubMenus(element);
					Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\""+$(element).down(".ul_sub_menu").className+"\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : ""), {
						className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
						showOn: "mouseover",
						hideTrigger: "tip",
						hideOn: "mouseout",
						stem: false,
						delay: false,
						tipJoint: [ "center", "top" ],
						target: element,
						showEffect: "appear",
						offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != '' && $$("html")[0].style.marginTop != '0px' ? parseFloat($$("html")[0].style.marginTop.replace('px', '')) : 0)) ]
					});
				});
				messages_on_layout[gadget] = response['items_on_layout'];
				messages_limit[gadget] = response['items_limit'];
				break;
		}
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
}

/**
 * Syndicate messages to RPX
 */
function syndicateRPX(comment, permalink, title)
{
	RPXNOW.loadAndRun(['Social'], function () {
		var rpx_comment = comment;
		var activity = new RPXNOW.Social.Activity(
		   "Share this",
		   rpx_comment,
		   permalink
		);
		if (typeof(title) == "undefined" || title == '') {
			activity.setTitle(window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length));
		} else {
			activity.setTitle(title);
		}
		//activity.setUserGeneratedContent("posted on");
		RPXNOW.Social.publishActivity(activity);
	});
}

/**
 * Parses first URL from given text
 */
function getFirstUrlInText(text) {
    //var urlRegex = /(https?:\/\/[^\s]+)/i;
    var urlRegex = /(\b(https?):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i;
    var result = text.match(urlRegex, function(url) {
		return url;
    });
	if (result && typeof(result[0]) != "undefined") {
		return result[0];
	} else {
		return '';
	}
    // or alternatively
    // return text.replace(urlRegex, '<a href="$1">$1</a>');
}

/**
 * Returns HTML string with info of URL in given text
 */
function createUpdatePreview(text)
{
	showWorkingNotification();
	// Remove all actions 
	if ($('update-preview')) {
		$('update-preview').style.display = 'none';
		while ($('update-preview').firstChild)
		{
			$('update-preview').removeChild($('update-preview').firstChild);
		};
		if (text.length > 0) {
			var url = getFirstUrlInText(text);
			if (url.length > 0) {
				var result = usersSync.geturlinfo(url);
				if (result) {
					if (result['message'] == "User not logged in.") {
						parent.parent.window.location.reload();
					}
					if (result['html'] && result['html'] != '') {
						$('update-preview').innerHTML = result['html'];
						$('update-preview').style.display = 'block';
						if ($('photo-external_url') && $('photo-url_type')) {
							$('photo-url_type').value = 'external';
							$('photo-external_url').value = url;
							$('photo-internal_url').value = '';
						}
					}
				}
			}
		}
	}
	hideWorkingNotification();
	return true;
}

/**
 * Converts an ACL struct (array) to a xtree obj returning its XHTML content
 */
function convertToTree(keys)
{
    tree = new WebFXTree(permissionsMsg);
    if (typeof(jaws_url) == "undefined" || !jaws_url) {
		jaws_url = '';
	}
    tree.openIcon = jaws_url+'images/blank.gif';
    for(gadget in keys) {
        if (typeof(keys[gadget]) == 'function') {
            continue;
        }
		var selectAll = '';
		var selectNone = '';
		if (typeof(keys[gadget]['selectAll']) != 'undefined') {
			var all_link = document.createElement('span');
			//all_link.setAttribute('href', 'javascript:void(0);');
			all_link.setAttribute('onclick', "javascript: $$('.tree-item-"+keys[gadget]['name'].replace(/ /gi, "")+"').each(function(element){element.setAttribute('checked', true); element.checked = true;});");
			all_link.onclick = function() {
				$$('.tree-item-'+keys[gadget]['name'].replace(/ /gi, "")).each(
					function(element){
						element.setAttribute('checked', true); 
						element.checked = true;
					}
				);
			}
			all_link.style.backgroundColor = '#FFFFFF';
			all_link.style.padding = '3px';
			all_link.style.cursor = 'pointer';
			all_link.style.color = '#999999';
			all_link.style.fontSize = '9px';
            all_link.appendChild(document.createTextNode("Select All"));
			var all_span = document.createElement('span');
            all_span.appendChild(all_link);
			selectAll = all_span.innerHTML;
		}
		if (typeof(keys[gadget]['selectNone']) != 'undefined') {
			var none_link = document.createElement('span');
			//none_link.setAttribute('href', 'javascript:void(0);');
			none_link.setAttribute('onclick', "javascript: $$('.tree-item-"+keys[gadget]['name'].replace(/ /gi, "")+"').each(function(element){element.setAttribute('checked', false); element.checked = false;});");
			none_link.onclick = function() {
				$$('.tree-item-'+keys[gadget]['name'].replace(/ /gi, "")).each(
					function(element){
						element.setAttribute('checked', false); 
						element.checked = false;
					}
				);
			}
			none_link.style.backgroundColor = '#FFFFFF';
			none_link.style.padding = '3px';
			none_link.style.cursor = 'pointer';
			none_link.style.color = '#999999';
			none_link.style.fontSize = '9px';
            none_link.appendChild(document.createTextNode("Select None"));
			var none_span = document.createElement('span');
            none_span.appendChild(none_link);
			selectNone = none_span.innerHTML;
		}
        var gadgetItem = new WebFXTreeItem(keys[gadget]['name']+String.fromCharCode(32)+selectAll+selectNone);

        for(aclKey in keys[gadget]) {
            if (keys[gadget][aclKey]['desc'] == undefined) {
                continue;
            }

            //Create checkbox with its label and all that sexy stuff
            var div = document.createElement('div');

            var chkbox = document.createElement('input');
            chkbox.setAttribute('type', 'checkbox');
            chkbox.setAttribute('name', 'acls[]');
            chkbox.setAttribute('class', 'tree-item-'+keys[gadget]['name'].replace(/ /gi, ""));
            chkbox.setAttribute('className', 'tree-item-'+keys[gadget]['name'].replace(/ /gi, ""));
            chkbox.setAttribute('value', keys[gadget][aclKey]['name']);
            if (keys[gadget][aclKey]['value'] == true) {
                chkbox.defaultChecked = true;
                chkbox.setAttribute('checked', true);
            }
            chkbox.setAttribute('id', keys[gadget][aclKey]['name']);
            //Little trick to know which values have changed their values
            chkbox.setAttribute('changed', false);
            chkbox.setAttribute('onclick', 'javascript: this.changed = true;');
            chkbox.onclick = function() {
                this.changed = true;
            }
            var label = document.createElement('label');
            label.htmlFor = keys[gadget][aclKey]['name'];
            label.appendChild(document.createTextNode(keys[gadget][aclKey]['desc']));

            div.appendChild(chkbox);
            div.appendChild(label);

            var aclItem = new WebFXTreeItem(div.innerHTML);
            gadgetItem.add(aclItem);
        }
        tree.add(gadgetItem);
    }
    return tree.toString();
}

/**
 * Show the advanced options for user
 */
function advancedUserOptions()
{
    var advancedOptions = usersSync.getadvuseroptionsui(selectedUser);
    $('work_area').innerHTML = advancedOptions;
    $('save_user').style.display = 'block';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'none';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('delete_user').style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
    currentAction = 'AdvancedUserOptions';
}

/**
 * Show the personal information of user
 */
function personalInformation()
{
    var pInfoUI = usersSync.getpersonalinformationui(selectedUser);
    $('work_area').innerHTML = pInfoUI;
    $('save_user').style.display = 'block';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'none';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('delete_user').style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
    currentAction = 'PersonalInformation';
}

/**
 * Show user-ACL keys
 */
function manageUserACL()
{
    var aclKeys = usersSync.getuseraclkeys(selectedUser);
    $('work_area').innerHTML = convertToTree(aclKeys);
    $('save_user').style.display = 'none';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'none';
        $('save_acl').style.display = 'block';
        $('reset_acl').style.display = 'block';
    }
    $('delete_user').style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
    currentAction = 'ManageUserACL';
}

/**
 * Show group-ACL keys
 */
function manageGroupACL()
{
    var aclKeys = usersSync.getgroupaclkeys(selectedGroup);
    $('work_area').innerHTML = convertToTree(aclKeys);
    $('save_group').style.display = 'none';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'none';
        $('save_acl').style.display = 'block';
        $('reset_acl').style.display = 'block';

    }
    $('add_usergroups').style.display = 'none';
    $('delete_group').style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
    currentAction = 'ManageGroupACL';
}

/**
 * Show a simple-form with checkboxes so users can check their groups
 */
function addUsersToGroup()
{
    $('work_area').innerHTML = usersSync.getusergroupsform(selectedGroup);
    $('save_group').style.display = 'block';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'none';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('add_usergroups').style.display = 'none';
    $('delete_group').style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
    currentAction = 'ManageUserGroups';
}

/**
 * Save settings
 */
function saveSettings()
{
    var priority   				= $('priority').value;
    var method     				= $('auth_method').value;
    var anon       				= $('anon_register').value;
    var repetitive 				= $('anon_repetitive_email').value;
    var act        				= $('anon_activation').value;
    var type       				= $('anon_type').value;
    //var group      				= $('anon_group').value;
    var recover    				= $('password_recovery').value;
    var signup_requires_address	= $('signup_requires_address').value;
    var social_sign_in  		= $('social_sign_in').value;
	//var users_gadgets 		= $('users_gadgets').value.split(',');
    var group   				= new Object();
	list2 = document.getElementsByName('anon_group[]')
    var j2 = 0;
	for (var i2=0;i2<list2.length;i2++){
		if(list2[i2].checked){
	    	group[j2] = list2[i2].value;
			j2++;
		}
    }
	
    var gadgets   				= new Object();
    /*
	for(var g = 0; g < users_gadgets.length; g++) {
		if (document.getElementById(users_gadgets[g])) {
			//alert('current gadget : ' + users_gadgets[g] + ' value : ' + document.getElementById(users_gadgets[g]).value);
			gadgets[users_gadgets[g]] = document.getElementById(users_gadgets[g]).value;
		}
	}
    */
	list1 = document.getElementsByName('user_access_items[]')
    var j1 = 0;
	for (var i1=0;i1<list1.length;i1++){
		if(list1[i1].checked){
	    	gadgets[j1] = list1[i1].value;
			j1++;
		}
    }
    
	var protected_pages = new Object();
    list = document.getElementsByName('protected_pages[]')
    var j = 0;
	for (var i=0;i<list.length;i++){
		if(list[i].checked){
	    	protected_pages[j] = list[i].value;
			j++;
		}
    }
    usersAsync.savesettings(
		priority, method, anon, repetitive, act, type, group, recover, gadgets, 
		protected_pages, signup_requires_address, social_sign_in
	);
}

/**
 * Update myAccount
 */
function updateMyAccount()
{
    if ($('pass1').value != $('pass2').value) {
        alert(wrongPassword);
        return false;
    }

    if (jawsTrim($('username').value) == '' ||
        jawsTrim($('nickname').value)    == '' ||
        jawsTrim($('email').value)    == '') {
        alert(incompleteUserFields);
        return false;
    }

    if ($('exponent')) {
        setMaxDigits(128);
        var pub_key = new RSAPublicKey($('exponent').value, $('modulus').value, 128);
        var password = encryptedString(pub_key, $('pass1').value);
    } else {
        var password = $('pass1').value;
    }

    usersAsync.updatemyaccount($('id').value,
                               $('username').value,
                               password,
                               $('nickname').value,
                               $('email').value);
}

/**
 * Stops doing a certain action
 */
function stopAction()
{
    switch(currentAction) {
    case 'EditUser':
    case 'AddUser':
        $('add_user').style.display = 'block';
        $('save_user').style.display = 'none';
        if ($('save_acl')) {
            $('manage_acl').style.display = 'none';
            $('save_acl').style.display = 'none';
            $('reset_acl').style.display = 'none';
        }
        $('delete_user').style.display = 'none';
        $('users_combo').selectedIndex = -1;
        $('work_area').innerHTML = '';
        $('cancel_action').style.display = 'none';
        $('right_menu').style.visibility = 'hidden';
        selectedUser = null;
        break;
    case 'ManageUserACL':
    case 'AdvancedUserOptions':
    case 'PersonalInformation':
        if ($('save_acl')) {
            $('save_acl').style.display = 'none';
            $('reset_acl').style.display = 'none';
        }
        $('save_user').style.display = 'block';
        editUser(selectedUser);
        break;
	case 'EditGroup':
    case 'AddGroup':
		location.href='index.php?gadget=Users&action=DefaultAction#pane=Groups';
		break;
		if ($('add_group')) {
			$('add_group').style.display = 'block';
        }
		if ($('save_group')) {
			$('save_group').style.display = 'none';
        }
		if ($('save_acl')) {
            $('manage_acl').style.display = 'none';
            $('save_acl').style.display = 'none';
            $('reset_acl').style.display = 'none';
        }
        $('delete_group').style.display = 'none';
        if ($('groups_combo')) {
			$('groups_combo').selectedIndex = -1;
        }
		$('work_area').innerHTML = '';
        if ($('cancel_action')) {
			$('cancel_action').style.display = 'none';
        }
		$('add_usergroups').style.display = 'none';
        $('right_menu').style.visibility = 'hidden';
        selectedGroup = null;
    case 'ManageGroupACL':
    case 'ManageUserGroups':
        if ($('save_acl')) {
            $('save_acl').style.display = 'none';
            $('reset_acl').style.display = 'none';
        }
        $('save_group').style.display = 'block';
        editGroup(selectedGroup);
        break;
    case 'SelectedPane':
    case 'MinimizePane':
    case 'MaximizePane':
    case 'DeleteSubscription':
    }
}

function usersExecuteFunctionByName(functionName, context /*, args */) {
    var args = Array.prototype.slice.call(arguments, 2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }
    return context[func].apply(context, args);
}

var usersAsync = new usersajax(UsersCallback);
//usersAsync.serverErrorFunc = Jaws_Ajax_ServerError;

var usersSync  = new usersajax();
//usersSync.serverErrorFunc = Jaws_Ajax_ServerError;
HTML_AJAX.Open = showWorkingNotification;
HTML_AJAX.Load = hideWorkingNotification;
HTML_AJAX.onError = Jaws_Ajax_ServerError;

//Current user
var selectedUser = null;
//current group
var selectedGroup = null;
//show all users
var showAll = false;
//Combo colors
var evenColor = '#fff';
var oddColor  = '#edf3fe';

//Cache for saving the group|user-form template
var cacheForm = null;
//Cache for group-user management
var cacheUserGroupForm = null;

//Which action are we runing?
var currentAction = null;
//We already loaded the xtree lib?
var xtreeLoaded = false;
//xtree obj
var xtree = null;
var shareCounter = 0;
var shareUsersString = '';
var shareGroupsString = '';
var emailCounter = 0;
var fileCount = 0;
var prevSite = '';
var prevGadget = '';
var prevMethod = '';
var prevId = '';
var prevLinkid = '';
var prevSectionid = '';
var prevCallback = '';
var prevCustom_function = '';
var prevQuery = '';
var prevPane = '';
var userList = null;
var friendList = null;
var groupList = null;
var userGroupList = null;
var updateImage = '';
var paneArray = new Array();
var messages_on_layout = new Array();
var total_messages = new Array();
var messages_limit = new Array();
var confirmCommentDelete = '';
var paneRequest = null;
