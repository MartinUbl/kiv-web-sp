
var $formHelper = {

    _generateErrorElementsOn: function(frm) {
        $('<span class="error-message"></span>').insertAfter($(frm).find('input[type=text],input[type=password],textarea,select'));
    },

    _generateErrorElements: function() {
        $.each($('form.create-error-elements'), function(i,e) {
            $formHelper._generateErrorElementsOn(e);
        });
    }

};

$(document).ready(function() {
    $formHelper._generateErrorElements();
});


