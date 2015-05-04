/**
 * TMS (Theme Management System) Javascript actions
 *
 * @category   Ajax
 * @package    Tms
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * Use async mode, create Callback
 */
var TmsCallback = {
    sharetheme: function(response) {
        var optionSelected = $('themes_combo').options[$('themes_combo').selectedIndex];
        if (response[0]['css'] == 'notice-message') {
            optionSelected.className = 'isshared';
            $('unshare_button').style.display = 'block';
            $('share_button').style.display   = 'none';
        } else {
            optionSelected.className          = 'isnotshared';
            $('unshare_button').style.display = 'none';
            $('share_button').style.display   = 'block';
        }
        showResponse(response);
    },
    
    unsharetheme: function(response) {
        var optionSelected = $('themes_combo').options[$('themes_combo').selectedIndex];
        if (response[0]['css'] == 'notice-message') {
            optionSelected.className = 'isnotshared';
            $('unshare_button').style.display = 'none';
            $('share_button').style.display   = 'block';
        } else {
            optionSelected.className = 'isshared';
            $('unshare_button').style.display = 'block';
            $('share_button').style.display   = 'none';
        }
        showResponse(response);
    },    
    
    installtheme: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('only_show').selectedIndex = 0;
            updateView();
            $('themes_combo').value = selectedTheme;
            editTheme(selectedTheme);
        }
        showResponse(response);
    },

    uninstalltheme: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('only_show').selectedIndex = 0;
            updateView();
        }
        showResponse(response);
    },

    enabletheme: function(response) {
        showResponse(response);
    },

    newrepository: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('repositories_datagrid').addItem();
            $('repositories_datagrid').setCurrentPage(0);
        }
        showResponse(response);
        getDG();
    },

    deleterepository: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('repositories_datagrid').deleteItem();          
        }
        showResponse(response);
        getDG();
    },

    getrepository: function(response) {
        updateForm(response);
    },

    updaterepository: function(response) {
        showResponse(response);
        getDG();
    },
    
    savesettings: function(response) {
        showResponse(response);
    }
}

/**
 * Show the buttons depending on the current tab and
 * the items to show
 */
function showButtons()
{
    if ($('only_show').value == 'local') {
        $('enable_button').style.display    = 'block';
        $('uninstall_button').style.display = 'block';
        $('install_button').style.display   = 'none';
        $('unshare_button').style.display   = 'none';
        $('share_button').style.display     = 'none';
    } else {
        $('enable_button').style.display    = 'none';
        $('uninstall_button').style.display = 'none';
        $('install_button').style.display   = 'block';
        $('unshare_button').style.display   = 'none';
        $('share_button').style.display     = 'none';
    }
}

/**
 * Edits a theme showing basic info about it
 */
function editTheme(theme)
{
    if (jawsTrim(theme) == '') {
        return false;
    }

    cleanWorkingArea(true);

    var themeInfo = tmsSync.getthemeinfo(theme, $('only_show').value);
    if (themeInfo == null) {
        return false; //Check
    }
    selectedTheme = theme;
    $('theme_area').innerHTML = themeInfo;
    showButtons();

    //Is selected theme is shared?
    var optionSelected = $('themes_combo').options[$('themes_combo').selectedIndex];
    if ($('only_show').value == 'local') {
        if (optionSelected.className == 'isshared') {
            $('unshare_button').style.display = 'block';
        } else {
            $('share_button').style.display = 'block';
        }
    } else {
        $('unshare_button').style.display = 'none';
        $('share_button').style.display   = 'none';
    }
}

/**
 * Clean the working area
 */
function cleanWorkingArea(hideButtons)
{
    $('theme_area').innerHTML = '';
    if (hideButtons != undefined) {
        if (hideButtons == true) {
            var buttons = new Array('enable_button',  'uninstall_button', 'share_button', 
                                    'unshare_button', 'install_button');
            for(var i=0; i<buttons.length; i++) {
                if ($(buttons[i]) != undefined) {
                    $(buttons[i]).style.display = 'none';
                }
            }
        }
    }
}

/**
 * Installs a theme
 */
function installTheme()
{
    tmsAsync.installtheme(selectedTheme, $('only_show').value);
}

/**
 * Uninstall a theme
 */
function uninstallTheme()
{
    var answer = confirm(confirmUninstallTheme);
    if (answer) {
        tmsAsync.uninstalltheme(selectedTheme);
    }
}

/**
 * Shares a theme
 */
function shareTheme()
{
    tmsAsync.sharetheme(selectedTheme);
}

/**
 * Unshares a theme
 */
function unshareTheme()
{
    tmsAsync.unsharetheme(selectedTheme);
}

/**
 * Enables a theme as the default one
 */
function enableTheme()
{
    var answer = confirm(confirmEnableTheme);
    if (answer) {
        tmsAsync.enabletheme(selectedTheme);
    }
}

function uploadTheme()
{
    document.theme_upload_form.submit();
}

/**
 * Fill the themes combo
 */
function getThemes()
{
    resetCombo($('themes_combo'));
    var themesList  = tmsSync.getthemes($('only_show').value);
    var found       = false;
    for(theme in themesList) {
        if (themesList[theme]['license'] == undefined) {
            continue;
        }
        var op   = new Option();
        op.value = theme;
        op.text  = theme + ' - ' + themesList[theme]['name'];
        if (themesList[theme]['desc'] == 'Unknown') {
            op.title = themesList[theme]['name'];
        } else {
            op.title = themesList[theme]['desc'];
        }
        
        if (themesList[theme]['isshared'] == true) {
            op.className = 'isshared';            
        } else {
            op.className = 'isnotshared';            
        }
        $('themes_combo').options[$('themes_combo').options.length] = op;
        found = true;
    }

    if (found == false) {
        var op   = new Option();
        op.value = '-';
        op.text  = noAvailableData;
        $('themes_combo').options[$('themes_combo').options.length] = op;
    }
    paintCombo($('themes_combo'), oddColor, evenColor);
}

/**
 * Updates the theme view
 */
function updateView()
{
    cleanWorkingArea(true);
    getThemes();
}

/**
 * Cleans the form
 */
function cleanForm(form) 
{
    form.elements['name'].value   = '';
    form.elements['url'].value    = 'http://';  
    form.elements['id'].value     = '';    
    form.elements['action'].value = 'AddRepository';
}

/**
 * Updates form with new values
 */
function updateForm(repositoryInfo) 
{
    $('repositories_form').elements['name'].value   = repositoryInfo['name'];
    $('repositories_form').elements['url'].value    = repositoryInfo['url'];
    $('repositories_form').elements['id'].value     = repositoryInfo['id'];
    $('repositories_form').elements['action'].value = 'UpdateRepository';
}

/**
 * Add a repository
 */
function addRepository(form)
{
    var name = form.elements['name'].value;
    var url  = form.elements['url'].value;
    
    tmsAsync.newrepository(name, url);
    cleanForm(form);
}

/**
 * Updates a repository
 */
function updateRepository(form)
{
    var name = form.elements['name'].value;
    var url  = form.elements['url'].value;
    var id   = form.elements['id'].value;

    tmsAsync.updaterepository(id, name, url);
    cleanForm(form);
}

/**
 * Submit the 
 */
function submitForm(form)
{
    if (form.elements['action'].value == 'UpdateRepository') {
        updateRepository(form);
    } else {
        addRepository(form);
    }
}

/**
 * Deletes a repository
 */
function deleteRepository(id)
{
    tmsAsync.deleterepository(id);
    cleanForm($('repositories_form'));
}

/**
 * Edits a repository
 */
function editRepository(id)
{
    tmsAsync.getrepository(id);
}

/**
 * Saves settings
 */
function saveSettings()
{
    tmsAsync.savesettings($('share_themes').value);
}

var tmsAsync = new tmsadminajax(TmsCallback);
tmsAsync.serverErrorFunc = Jaws_Ajax_ServerError;
tmsAsync.onInit = showWorkingNotification;
tmsAsync.onComplete = hideWorkingNotification;

var tmsSync  = new tmsadminajax();
tmsSync.serverErrorFunc = Jaws_Ajax_ServerError;
tmsSync.onInit = showWorkingNotification;
tmsSync.onComplete = hideWorkingNotification;

var selectedTheme = null;

var evenColor = '#fff';
var oddColor  = '#edf3fe';
