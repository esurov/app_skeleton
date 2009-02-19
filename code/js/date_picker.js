function DatePicker(inputElement, config) {
    this.init(inputElement, config);
}

DatePicker.prototype = {

    config : {},
    _calendar : null,
    _inputElement : null,
    _calendarButtonElement : null,
    _calendarContainerElement : null,
    _fullscreenLayerElement : null,
    _timer : null,

    init : function(inputElement, config) {
        this.config = config;
        this.config.formatDate = this.config.formatDate || 'Y-m-d';
        this.config.button_class = this.config.button_class || 'date_picker_button';
        this.config.button_caption = this.config.button_caption || '';
        this.config.calendar_container_class =
            this.config.calendar_container_class || 'yui-calendar-container';
        this.config.pages = this.config.pages || 1;
        
        this._inputElement = inputElement;

        this._calendarButtonElement = this.createCalendarButtonElement();
        this._calendarContainerElement = this.createCalendarContainerElement();

        this._calendar = new YAHOO.widget.CalendarGroup(
            this._calendarContainerElement,
            this.config
        );
        this._calendar.selectEvent.subscribe(this.onCalendarDateSelect, this, true);
        this._calendar.beforeShowEvent.subscribe(this.onBeforeShowCalendar, this, true);
        this._calendar.hideEvent.subscribe(this.onHideCalendar, this, true);
        this._calendar.render();

        YAHOO.util.Event.addListener(
            this._inputElement,
            'keypress',
            this.setCalendarDateAfterTimeout,
            this,
            true
        );
        YAHOO.util.Event.addListener(
            this._calendarButtonElement,
            'click',
            this.showOrHideCalendar,
            this,
            true
        );
    },

    createCalendarButtonElement : function() {
        var calendarButtonElement = document.createElement('input');
        calendarButtonElement.type = 'button';
        calendarButtonElement.className = this.config.button_class;
        calendarButtonElement.value = this.config.button_caption;
        
        this._inputElement.parentNode.appendChild(calendarButtonElement);
        
        return calendarButtonElement;
    },

    createCalendarContainerElement : function() {
        var containerElement = document.createElement('div');
        containerElement.className = this.config.calendar_container_class;
        containerElement.style.display = 'none';
        containerElement.style.position = 'absolute';
        containerElement.style.left =
            YAHOO.util.Dom.getX(this._calendarButtonElement) +
            this._calendarButtonElement.offsetWidth + 'px';
        containerElement.style.top =
            YAHOO.util.Dom.getY(this._calendarButtonElement) + 'px';
        containerElement.style.zIndex = '3';

        this._inputElement.parentNode.appendChild(containerElement);
                
        return containerElement;
    },

    onCalendarDateSelect : function(type, args) {
        var dates = args[0];
        var date = dates[0];
        this.fillInput(date);
        this._calendar.hide();
    },

    fillInput : function() {
        var selectedDates = this._calendar.getSelectedDates();

        writeLog('Filling input: ' + selectedDates, 'info');

        this._inputElement.value = '';
        for (var i = 0; i < selectedDates.length; i++) {
            this._inputElement.value += getFormattedDate(
                selectedDates[i],
                this.config.formatDate
            );
        }
    },

    setCalendarDateAfterTimeout : function() {
        clearTimeout(this._timer);

        var that = this;
        this._timer = setTimeout(function() {
            that.setCalendarDate();
        }, 300);
    },

    setCalendarDate : function(shouldRender) {
        var selectedDate = parseDateByFormat(this.config.formatDate, this._inputElement.value);
        if (selectedDate.year == 0) {
            writeLog('Year is not valid' , 'warn');
        } else {
            var date = new Date(selectedDate.year, selectedDate.month - 1, selectedDate.day);

            // Unsubscribe from select event so its handler will not be
            // executed after Calendar.select() call
            this._calendar.selectEvent.unsubscribe(this.onCalendarDateSelect, this);

            this._calendar.deselectAll();
            this._calendar.select(date);
            this._calendar.setMonth(date.getMonth());
            this._calendar.setYear(date.getFullYear());

            // Restore select event handler
            this._calendar.selectEvent.subscribe(this.onCalendarDateSelect, this, true);
        }
        if (shouldRender == null || shouldRender == true) {
            this._calendar.render();
        }
    },

    showOrHideCalendar : function() {
        if (this._calendar.oDomContainer.style.display == 'block') {
            this._calendar.hide();
        } else {
            this._calendar.show();
        }
    },

    onBeforeShowCalendar : function() {
        writeLog('Showing DatePicker', 'info');
        this._fullscreenLayerElement = this.createFullscreenLayerElement();
        this.setCalendarDate();
    },

    onHideCalendar : function() {
        writeLog('Hiding DatePicker', 'info');
        this._inputElement.parentNode.removeChild(this._fullscreenLayerElement);
        this._fullscreenLayerElement = null;
        this._inputElement.focus();
    },

    createFullscreenLayerElement : function() {
        var fullscreenLayerElement = document.createElement('div');
        fullscreenLayerElement.style.position = 'absolute';
        fullscreenLayerElement.style.top = '0';
        fullscreenLayerElement.style.left = '0';
        fullscreenLayerElement.style.width = document.body.clientWidth + 'px';
        fullscreenLayerElement.style.height = document.body.clientHeight + 'px';
        fullscreenLayerElement.zIndex = '2';

        this._inputElement.parentNode.appendChild(fullscreenLayerElement);

        YAHOO.util.Event.addListener(
            fullscreenLayerElement,
            'click',
            this.showOrHideCalendar,
            this,
            true
        );
        
        return fullscreenLayerElement;
    }

};

function createDatePicker(inputElement, config) {
    YAHOO.util.Event.onDOMReady(function() {
        writeLog('Creating DatePicker for input ' + inputElement.name,  'info');
        new DatePicker(inputElement, config);
    });
}
//

function DateRangePicker(inputElement, rangeFromInputElement, rangeToInputElement, config) {
    // DateRangePicker.superclass.constructor.call(this, inputElement, config);
    this.init(inputElement, rangeFromInputElement, rangeToInputElement, config);
}

YAHOO.lang.extend(DateRangePicker, DatePicker, {

    _rangeFromInputElement : null,
    _rangeToInputElement : null,
    _minDateTimer : null,
    _maxDateTimer : null,

    init : function(inputElement, rangeFromInputElement, rangeToInputElement, config) {
        DateRangePicker.superclass.init.call(this, inputElement, config);

        this._rangeFromInputElement = rangeFromInputElement;
        this._rangeToInputElement = rangeToInputElement;

        if (this._rangeFromInputElement != null) {
            YAHOO.util.Event.addListener(
                this._rangeFromInputElement,
                'keypress',
                this.setCalendarMinDateAfterTimeout,
                this,
                true
            );
        }
        if (this._rangeToInputElement != null) {
            YAHOO.util.Event.addListener(
                this._rangeToInputElement,
                'keypress',
                this.setCalendarMaxDateAfterTimeout,
                this,
                true
            );
        }
    },

    setCalendarMinDateAfterTimeout : function() {
        clearTimeout(this._minDateTimer);

        var that = this;
        this._minDateTimer = setTimeout(function() {
            that.setCalendarMinDate();
        }, 300);
    },
    
    setCalendarMinDate : function(shouldRender) {
        if (this._rangeFromInputElement == null) {
            return;
        }
        var minDate = parseDateByFormat(this.config.formatDate, this._rangeFromInputElement.value);
        var minDateNew = null;
        if (minDate.year != 0) {
            minDateNew = new Date(minDate.year, minDate.month - 1, minDate.day);
        }
        this._calendar.cfg.setProperty('mindate', minDateNew);
        if (shouldRender == null || shouldRender == true) {
            this._calendar.render();
        }
    },

    setCalendarMaxDateAfterTimeout : function() {
        clearTimeout(this._maxDateTimer);

        var that = this;
        this._maxDateTimer = setTimeout(function() {
            that.setCalendarMaxDate();
        }, 300);
    },
    
    setCalendarMaxDate : function(shouldRender) {
        if (this._rangeToInputElement == null) {
            return;
        }
        var maxDate = parseDateByFormat(this.config.formatDate, this._rangeToInputElement.value);
        var maxDateNew = null;
        if (maxDate.year != 0) {
            maxDateNew = new Date(maxDate.year, maxDate.month - 1, maxDate.day);
        }
        this._calendar.cfg.setProperty('maxdate', maxDateNew);
        if (shouldRender == null || shouldRender == true) {
            this._calendar.render();
        }
    },

    onBeforeShowCalendar : function() {
        this.setCalendarMinDate(false);
        this.setCalendarMaxDate(false);

        DateRangePicker.superclass.onBeforeShowCalendar.call(this);
    }

});

function createDateRangePickers(rangeFromInputElement, rangeToInputElement, config) {
    YAHOO.util.Event.onDOMReady(function() {
        writeLog('Creating DateRangePicker for input ' + rangeFromInputElement.name,  'info');
        new DateRangePicker(
            rangeFromInputElement,
            null,
            rangeToInputElement,
            config
        );
        writeLog('Creating DateRangePicker for input ' + rangeToInputElement.name,  'info');
        new DateRangePicker(
            rangeToInputElement,
            rangeFromInputElement,
            null,
            config
        );
    });
}

function getZeroPaddedNumber(number, countOfDigits) {
    countOfDigits = countOfDigits || 2;
    var result = number.toString();
    var numberRegex = new RegExp('^[1-9]\\d{0,' + countOfDigits + '}$');
    var countOfZeros = 0;
    var zeros = '';

    if (result.match(numberRegex)) {
        countOfZeros = countOfDigits - result.length;
        for (var i = 0; i < countOfZeros; i++) {
            zeros += '0';
        }
        result = zeros + result;
    } else {
        result = result.substring(result.length - countOfDigits, result.length);
    }
    
    return result;
}

function getFormattedDate(date, format) {
    format = format || 'Y-m-d';
    var result = format;
    result = result.replace(/Y/, getZeroPaddedNumber(date.getFullYear(), 4));
    result = result.replace(/y/, getZeroPaddedNumber(date.getFullYear(), 2));
    result = result.replace(/m/, getZeroPaddedNumber(date.getMonth() + 1));
    result = result.replace(/d/, getZeroPaddedNumber(date.getDate()));

    writeLog(
        'Formatting date...\n' +
        '\tSource date: ' + date + '\n' +
        '\tResult date: ' + result, 'info'
    );

    return result;
}

function parseDateByFormat(format, value, params) {

    function createDateRegexpByFormat(format) {
        var res = '';
        for (var i = 0; i < format.length; i++) {
            formatChar = format.charAt(i); 
            switch (formatChar) {
            case 'Y':
                res += '(\\d{4})'; 
                break;
            
            case 'y':
                res += '(\\d{1,2})'; 
                break;

            case 'm':
                res += '(\\d{1,2})';
                break;
            
            case 'd':
                res += '(\\d{1,2})';
                break;
            
            case 'h':
                res += '(\\d{1,2})';
                break;
            
            case 'H':
                res += '(\\d{1,2})';
                break;
            
            case 'i':
                res += '(\\d{1,2})';
                break;
            
            case 's':
                res += '(\\d{1,2})';
                break;
            
            case 't':
                res += '(AM|PM)?';
                break;
            
            case '.':
                res += '\\.';
                break;
            
            case '/':
                res += '\\/';
                break;
            
            case '\\':
                res += '\\\\\\\\';
                break;
            
            default:
                res += formatChar;
            }
        }
        return res;
    }

    var params = params || {};
    var yearDelimiter = params.yearDelim || 70;

    var dateParts = {
        year:  0,
        month: 0,
        day: 0,
        hour: 0,
        minute: 0,
        second: 0
    };
    
    var datePartsUnordered = value.match(
        new RegExp(createDateRegexpByFormat(format), 'i')
    );
    if (datePartsUnordered != null) {
        var p = 1;
        for (var i = 0; i < format.length; i++) {
            var formatChar = format.charAt(i);
            switch (formatChar) {
            case 'Y':
                dateParts['year'] = parseFloat(datePartsUnordered[p++]);
                break;

            case 'y': 
                var year = parseFloat(datePartsUnordered[p++]);
                if (year < yearDelimiter) {
                    year += 2000;    
                } else {
                    year += 1900;
                }
                dateParts['year'] = year;
                break;

            case 'm':
                dateParts['month'] = parseFloat(datePartsUnordered[p++]);
                break;
            
            case 'd':
                dateParts['day'] = parseFloat(datePartsUnordered[p++]);
                break;
            
            case 'h':
                dateParts['hour'] = parseFloat(datePartsUnordered[p++]);
                break;
            
            case 'H':
                dateParts['hour'] = parseFloat(datePartsUnordered[p++]);
                break;
            
            case 'i':
                dateParts['minute'] = parseFloat(datePartsUnordered[p++]);
                break;
            
            case 's':
                dateParts['second'] = parseFloat(datePartsUnordered[p++]);
                break;
            
            case 't':
                var hour = dateParts['hour'];
                var ampmStr = datePartsUnordered[p++].toUpperCase();
                
                if (ampmStr == 'PM') { 
                    if (hour != 12) {
                        hour += 12;
                    }    
                } else {
                    if (hour == 12) {
                        hour = 0;
                    }
                }
                dateParts['hour'] = hour;        
                break;
            }
        }

        if (dateParts['month'] > 12) {
            dateParts['month'] = 12;
        }
        if (dateParts['day'] > 31) {
            dateParts['day'] = 31;
        }
        if (dateParts['hour'] > 23) {
            dateParts['hour'] = 23;
        }
        if (dateParts['minute'] > 59) {
            dateParts['minute'] = 59;
        }
        if (dateParts['second'] > 59) {
            dateParts['second'] = 59;
        }
    }
    
    return dateParts;
}

function createDateByFormat(format, dateParts, dateIfUnknown) {
    
    function jsPrintF(format, strValue) {
        var zeroString = '';
        strValue += '';
        var diff = parseInt(format) - strValue.length;
        if (diff > 0) {
            for (var i = 0; i < diff; i++) {
                zeroString += '0';    
            }
        }
        return zeroString + strValue;
    }

    var ampmStr = '';
    var res = '';
    for (var i = 0; i < format.length; i++) {
        var formatChar = format.charAt(i);
        switch (formatChar) {
        case 'Y':
            res += jsPrintF('4', dateParts['year']);
            break;
        
        case 'y':
            res += jsPrintF('2', dateParts['year']);
            break;

        case 'm':
            res += jsPrintF('2', dateParts['month']);
            break;
        
        case 'd':
            res += jsPrintF('2', dateParts['day']);
            break;
        
        case 'h':
            res += jsPrintF('2', dateParts['hour']);
            break;
        
        case 'H':
            var hour = dateParts['hour'];
            if (hour <= 12) {
                ampmStr = 'AM';
            } else {
                hour -= 12;
                ampmStr = 'PM';
            }
            if (hour == 0) {
                hour = 12;
            }
            dateParts['hour'] = hour;
            res += jsPrintF('2', dateParts['hour']);
            break;
        
        case 'i':
            res += jsPrintF('2', dateParts['minute']);
            break;
        
        case 's':
            res += jsPrintF('2', dateParts['second']);
            break;
        
        case 't':
            res += ampmStr;
            break;
        
        default:
            res += formatChar;
        }
    }
    if (
        dateParts['year'] == 0 &&
        dateParts['month'] == 0 &&
        dateParts['day'] == 0 &&
        dateParts['hour'] == 0 &&
        dateParts['minute'] == 0 &&
        dateParts['second'] == 0 
    ) {
        return dateIfUnknown;
    } else {
        return res;
    }
}
