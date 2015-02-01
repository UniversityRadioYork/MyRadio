$(document).ready(
    function() {
        $('.myury-news-alert').dialog(
            {
                //Prevent closing without clicking continue
                closeOnEscape: false,
                open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); },
                //Make the page unusable
                modal: true,
                //Pretty button
                buttons: {
                    'Continue': function() {
                        $.ajax(
                            {
                                url: myury.makeURL('MyRadio', 'a-readnews'),
                                type: 'post',
                                data: 'newsentryid='+($(this).attr('id').replace(/newsentry\-/,''))
                            }
                        );
                        $(this).dialog('close');
                    }
                },
                width: 600
            }
        );
    }
);
