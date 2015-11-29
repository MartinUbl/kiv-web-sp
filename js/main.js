
// bind foundation immediatelly
$(document).foundation();

$(document).ready(function() {
    // set timeout to hide all flash messages
    setTimeout(function() {
        $('.flashmessage').addClass('hidden');

        // and then wait and remove them completelly
        setTimeout(function() {
            $('.flashmessage').remove();
        }, 1000);
    }, 2000);
});
