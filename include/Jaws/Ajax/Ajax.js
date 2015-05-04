/**
 * Repaints a combo
 */
function paintCombo(combo, oddColor, evenColor)
{
    if (evenColor == undefined) {
        evenColor = '#fff';
    }

    var color  = evenColor;
    for(var i=0; i<combo.length; i++) {
        combo.options[i].style.backgroundColor = color;;
        if (i % 2 == 0) {
            color = oddColor;
        } else {
            color = evenColor;
        }
    }
}

/**
 * Change the value of the editor/textarea
 */
function changeEditorValue(name, value)
{
    var usingMCE = typeof tinyMCE == 'undefined' ? false : true;
    var usingFCK = typeof FCKeditorAPI == 'undefined' ? false : true;
    if (usingMCE) {
        var editor = tinyMCE.get(name);
        if (editor) {
            editor.setContent(value);
         } else {
            $(name).value = value;
         }
    } else if (usingFCK) {
        var editor = FCKeditorAPI.GetInstance(name);
        if (editor.Status == FCK_STATUS_NOTLOADED) {
            $(name).value = value;
        } else {
            editor.SetData(value);
        }
    } else {
        $(name).value = value;
    }
}

/**
 * Get the value of the editor/textarea
 */
function getEditorValue(name)
{
    var usingMCE = typeof tinyMCE == 'undefined' ? false : true;
    var usingFCK = typeof FCKeditorAPI == 'undefined' ? false : true;
    if (usingMCE) {
        var editor = tinyMCE.get(name);
        return editor.getContent();
    } else if (usingFCK) {
        var editor = FCKeditorAPI.GetInstance(name);
        if (editor.Status != FCK_STATUS_NOTLOADED) {
            return editor.GetXHTML(true);   // "true" means you want it formatted.
        }
    }

    return $(name).value;
}

/**
 * Reset a (piwi)datagrid:
 *  - Clean all data
 *  - Set the new data
 *  - Repaint
 */
function resetGrid(name, data, rowsSize)
{
    $(name).reset();
    $(name).fillWithArray(data);
    if (rowsSize != undefined) {
        $(name).rowsSize = rowsSize;
    }
    $(name).updatePageCounter();
    $(name).repaint();
}

//Which row selected in DataGrid
var selectedRows = new Array();
var selectedRowsColor = new Array();

/**
 * Select a (piwi)datagrid row
 */
function selectGridRow(name, rowElement)
{
    if (selectedRows[name]) {
        if (typeof(selectedRows[name]) == 'object') {
            selectedRows[name].style.backgroundColor = selectedRowsColor[name];
        } else {
            $(selectedRows[name]).style.backgroundColor = selectedRowsColor[name];
        }
    }

    if (typeof(rowElement) == 'object') {
        selectedRowsColor[name] = rowElement.style.backgroundColor;
        rowElement.style.backgroundColor = '#ffffcc';
    } else {
        selectedRowsColor[name] = $(rowElement).style.backgroundColor;
        $(rowElement).style.backgroundColor = '#ffffcc';
    }

    selectedRows[name] = rowElement;
}

/**
 * Unselect a (piwi)datagrid row
 *
 */
function unselectGridRow(name)
{
    if (selectedRows[name]) {
        if (typeof(selectedRows[name]) == 'object') {
            selectedRows[name].style.backgroundColor = selectedRowsColor[name];
        } else {
            $(selectedRows[name]).style.backgroundColor = selectedRowsColor[name];
        }
    }

    selectedRows[name] = null;
    selectedRowsColor[name] = null;
}

/**
 * Class JawsDatagrid
 */
var JawsDataGrid = {

    /**
     * Get the first Values and prepares the datagrid
     */
    getFirstValues: function() {
        var firstValues = $(this.name).getFirstPagerValues();
        var ajaxObject  = $(this.name).objectName;
        var result      = ajaxObject.getdata(firstValues, $(this.name).id);
        resetGrid($(this.name), result);
        $(this.name).firstPage();
    },

    /**
     * Get the previous Values and prepares the datagrid
     */
    getPreviousValues: function() {
        var previousValues = $(this.name).getPreviousPagerValues();
        var ajaxObject     = $(this.name).objectName;
        var result         = ajaxObject.getdata(previousValues, $(this.name).id);
        resetGrid($(this.name), result);
        $(this.name).previousPage();
    },

    /**
     * Get the next Values and prepares the datagrid
     */
    getNextValues: function() {
        var nextValues     = $(this.name).getNextPagerValues();
        var ajaxObject     = $(this.name).objectName;
        var result         = ajaxObject.getdata(nextValues, $(this.name).id);
        resetGrid($(this.name), result);
        $(this.name).nextPage();
    },

    /**
     * Get the last Values and prepares the datagrid
     */
    getLastValues: function() {
        var lastValues = $(this.name).getLastPagerValues();
        var ajaxObject = $(this.name).objectName;
        var result     = ajaxObject.getdata(lastValues, $(this.name).id);
        resetGrid($(this.name), result);
        $(this.name).lastPage();
    },

    /**
     * Only retrieves information with the current page the pager has and prepares the datagrid
     */
    getData: function() {
        var currentPage = $(this.name).getCurrentPage();
        var ajaxObject  = $(this.name).objectName;
        var result      = ajaxObject.getdata(currentPage, $(this.name).id);
        resetGrid($(this.name), result);
    }
}

/**
 * Prepares the datagrid with basic data
 */
function initDataGrid(name, objectName, dataFunc)
{
    if ($(name) == undefined || objectName == undefined) {
        return true;
    }

    $(name).objectName = objectName;
    if (dataFunc == undefined) {
        JawsDataGrid.name = name;
        $(name + '_pagerFirstAnchor').onclick = function() {
            JawsDataGrid.getFirstValues();
        }

        $(name + '_pagerPreviousAnchor').onclick = function() {
            JawsDataGrid.getPreviousValues();
        }

        $(name + '_pagerNextAnchor').onclick = function() {
                JawsDataGrid.getNextValues();
        }

        $(name + '_pagerLastAnchor').onclick = function() {
                JawsDataGrid.getLastValues();
        }

        getDG();
    } else {
        $(name).dataFunc = dataFunc;

        $(name + '_pagerFirstAnchor').onclick = function() {
            var offset = $(name).getFirstPagerValues();
            getDG(name, offset);
            $(name).firstPage();
        }

        $(name + '_pagerPreviousAnchor').onclick = function() {
            var offset = $(name).getPreviousPagerValues();
            getDG(name, offset);
            $(name).previousPage();
        }

        $(name + '_pagerNextAnchor').onclick = function() {
            var offset = $(name).getNextPagerValues();
            getDG(name, offset);
            $(name).nextPage();
        }

        $(name + '_pagerLastAnchor').onclick = function() {
            var offset = $(name).getLastPagerValues();
            getDG(name, offset);
            $(name).lastPage();
        }

        getDG(name);
    }
}

/**
 * Fast method to retrieve datagrid data
 */
function getDG(name, offset, reset)
{
    if (name == undefined) {
        JawsDataGrid.getData();
    } else {
        dataFunc = eval($(name).dataFunc);

        if (offset == undefined) {
            var offset = $(name).getCurrentPage();
        }

        reset = (reset == true) || ($(name).rowsSize == 0);
        dataFunc(name, offset, reset);
        if (reset) {
            $(name).setCurrentPage(0);
        }
    }
}


/**
 * Changes the text of a button with a stock
 */
function changeButtonText(button, message)
{
    var buttonInner = button.innerHTML.substr(0, button.innerHTML.indexOf("&nbsp;"));
    button.innerHTML = buttonInner + "&nbsp;" + message;
    button.value     = message;
}

/**
 * Similar to PHP-trim fuction
 */
function jawsTrim(str)
{
    return str.replace(/^\s*|\s*$/g, '');
}

function initJawsObservers()
{
    var forms = document.getElementsByTagName('form');
    for(var i=0; i<forms.length; i++) {
        new Form.Observer(forms[i], 1, jawsFormCallback);
    }
}


function jawsFormCallback(form, elements)
{
    elements = elements.split('&');
    for(var i=0; i<elements.length; i++) {
        var elementName = elements[i].split('=')[0];
        var element     = form.elements[elementName];
        switch(element.type) {
        case 'text':
            element.value = element.value.unescapeHTML();
            break;
        }
        //var element = $(element
        //alert($elements.type);
    }
}

/**
 * Creates an image link
 *
 *   <a href="link"><img src="imgSrc" border="0" title="text" /></a>
 */
function createImageLink(imgSrc, link, text, space)
{
    var linkElement = document.createElement('a');
    linkElement.href = link;
    if (space == true) {
        linkElement.style.paddingRight = '3px';
    }

    var image = document.createElement('img');
    image.border = '0';
    image.src = imgSrc;
    image.title = text;

    linkElement.appendChild(image);

    return linkElement;
}

/**
 * Resets a combo
 */
function resetCombo(combo)
{
    while(combo.options.length != 0) {
        combo.options[0] = null;
    }
}

/**
 * Prepares the datepicker with basic data
 */
function initDatePicker(name)
{
    Calendar.setup({
        inputField: name,
        ifFormat: "%Y-%m-%d %H:%M:%S",
        button: name + "_button",
        singleClick: true,
        weekNumbers: false,
        firstDay: 0,
        date: $(name).value,
        showsTime: true,
        multiple: false});
}
