/**
 * Weather Javascript Actions
 *
 * @category   Ajax
 * @package    Webcam
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var WeatherCallback = { 
    newcity: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('weather_datagrid').addItem();
            $('weather_datagrid').setCurrentPage(0);
            getDG();
        }
        showResponse(response);        
    },

    deletecity: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('weather_datagrid').deleteItem();          
            getDG();
        }
        showResponse(response);
    },
    
    updatecity: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getDG();
        }
        showResponse(response);      
    },

    updateproperties: function(response) {
        showResponse(response);
    }
}

/**
 * Clean the form
 *
 */
function cleanForm(form) 
{
    form.reset();
    form.elements['hidden_code'].value = '';
    form.elements['action'].value = 'AddCity';
    form.elements['cancelform'].style.display = 'none';
    oldFriend = '';
}

/**
 * Update form with new values
 *
 */
function updateForm(code) 
{
    //Get the location 
    var location = weatherSync.code2location(code);

    $('city_form').elements['code'].value        = location;
    $('city_form').elements['hidden_code'].value = code;
    $('city_form').elements['action'].value      = 'UpdateCity';
    $('city_form').elements['cancelform'].style.display = 'block';
    oldCode = code;
}

/**
 * Add a city: function
 */
function addCity(form)
{
    var cityCode = form.elements['hidden_code'].value;
    try {
        weatherAsync.newcity(cityCode);
    } catch(e) {
        alert(e);
    }
    cleanForm(form);
}


/**
 * Update a City
 */
function updateCity(form)
{
    var cityCode = form.elements['hidden_code'].value;
    weatherAsync.updatecity(oldCode, cityCode);
    cleanForm(form);
}

/**
 * Submit the button
 */
function submitForm(form)
{
    if (form.elements['action'].value == 'AddCity') {
        addCity(form);
    } else {
        updateCity(form);
    }
}

/**
 * Delete a city : function
 */
function deleteCity(code)
{
    weatherAsync.deletecity(code);
    cleanForm($('city_form'));
}

/**
 * Edit a city
 *
 */
function editCity(code)
{
    updateForm(code);
}

/**
 * Update the properties
 *
 */
function updateProperties(form)
{
    var limit    = form.elements['limit_values'].value;
    var units    = form.elements['units'].value;
    var forecast = 'yes';
    var partner_id  = form.elements['partner_id'].value;;
    var license_key = form.elements['license_key'].value;;

    if (form.elements['show_forecast'][1].checked) {
        forecast = 'no';
    }
    weatherAsync.updateproperties(limit, units, forecast, partner_id, license_key);
}

/**
 * Copies the city name and not the code in the entry
 */
function autoCompleteLocation(liContent)
{
    var location = liContent.innerHTML;
    var code     = liContent.className;
    if (code != 'selected') {
        code = code.substr(5, code.indexOf(' ') - 5);
        $('weather_code').value = location;
        $('hidden_code').value  = code;
    } 
}

/**
 * Check code written by user
 */
function CheckCodeChange()
{
    var pattern = /^[A-Z]{4}[0-9]{4}/;
    var code    = $('weather_code').value;
    if (code.match(pattern)) {
        //Get the location 
        var weatherSync = new weatheradminajax();
        var location    = weatherSync.code2location(code);
        if (location != code) {
            $('weather_code').value = location;
            $('hidden_code').value  = code;
        } else {
            $('weather_code').value = code;
            $('hidden_code').value  = code;
        }
    }        
}

var weatherAsync = new weatheradminajax(WeatherCallback);
weatherAsync.serverErrorFunc = Jaws_Ajax_ServerError;
weatherAsync.onInit = showWorkingNotification;
weatherAsync.onComplete = hideWorkingNotification;

var weatherSync  = new weatheradminajax();
weatherSync.serverErrorFunc = Jaws_Ajax_ServerError;
weatherSync.onInit = showWorkingNotification;
weatherSync.onComplete = hideWorkingNotification;

var oldCode = '';
