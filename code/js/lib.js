/**
 * Validates form defined by form name.
 *
 * @access public
 * @param  string
 */
function validate(formName)
{
    switch (formName) {
    case 'contact':
        conditions = [
            ['first_name', 'not_empty', 'input_first_name'],
            ['last_name', 'not_empty', 'input_last_name'],
            ['email', 'not_empty', 'input_email'],
            ['email', 'email', 'incorrect_email'],
            ['zip', 'zip', 'incorrect_zip'],
            ['country', 'not_empty', 'input_country'],
            ['city', 'not_empty', 'input_city']
        ];
        break;
    default:
        alert(getMessage('invalid_form', language));
        return false;
    }

    return check(conditions, formName);
}

/**
 * Returns message for given name with given language
 *
 * @access public
 * @param  string
 */
function getMessage(message, language)
{
    var en = '';
    var it = '';
    var ru = '';

    switch (message) {
    case 'input_first_name':
        en = 'Please input your first name.';
        it = 'Inserire il nome.';
        ru = 'Пожалуйста, введите свое имя. ';
        break;
    case 'input_last_name':
        en = 'Please input your last name.';
        it = 'Inserire il cognome.';
        ru = 'Пожалуйста, введите свою фамилию. ';
        break;
    case 'input_email':
        en = 'Please input your e-mail.';
        it = "Inserire l'e-mail.";
        ru = 'Пожалуйста, введите e-mail. ';

        break;
    case 'incorrect_email':
        en = 'Please input correct e-mail address.';
        it = 'Inserire un indirizzo e-mail valido.';
        ru = 'Пожалуйста, введите корректный e-mail. ';

        break;
    case 'incorrect_zip':
        en = 'Postal code should be a number with minimum 5 digits.';
        it = 'Il codice postale dovrebbe essere un numero di 5 cifre.';
        ru = 'Почтовый индекс должен состоять как минимум из 5 цифр. ';

        break;
    case 'input_country':
        en = 'Please input your country.';
        it = 'Inserire la nazione.';
        ru = 'Пожалуйста, введите страну. ';

        break;
    case 'input_city':
        en = 'Please input your city.';
        it = 'Inserire la cittа.';
        ru = 'Пожалуйста, введите город. ';

        break;
    case 'input_phone':
        en = 'Please input your contact phone.';
        it = 'Inserire il nr. di telefono.';
        ru = 'Пожалуйста, введите телефон. ';

        break;
    case 'invalid_phone':
        en = 'Bad phone number format.';
        it = 'Nr. di telefono non valido.';
        ru = 'Пожалуйста, введите корректный номер телефона. ';

        break;
    case 'invalid_fax':
        en = 'Bad fax number format.';
        it = 'Nr. di telefono non valido.';
        ru = 'Пожалуйста, введите корректный номер факса. ';
        break;
    case 'incorrect_form':
        en = 'Error! Incorrect form name.';
        it = 'Errore! Nome campo non valido.';
        ru = 'Ошибка! Неправильное имя формы. ';
        break;

    default:
        return '';
    }

    if (language == 'en') {
        return en;
    } else if (language == 'it'){
        return it;
    } else {
        return ru;
    }
}

/**
 * Checks form using given conditions.
 *
 * @access public
 * @param  string
 */
function check(conditions, formName)
{
    form = document.forms[formName];
    elems = form.elements;

    for (i = 0; i<conditions.length; i++) {
        field = conditions[i][0];
        type = conditions[i][1];
        message = conditions[i][2];

        for (j = 0; j < elems.length; j++) {
            if (elems[j].name == field) {
                elem = elems[j];
                value = elem.value;
                switch (type) {
                case 'not_empty':
                    if (value == '') {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'email':
                    if (!value.match(/.+@.+\..+/)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'url':
                    if (!(
                        value == 'http://www.' ||
                        value == '' ||
                        value.match(/^https?:\/\/([\w-]+\.)+[\w-]+(\/.*|)$/)
                    )) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'positive':
                    value = Number(value);
                    if (value <= 0) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'regexp':
                    param = conditions[i][3];
                    if (!value.match(param)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'not_equal':
                    param = conditions[i][3];
                    if (value == param) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'exp':
                    parts = value.split('/');
                    if (!(
                        parts.length == 2 &&
                        parts[0] > 0 &&
                        parts[0] <= 12 &&
                        parts[1] >= 2000
                    )) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'integer':
                    if (!value.match(/^\d+$/)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'date':
                    if (value != '' && !isCorrectDate(value)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'currency':
                    if (value != '' && !isCorrectCurrency(value)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'currency/date':
                    if (value != '') {
                        parts = value.split('/');
                        if (parts.length != 4) {
                            fail(message, formName, elem);
                            return false;
                        }
                        price = parts[0];
                        date = parts[1] + '/' + parts[2] + '/' + parts[3];

                        if (!(
                            isCorrectCurrency(price) &&
                            isCorrectDate(date)
                        )) {
                            fail(message, formName, elem);
                            return false;
                        }
                    }
                    break;
                case 'zip':
                    if (value != '' && !value.match(/^\d{5,}$/)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                case 'phone':
                    if (value != '' && !value.match(/^[0-9 \+\-\(\)]+$/)) {
                        fail(message, formName, elem);
                        return false;
                    }
                    break;
                }
            }
        }
    }

    return true;
}


/**
 * Checks if given string is a correct date in format mm/dd/yyyy.
 *
 * @param  string
 * @return bool
 */
function isCorrectDate(str)
{
    parts = str.split('/');
    if (parts.length != 3) {
        return false;
    }
    date = new Date(parts[2], parts[0] - 1, parts[1]);

    if(
        (Number(date.getDate()) == Number(parts[1])) &&
        (Number(date.getMonth()) + 1 == Number(parts[0])) &&
        (Number(date.getFullYear()) == Number(parts[2]))
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if given string is a correct currency with thouthands and decimals separators.
 *
 * @param  string
 * @return bool
 */
function isCorrectCurrency(value)
{
    return value.match(/^\d+(,\d{3})*(\.\d+)?$/);
}

/**
 * Fails form field one condition check.
 *
 * @param string
 * @param string
 * @param object element
 */
function fail(message, formName, field)
{
    alert(getMessage(message, language));
    focus(field);
}

/**
 * Sets focus to given element.
 *
 * @access public
 * @param  object element
 */
function focus(element)
{
    element.focus();
}

/**
 * Sets focus to given element of given form.
 *
 * @access public
 * @param  string
 * @param  string
 */
function focusElement(formName, elementName)
{
    focus(document.forms[formName].elements[elementName]);
}


function openIT(theURL)
{
    var windowprops = "width=500,height=400,location=no,toolbar=no,menubar=no,scrollbars=no,resizable=yes";
    var subwin;

    subwin = window.open(theURL, "",windowprops);
}

function openPopup(url, width, height, scroll) {
    w = window.open(
        url,
        'popup',
        'width=' + width + ',height=' + height + ',scrollbars=' + scroll + ',resizable'
    );
    w.focus();
}

function ifConfirmed(message) {
    result = confirm(message);
    event.returnValue = result;
    return result;
}

function acceptChoice(formName, input1, value1, input2, value2) {
    var input = eval("window.opener.document." + formName + "." + input1);
    input.value = value1;
    input.onchange(); // onchange is not fired automatically ;(

    if (input2 != null && value2 != null) {
        var input = eval("window.opener.document." + formName + "." + input2);
        input.value = value2;
    }
    window.opener.focus();
    window.close();
}

function Dependency(formName, mainSelect, dependentSelect, dependencyArray) {
    this.formName = formName;
    this.mainSelectName = mainSelect;
    this.dependentSelectName = dependentSelect;
    this.dependencyArray = dependencyArray;

    this.dependentSelectValues = new Array();
    this.dependentSelectCaptions = new Array();

    this.read = read;
    this.update = update;
    this.init = init;
    this.getCaptionByValue = getCaptionByValue;
}

function init() {
    this.form = eval('document.' + this.formName);

    this.mainSelect = eval('this.form.' + this.mainSelectName);
    this.dependentSelect = eval('this.form.' + this.dependentSelectName);

    var oldDependentSelectValue = getSelectValue(this.dependentSelect);

    this.read();
    this.update();
    
    setSelectValue(this.dependentSelect, oldDependentSelectValue);
}

function getSelectValue(obj) {
    return obj.value;
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

function read() {
    var options = this.dependentSelect.options;
    for (var i = 0; i < options.length; i++) {
        this.dependentSelectValues[i] = options[i].value;
        this.dependentSelectCaptions[i] = options[i].text;
    }
}

function update() {
    var caption, value;
    var values = this.dependencyArray[this.mainSelect.selectedIndex];
    this.dependentSelect.options.length = 0;
    for (var i = 0; i < values.length; i++) {
        value = values[i];
        caption = this.getCaptionByValue(value);
        if (caption != null) {
            this.dependentSelect.options[i] = new Option(caption, value);
        }
    }
}

function getCaptionByValue(value) {
    for (var i = 0; i < this.dependentSelectValues.length; i++) {
        if (this.dependentSelectValues[i] == value) {
            return this.dependentSelectCaptions[i];
        }
    }
    return null;
}

function updateDependentSelect(mainObj, dependentName) {
    var formName = mainObj.form.name;
    var mainName = mainObj.name;
    dependency = getDependency(formName, mainName, dependentName);
    if (dependency != null) {
        dependency.update();
    }
}

function getDependency(formName, mainName, dependentName) {
    for (var i = 0; i < depends.length; i++) {
        if (
            depends[i].formName == formName && 
            depends[i].mainSelectName == mainName && 
            depends[i].dependentSelectName == dependentName 
        ) {
            return depends[i];
        }
    }
    return null;
}

function initDependencies() {
    for (var i = 0; i < depends.length; i++) {
        depends[i].init();
    }
}

var depends = new Array();

window.onload = initDependencies;
