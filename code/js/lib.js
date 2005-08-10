/**
 * Sets focus to given element.
 *
 * @access public
 * @param  object element
 */
function focusElement(element) {
    if (element != null) {
        element.focus();
    }
}

/**
 * Sets focus to element given by its name in given form.
 *
 * @access public
 * @param  object form or string
 * @param  string
 */
function focusFormElement(form, elementName) {
    if (typeof(form) == "string") {
        form = document.forms[form];
    }
    focusElement(form.elements[elementName]);
}

function getElementValue(element) {
    if (element == null) {
        return null;
    }
    switch (element.type) {
    case 'radio':
        elements = element.form.elements;
        for (j = 0; j < elements.length; j++) {
            if (elements[j].name == element.name) {
                if (elements[j].checked) {
                    return elements[j].value;
                }
            }
        }
        break;
    default:
        return element.value;
    }
    return null;
}

function setElementValue(element, value) {
    if (element == null) {
        return;
    }
    switch (element.type) {
    case 'radio':
        elements = element.form.elements;
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

function copyToClipboard(element) {
    element.select();
    element.createTextRange().execCommand("Copy");
}

function openPopup(url, width, height, use_scroll) {
    var corner_x = (screen.width - width) / 2;
    var corner_y = (screen.height - height) / 2 - 32;

    w = window.open(
        url,
        'popup',
        'width=' + width + ',height=' + height +
        ',left=' + corner_x + ',top=' + corner_y +
        ',scrollbars=' + use_scroll + ',resizable'
    );
    if (w != null) {
        w.focus();
    }
}

function reloadParentWindow() {
    if (window.opener != null) {
        window.opener.location.reload();
    }
}

function ifConfirmed(message_text) {
    result = confirm(message_text);
    event.returnValue = result;
    return result;
}

function acceptChoice(formName, element1, value1, element2, value2) {
    var element = eval("window.opener.document." + formName + "." + element1);
    element.value = value1;

    // onchange is not fired automatically ;(
    element.onchange(); 

    if (element2 != null && value2 != null) {
        var element = eval("window.opener.document." + formName + "." + element2);
        element.value = value2;
    }
    window.opener.focus();
    window.close();
}

function getYear() {
    var d = new Date();
    var year = d.getFullYear();
    return year;
}

//

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
    var form = eval('document.' + this.formName);

    this.mainSelect = eval('form.' + this.mainSelectName);
    this.dependentSelect = eval('form.' + this.dependentSelectName);

    var oldDependentSelectValue = getElementValue(this.dependentSelect);

    this.storeMainSelectData();
    this.storeDependentSelectData();

    updateDependentSelect(this.mainSelect, this.dependentSelectName);

    setElementValue(this.dependentSelect, oldDependentSelectValue);
}

function storeMainSelectData() {
    options = this.mainSelect.options;
    for (var i = 0; i < options.length; i++) {
        this.mainSelectValues[i] = options[i].value;
        this.mainSelectCaptions[i] = options[i].text;
    }
}

function storeDependentSelectData() {
    options = this.dependentSelect.options;
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
    for (i = 0; i < dependencies.length; i++) {
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
    formName = mainSelect.form.name;
    mainSelectName = mainSelect.name;

    dependency = getDependency(formName, mainSelectName, dependentSelectName);
    if (dependency != null) {
        dependency.update();
    }
}

function initDependencies() {
    for (i = 0; i < dependencies.length; i++) {
        dependencies[i].init();
    }
}

dependencies = new Array();

//
function validateForm(form, conditions) {
    elements = form.elements;
    for (i = 0; i < conditions.length; i++) {
        validationErrorMsg = conditions[i].validate(form);
        if (validationErrorMsg != null) {
            alert(validationErrorMsg.text);
            focusElement(validationErrorMsg.element);
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
        elements = form.elements;
        for (j = 0; j < elements.length; j++) {
            element = elements[j];
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
            null : this.dependentCondition.validate(form);
    }

    this.validateElement = function (element) {
        value = getElementValue(element);
        switch (this.type) {
        case 'regexp':
            re = eval(params[0]);
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
        case 'number':
            result = (value.match(/^\d+$/)) ? true : false;
            break;
        case 'number_greater':
            result = (Number(value) > Number(params[0]));
            break;
        case 'number_greater_equal':
            result = (Number(value) >= Number(params[0]));
            break;
        case 'number_less':
            result = (Number(value) < Number(params[0]));
            break;
        case 'number_less_equal':
            result = (Number(value) <= Number(params[0]));
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
    }
    if (form.submit_btn) {
        form.submit_btn.disabled = true;
    }
    return true;
}

function onsubmitValidateFormHandler(form) {
    if (form == null) {
        form = this;
    }
    if (validateForm(form, conditions)) {
        return onsubmitFormHandler(form);
    } else {
        return false;
    }
}

//        case 'url':
//        if (!(
//            value == 'http://www.' ||
//            value == '' ||
//            value.match(/^https?:\/\/([\w-]+\.)+[\w-]+(\/.*|)$/)
//        )) {
//        case 'date':
//            if (value != '' && !isCorrectDate(value)) {
//            }
//            break;
//        case 'currency':
//            if (value != '' && !isCorrectCurrency(value)) {
//            }
//            break;
//        case 'currency/date':
//            if (value != '') {
//                parts = value.split('/');
//                if (parts.length != 4) {
//                    fail(message_text, formName, elem);
//                    return false;
//                }
//                price = parts[0];
//                date = parts[1] + '/' + parts[2] + '/' + parts[3];
//
//                if (!(
//                    isCorrectCurrency(price) &&
//                    isCorrectDate(date)
//                )) {
//                }
//            }
//            break;
//        }
//    }
///**
// * Checks if given string is a correct date in format mm/dd/yyyy.
// *
// * @param  string
// * @return bool
// */
//function isCorrectDate(str) {
//    parts = str.split('/');
//    if (parts.length != 3) {
//        return false;
//    }
//    date = new Date(parts[2], parts[0] - 1, parts[1]);
//
//    if(
//        (Number(date.getDate()) == Number(parts[1])) &&
//        (Number(date.getMonth()) + 1 == Number(parts[0])) &&
//        (Number(date.getFullYear()) == Number(parts[2]))
//    ) {
//        return true;
//    } else {
//        return false;
//    }
//}
//
///**
// * Checks if given string is a correct currency with thouthands and decimals separators.
// *
// * @param  string
// * @return bool
// */
//function isCorrectCurrency(value) {
//    return value.match(/^\d+(,\d{3})*(\.\d+)?$/);
//}
