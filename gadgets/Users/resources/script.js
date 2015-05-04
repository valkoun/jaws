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
        showResponse(response);
    },

    deleteuser: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getUsers();
            stopAction();
        }
        showResponse(response);
    },

    addgroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getGroups();
            stopAction();
        }
        showResponse(response);
    },

    updategroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getGroups();
        }
        //stopAction();
        showResponse(response);
    },

    deletegroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getGroups();
            stopAction();
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
        if (response['success']) {
            if (response['id'] && $('newgrouprequest-'+response['id'])) {
				$('newgrouprequest-'+response['id']).parentNode.removeChild($('newgrouprequest-'+response['id']));
			}
		}
        showResponse(response['message']);
    },

    deleteuserfromgroup: function(response) {
        if (response['success']) {
            if (response['id'] && $('newgrouprequest-'+response['id'])) {
				$('newgrouprequest-'+response['id']).parentNode.removeChild($('newgrouprequest-'+response['id']));
			}
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
	
    deleteemailpage: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getEmailPagesData();
        }
        showResponse(response);
    }, 
    
    massivedeleteemailpages: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('emailpages_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('emailpages_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('emailpages_datagrid'));
            getEmailPagesData();
        }
        showResponse(response);      
    }, 
	
    deletemessage: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getMessagesData();
        }
        showResponse(response);
    }, 
    
    massivedeleteemailpages: function(response) {
        if (response[0]['css'] == 'notice-message') {
            var rows = $('messages_datagrid').getSelectedRows();
            if (rows.length > 0) {
                for(var i=0; i<rows.length; i++) {
                    $('messages_datagrid').deleteItem();
                }
            }
            PiwiGrid.multiSelect($('messages_datagrid'));
            getMessagesData();
        }
        showResponse(response);      
    }
};

function addUpdate(url, title)
{
	usersW = new UI.URLWindow({
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
	usersW.setPosition((window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-50, ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(usersW.getSize().width/2));
	//usersW.setZIndex(2147483647);
	usersW.show(true).focus();
	usersW.setZIndex(2147483647);
	usersW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-usersW.getSize().height-90, left: usersW.getPosition().left});
	Event.observe(window, "resize", function() {
		usersW.morph({top: (window.innerHeight || (window.document.documentElement.clientHeight || window.document.body.clientHeight))-usersW.getSize().height-90, left: ((window.innerWidth || (window.document.documentElement.clientWidth || window.document.body.clientWidth))/2)-(usersW.getSize().width/2)});
	});
	//usersW.setPosition(25, usersW.getPosition().left);
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
		ifrm[g].setAttribute("src", "admin.php?gadget="+g+"&action=GetQuickAddForm&method="+method+"&id="+id+"&linkid="+linkid+"&section_id="+section_id+query);
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
		window.frames['quick_add_'+g].saveQuickAdd('Comment', method, callback, $('sharing').value, $('syndication').value, ($('OwnerID') ? $('OwnerID').value : ''));
	}
	$('save').style.display = '';
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
	if($('share')) {
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
    var userList  = usersSync.getusers(
		$('filter_group').value, $('filter_type').value, $('filter_status').value, 
		$('filter_search').value, $('filter_orderBy').value, $('filter_sortDir').value
	);
    if (userList != false) {
        var combo = $('users_combo');
        combo.options.length = 0;
        var i = 0;
        userList.each(function(value, index) {
            var op = new Option(value['username'], value['id']);
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
        if (jawsTrim($('name').value) == '') {
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
    stopAction();
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
    var answer = confirm(confirmUserDelete);
    if (answer) {
        usersAsync.deleteuser(selectedUser);
    }
}

/**
 * Delete group
 */
function deleteGroup()
{
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
        op.value = '';
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
    $('cancel_action').style.display = 'block';
    $('save_group').style.display = 'block';
    $('add_group').style.display = 'none';
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
    $('user_id').style.display = 'block';

    $('user_avatar').style.width = 'auto';
    $('user_avatar').style.height = 'auto';
    $('user_avatar').style.maxWidth = '60px';
    $('user_avatar').style.maxHeight = '60px';
    $('user_avatar').src = uInfo['image'];
    $('user_id').value  = uInfo['id'];
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

    $('cancel_action').style.display = 'block';
    $('save_group').style.display = 'block';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'block';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('add_usergroups').style.display = 'block';
    $('delete_group').style.display = 'block';
    $('add_group').style.display = 'none';
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
 * Log-in as user
 */
function loginAsUser()
{
	currentAction = 'LoginAsUser';
	var response = usersSync.loginasuser(selectedUser);
	if (response[0]['css'] == 'notice-message') {
		if (typeof response[0]['url'] != "undefined") {
			location.href = response[0]['url'];
		} else {
			location.href = 'index.php?gadget=Users';
		}
	} else if (response[0]['css'] == 'notice-error') {
		if (typeof response[0]['url'] != "undefined") {
			location.href = response[0]['url'];
		}
		//showResponse(response);
	}
}

/**
 * View user's files
 */
function viewUserFiles()
{
	currentAction = 'ViewUserFiles';
	location.href = 'index.php?files/users/'+selectedUser+'/';
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
	if (!base_url) {
		base_url = '';
	}
	if (!types) {
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
	if($('share')) {
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
		ifrm[g].setAttribute("src", "admin.php?gadget=Users&action=ShareComment");
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
			parent.$('sharing').value = 'users:';
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
					keys['Users'][shareCounter] = new Array();
					keys['Users'][shareCounter]['desc'] = value['realname'];
					keys['Users'][shareCounter]['value'] = false;
					keys['Users'][shareCounter]['name'] = 'share_user_'+value['id'];
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
					keys['Groups'][shareCounter] = new Array();
					keys['Groups'][shareCounter]['desc'] = value['realname'];
					keys['Groups'][shareCounter]['value'] = false;
					keys['Groups'][shareCounter]['name'] = 'share_group_'+value['id'];
					shareCounter++;
				});
			}
			$('share-user').innerHTML = convertToTree(keys);	
		} else {
			parent.$('sharing').value = share;
		
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
			parent.$('sharing').value = 'users:';
			var inputs = $('share-user').getElementsByTagName('input');
			shareCounter = 0;
			for (var i=0; i<inputs.length; i++) {
				if (inputs[i].value.indexOf('share_user') >= 0) {
					if (inputs[i].checked) {
						parent.$('sharing').value += (shareCounter > 0 ? ','+inputs[i].value.replace('share_user_', '') : inputs[i].value.replace('share_user_', ''));
						shareCounter++;
					} 
				} else if (inputs[i].value.indexOf('share_group') >= 0) {
					if (inputs[i].checked) {
						var userGroupList = usersSync.getusersofgroup(inputs[i].value.replace('share_group_', ''));
						if (userGroupList != false) {
							userGroupList.each(function(value, index) {
								parent.$('sharing').value += (shareCounter > 0 ? ','+value['id'] : value['id']);
								shareCounter++;
							});
						}
					}		
				} else {
					continue;
				}
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
    $('update-holder').style.display = 'none';
    $('update-buttons').style.display = 'block';
    $('update-area').style.display = 'block';
	$('update-entry').focus();
	//hideWorkingNotification();
}

/**
 * Hide Comment Form
 */
function hideUpdateForm()
{
	//showWorkingNotification();
    $('update-holder').style.display = 'block';
    $('update-buttons').style.display = 'none';
    $('update-area').style.display = 'none';
	$('update-entry').value = '';	
	//hideWorkingNotification();
}

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
    if (typeof(OwnerID) == "undefined") {
		OwnerID = '';
	}
    if (typeof(syndication) == "undefined") {
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
	}
	if (callback !== null) {
		usersExecuteFunctionByName(callback, window);
		//callback();
	}
	
	message = new Array();
	message[0] = new Array();
	message[0]['css'] = response['css'];
	message[0]['message'] = response['message'];
	showResponse(message);
	
	if ($('shareButton')) {
		window.location.reload();
	}
	//hideUpdateForm();
	//hideWorkingNotification();
	return true;
}

/**
 * Saves a status Update
 */
function saveStatus(id, comment, title, parent, sharing, syndication, OwnerID)
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
    if (!OwnerID) {
		OwnerID = '';
	}
    if (!syndication) {
		syndication = false;
	}
	if (comment.length <= 0) {
		if ($('update-entry') && $('update-entry').value.length > 0) {
			comment = $('update-entry').value;
			$('update-entry').value = '';
		}
	}
	response = usersSync.newstatus(title, comment, parent, id, '', false, sharing);
	if (response['css'] == 'notice-message') {
		news_items_html = '<div class="news-item news-message" id="news-'+response['id']+'" onmouseout="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'news-delete-'+response['id']+'\')){$(\'news-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_items_html += '	<div class="news-delete" id="news-delete-'+response['id']+'"><a href="javascript:void(0);" onclick="DeleteComment('+response['id']+', \'update\');">X</a></div>';
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
		news_items_html += '			<input class="comment-click" id="comment-click-'+response['id']+'" value="Reply to this..." onclick="showCommentForm('+response['id']+');" />';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-area" id="comment-area-'+response['id']+'">';
		news_items_html += '			<textarea class="comment-entry" id="comment-entry-'+response['id']+'" onblur="if (this.value == \'\') {hideCommentForm('+response['id']+');};"></textarea>';
		news_items_html += '			</div>';
		news_items_html += '			<div class="comment-buttons" id="comment-buttons-'+response['id']+'"><button type="button" name="commentButton'+response['id']+'" id="commentButton-'+response['id']+'" value="Ok" style="min-width: 60px;" onclick="javascript: saveReply('+response['id']+');">Ok</button></div>';
		news_items_html += '		</div>';
		news_items_html += '	</div>';
		news_items_html += '</div>';
		$('news-items').innerHTML = news_items_html + $('news-items').innerHTML;
		
		if (syndication === true) {
			RPXNOW.loadAndRun(['Social'], function () {
				var rpx_comment = "posted on "+window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length);
				if (response['comment'] != '') {
					rpx_comment = "posted "+'"'+response['comment']+'"';
				}
				var activity = new RPXNOW.Social.Activity(
				   "Share this",
				   rpx_comment,
				   response['permalink']
				);
				if (response['title'] != '') {
					activity.setTitle(response['title']);
				} else {
					activity.setTitle(window.location.hostname.charAt(0).toUpperCase() + window.location.hostname.substring(1, window.location.hostname.length));
				}
				//activity.setUserGeneratedContent("posted on");
				RPXNOW.Social.publishActivity(activity);
			});
		}
	}	
	message = new Array();
	message[0] = new Array();
	message[0]['css'] = response['css'];
	message[0]['message'] = response['message'];
	showResponse(message);
	//hideUpdateForm();
	//hideWorkingNotification();
}

/**
 * Saves a status Update
 */
function savePhoto(
	id, comment, title, parent, sharing, syndication, OwnerID, image, 
	url_type, internal_url, external_url, url_target, gadget, callback
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
    if (typeof(callback) == "undefined" || callback == '') {
		callback = null;
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
	}	
	if (callback !== null) {
		usersExecuteFunctionByName(callback, window);
		//callback();
	}
	message = response['messages'];
	showResponse(message);
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
function saveReply(cid, id)
{
    if ($('comment-entry-'+cid) && $('comment-entry-'+cid).value.length > 0) {
		comment = $('comment-entry-'+cid).value;
		$('comment-entry-'+cid).value = '';
	}
	response = usersSync.newcomment('', comment, cid, id, '', false);
	if (response['css'] == 'notice-message') {
		news_comments_html = '<div class="comment comment-new" id="comment-'+response['id']+'" onmouseout="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'hidden\';};" onmouseover="if ($(\'comment-delete-'+response['id']+'\')){$(\'comment-delete-'+response['id']+'\').style.visibility = \'visible\';};">';
		news_comments_html += '<div id="comment-delete-'+response['id']+'" class="comment-delete"><a onclick="DeleteComment('+response['id']+', \'reply\');" href="javascript:void(0);">X</a></div>';		
		news_comments_html += response['image']+'<div class="comment-body"><span class="comment-name">'+(response['link'] != '' ? '<a href="'+response['link']+'" class="comment-name">' : '')+response['name']+(response['link'] != '' ? '</a>' : '')+'</span>&nbsp;<span class="comment-preview" id="comment-preview-'+response['id']+'"'+response['preview_style']+'>'+response['preview_comment']+'</span><span class="comment-message" id="comment-full-'+response['id']+'"'+response['full_style']+'>'+response['comment']+'</span>';
		news_comments_html += '</div><div class="comment-created news-timestamp">'+response['created']+'</div>';
		news_comments_html += '</div>';
		$('news-comments-'+cid).innerHTML = $('news-comments-'+cid).innerHTML + news_comments_html;
	} else {
		error = new Array();
		error[0] = new Array();
		error[0]['css'] = response['css'];
		error[0]['message'] = response['message'];
		showResponse(error);
	}
	hideCommentForm(cid);
    if ($('all-comments-'+cid)) {
		$('all-comments-'+cid).innerHTML = '<a href="javascript:void(0);" onclick="toggleAllComments('+response['id']+');">View all comments</a>';
	}
}

/**
 * Delete Comment
 */
function DeleteComment(cid, type, parent)
{
    if (typeof(parent) == "undefined") {
		parent = cid;
	}
    if (typeof(type) == "undefined") {
		type = 'update';
	}
	if (typeof(confirmCommentDelete) == "undefined") {
		var answer = true;
	} else {
		var answer = confirm(confirmCommentDelete);
    }
	if (answer) {
		//showWorkingNotification();
		var response = usersSync.deletecomment(cid);
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
 * Stops doing a certain action
 */
function stopAction()
{
    switch(currentAction) {
    case 'EditUser':
    case 'AddUser':
		$('user_id').style.display = 'none';
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
        $('add_group').style.display = 'block';
        $('save_group').style.display = 'none';
        if ($('save_acl')) {
            $('manage_acl').style.display = 'none';
            $('save_acl').style.display = 'none';
            $('reset_acl').style.display = 'none';
        }
        $('delete_group').style.display = 'none';
        $('groups_combo').selectedIndex = -1;
        $('work_area').innerHTML = '';
        $('cancel_action').style.display = 'none';
        $('add_usergroups').style.display = 'none';
        $('right_menu').style.visibility = 'hidden';
        selectedGroup = null;
        break;
    case 'ManageGroupACL':
    case 'ManageUserGroups':
        if ($('save_acl')) {
            $('save_acl').style.display = 'none';
            $('reset_acl').style.display = 'none';
        }
        $('save_group').style.display = 'block';
        editGroup(selectedGroup);
        break;
    case 'MinimizePane':
    case 'MaximizePane':
    case 'DeleteSubscription':
    }
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
    if ($('main_image')) {
		while ($('main_image').firstChild) {
			$('main_image').removeChild($('main_image').firstChild);
		};
    }
	$('work_area').innerHTML = pInfoUI;
    $('save_user').style.display = 'block';
    if ($('save_acl')) {
        $('manage_acl').style.display = 'none';
        $('save_acl').style.display = 'none';
        $('reset_acl').style.display = 'none';
    }
    $('delete_user').style.display = 'none';
    $('right_menu').style.visibility = 'hidden';
	addFileToPost('Users', 'NULL', 'NULL', 'main_image', 'logo', 1, 310, 34);
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
 * Delete a message : function
 */
function deleteEmailPage(id)
{
    usersAsync.deleteemailpage(id);
}

/**
 * Can use massive delete?
 */
function massiveDeleteEmailPages(message) 
{
    var rows = $('emailpages_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            usersAsync.massivedeleteemailpages(rows);
        }
    }
}

/**
 * Search for messages
 */
function searchEmailPages()
{
    updateEmailPagesDatagrid($('status_emailpages').value, $('search_emailpages').value, 0, true);
}

/**
 * Get pages data
 */
function getEmailPagesData(limit)
{
    if (limit == undefined) {
        limit = $('emailpages_datagrid').getCurrentPage();
    }
    updateEmailPagesDatagrid($('status_emailpages').value,
                        $('search_emailpages').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousEmailPagesValues()
{
    var previousEmailPagesValues = $('emailpages_datagrid').getPreviousPagerValues();
    getEmailPagesData(previousEmailPagesValues);
    $('emailpages_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextEmailPagesValues()
{
    var nextEmailPagesValues = $('emailpages_datagrid').getNextPagerValues();
    getEmailPagesData(nextEmailPagesValues);
    $('emailpages_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateEmailPagesDatagrid(status, search, limit, resetCounter)
{
    $('emailpages_datagrid').objectName = usersSync;
    JawsDataGrid.name = 'emailpages_datagrid';

    var result = usersSync.searchemailpages(status, search, limit);
    resetGrid('emailpages_datagrid', result);
    if (resetCounter) {
        var size = usersSync.sizeofsearch1(status, search);
        $('emailpages_datagrid').rowsSize    = size;
        //$('emailpages_datagrid').setCurrentPage(0);
        $('emailpages_datagrid').updatePageCounter();
    }
}

/**
 * Delete a message : function
 */
function deleteMessage(id)
{
    usersAsync.deletemessage(id);
}

/**
 * Can use massive delete?
 */
function massiveDeleteMessages(message) 
{
    var rows = $('messages_datagrid').getSelectedRows();
    if (rows.length > 0) {
        var confirmation = confirm(message);
        if (confirmation) {
            usersAsync.massivedeletemessages(rows);
        }
    }
}

/**
 * Search for messages
 */
function searchMessages()
{
    updateMessagesDatagrid($('search_messages').value, 0, true);
}

/**
 * Get messages data
 */
function getMessagesData(limit)
{
    if (limit == undefined) {
        limit = $('messages_datagrid').getCurrentPage();
    }
    updateMessagesDatagrid($('search_messages').value,
                        limit,
                        false);
}

/**
 * Get previous values of pages
 */
function previousMessagesValues()
{
    var previousMessagesValues = $('messages_datagrid').getPreviousPagerValues();
    getMessagesData(previousMessagesValues);
    $('messages_datagrid').previousPage();
}

/**
 * Get next values of pages
 */
function nextMessagesValues()
{
    var nextMessagesValues = $('messages_datagrid').getNextPagerValues();
    getMessagesData(nextMessagesValues);
    $('messages_datagrid').nextPage();
}

/**
 * Update pages datagrid
 */
function updateMessagesDatagrid(search, limit, resetCounter)
{
    $('messages_datagrid').objectName = usersSync;
    JawsDataGrid.name = 'messages_datagrid';

    var result = usersSync.searchmessages(search, limit);
    resetGrid('messages_datagrid', result);
    if (resetCounter) {
        var size = usersSync.sizeofsearch2(search);
        $('messages_datagrid').rowsSize    = size;
        //$('messages_datagrid').setCurrentPage(0);
        $('messages_datagrid').updatePageCounter();
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

var usersAsync = new usersadminajax(UsersCallback);
usersAsync.serverErrorFunc = Jaws_Ajax_ServerError;
usersAsync.onInit = showWorkingNotification;
usersAsync.onComplete = hideWorkingNotification;

var usersSync  = new usersadminajax();
usersSync.serverErrorFunc = Jaws_Ajax_ServerError;
usersSync.onInit = showWorkingNotification;
usersSync.onComplete = hideWorkingNotification;

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
var emailCounter = 0;
var fileCount = 0;
var prevSite = '';
var prevGadget = '';
var prevMethod = '';
var prevId = '';
var prevLinkid = '';
var prevSectionid = '';
var prevCallback = '';
var prevQuery = '';
var prevCustom_function = '';
var prevPane = '';
var userList = null;
var friendList = null;
var groupList = null;
var userGroupList = null;
var usersW;