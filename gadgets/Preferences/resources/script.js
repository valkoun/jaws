/**
 * Preferences Javascript actions
 *
 * @category   Ajax
 * @package    Preferences
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var PreferencesCallback = {
    updatepreferences: function(response) {
        showResponse(response);
    }
}

/**
 * Update preferences
 */
function updatePreferences()
{
    var preferences_config = new Object();
    preferences_config['display_theme']             = document.getElementsByName('display[]')[0].checked;
    preferences_config['display_editor']            = document.getElementsByName('display[]')[1].checked;
    preferences_config['display_language']          = document.getElementsByName('display[]')[2].checked;
    preferences_config['display_calendar_type']     = document.getElementsByName('display[]')[3].checked;
    preferences_config['display_calendar_language'] = document.getElementsByName('display[]')[4].checked;
    preferences_config['display_date_format']       = document.getElementsByName('display[]')[5].checked;
    preferences_config['display_timezone']          = document.getElementsByName('display[]')[6].checked;
    preferences_config['cookie_precedence']         = document.getElementsByName('display[]')[7].checked;

    preferences.updatepreferences(preferences_config);
}

var preferences = new preferencesadminajax(PreferencesCallback);
preferences.serverErrorFunc = Jaws_Ajax_ServerError;
preferences.onInit = showWorkingNotification;
preferences.onComplete = hideWorkingNotification;