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

    var oldDependentSelectValue = getSelectValue(this.dependentSelect);

    this.storeMainSelectData();
    this.storeDependentSelectData();

    updateDependentSelect(this.mainSelect, this.dependentSelectName);

    setSelectValue(this.dependentSelect, oldDependentSelectValue);
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

    var currentOptionValue = getSelectValue(this.mainSelect);

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

///////////////////////////////////////////////////////////////////////////

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

window.onload = initDependencies;
