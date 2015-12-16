var ulsort = {}
ulsort.List = {
    Filter : function (inputSelector, listSelector) {

        // Sanity check
        var inp, rgx = new RegExp(), titles = $(listSelector), keys;

        // The list with keys to skip (esc, arrows, return, etc)
        keys = [ 13, 27, 32, 37, 38, 39, 40 ];

        // binding keyup to the unordered list
        $(inputSelector).bind(
            'keyup',
            function (e) {
                console.log('keyup');
                if (jQuery.inArray(e.keyCode, keys) >= 0) {
                    return false;
                }

                // Building the regex from our user input, 'inp' should be escaped
                inp = $(this).val();
                rgx.compile(inp, 'im');
                titles.each(
                    function () {
                        if (rgx.source !== '' && !rgx.test($(this).html())) {
                            $(this).hide();
                        } else {
                            $(this).show();
                        }
                    }
                );
            }
        );
    }
};
