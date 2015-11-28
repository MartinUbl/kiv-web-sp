
/**
 * Javascript wrapper for AJAX calls to allow generic handling of some stuff
 */
var $ajaxer = {

    /** All forms present within ajaxer */
    _forms: {},
    /** All form callbacks present */
    _formCallbacks: {},
    /** Disabled forms - ajax exceptions */
    _formsDisabled: {},

    /** Bind all forms with 'ajax' class */
    _bindForms: function() {
        $.each($('form.ajax'), function(i,e) {

            // put form into evidence, if it has ID
            var id = $(e).attr('id');
            if (typeof id !== 'undefined' && id.length > 1)
                $ajaxer._forms[id] = $(e);

            // hook submit event
            $(e).submit(function(event) {

                // if it's listed in ajax exceptions, kill handler and do not override
                var id = $(e).attr('id');
                if (typeof id !== 'undefined' && id.length > 1)
                    if (typeof $ajaxer._formsDisabled[id] !== undefined && $ajaxer._formsDisabled[id])
                        return;

                // prevent default action
                event.preventDefault();

                // disable all submit buttons
                $(e).find('input[type=submit]').attr('disabled', 'disabled');

                // retrieve form method to be used
                var method = $(e).attr('method');
                if (typeof method === 'undefined' || !method)
                    method = 'post';

                method = method.toLowerCase();

                // perform AJAX call
                $ajaxer[method]($(e).attr('action'), $(e).serialize(), function(resp) {
                    // enable all submit buttons
                    $(e).find('input[type=submit]').removeAttr('disabled');

                    // malformed response, reject
                    if (typeof resp.status === 'undefined')
                    {
                        console.error("Server returned invalid response for ajax call: "+JSON.stringify(resp));
                        return;
                    }

                    // clear all error messages
                    $(e).find('.error-message').html('');

                    // if there are form errors, parse it
                    if (resp.status === 'error-form' && typeof resp.data !== 'undefined')
                    {
                        for (var i in resp.data)
                        {
                            // find errorneous field and append error to next error output element
                            var errmsgfield = $(e).find('input[name="'+i+'"],textarea[name="'+i+'"],select[name="'+i+'"]').parent().find('.error-message');
                            if (typeof errmsgfield !== 'undefined' && errmsgfield)
                                errmsgfield.html(resp.data[i]);
                        }
                    }

                    // call user defined callback, if any
                    var id = $(e).attr('id');
                    if (typeof id !== 'undefined' && id.length > 1 && typeof $ajaxer._formCallbacks[id] !== 'undefined')
                        $ajaxer._formCallbacks[id](resp);
                });
            });
        });
    },

    /** Generic ajax response handler */
    _ajaxResponseHandler: function(response, callback) {
        if (typeof response.redirect !== 'undefined' && response.redirect && response.redirect.length > 1)
            location.href = response.redirect;

        if (typeof callback !== 'undefined' && callback)
            callback(response);
    },

    /** Registers form submit callback */
    registerFormSubmitCallback: function(formid, callback) {
        $ajaxer._formCallbacks[formid] = callback;
    },

    /** Disables ajax for specified form */
    disableFormAjax: function(formid) {
        $ajaxer._formsDisabled[formid] = true;
    },

    /** Enables ajax for specified form */
    enableFormAjax: function(formid) {
        $ajaxer._formsDisabled[formid] = false;
    },

    /** Performs GET AJAX request */
    get: function(url, data, callback) {
        $.get(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },
    /** Performs POST AJAX request */
    post: function(url, data, callback) {
        $.post(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },
    /** Performs PUT AJAX request */
    put: function(url, data, callback) {
        $.put(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },
    /** Performs DELETE AJAX request */
    del: function(url, data, callback) {
        $.del(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    }

};

// bind all forms with ajax class on load
$(document).ready(function() {
    $ajaxer._bindForms();
});
