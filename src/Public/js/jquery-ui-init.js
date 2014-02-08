$(document).ready(function() {
    $("#tabs").tabs({
        ajaxOptions: { async: false },
    });
    $(".accord").accordion({
            collapsible: true,
            heightStyle: 'content'
    });
    $(".accordClose").accordion({
        collapsible: true,
        active: false
    });
    $("input:submit, button").button();
});
