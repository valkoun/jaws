/**
 * Poll JS actions
 *
 * @category   Ajax
 * @package    Poll
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var PollCallback = {
    insertpoll: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            $('polls_datagrid').addItem();
            $('polls_datagrid').setCurrentPage(0);
            getDG();
        }
        showResponse(response);
    },

    updatepoll: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            getDG();
        }
        showResponse(response);
    },

    deletepoll: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            $('polls_datagrid').deleteItem();
            getDG();
        }
        showResponse(response);
    },

    updatepollanswers: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
        }
        showResponse(response);
    },

    insertpollgroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            $('pollgroups_datagrid').addItem();
            $('pollgroups_datagrid').setCurrentPage(0);
            getDG();
        }
        showResponse(response);
    },

    updatepollgroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            getDG();
        }
        showResponse(response);
    },

    deletepollgroup: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopAction();
            $('pollgroups_datagrid').deleteItem();
            getDG();
        }
        showResponse(response);
    },

    addpollstopollgroup: function(response) {
        stopAction();
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
 * Stops doing a certain action
 */
function stopAction()
{
    switch(currentAction) {
    case 'Polls':
        selectedPoll = null;
        $('legend_title').innerHTML = addPoll_title;
        $('question').value    = '';
        $('gid').selectedIndex = 0;
        $('start_time').value  = '';
        $('stop_time').value   = '';
        $('select_type').value = 0;
        $('poll_type').value   = 0;
        $('result_view').value = 1;
        $('visible').value     = 1;
        unselectDataGridRow();
        $('question').focus();
        break;
    case 'PollAnswers':
        selectedPoll = null;
        currentAction = 'Polls';
        $('legend_title').innerHTML = addPoll_title;
        $('p_work_area').innerHTML = cachePollForm;
        initDatePicker('start_time');
        initDatePicker('stop_time');
        unselectDataGridRow();
        break;
    case 'PollGroups':
        selectedPollGroup = null;
        $('legend_title').innerHTML = addPollGroup_title;
        $('title').value   = '';
        $('visible').value = 1;
        unselectDataGridRow();
        $('title').focus();
        break;
    case 'PollGroupPolls':
        selectedPollGroup = null;
        currentAction = 'PollGroups';
        $('legend_title').innerHTML = addPollGroup_title;
        $('pg_work_area').innerHTML = cachePollGroupsForm;
        unselectDataGridRow();
        break;
    }
}

/**
 * Edit Poll
 */
function editPoll(element, pid)
{
    currentAction = 'Polls';
    selectedPoll = pid;
    $('legend_title').innerHTML = editPoll_title;
    if (cachePollForm != null) {
        $('p_work_area').innerHTML = cachePollForm;
        initDatePicker('start_time');
        initDatePicker('stop_time');
    }

    selectDataGridRow(element.parentNode.parentNode);

    var pollInfo = pollSync.getpoll(selectedPoll);
    $('question').value    = pollInfo['question'];
    $('gid').value         = pollInfo['gid'];
    if (pollInfo['start_time'] == null) pollInfo['start_time'] = '';
    if (pollInfo['stop_time']  == null) pollInfo['stop_time']  = '';
    $('start_time').value  = pollInfo['start_time'];
    $('stop_time').value   = pollInfo['stop_time'];
    $('select_type').value = pollInfo['select_type'];
    $('poll_type').value   = pollInfo['poll_type'];
    $('result_view').value = pollInfo['result_view'];
    $('visible').value     = pollInfo['visible'];
}

/**
 * Edit Poll Answers
 */
function editPollAnswers(element, pid)
{
    if (cachePollForm == null) {
        cachePollForm = $('p_work_area').innerHTML;
    }

    selectDataGridRow(element.parentNode.parentNode);

    if (cachePollAnswersForm == null) {
        cachePollAnswersForm = pollSync.pollanswersui();
    }
    currentAction = 'PollAnswers';

    selectedPoll = pid;
    $('legend_title').innerHTML = editAnswers_title;
    $('p_work_area').innerHTML = cachePollAnswersForm;
    var answersData = pollSync.getpollanswers(selectedPoll);
    var answers  = answersData['Answers'];
    $('question').value  = answersData['question'];

    var box = $('answers_combo');
    box.length = 0;
    for(var i = 0; i < answers.length; i++) {
        box.options[i] = new Option(answers[i]['answer'], answers[i]['id']);
    }
    $('answer').focus();
}

/**
 * Saves data / changes on the poll group's form
 */
function savePoll()
{
    if (currentAction == 'PollAnswers') {
        var box = $('answers_combo');
        var answers = new Object();
        if (box.length < 2) {
            alert(requiresTwoAnswers);
            return false;
        }
        for(var i = 0; i < box.length; i++) {
            answers[i] = new Array();
            answers[i]['id']     = box.options[i].value;
            answers[i]['answer'] = box.options[i].text;
        }
        pollAsync.updatepollanswers(selectedPoll, answers);
    } else {
        if (jawsTrim($('question').value) == '') {
            alert(incompletePollsFields);
            return false;
        }

        if (selectedPoll == null) {
            pollAsync.insertpoll(
                    $('question').value,
                    $('gid').value,
                    $('start_time').value,
                    $('stop_time').value,
                    $('select_type').value,
                    $('poll_type').value,
                    $('result_view').value,
                    $('visible').value);
        } else {
            pollAsync.updatepoll(
                    selectedPoll,
                    $('question').value,
                    $('gid').value,
                    $('start_time').value,
                    $('stop_time').value,
                    $('select_type').value,
                    $('poll_type').value,
                    $('result_view').value,
                    $('visible').value);
        }
    }
}

/**
 * Delete a poll
 */
function deletePoll(element, pid)
{
    stopAction();
    selectDataGridRow(element.parentNode.parentNode);
    if (confirm(confirmPollDelete)) {
        pollAsync.deletepoll(pid);
    }
    unselectDataGridRow();
}

/**
 *
 */
function keypressOnAnswer(event)
{
    keynum = (window.event)? event.keyCode : event.which;
    if(keynum == '13') {
        addAnswer();
    }
}

/**
 *
 */
function addAnswer()
{
    var answer = $('answer').value;
    $('answer').value = '';
    $('answer').focus();
    if (jawsTrim(answer) == '') return;
    var box = $('answers_combo');
    if (box.selectedIndex != -1) {
        box.options[box.selectedIndex].text = answer;
        box.selectedIndex = -1;
    } else {
        box.options[box.options.length] = new Option(answer, 0);
    }
}

/**
 *
 */
function delAnswer()
{
    var box = $('answers_combo');
    if (box.selectedIndex != -1) {
        box.options[box.selectedIndex] = null;
    }
    stopAnswer();
}

/**
 *
 */
function editAnswer()
{
    var box = $('answers_combo');
    if (box.selectedIndex == -1) return;
    var answer = box.options[box.selectedIndex].text;
    if (jawsTrim(answer) == '') return;
    $('answer').value = answer;
    $('answer').focus();
}

/**
 *
 */
function stopAnswer()
{
    var box = $('answers_combo');
    $('answer').value = '';
    box.selectedIndex = -1;
    $('answer').focus();
}

/**
 *
 */
function upAnswer()
{
    var box = $('answers_combo');
    if (box.selectedIndex < 1) return;
    var tmpText  = box.options[box.selectedIndex - 1].text;
    var tmpValue = box.options[box.selectedIndex - 1].value;
    box.options[box.selectedIndex - 1].text  = box.options[box.selectedIndex].text;
    box.options[box.selectedIndex - 1].value = box.options[box.selectedIndex].value;
    box.options[box.selectedIndex].text  = tmpText;
    box.options[box.selectedIndex].value = tmpValue;
    box.selectedIndex  = box.selectedIndex - 1;
}

/**
 *
 */
function downAnswer()
{
    var box = $('answers_combo');
    if (box.selectedIndex == -1) return;
    if (box.selectedIndex > box.length-2) return;
    var tmpText  = box.options[box.selectedIndex + 1].text;
    var tmpValue = box.options[box.selectedIndex + 1].value;
    box.options[box.selectedIndex + 1].text  = box.options[box.selectedIndex].text;
    box.options[box.selectedIndex + 1].value = box.options[box.selectedIndex].value;
    box.options[box.selectedIndex].text  = tmpText;
    box.options[box.selectedIndex].value = tmpValue;
    box.selectedIndex  = box.selectedIndex + 1;
}

/**
 * Edit poll group
 */
function editPollGroup(element, gid)
{
	if (gid == 0) return;

    currentAction = 'PollGroups';
    selectedPollGroup = gid;
    $('legend_title').innerHTML = editPollGroup_title;
    if (cachePollGroupsForm != null) {
        $('pg_work_area').innerHTML = cachePollGroupsForm;
    }

    selectDataGridRow(element.parentNode.parentNode);

    var groupInfo = pollSync.getpollgroup(selectedPollGroup);
    $('gid').value = groupInfo['id'];
    $('title').value  = groupInfo['title'];
    $('visible').selectedIndex = groupInfo['visible'];
}

/**
 * Show a simple-form with checkboxes so polls can check their group
 */
function editPollGroupPolls(element, gid)
{
    if (cachePollGroupsForm == null) {
        cachePollGroupsForm = $('pg_work_area').innerHTML;
    }

    selectDataGridRow(element.parentNode.parentNode);

    if (cachePollGroupPollsForm == null) {
        cachePollGroupPollsForm = pollSync.pollgrouppollsui();
    }

    currentAction = 'PollGroupPolls';
    selectedPollGroup = gid;
    $('legend_title').innerHTML = editPollGroupPolls_title;
    $('pg_work_area').innerHTML = cachePollGroupPollsForm;

    var pollsData  = pollSync.getpollgrouppolls(selectedPollGroup);
    var pollsList  = pollsData['Polls'];
    $('title').value  = pollsData['title'];
    if ($('pg_polls_combo')) {
        var inputs  = $('pg_polls_combo').getElementsByTagName('input');
        pollsList.each(function(value, index) {
            for (var i=0; i<inputs.length; i++) {
                if (value['id'] == inputs[i].value) {
                    inputs[i].checked= true;
                    break
                }
            }
        });   
    }
}

/**
 * Saves data / changes on the poll group's form
 */
function savePollGroup()
{
    if (currentAction == 'PollGroupPolls') {
        if ($('pg_polls_combo')) {
            var inputs  = $('pg_polls_combo').getElementsByTagName('input');
            var keys    = new Object();
            var counter = 0;
            for (var i=0; i<inputs.length; i++) {
                if (inputs[i].checked) {
                    keys[counter] = inputs[i].value;
                    counter++;
                }
            }
            pollAsync.addpollstopollgroup(selectedPollGroup, keys);
        }
    } else {
        if (jawsTrim($('title').value) == '') {
            alert(incompleteGroupsFields);
            return false;
        }

        if (selectedPollGroup == null) {
            pollAsync.insertpollgroup(
                            $('title').value,
                            $('visible').value);
        } else {
            pollAsync.updatepollgroup(
                            selectedPollGroup,
                            $('title').value,
                            $('visible').value);
        }
    }
}

/**
 * Delete poll group
 */
function deletePollGroup(element, gid)
{
    stopAction();
    selectDataGridRow(element.parentNode.parentNode);
    if (confirm(confirmPollGroupDelete)) {
        pollAsync.deletepollgroup(gid);
    }
    unselectDataGridRow();
}

function getGroupPolls(gid)
{
    var box = $('grouppolls');
    box.length = 0;
    $('result_area').innerHTML = '';
    $('result_box').getElementsByTagName('span')[0].innerHTML = '';
    if (gid == 0) return;
    var polls = pollSync.getgrouppolls(gid);
    for(var i = 0; i < polls.length; i++) {
        var op = new Option(polls[i]['question'], polls[i]['id']);
        if (i % 2 == 0) {
            op.style.backgroundColor = evenColor;
        } else {
            op.style.backgroundColor = oddColor;
        }
        box.options[i] = op;
    }
    box.selectedIndex == -1
}

function showResult(pid)
{
    var box = $('grouppolls');
    $('result_box').getElementsByTagName('span')[0].innerHTML = box.options[box.selectedIndex].text;
    $('result_area').innerHTML = pollSync.pollresultsui(pid);
}

var pollAsync = new polladminajax(PollCallback);
pollAsync.serverErrorFunc = Jaws_Ajax_ServerError;
pollAsync.onInit = showWorkingNotification;
pollAsync.onComplete = hideWorkingNotification;

var pollSync  = new polladminajax();
pollSync.serverErrorFunc = Jaws_Ajax_ServerError;
pollSync.onInit = showWorkingNotification;
pollSync.onComplete = hideWorkingNotification;

//Current poll
var selectedPoll = null;
//current poll group
var selectedPollGroup = null;

//Combo colors
var evenColor = '#fff';
var oddColor  = '#edf3fe';

//Cache for Poll template
var cachePollForm = null;
//Cache for Poll Answers template
var cachePollAnswersForm = null;
//Cache for PollGroups template
var cachePollGroupsForm = null;
//Cache for saving the PollGroup-Polls template
var cachePollGroupPollsForm = null;

//Which action are we runing?
var currentAction = null;

//Which row selected in DataGrid
var selectedRow = null;
var selectedRowColor = null;
