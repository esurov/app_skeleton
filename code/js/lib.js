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
        ru = '����������, ������� ���� ���. ';
        break;
    case 'input_last_name':
        en = 'Please input your last name.';
        it = 'Inserire il cognome.';
        ru = '����������, ������� ���� �������. ';
        break;
    case 'input_email':
        en = 'Please input your e-mail.';
        it = "Inserire l'e-mail.";
        ru = '����������, ������� e-mail. ';

        break;
    case 'incorrect_email':
        en = 'Please input correct e-mail address.';
        it = 'Inserire un indirizzo e-mail valido.';
        ru = '����������, ������� ���������� e-mail. ';

        break;
    case 'incorrect_zip':
        en = 'Postal code should be a number with minimum 5 digits.';
        it = 'Il codice postale dovrebbe essere un numero di 5 cifre.';
        ru = '�������� ������ ������ �������� ��� ������� �� 5 ����. ';

        break;
    case 'input_country':
        en = 'Please input your country.';
        it = 'Inserire la nazione.';
        ru = '����������, ������� ������. ';

        break;
    case 'input_city':
        en = 'Please input your city.';
        it = 'Inserire la citt�.';
        ru = '����������, ������� �����. ';

        break;
    case 'input_phone':
        en = 'Please input your contact phone.';
        it = 'Inserire il nr. di telefono.';
        ru = '����������, ������� �������. ';

        break;
    case 'invalid_phone':
        en = 'Bad phone number format.';
        it = 'Nr. di telefono non valido.';
        ru = '����������, ������� ���������� ����� ��������. ';

        break;
    case 'invalid_fax':
        en = 'Bad fax number format.';
        it = 'Nr. di telefono non valido.';
        ru = '����������, ������� ���������� ����� �����. ';
        break;
    case 'incorrect_form':
        en = 'Error! Incorrect form name.';
        it = 'Errore! Nome campo non valido.';
        ru = '������! ������������ ��� �����. ';
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
