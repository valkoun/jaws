/**
 * Menu JS actions
 *
 * @category   Ajax
 * @package    Menu
 * @author     Jonathan Hernandez <ion@gluch.org.mx>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var MenuCallback = {

    updategroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('group_'+$('gid').value).getElementsByTagName('a')[0].innerHTML = $('title').value;
            stopAction();
        }
        showResponse(response);
    }
}

function isValidURL(url) {
    return (/^(((ht|f)tp(s?))\:\/\/).*$/.test(url));
}

function changeMenuGroup(gid, mid) {
    mid = ((mid == null)? $('mid').value : mid);
    getParentMenus(gid, mid);
    changeMenuParent(0);
}

function changeMenuParent(pid) {
    setRanksCombo($('gid').value, pid);
}

function AddNewMenuGroup(gid) {
    var mainDiv = document.createElement('div');
    var div =$('group_1').getElementsByTagName('div')[0].cloneNode(true);
    mainDiv.className = 'menu_groups';
    mainDiv.id = "group_"+gid;
    mainDiv.appendChild(div);
    $('menus_trees').appendChild(mainDiv);
    var links = mainDiv.getElementsByTagName('a');
    links[0].href      = 'javascript: editGroup('+gid+');';
    links[0].innerHTML = $('title').value;
    links[1].href = 'javascript: addMenu('+gid+', 0);';
}

function AddNewMenuItem(gid, pid, mid, rank)
{
    var mainDiv = document.createElement('div');
    var div =$('group_1').getElementsByTagName('div')[0].cloneNode(true);
    mainDiv.className = 'menu_levels';
    mainDiv.id = "menu_"+mid;
    mainDiv.appendChild(div);
    if (pid == 0) {
        var parentNode = $('group_'+gid);
    } else {
        var parentNode = $('menu_'+pid);
    }
    parentNode.appendChild(mainDiv);
    //set ranking
    var oldRank = $A(parentNode.childNodes).indexOf($('menu_'+mid));
    if (rank < oldRank) {
        parentNode.insertBefore($('menu_'+mid), parentNode.childNodes[rank]);
    }
    //--
    var links = mainDiv.getElementsByTagName('a');
    links[0].href      = 'javascript: editMenu('+mid+');';
    links[0].innerHTML = $('title').value;
    links[1].href = 'javascript: addMenu('+gid+', '+ mid +');';
    var images = mainDiv.getElementsByTagName('img');
    images[0].src = menuImageSrc;
    // hide menu actions
    mainDiv.getElementsByTagName('div')[2].style.visibility = 'hidden';
}

/**
 * Saves data / changes
 */
function saveMenus()
{
    if (currentAction == 'Groups') {
        if ((jawsTrim($('title').value) == '')) {
            alert(incompleteFields);
            return false;
        }
        cacheMenuForm = null;
        if (selectedGroup == null) {
            var response = menuSync.insertgroup(
                                    $('title').value,
                                    $('title_view').value,
                                    $('visible').value);
            if (response[0]['css'] == 'notice-message') {
                var gid = response[0]['message'].substr(0, response[0]['message'].indexOf('%%'));
                response[0]['message'] = response[0]['message'].substr(response[0]['message'].indexOf('%%')+2);
                AddNewMenuGroup(gid);
                stopAction();
            }
            showResponse(response);
        } else {
            menuAsync.updategroup(
                                $('gid').value,
                                $('title').value,
                                $('title_view').value,
                                $('visible').value);
        }
    } else {
        if ((jawsTrim($('title').value) == '') || ($('references').selectedIndex == -1)) {
            alert(incompleteFields);
            return false;
        }
        if (selectedMenu == null) {
            var response = menuSync.insertmenu(
                                    $('pid').value,
                                    $('gid').value,
                                    $('type').value,
                                    $('title').value,
                                    $('url').value,
                                    $('url_target').value,
                                    $('rank').value,
                                    $('visible').value);
            if (response[0]['css'] == 'notice-message') {
                var mid = response[0]['message'].substr(0, response[0]['message'].indexOf('%%'));
                response[0]['message'] = response[0]['message'].substr(response[0]['message'].indexOf('%%')+2);
                AddNewMenuItem($('gid').value, $('pid').value, mid, $('rank').value);
                stopAction();
            }
            showResponse(response);
        } else {
            var response = menuSync.updatemenu(
                                    $('mid').value,
                                    $('pid').value,
                                    $('gid').value,
                                    $('type').value,
                                    $('title').value,
                                    $('url').value,
                                    $('url_target').value,
                                    $('rank').value,
                                    $('visible').value);
            if (response[0]['css'] == 'notice-message') {
                $('menu_'+$('mid').value).getElementsByTagName('a')[0].innerHTML = $('title').value;
                if ($('pid').value == 0) {
                    var new_parentNode = $('group_'+$('gid').value);
                } else {
                    var new_parentNode = $('menu_'+$('pid').value);
                }
                if ($('menu_'+$('mid').value).parentNode != new_parentNode) {
                    if ($('rank').value > (new_parentNode.childNodes.length - 1)) {
                        new_parentNode.appendChild($('menu_'+$('mid').value));
                    } else {
                        new_parentNode.insertBefore($('menu_'+$('mid').value), new_parentNode.childNodes[$('rank').value]);
                    }
                } else {
                    var oldRank = $A(new_parentNode.childNodes).indexOf($('menu_'+$('mid').value));
                    if ($('rank').value > oldRank) {
                        new_parentNode.insertBefore($('menu_'+$('mid').value), new_parentNode.childNodes[$('rank').value].nextSibling);
                    } else {
                        new_parentNode.insertBefore($('menu_'+$('mid').value), new_parentNode.childNodes[$('rank').value]);
                    }
                }
                stopAction();
            }
            showResponse(response);
        }
    }
}

function setRanksCombo(gid, pid, selected) {
    $('rank').options.length = 0;
    if (pid == 0) {
        var new_parentNode = $('group_'+gid);
    } else {
        var new_parentNode = $('menu_'+pid);
    }
    var rank = new_parentNode.childNodes.length;
    rank = rank - 1;

    if (($('mid').value < 1) || ($('menu_'+$('mid').value).parentNode != new_parentNode)) {
        rank = rank + 1;
    }

    for(var i = 0; i < rank; i++) {
        $('rank').options[i] = new Option(i+1, i+1);
    }
    if (selected == null) {
        $('rank').value = rank;
    } else {
        $('rank').value = selected;
    }
}

/**
 * Add group
 */
function addGroup()
{
    if (cacheGroupForm == null) {
        cacheGroupForm = menuSync.getgroupui();
    }
    currentAction = 'Groups';

    $('edit_area').getElementsByTagName('span')[0].innerHTML = addGroupTitle;
    selectedGroup = null;
    $('btn_cancel').style.display = 'inline';
    $('btn_del').style.display    = 'none';
    $('btn_save').style.display   = 'inline';
    $('btn_add').style.display    = 'none';
    $('menus_edit').innerHTML = cacheGroupForm;
}

/**
 */
function mm_enter(eid)
{
    m_bg_color = $(eid).style.backgroundColor;
    if ($(eid).parentNode.className != 'menu_groups') {
        $(eid).style.backgroundColor = "#f0f0f0";
    }
    $(eid).getElementsByTagName('div')[1].style.visibility = 'visible';
}

/**
 */
function mm_leave(eid)
{
    $(eid).style.backgroundColor = m_bg_color;
    if ($(eid).parentNode.className != 'menu_groups') {
        $(eid).getElementsByTagName('div')[1].style.visibility = 'hidden';
    }
}

/**
 * Add menu
 */
function addMenu(gid, pid)
{
    if (cacheMenuForm == null) {
        cacheMenuForm = menuSync.getmenuui();
    }

    stopAction();
    currentAction = 'Menus';

    if (pid == 0) {
        $('edit_area').getElementsByTagName('span')[0].innerHTML =
            addMenuTitle + ' - ' + $('group_'+gid).getElementsByTagName('a')[0].innerHTML;
    } else {
        $('edit_area').getElementsByTagName('span')[0].innerHTML =
            addMenuTitle + ' - ' + $('group_'+gid).getElementsByTagName('a')[0].innerHTML +
            ' - ' + $('menu_'+pid).getElementsByTagName('a')[0].innerHTML;
    }

    selectedMenu = null;
    $('btn_cancel').style.display = 'inline';
    $('btn_del').style.display    = 'none';
    $('btn_save').style.display   = 'inline';
    $('btn_add').style.display    = 'none';
    $('menus_edit').innerHTML = cacheMenuForm;

    $('gid').value = gid;
    getParentMenus(gid, 0);
    $('pid').value = pid;
    setRanksCombo(gid, pid);

    getReferences($('type').value);
    $('references').selectedIndex = -1;
}

/**
 * Edit group
 */
function editGroup(gid)
{
    if (gid == 0) return;
    if (cacheGroupForm == null) {
        cacheGroupForm = menuSync.getgroupui();
    }
    currentAction = 'Groups';
    selectedGroup = gid;

    $('edit_area').getElementsByTagName('span')[0].innerHTML =
        editGroupTitle + ' - ' + $('group_'+gid).getElementsByTagName('a')[0].innerHTML;
    $('btn_cancel').style.display = 'inline';
    $('btn_del').style.display    = 'inline';
    $('btn_save').style.display   = 'inline';
    $('btn_add').style.display    = 'none';
    $('menus_edit').innerHTML = cacheGroupForm;  

    var groupInfo = menuSync.getgroups(selectedGroup);

    $('gid').value         = groupInfo['id'];
    $('title').value       = groupInfo['title'];
    $('title_view').value  = groupInfo['title_view'];
    $('visible').value     = groupInfo['visible'];
}

/**
 * Edit menu
 */
function editMenu(mid)
{
    if (mid == 0) return;
    if (cacheMenuForm == null) {
        cacheMenuForm = menuSync.getmenuui();
    }
    currentAction = 'Menus';

    $('edit_area').getElementsByTagName('span')[0].innerHTML =
        editMenuTitle + ' - ' + $('menu_'+mid).getElementsByTagName('a')[0].innerHTML;
    $('btn_cancel').style.display = 'inline';
    $('btn_del').style.display    = 'inline';
    $('btn_save').style.display   = 'inline';
    $('btn_add').style.display    = 'none';
    $('menus_edit').innerHTML = cacheMenuForm;  

    //highlight selected menu
    if (selectedMenu != mid) {
        org_m_bg_color = m_bg_color;
        m_bg_color = '#eeeecc';
        if (selectedMenu != null) {
            $('menu_'+selectedMenu).getElementsByTagName('div')[0].style.backgroundColor = org_m_bg_color;
        }
    }
    $('menu_'+mid).getElementsByTagName('div')[0].style.backgroundColor = m_bg_color;

    selectedMenu = mid;
    var menuInfo = menuSync.getmenu(selectedMenu);
    getParentMenus(menuInfo['gid'], mid);

    $('mid').value         = menuInfo['id'];
    $('pid').value         = menuInfo['pid'];
    $('gid').value         = menuInfo['gid'];
    $('type').value        = menuInfo['menu_type'];
    $('title').value       = menuInfo['title'];
    $('url').value         = menuInfo['url'];
    $('url_target').value  = menuInfo['url_target'];

    setRanksCombo($('gid').value, $('pid').value);
    $('rank').value        = menuInfo['rank'];

    $('visible').value     = menuInfo['visible'];
    getReferences($('type').value);
    $('references').value = $('url').value;
}

/**
 * Delete group/menu
 */
function delMenus()
{
    if (currentAction == 'Groups') {
        var gid = selectedGroup;
        var msg = confirmGroupDelete;
        msg = msg.substr(0,  msg.indexOf('%s%')) + $('group_'+gid).getElementsByTagName('a')[0].innerHTML + msg.substr(msg.indexOf('%s%')+3);
        if (confirm(msg)) {
            cacheMenuForm = null;
            var response = menuSync.deletegroup(gid);
            if (response[0]['css'] == 'notice-message') {
                Element.remove($('group_'+gid));
            }
            stopAction();
            showResponse(response);
        }
    } else {
        var mid = selectedMenu;
        var msg = confirmMenuDelete;
        msg = msg.substr(0,  msg.indexOf('%s%')) + $('menu_'+mid).getElementsByTagName('a')[0].innerHTML + msg.substr(msg.indexOf('%s%')+3);
        if (confirm(msg)) {
            var response = menuSync.deletemenu(mid);
            if (response[0]['css'] == 'notice-message') {
                Element.remove($('menu_'+mid));
            }
            stopAction();
            showResponse(response);
        }
    }
}

/**
 * Get list of menu levels
 */
function getParentMenus(gid, mid) {
    var parents = menuSync.getparentmenus(gid, mid);
    $('pid').options.length = 0;
    for(var i = 0; i < parents.length; i++) {
        $('pid').options[i] = new Option(parents[i]['title'], parents[i]['pid']);
    }
}

/**
 * Get a list of public URLs
 */
function changeType(type) {
    getReferences(type);
    $('references').selectedIndex = -1;
}

/**
 * Get a list of public URLs
 */
function getReferences(type)
{
    if (cacheReferences[type]) {
        $('references').options.length = 0;
        for(var i = 0; i < cacheReferences[type].length; i++) {
            $('references').options[i] = new Option(cacheReferences[type][i]['title'], cacheReferences[type][i]['url']);
        }
        return;
    }
    var links = menuSync.getpublicurlist(type);
    cacheReferences[type] = new Array();
    $('references').options.length = 0;
    for(var i = 0; i < links.length; i++) {
        $('references').options[i] = new Option(links[i]['title'], links[i]['url']);
        cacheReferences[type][i] = new Array();
        cacheReferences[type][i]['url']   = links[i]['url'];
        cacheReferences[type][i]['title'] = links[i]['title'];
        if (links[i]['title2']) {
            cacheReferences[type][i]['title2'] = links[i]['title2'];
        }
    }
}

/**
 * change references
 */
function changeReferences() {
    var type = $('type').value;
    var selIndex = $('references').selectedIndex;
    if (type != 'url') {
        if (cacheReferences[type][selIndex]['title2']) {
            $('title').value = cacheReferences[type][selIndex]['title2'];
        } else {
            $('title').value = $('references').options[selIndex].text;
        }
    }

    if ($('references').value !='') {
        $('url').value = $('references').value;
    }
}

/**
 * Stops doing a certain action
 */
function stopAction()
{
    $('btn_cancel').style.display = 'none';
    $('btn_del').style.display    = 'none';
    $('btn_save').style.display   = 'none';
    $('btn_add').style.display    = 'inline';

    var old_selected_menu = $('menu_'+selectedMenu);
    if (old_selected_menu) {
        old_selected_menu.getElementsByTagName('div')[0].style.backgroundColor = org_m_bg_color;
    }

    selectedMenu  = null;
    selectedGroup = null;
    currentAction = null;
    $('menus_edit').innerHTML = '';
    $('edit_area').getElementsByTagName('span')[0].innerHTML = '';
}

var menuAsync = new menuadminajax(MenuCallback);
menuAsync.serverErrorFunc = Jaws_Ajax_ServerError;
menuAsync.onInit = showWorkingNotification;
menuAsync.onComplete = hideWorkingNotification;

var menuSync  = new menuadminajax();
menuSync.serverErrorFunc = Jaws_Ajax_ServerError;
menuSync.onInit = showWorkingNotification;
menuSync.onComplete = hideWorkingNotification;

//Current group
var selectedGroup = null;

//Current menu
var selectedMenu = null;

//Cache for saving the group form template
var cacheGroupForm = null;

//Cache for saving the menu form template
var cacheMenuForm = null;

//Menu items background color
var m_bg_color = null;
var org_m_bg_color = null;

var cacheReferences = new Array();
