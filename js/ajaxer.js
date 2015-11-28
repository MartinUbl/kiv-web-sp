
var $ajaxer = {

    _forms: {},
    _formCallbacks: {},
    _formsDisabled: {},

    _bindForms: function() {
        $.each($('form.ajax'), function(i,e) {

            var id = $(e).attr('id');
            if (typeof id !== 'undefined' && id.length > 1)
                $ajaxer._forms[id] = $(e);

            $(e).submit(function(event) {

                var id = $(e).attr('id');
                if (typeof id !== 'undefined' && id.length > 1)
                    if (typeof $ajaxer._formsDisabled[id] !== undefined && $ajaxer._formsDisabled[id])
                        return;

                event.preventDefault();

                $(e).find('input[type=submit]').attr('disabled', 'disabled');

                var method = $(e).attr('method');
                if (typeof method === 'undefined' || !method)
                    method = 'post';

                method = method.toLowerCase();

                $ajaxer[method]($(e).attr('action'), $(e).serialize(), function(resp) {
                    $(e).find('input[type=submit]').removeAttr('disabled');

                    // malformed response, reject
                    if (typeof resp.status === 'undefined')
                    {
                        console.error("Server returned invalid response for ajax call: "+JSON.stringify(resp));
                        return;
                    }

                    $(e).find('.error-message').html('');

                    if (resp.status === 'error-form' && typeof resp.data !== 'undefined')
                    {
                        for (var i in resp.data)
                        {
                            var errmsgfield = $(e).find('input[name="'+i+'"],textarea[name="'+i+'"],select[name="'+i+'"]').parent().find('.error-message');
                            if (typeof errmsgfield !== 'undefined' && errmsgfield)
                                errmsgfield.html(resp.data[i]);
                        }
                    }

                    var id = $(e).attr('id');
                    if (typeof id !== 'undefined' && id.length > 1 && typeof $ajaxer._formCallbacks[id] !== 'undefined')
                        $ajaxer._formCallbacks[id](resp);
                });
            });
        });
    },

    _ajaxResponseHandler: function(response, callback) {
        if (typeof response.redirect !== 'undefined' && response.redirect && response.redirect.length > 1)
            location.href = response.redirect;

        if (typeof callback !== 'undefined' && callback)
            callback(response);
    },

    registerFormSubmitCallback: function(formid, callback) {
        $ajaxer._formCallbacks[formid] = callback;
    },

    disableFormAjax: function(formid) {
        $ajaxer._formsDisabled[formid] = true;
    },

    enableFormAjax: function(formid) {
        $ajaxer._formsDisabled[formid] = false;
    },

    get: function(url, data, callback) {
        $.get(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },
    post: function(url, data, callback) {
        $.post(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },
    put: function(url, data, callback) {
        $.put(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },
    del: function(url, data, callback) {
        $.del(url, data, function(resp) {
            $ajaxer._ajaxResponseHandler(resp, callback);
        });
    },

};

$(document).ready(function() {
    $ajaxer._bindForms();
});
