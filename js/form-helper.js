
/**
 * Form helper for some form-related stuff
 */
var $formHelper = {

    /** Generates error elements for supplied element */
    _generateErrorElementsOn: function(frm) {
        $('<span class="error-message"></span>').insertAfter($(frm).find('input[type=text],input[type=password],textarea,select'));
    },

    /** Generates error elements for all elements within forms with create-error-elements class */
    _generateErrorElements: function() {
        $.each($('form.create-error-elements'), function(i,e) {
            $formHelper._generateErrorElementsOn(e);
        });
    }

};

// on document ready - generate all error elements
$(document).ready(function() {
    $formHelper._generateErrorElements();
});


