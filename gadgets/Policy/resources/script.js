/**
 * Policy Javascript actions
 *
 * @category   Ajax
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var PolicyCallback = {
    addiprange: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('blocked_ips_datagrid').addItem();
            $('blocked_ips_datagrid').setCurrentPage(0);
            getDG();
            stopAction();
        }
        showResponse(response);
    },

    editiprange: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            getDG();
        }
        showResponse(response);
    },

    deleteiprange: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('blocked_ips_datagrid').deleteItem();
            getDG();
        }
        showResponse(response);
    },

    addagent: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('blocked_agents_datagrid').addItem();
            $('blocked_agents_datagrid').setCurrentPage(0);
            getDG();
            stopAction();
        }
        showResponse(response);
    },

    editagent: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            getDG();
        }
        showResponse(response);
    },

    deleteagent: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('blocked_agents_datagrid').deleteItem();
            getDG();
        }
        showResponse(response);
    },

    enableblockbyip: function(response) {
        showResponse(response);
    },

    enableblockbyagent: function(response) {
        showResponse(response);
    },

    updateencryptionsettings: function(response) {
        showResponse(response);
    },

    updateantispamsettings: function(response) {
        showResponse(response);
    },

    updateadvancedpolicies: function(response) {
        showResponse(response);
    }
}

/**
 * Select DataGrid row
 *
 */
function selectDataGridRow(rowElement)
{
    if (selectedRow) {
        selectedRow.style.backgroundColor = selectedRowColor;
    }
    selectedRowColor = rowElement.style.backgroundColor;
    rowElement.style.backgroundColor = '#ffffcc';
    selectedRow = rowElement;
}

/**
 * Unselect DataGrid row
 *
 */
function unselectDataGridRow()
{
    if (selectedRow) {
        selectedRow.style.backgroundColor = selectedRowColor;
    }
    selectedRow = null;
    selectedRowColor = null;
}

/**
 * Add/Edit Blocked a IP Range
 */
function saveIPRange()
{
    if ($('id').value == 0) {
        policyAsync.addiprange($('from_ipaddress').value, $('to_ipaddress').value);
    } else {
        policyAsync.editiprange($('id').value, $('from_ipaddress').value, $('to_ipaddress').value);
    }
}

/**
 * Edit an IP range
 *
 */
function editIPRange(element, id)
{
    currentAction = 'IPBlocking';
    selectDataGridRow(element.parentNode.parentNode);
    var ipRange = policySync.getiprange(id);

    $('id').value = ipRange['id'];
    $('from_ipaddress').value = ipRange['from_ip'];
    $('to_ipaddress').value   = ipRange['to_ip'];
}

/**
 * Delete an IP range
 */
function deleteIPRange(element, id)
{
    stopAction();
    selectDataGridRow(element.parentNode.parentNode);
    var answer = confirm(confirmIPRangeDelete);
    if (answer) {
        policyAsync.deleteiprange(id);
    }
    unselectDataGridRow();
}

/**
 * Add/Edit Blocked Agent
 */
function saveAgent()
{
    if ($('id').value == 0) {
        policyAsync.addagent($('agent').value);
    } else {
        policyAsync.editagent($('id').value, $('agent').value);
    }
}

/**
 * Edit a Agent
 *
 */
function editAgent(element, id)
{
    currentAction = 'AgentBlocking';
    selectDataGridRow(element.parentNode.parentNode);
    var agent = policySync.getagent(id);

    $('id').value    = agent['id'];
    $('agent').value = agent['agent'];
}

/**
 * Delete an Agent
 */
function deleteAgent(element, id)
{
    stopAction();
    selectDataGridRow(element.parentNode.parentNode);
    var answer = confirm(confirmAgentDelete);
    if (answer) {
        policyAsync.deleteagent(id);
    }
    unselectDataGridRow();
}

/**
 * setBlockByIP
 */
function setBlockByIP()
{
    try {
        policyAsync.enableblockbyip($('blockByIP').checked);
    } catch(e) {
        alert(e);
    }
}

/**
 * setBlockByAgent
 */
function setBlockByAgent()
{
    try {
        policyAsync.enableblockbyagent($('blockByAgent').checked);
    } catch(e) {
        alert(e);
    }
}

/**
 * save encryption settings
 */
function saveEncryptionSettings()
{
    try {
        policyAsync.updateencryptionsettings($('enabled').value,
                                             $('key_age').value,
                                             $('key_len').value);
    } catch(e) {
        alert(e);
    }
}

/**
 * save AntiSpam settings
 */
function saveAntiSpamSettings()
{
    try {
        policyAsync.updateantispamsettings($('allow_duplicate').value,
                                           $('filter').value,
                                           $('captcha').value,
                                           $('obfuscator').value);
    } catch(e) {
        alert(e);
    }
}

/**
 * save Advanced Policies
 */
function saveAdvancedPolicies()
{
    try {
        policyAsync.updateadvancedpolicies($('passwd_complexity').value,
                                           $('passwd_bad_count').value,
                                           $('passwd_lockedout_time').value,
                                           $('passwd_max_age').value,
                                           $('passwd_min_length').value,
                                           $('xss_parsing_level').value,
                                           $('session_idle_timeout').value,
                                           $('session_remember_timeout').value);
    } catch(e) {
        alert(e);
    }
}

/**
 * Submit the form
 */
function submitForm(form)
{
    switch (form.elements['action'].value) {
        case 'AddIPBand':
            addIPBand(form);
            break;
        case 'AddAgent':
            addAgent(form);
            break;
        case 'UpdateProperties':
            updateProperties(form);
            break;
        default:
            break;
    }
}

/**
 * Clean the form
 */
function stopAction()
{
    switch (currentAction) {
        case 'IPBlocking':
            $('id').value = 0;
            $('from_ipaddress').value = '';
            $('to_ipaddress').value   = '';
            unselectDataGridRow();
            break;
        case 'AgentBlocking':
            $('id').value = 0;
            $('agent').value = '';
            unselectDataGridRow();
            break;
        default:
            break;
    }
}

var policyAsync = new policyadminajax(PolicyCallback);
policyAsync.serverErrorFunc = Jaws_Ajax_ServerError;
policyAsync.onInit = showWorkingNotification;
policyAsync.onComplete = hideWorkingNotification;

var policySync  = new policyadminajax();
policySync.serverErrorFunc = Jaws_Ajax_ServerError;
policySync.onInit = showWorkingNotification;
policySync.onComplete = hideWorkingNotification;

//Which action are we runing?
var currentAction = null;

//Which row selected in DataGrid
var selectedRow = null;
var selectedRowColor = null;
