/**
 * Settings Javascript actions
 *
 * @category   Ajax
 * @package    Settings
 * @author     Jonathan Hernandez <ion@gluch.org.mx>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * Use async mode, create Callback
 */
var SettingsCallback = {
    updatebasicsettings: function(response) {
        showResponse(response);
    },

    updateadvancedsettings: function(response) {
        showResponse(response);
    },

    updatemetasettings: function(response) {
        showResponse(response);
    },

    updatemailsettings: function(response) {
        showResponse(response);
    },

    updateftpsettings: function(response) {
        showResponse(response);
    },

    updateproxysettings: function(response) {
        showResponse(response);
    }
}

/**
 * Update basic settings
 */
function submitBasicForm(form)
{
    var settingsArray = new Object;
    for (i=0; i<form.elements.length; i++) {
        settingsArray[form.elements[i].name] = form.elements[i].value;
    }
    settings.updatebasicsettings(settingsArray);
}

/**
 * Update advanced settings
 */
function submitAdvancedForm(form)
{
    var settingsArray = new Object;
    for (i=0; i<form.elements.length; i++) {
        settingsArray[form.elements[i].name] = form.elements[i].value;
    }
    settings.updateadvancedsettings(settingsArray);
}

/**
 * Update meta
 */
function submitMetaForm(form)
{
    var settingsArray = new Object;
    for (i=0; i<form.elements.length; i++) {
        settingsArray[form.elements[i].name] = form.elements[i].value;
    }
    settings.updatemetasettings(settingsArray);
}

/**
 * Update mailserver settings
 */
function submitMailSettingsForm(form)
{
    var settingsArray = new Object;
    for (i=0; i<form.elements.length; i++) {
        settingsArray[form.elements[i].name] = form.elements[i].value;
    }
    settings.updatemailsettings(settingsArray);
}

/**
 * Update ftpserver settings
 */
function submitFTPSettingsForm(form)
{
    var settingsArray = new Object;
    for (i=0; i<form.elements.length; i++) {
        settingsArray[form.elements[i].name] = form.elements[i].value;
    }
    settings.updateftpsettings(settingsArray);
}

/**
 * Update proxy settings
 */
function submitProxySettingsForm(form)
{
    var settingsArray = new Object;
    for (i=0; i<form.elements.length; i++) {
        settingsArray[form.elements[i].name] = form.elements[i].value;
    }
    settings.updateproxysettings(settingsArray);
}

function toggleGR() 
{
    if ($('use_gravatar').value == 'yes') {
        $('gravatar_rating').disabled = false;
    } else {
        $('gravatar_rating').disabled = true;
    }
}

function changeMailer()
{
    switch($('mailer').value) {
    case 'DISABLED':
        $('from_email').disabled = true;
        $('from_name').disabled  = true;
        $('sendmail_path').disabled = true;
        $('smtp_host').disabled  = true;
        $('smtp_port').disabled  = true;
        $('smtp_auth').disabled  = true;
        $('smtp_user').disabled  = true;
        $('smtp_pass').disabled  = true;
        break;
    case 'phpmail':
        $('from_email').disabled = false;
        $('from_name').disabled  = false;
        $('sendmail_path').disabled = true;
        $('smtp_host').disabled  = true;
        $('smtp_port').disabled  = true;
        $('smtp_auth').disabled  = true;
        $('smtp_user').disabled  = true;
        $('smtp_pass').disabled  = true;
        break;
    case 'sendmail':
        $('from_email').disabled = false;
        $('from_name').disabled  = false;
        $('sendmail_path').disabled = false;
        $('smtp_host').disabled  = true;
        $('smtp_port').disabled  = true;
        $('smtp_auth').disabled  = true;
        $('smtp_user').disabled  = true;
        $('smtp_pass').disabled  = true;
        break;
    case 'smtp':
        $('from_email').disabled = false;
        $('from_name').disabled  = false;
        $('sendmail_path').disabled = true;
        $('smtp_host').disabled  = false;
        $('smtp_port').disabled  = false;
        $('smtp_auth').disabled  = false;
        $('smtp_user').disabled  = false;
        $('smtp_pass').disabled  = false;
        break;
    }
}

var settings = new settingsadminajax(SettingsCallback);
settings.serverErrorFunc = Jaws_Ajax_ServerError;
settings.onInit = showWorkingNotification;
settings.onComplete = hideWorkingNotification;
