/**
 * Languages Javascript actions
 *
 * @category   Ajax
 * @package    Languages
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * Use async mode, create Callback
 */
var LanguagesCallback = {
    savelanguage: function(response) {
        if (response[0]['css'] == 'notice-message') {
            setTimeout( "refresh()", 1000);
        }
        showResponse(response);
    },

    setlangdata: function(response) {
        if (response[0]['css'] == 'notice-message') {
            changeColorOfTranslatedTerms();
        }
        showResponse(response);
    },
    
	savesettings: function(response) {
        showResponse(response);
    }
}

/**
 * refresh page
 */
function refresh()
{
    document.location.reload();
}

/**
 * Add new language
 */
function save_lang()
{
    if (jawsTrim($('lang_code').value) != '' && jawsTrim($('lang_name').value) != '') {
        lang_str = jawsTrim($('lang_code').value) + ';' + jawsTrim($('lang_name').value);
        languagesAsync.savelanguage(lang_str);
    }
}

/**
 *
 */
function changeColorOfTranslatedTerms()
{
    var strings_elements = $('tbl_strings').getElementsByTagName('textarea');
    for(var i = 0; i < strings_elements.length; i++) {
        if (strings_elements[i].value != "") {
            strings_elements[i].parentNode.parentNode.getElementsByTagName('span')[0].style.color="#000";
        }
    }
}

/**
 *
 */
function filterTranslated()
{
    if ($('tbl_strings')) {
        var strings_elements = $('tbl_strings').getElementsByTagName('textarea');
        for(var i = 0; i < strings_elements.length; i++) {
            if ($('checkbox_filter').checked && strings_elements[i].value != "") {
                strings_elements[i].parentNode.parentNode.style.display = 'none';
            } else {
                strings_elements[i].parentNode.parentNode.style.display = 'inline';
            }
        }
    }
}

/**
 *
 */
function setButtonTitle(title)
{
    imgBtn = $('btn_lang').getElementsByTagName('img')[0];
    text = document.createTextNode(' ' + title);
    $('btn_lang').innerHTML = '';
    $('btn_lang').appendChild(imgBtn);
    $('btn_lang').appendChild(text);
}

/**
 *
 */
function change_lang_option()
{
    if (LangDataChanged) {
        var answer = confirm(confirmSaveData);
        if (answer) {
            save_lang_data();
        }
        LangDataChanged = false;
    }

    if ($('lang').selectedIndex == 0) {
        $('btn_export').disabled = true;
        $('lang_code').disabled  = false;
        $('component').disabled  = true;
        $('lang_code').value = '';
        $('lang_name').value = '';
        setButtonTitle(add_language_title);
        $('lang_code').focus();
        stopAction();
        return;
    } else {
        $('btn_export').disabled = false;
        $('lang_code').disabled  = true;
        $('component').disabled  = false;
        $('lang_code').value = $('lang').options[$('lang').selectedIndex].value;
        $('lang_name').value = $('lang').options[$('lang').selectedIndex].text;
        setButtonTitle(save_language_title);
    }

    lang = $('lang').value;
    component = $('component').value;

    if (jawsTrim($('lang').value) != '' && jawsTrim($('component').value) != '') {
        $('btn_save').style.visibility = 'visible';
        $('btn_cancel').style.visibility = 'visible';
        $('lang_strings').innerHTML = languagesSync.getlangdataui($('component').value, $('lang').value);
        filterTranslated();
    }
}

/**
 *
 */
function save_lang_data()
{
    if (jawsTrim(lang) == '' || jawsTrim(component) == '') {
        // display message there
        return;
    }

    var data = new Object();
    var meta_elements = document.getElementById('meta_lang').getElementsByTagName('input');
    data['meta'] = new Object();
    for(var i = 0; i < meta_elements.length; i++) {
        data['meta'][meta_elements[i].name] = meta_elements[i].value;
    }

    var strings_elements = document.getElementById('tbl_strings').getElementsByTagName('textarea');
    data['strings'] = new Object();
    for(var i = 0; i < strings_elements.length; i++) {
        data['strings'][strings_elements[i].name] = strings_elements[i].value;
    }

    languagesAsync.setlangdata(component, lang, data);
    LangDataChanged = false;
    data = null;
}

/**
 * Save settings
 */
function saveSettings()
{
	var language_choices = $('language_choices').value.split(',');
    var gadgets   = new Object();
    
	for(var g = 0; g < language_choices.length; g++) {
		if (document.getElementById(language_choices[g])) {
			//alert('current gadget : ' + language_choices[g] + ' value : ' + document.getElementById(language_choices[g]).value);
			gadgets[language_choices[g]] = document.getElementById(language_choices[g]).value;
		}
	}
   languagesAsync.savesettings(gadgets);
}


/**
 * Stops doing a certain action
 */
function stopAction()
{
    $('btn_save').style.visibility   = 'hidden';
    $('btn_cancel').style.visibility = 'hidden';
    $('component').selectedIndex = -1;
    $('lang_strings').innerHTML = '';
    LangDataChanged = false;
}

/**
 * Export language
 */
function export_lang()
{
    window.location= base_script + '?gadget=Languages&action=Export&lang=' + $('lang').value;
}

var languagesAsync = new languagesadminajax(LanguagesCallback);
languagesAsync.serverErrorFunc = Jaws_Ajax_ServerError;
languagesAsync.onInit = showWorkingNotification;
languagesAsync.onComplete = hideWorkingNotification;

var languagesSync  = new languagesadminajax();
languagesSync.serverErrorFunc = Jaws_Ajax_ServerError;
languagesSync.onInit = showWorkingNotification;
languagesSync.onComplete = hideWorkingNotification;

//data language changed?
var LangDataChanged = false

//Which language are selected?
var lang = '';

//Which component are selected?
var component = '';

//New language string
var lang_str = '';
