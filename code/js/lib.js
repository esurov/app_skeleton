function a(obj) {
    alert(obj);
}

function aa(obj) {
    if (typeof(obj) == "object") {
        objStr = typeof(obj) + '\n';
        for (var i in obj) {
            objStr += '.' + i + '=' + obj[i] + '\n';
        }
        a(objStr);
    } else {
        a(obj);
    }
}

function focusElement(element) {
    if (
        element != null &&
        element.focus &&
        element.type != 'hidden' &&
        !element.disabled
    ) {
        element.focus();
    }
}

function focusFormElement(form, elementName) {
    if (typeof(form) == "string") {
        form = document.forms[form];
    }
    focusElement(form.elements[elementName]);
}

function selectElement(element) {
    if (
        element != null &&
        element.select &&
        element.type != 'hidden'
    ) {
        element.select();
    }
}

function getFormElement(form, elementName) {
    if (typeof(form) == "string") {
        form = document.forms[form];
    }
    var elements = form.elements;
    for (var j = 0; j < elements.length; j++) {
        if (elements[j].name == elementName) {
            return elements[j];
        }
    }
    return null;
}

function getElementValue(element) {
    if (element == null) {
        return null;
    }
    switch (element.type) {
    case 'radio':
        var elements = element.form.elements;
        for (var j = 0; j < elements.length; j++) {
            var currentElement = elements[j];
            if (
                currentElement.name == element.name &&
                currentElement.type == 'radio' &&
                currentElement.checked
            ) {
                return currentElement.value;
            }
        }
        break;
    default:
        return element.value;
    }
    return null;
}

function getFormElementValue(form, elementName) {
    return getElementValue(getFormElement(form, elementName));
}

function setElementValue(element, value) {
    if (element == null) {
        return;
    }
    switch (element.type) {
    case 'radio':
        var elements = element.form.elements;
        for (j = 0; j < elements.length; j++) {
            if (elements[j].name == element.name && elements[j].value == value) {
                elements[j].checked = true;
                return;
            }
        }
        break;
    default:
        element.value = value;
    }
    return;
}

function displayElement(element) {
    if (element != null) {
        element.style.display = "inline";
    }
}

function hideElement(element) {
    if (element != null) {
        element.style.display = "none";
    }
}

function getElementVisibility(element) {
    if (element == null) {
        return null;
    } else {
        return element.style.display;
    }
}

function toggleElementVisibility(element) {
    if (getElementVisibility(element) == "none") {
        displayElement(element);
    } else {
        hideElement(element);
    }
}

function displayGroupElementById(elementIds, elementIdToDisplay) {
    for (var i = 0; i < elementIds.length; i++) {
        var element = document.getElementById(elementIds[i]);
        if (elementIds[i] == elementIdToDisplay) {
            displayElement(element);
        } else {
            hideElement(element);
        }
    }
}

function checkFormCheckboxes(form, checkboxName, shouldCheck) {
    if (typeof(form) == "string") {
        form = document.forms[form];
    }

    var elements = form.elements;
    for (var i = 0; i < elements.length; i++) {
        if (elements[i].name == checkboxName) {
            elements[i].checked = shouldCheck;
        }
    }
}

function doFormSubmit(form, elementName, elementValue) {
    var element = getFormElement(form, elementName);
    element.value = elementValue;
    element.form.submit();
}

function ifConfirmed(message_text) {
    return confirm(message_text);
}

function copyToClipboard(element) {
    selectElement(element);
    element.createTextRange().execCommand("Copy");
}

function getCurrentYear() {
    var d = new Date();
    return d.getFullYear();
}

// Popup windows

// Opens new window with automatically calculated at server side width and height
// Usually used to open popup windows with dynamic content
function openPopup(url, windowName, useScroll) {
    if (windowName == null) {
        windowName = 'popup';
    }

    var useScrollStr = (useScroll == null) ? 'yes' : '';

    var w = null;
    if (window.name == windowName) {
        w = window;
        w.location.href = url;
    } else {
        // Default width and height of new popup
        var popupWidth = 500;
        var popupHeight = 400;
        var cornerX = (screen.width - popupWidth) / 2;
        var cornerY = (screen.height - popupHeight) / 2 - 32;
        
        w = window.open(
            url + '&new_popup=1',
            windowName,
            'width=' + popupWidth + ',height=' + popupHeight +
            ',left=' + cornerX + ',top=' + cornerY +
            ',scrollbars=' + useScrollStr + ',resizable'
        );
    }

    if (w != null) {
        w.focus();
    }
    
    return w;
}

// Opens new window with specified width and height
// Usually used to open popup windows with static content
function openWindow(url, width, height, useScroll, windowName) {
    var cornerX = (screen.width - width) / 2;
    var cornerY = (screen.height - height) / 2 - 32;
    
    var useScrollStr = (useScroll == null) ? 'yes' : '';

    if (windowName == null) {
        windowName = 'popup';
    }

    var w = window.open(
        url,
        windowName,
        'width=' + width + ',height=' + height +
        ',left=' + cornerX + ',top=' + cornerY +
        ',scrollbars=' + useScrollStr + ',resizable'
    );
    
    if (w != null) {
        w.focus();
    }
    
    return w;
}

function closeWindow(w) {
    if (w == null) {
        w = window;
    }
    
    if (w.opener != null) {
        w.opener.focus();
    }
    
    w.close();
}

function reloadOpenerWindow() {
    if (window.opener != null) {
        window.opener.location.reload();
    }
}

function acceptChoice(formName, element1Name, value1, element2Name, value2) {
    var element1 = window.opener.document.forms[formName].elements[element1Name];
    element1.value = value1;

    if (element2Name != null && value2 != null) {
        var element2 = window.opener.document.forms[formName].elements[element2Name];
        element2.value = value2;
    }

    // onchange is not fired automatically ;(
    if (element1.onchange) {
        element1.onchange();
    }
    window.opener.focus();
    window.close();
}

//

dependencies = new Array();

function Dependency(formName, mainSelectName, dependentSelectName, dependencyArray) {
    this.formName = formName;
    this.mainSelectName = mainSelectName;
    this.dependentSelectName = dependentSelectName;
    this.dependencyArray = dependencyArray;

    this.mainSelectValues = new Array();
    this.mainSelectCaptions = new Array();

    this.dependentSelectValues = new Array();
    this.dependentSelectCaptions = new Array();

    this.init = init;
    this.storeMainSelectData = storeMainSelectData;
    this.storeDependentSelectData = storeDependentSelectData;
    this.update = update;
}

function init() {
    var form = document.forms[this.formName];

    this.mainSelect = form.elements[this.mainSelectName];
    this.dependentSelect = form.elements[this.dependentSelectName];

    var oldDependentSelectValue = getElementValue(this.dependentSelect);

    this.storeMainSelectData();
    this.storeDependentSelectData();

    updateDependentSelect(this.mainSelect, this.dependentSelectName);

    setElementValue(this.dependentSelect, oldDependentSelectValue);
}

function storeMainSelectData() {
    var options = this.mainSelect.options;
    for (var i = 0; i < options.length; i++) {
        this.mainSelectValues[i] = options[i].value;
        this.mainSelectCaptions[i] = options[i].text;
    }
}

function storeDependentSelectData() {
    var options = this.dependentSelect.options;
    for (var i = 0; i < options.length; i++) {
        this.dependentSelectValues[i] = options[i].value;
        this.dependentSelectCaptions[i] = options[i].text;
    }
}

function update() {
    var caption, value;

    var currentOptionValue = getElementValue(this.mainSelect);

    var dependencyArrayIndex = getArrayIndexByValue(
        this.mainSelectValues,
        currentOptionValue
    );

    var values = this.dependencyArray[dependencyArrayIndex];
    this.dependentSelect.options.length = 0;
    for (var i = 0; i < values.length; i++) {
        value = values[i];
        caption = getSelectCaptionByValue(
            this.dependentSelectValues,
            this.dependentSelectCaptions,
            value
        );
        if (caption != null) {
            this.dependentSelect.options[i] = new Option(caption, value);
        }
    }
}

function setSelectValue(obj, newValue) {
    if (isValueInSelectOptions(obj, newValue)) {
        obj.value = newValue;
    } else {
        obj.value = 0; // value 0 must be present in dependency
    }
}

function isValueInSelectOptions(obj, value) {
    var options = obj.options;
    for (var i = 0; i < options.length; i++) {
        if (options[i].value == value) {
            return true;
        }
    }
    return false;
}

function getArrayIndexByValue(valuesArray, value) {
    for (var i = 0; i < valuesArray.length; i++) {
        if (valuesArray[i] == value) {
            return i;
        }
    }
    return 0;
}

function getSelectCaptionByValue(selectValuesArray, selectCaptionsArray, value) {
    for (var i = 0; i < selectValuesArray.length; i++) {
        if (selectValuesArray[i] == value) {
            return selectCaptionsArray[i];
        }
    }
    return null;
}

function getDependency(formName, mainName, dependentName) {
    for (var i = 0; i < dependencies.length; i++) {
        if (
            dependencies[i].formName == formName && 
            dependencies[i].mainSelectName == mainName && 
            dependencies[i].dependentSelectName == dependentName 
        ) {
            return dependencies[i];
        }
    }
    return null;
}

function updateDependentSelect(mainSelect, dependentSelectName) {
    var formName = mainSelect.form.name;
    var mainSelectName = mainSelect.name;

    var dependency = getDependency(formName, mainSelectName, dependentSelectName);
    if (dependency != null) {
        dependency.update();
    }
}

function initDependencies() {
    for (var i = 0; i < dependencies.length; i++) {
        dependencies[i].init();
    }
}

//

validateConditions = new Array();

function validateForm(form, conditions) {
    var elements = form.elements;
    for (var i = 0; i < conditions.length; i++) {
        var validationErrorMsg = conditions[i].validate(form);
        if (validationErrorMsg != null) {
            alert(validationErrorMsg.text);
            focusElement(validationErrorMsg.element);
            selectElement(validationErrorMsg.element);
            return false;
        }
    }
    return true;
}

function ValidateCondition(elementName, type, messageText, params, dependentCondition) {
    this.elementName = elementName;
    this.type = type;
    this.messageText = messageText;
    this.params = params;
    this.dependentCondition = dependentCondition;

    this.validate = function (form) {
        var elements = form.elements;
        for (var j = 0; j < elements.length; j++) {
            var element = elements[j];
            if (element.name == this.elementName) {
                if (!this.validateElement(element)) {
                    if (this.messageText == null) {
                        return null;
                    } else {
                        return new ValidationErrorMsg(this.messageText, element);
                    }
                }
            }    
        }
        return (this.dependentCondition == null) ?
            null :
            this.dependentCondition.validate(form);
    }

    this.validateElement = function (element) {
        var result;
        var value = getElementValue(element);
        
        switch (this.type) {
        case 'regexp':
            var re = eval(params[0]);
            result = (value.match(re)) ? true : false;
            break;
        case 'empty':
            result = (value.match(/^\s*$/)) ? true : false;
            break;
        case 'not_empty':
            result = (value.match(/^\s*$/)) ? false : true;
            break;
        case 'email':
            result = (value.match(/.+@.+\..+/)) ? true : false;
            break;
        case 'equal':
            result = (value == params[0]);
            break;
        case 'not_equal':
            result = (value != params[0]);
            break;
        case 'zip':
            result = (value.match(/^\d{5,}$/)) ? true : false;
            break;
        case 'phone':
            result = (value.match(/^[0-9 \+\-\(\)]+$/)) ? true : false;
            break;
        case 'number_integer':
            result = isInteger(value);
            break;
        case 'number_double':
            result = isDouble(value);
            break;
        case 'number_equal':
            result = (getJsDouble(value) == parseFloat(params[0]));
            break;
        case 'number_not_equal':
            result = (getJsDouble(value) != parseFloat(params[0]));
            break;
        case 'number_greater':
            result = (getJsDouble(value) > parseFloat(params[0]));
            break;
        case 'number_greater_equal':
            result = (getJsDouble(value) >= parseFloat(params[0]));
            break;
        case 'number_less':
            result = (getJsDouble(value) < parseFloat(params[0]));
            break;
        case 'number_less_equal':
            result = (getJsDouble(value) <= parseFloat(params[0]));
            break;
        default:
            result = true;
        }
        return result;
    }
}

function ValidationErrorMsg(text, element) {
    this.text = text;
    this.element = element;
}

function onsubmitFormHandler(form) {
    if (form == null) {
        form = this;
    } else if (form.target) {
        form = form.target;
    }
    if (form.submit_btn) {
        form.submit_btn.disabled = true;
    }
    return true;
}

function onsubmitValidateFormHandler(form) {
    if (form == null) {
        form = this;
    } else if (form.currentTarget) {
        form = form.currentTarget;
    } else if (form.target) {
        form = form.target;
    }
    if (validateForm(form, validateConditions)) {
        return onsubmitFormHandler(form);
    } else {
        return false;
    }
}

// Number formatting for integer (decimals = 0) and currency (decimals = 2) numbers
function formatNumber(num, decimals, decPoint, thousandsSep) {
    if (isNaN(num)) {
        num = 0;
    }
    var sign = (num == (num = Math.abs(num)));
    num = Math.floor(num * 100 + 0.50000000001);
    var dec = num % 100;
    num = Math.floor(num / 100).toString();
    if (decimals == 2 ) {
        if (dec < 10) {
            dec = '0' + dec;
        }
    } else {
        dec = '';
    }
    for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++) {
        num = num.substring(0, num.length - (4 * i + 3)) + thousandsSep +
            num.substring(num.length - (4 * i + 3));
    }
    return ((sign) ? '' : '-') + num + decPoint + dec;
}

// App specific js functions
function getJsIntegerStr(appIntegerStr) {
    var str = appIntegerStr.replace(/\./g, '');
    return str.replace(/\,/g, '.', str);
}

function getJsInteger(appIntegerStr) {
    return parseInt(getJsIntegerStr(appIntegerStr));
}

function isInteger(appIntegerStr) {
    return !isNaN(getJsIntegerStr(appIntegerStr));
}

function getJsDoubleStr(appDoubleStr) {
    var str = appDoubleStr.replace(/\./g, '');
    return str.replace(/\,/g, '.', str);
}

function getJsDouble(appDoubleStr) {
    return parseFloat(getJsDoubleStr(appDoubleStr));
}

function isDouble(appDoubleStr) {
    return !isNaN(getJsDoubleStr(appDoubleStr));
}

function getAppInteger(jsInteger) {
    return formatNumber(jsInteger, 0, '', '.');
}

function getAppDouble(jsDouble) {
    return formatNumber(jsDouble, 2, ',', '.');
}

function getAppCurrency(jsDouble) {
    return formatNumber(jsDouble, 2, ',', '.');
}

//
function openPolicyTermsOfUsePopupWindow() {
    return openWindow(
        '?action=static&page=policy_terms_of_use&popup=1',
        600,
        400,
        'yes'
    );
}

function openPolicyTermsAndConditionsPopupWindow() {
    return openWindow(
        '?action=static&page=policy_terms_and_conditions&popup=1',
        600,
        400,
        'yes'
    );
}

function openPolicyPrivacyPopupWindow() {
    return openWindow(
        '?action=static&page=policy_privacy&popup=1',
        600,
        400,
        'yes'
    );
}

function openPolicyDisclaimerPopupWindow() {
    return openWindow(
        '?action=static&page=policy_disclaimer&popup=1',
        600,
        400,
        'yes'
    );
}
