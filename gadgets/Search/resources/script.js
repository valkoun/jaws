/**
 * Search Javascript actions
 *
 * @category   Ajax
 * @package    Search
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var SearchCallback = {
    savechanges: function(response) {
        showResponse(response);
    }
}

/**
 * Submit the button
 */
function saveChanges(form)
{
    var useWith = form.elements['use_with'].value;
    if (useWith == 'selected') {
        var pattern = /^gadgets\[\]/;
        var gadgets = new Object();
        var option  = null;
        var counter = 0;
        for(i=0; i<form.elements.length; i++) {
            if (pattern.test(form.elements[i].name)) {
                option = form.elements[i];
                if (option.checked) {
                    gadgets[counter] = option.value;
                    counter++;
                }
            }
        }
    } else {
        gadgets = '*';
    }
    search.savechanges(gadgets);
}

var search = new searchadminajax(SearchCallback);
search.serverErrorFunc = Jaws_Ajax_ServerError;
search.onInit = showWorkingNotification;
search.onComplete = hideWorkingNotification;
