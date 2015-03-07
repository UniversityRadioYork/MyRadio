/**
 * The MyRadio Standard Form JavaScript Tools
 * This file initialises jQuery validation, autocompletes and other resources
 * needed for a MyRadio Form
 */
jQuery.validator.addMethod(
    "dateITA", function(value, element) {
        var check = false;
        var re = /^\d{1,2}\/\d{1,2}\/\d{4}$/;
        if (re.test(value)) {
            var adata = value.split('/');
            var gg = parseInt(adata[0],10);
            var mm = parseInt(adata[1],10);
            var aaaa = parseInt(adata[2],10);
            var xdata = new Date(aaaa,mm-1,gg);
            if ((xdata.getFullYear() === aaaa) && (xdata.getMonth() === mm - 1) && (xdata.getDate() === gg)) {
                check = true;
            } else {
                check = false;
            }
        } else {
            check = false;
        }
        return this.optional(element) || check;
    }, "Please enter a valid date."
);
window.MyRadioForm = {
    gCheckedValue: null,
    setUpMemberFields: function() {
        /**
     * Initialises the Member autocomplete pickers where necessary
     */
        var memberFields = $('fieldset.myradiofrm input.member-autocomplete:not(.tt-hint):not(.tt-input)');
        if (memberFields.length > 0) {
            var memberLookup = new Bloodhound(
                {
                    datumTokenizer: function(i) {
                        return Bloodhound.tokenizers.whitespace(i.fname)
                        .concat(Bloodhound.tokenizers.whitespace(i.sname));
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    limit: 5,
                    dupDetector: function(remote, local) {
                        return local.memberid == remote.memberid;
                    },
                    prefetch: {
                        url: myury.makeURL('MyRadio', 'a-findmember', {term: null, limit: 500})
                    },
                    remote: myury.makeURL('MyRadio', 'a-findmember', {limit: 5, term: ''}) + '%QUERY' //Seperated out otherwise % gets urlescaped
                }
            );
            memberLookup.initialize();

            memberFields.each(
                function() {
                    var idField =  $('#' + $(this).attr('id').replace(/-ui$/, ''));
                    var defaultVal = idField.val();

                    $(this).typeahead(
                        {
                            hint: false,
                            highlight: true,
                            minLength: 1
                        },
                        {
                            displayKey: function(i) {
                                return i.fname + ' ' + i.sname;
                            },
                            source: memberLookup.ttAdapter(),
                            templates: {
                                //Only needed for workaround
                                suggestion: function(i) {
                                    //Fix typeahead not showing after hiding
                                    //TODO: Report this @ https://github.com/twitter/typeahead.js/
                                    $('input:focus').parent().children('.tt-dropdown-menu').removeClass('hidden');
                                    return '<p>' + i.fname + ' ' + i.sname + '</p>';
                                }
                            }
                        }
                    )
                    .on(
                        'typeahead:selected', function(e, obj) {
                            idField.val(obj.memberid);
                        }
                    );
                }
            );
        }
    },
    setUpTrackFields: function() {
        /**
     * Initialises the Track autocomplete pickers where necessary
     */
        var trackFields = $('fieldset.myradiofrm input.track-autocomplete:not(.tt-hint):not(.tt-input)');
        if (trackFields.length > 0) {
            var trackLookup = new Bloodhound(
                {
                    datumTokenizer: function(i) {
                        return Bloodhound.tokenizers.whitespace(i.title)
                        .concat(Bloodhound.tokenizers.whitespace(i.artist));
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    limit: 5,
                    dupDetector: function(remote, local) {
                        return local.trackid == remote.trackid;
                    },
                    prefetch: {
                        url: myury.makeURL('MyRadio', 'a-findtrack', {term: null, limit: 500})
                    },
                    remote: myury.makeURL('MyRadio', 'a-findtrack', {limit: 5, term: ''}) + '%QUERY' //Seperated out otherwise % gets urlescaped
                }
            );
            trackLookup.initialize();

            trackFields.each(
                function() {
                    var idField =  $('#' + $(this).attr('id').replace(/-ui$/, ''));
                    var defaultVal = idField.val();

                    $(this).typeahead(
                        {
                            hint: false,
                            highlight: true,
                            minLength: 1
                        },
                        {
                            displayKey: function(i) {
                                return i.title;
                            },
                            source: trackLookup.ttAdapter(),
                            templates: {
                                suggestion: function(i) {
                                    //Fix typeahead not showing after hiding
                                    //TODO: Report this @ https://github.com/twitter/typeahead.js/
                                    $('input:focus').parent().children('.tt-dropdown-menu').removeClass('hidden');
                                    return '<p>' + i.title + '<br><span style="font-size:.8em">' + i.artist + '</span></p>';
                                }
                            }
                        }
                    )
                    .on(
                        'typeahead:selected', function(e, obj) {
                            idField.val(obj.trackid);
                        }
                    );
                }
            );
        }
    },
    setUpArtistFields: function() {
        /**
     * Initialises the Artist autocomplete pickers where necessary
     */
        var artistFields = $('fieldset.myradiofrm input.artist-autocomplete:not(.tt-hint):not(.tt-input)');
        if (artistFields.length > 0) {
            var artistLookup = new Bloodhound(
                {
                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    limit: 5,
                    dupDetector: function(remote, local) {
                        return local.title == remote.title;
                    },
                    prefetch: {
                        url: myury.makeURL('MyRadio', 'a-findartist', {term: null, limit: 500})
                    },
                    remote: myury.makeURL('MyRadio', 'a-findartist', {limit: 5, term: ''}) + '%QUERY' //Seperated out otherwise % gets urlescaped
                }
            );
            artistLookup.initialize();

            artistFields.each(
                function() {
                    var idField =  $('#' + $(this).attr('id').replace(/-ui$/, ''));
                    var defaultVal = idField.val();

                    $(this).typeahead(
                        {
                            hint: false,
                            highlight: true,
                            minLength: 1
                        },
                        {
                            displayKey: 'title',
                            source: artistLookup.ttAdapter(),
                            templates: {
                                //Only needed for workaround
                                suggestion: function(i) {
                                    //Fix typeahead not showing after hiding
                                    //TODO: Report this @ https://github.com/twitter/typeahead.js/
                                    $('input:focus').parent().children('.tt-dropdown-menu').removeClass('hidden');
                                    return '<p>' + i.title + '</p>';
                                }
                            }
                        }
                    )
                    .on(
                        'typeahead:selected', function(e, obj) {
                            idField.val(obj.artistid);
                        }
                    );
                }
            );
        }
    },
    setUpAlbumFields: function() {
        /**
     * Initialises the Album autocomplete pickers where necessary
     */
        var albumFields = $('fieldset.myradiofrm input.album-autocomplete:not(.tt-hint):not(.tt-input)');
        if (albumFields.length > 0) {
            var albumLookup = new Bloodhound(
                {
                    datumTokenizer: function(i) {
                        return Bloodhound.tokenizers.whitespace(i.title)
                        .concat(Bloodhound.tokenizers.whitespace(i.artist));
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    limit: 5,
                    dupDetector: function(remote, local) {
                        return local.title == remote.title;
                    },
                    prefetch: {
                        url: myury.makeURL('MyRadio', 'a-findalbum', {term: null, limit: 500})
                    },
                    remote: myury.makeURL('MyRadio', 'a-findalbum', {limit: 5, term: ''}) + '%QUERY' //Seperated out otherwise % gets urlescaped
                }
            );
            albumLookup.initialize();

            albumFields.each(
                function() {
                    var idField =  $('#' + $(this).attr('id').replace(/-ui$/, ''));
                    var defaultVal = idField.val();

                    $(this).typeahead(
                        {
                            hint: false,
                            highlight: true,
                            minLength: 1
                        },
                        {
                            displayKey: 'title',
                            source: albumLookup.ttAdapter(),
                            templates: {
                                //Only needed for workaround
                                suggestion: function(i) {
                                    //Fix typeahead not showing after hiding
                                    //TODO: Report this @ https://github.com/twitter/typeahead.js/
                                    $('input:focus').parent().children('.tt-dropdown-menu').removeClass('hidden');
                                    return '<p>' + i.title + '</p>';
                                }
                            }
                        }
                    )
                    .on(
                        'typeahead:selected', function(e, obj) {
                            idField.val(obj.recordid);
                        }
                    );
                }
            );
        }
    },
    setUpTimePickers: function() {
        /**
     * Initialises the Time pickers where necessary
     * @todo Make minuteStepping customisable?
     */
        $('fieldset.myradiofrm input.time').datetimepicker(
            {
                pickDate: false,
                minuteStepping: 15,
                useSeconds: false
            }
        );
    },
    setUpDatePickers: function() {
        /**
     * Initialises the Date pickers where necessary
     */
        $('fieldset.myradiofrm input.dateITA').datetimepicker(
            {
                pickTime: false
            }
        );
    },
    validate: function() {
        /**
     * Validation
     */
        $('fieldset.myradiofrm form').validate(
            {
                errorClass: 'bg-danger',
                errorPlacement: function(error, element) {
                    error.css('width', element.css('width'))
                    .css('margin-left', element.css('margin-left'))
                    .appendTo(element.parents('div.myradiofrmfield-container'));
                },
                submitHandler: function(form) {
                    $(form).children('input[type=submit]').attr('disabled', 'disabled');
                    form.submit();
                }
            }
        );
    },
    /**
   * Sets up those pretty week drag-drop select fields. I wrote it, but don't understand it.
   */
    setUpWeekSelectFields: function() {
        $.each(
            $('table.myradiofrmfield-weeklycheck td'), function() {
                $(this).on(
                    'mousedown', function(e) {
                        if (MyRadioForm.gCheckedValue === null) {
                            /**
                    * Start a drag selection. Invert the state of the selected checkbox,
                    * and set a variable define what other checkboxes selected should be
                    * configured to (checked or unchecked). This variable is also
                    * used to mark dragging as active.
                    */
                            var input = $(this).children('input[type=checkbox]').first();
                            input.prop('checked', !input.prop('checked'));
                            MyRadioForm.gCheckedValue = input.prop('checked');
                        }
                        e.preventDefault();
                    }
                ).on(
                    'mouseenter', function() {
                        //Is there an active dragging event?
                        if (MyRadioForm.gCheckedValue === null) {
                            return; //Nope.
                        }
                        $(this).children('input[type=checkbox]').prop('checked', MyRadioForm.gCheckedValue);
                    }
                ).on(
                    'click', function(e) {
                        //Stop the default click handler running - it unselects boxes.
                        e.preventDefault();
                    }
                );
            }
        );
        //Initialise this to the whole page, otherwise mouseup outside the table makes a mess
        $(document).on(
            'mouseup', function() {
                //End the dragging event
                MyRadioForm.gCheckedValue = null;
            }
        );
    },
    setUpTinyMCEFields: function() {
        /**
     * Initialises TinyMCE fields
     */
        if (typeof tinymce !== 'undefined') {
            tinymce.init(
                {
                    selector: "textarea.myury-form-tinymce",
                    plugins: "anchor autolink charmap code contextmenu fullscreen hr image link lists media tabfocus table wordcount",
                    relative_urls: false,
                    remove_script_host: false
                }
            );
        }
    },
    setUpDateTimePickerFields: function() {
        /**
     * Initialises the Datetime pickers where necessary
     * @todo Make stepminute customisable?
     */
        $('fieldset.myradiofrm input.datetime').datetimepicker(
            {
                minuteStepping: 15,
                useSeconds: false
            }
        );
    },
    setUpCheckboxGroups: function() {
        /**
     * Setup Checkbox Group select all / select none
     */
        $('fieldset a.checkgroup-all').click(
            function() {
                $(this).parents('fieldset:first').find('input[type=checkbox]').each(
                    function() {
                        $(this).attr('checked', 'checked');
                    }
                );
            }
        );
        $('fieldset a.checkgroup-none').click(
            function() {
                $(this).parents('fieldset:first').find('input[type=checkbox]').each(
                    function() {
                        $(this).attr('checked', null);
                    }
                );
            }
        );
    },
    setUpRepeatingSets: function() {
        //Set up tabular repeating sets
        $('.myury-form-add-row-button').on(
            'click', function() {
                var new_id = $(this).attr('nextvalue');
                $('#' + $(this).attr('id').replace(/add-to-/, '') + ' tbody tr:first').clone()
                .addClass(parseInt(new_id) % 2 === 0 ? 'odd' : 'even')
                .find('input:not(.tt-hint)').each(
                    function() {
                        $(this).val('').removeClass('tt-input').attr('id', $(this).attr('id').replace(/0/, new_id));
                    }
                ).end().appendTo('#' + $(this).attr('id').replace(/add-to-/, '') + ' tbody');
                MyRadioForm.setUpArtistFields();
                MyRadioForm.setUpMemberFields();
                MyRadioForm.setUpTrackFields();
                MyRadioForm.setUpAlbumFields();
                MyRadioForm.setUpTimePickers();
                $(this).attr('nextvalue', parseInt(new_id) + 1);
            }
        );
        $('button.myuryfrm-remove-row').on(
            'click', function() {
                $(this).closest('tr').remove();
                return false;
            }
        );
        //And the dataTable that contains them
        $('table.myuryfrm-repeaterset-container').dataTable(
            {
                bSort: false,
                bPaginate: false,
                bFilter: false
            }
        );
    },
    setUpFileProgress: function() {
        /**
     * Sets up the progress bar for file upload progress
     */
        if ($('#UPLOAD_IDENTIFIER').length !== 0) {
            $('form').on(
                'submit', function() {
                    if ($('#UPLOAD_IDENTIFIER').nextAll('input')[0].value !== "") {
                        $('.myuryfrm-file-upload-progress').progressbar({value: false});
                        //Poke the server for upload progress status
                        setInterval(MyRadioForm.pollFileProgress, 1000);
                    }
                }
            );
            $('#myradiofrm-file-upload-iframe').on(
                'load', function() {
                    data = $.parseJSON($($(this).contents().find('body').children()[0]).html());
                    percent = data.bytes_uploaded / data.bytes_total * 100;
                    $('.myuryfrm-file-upload-progress').progressbar('value', percent);
                    $('.progress-label').html(
                        Math.floor(percent) + '% (' +
                        Math.floor(data.speed_average / 1024) + 'Kbps)'
                    );
                }
            );
        }
    },
    pollFileProgress: function() {
        /**
     * You could ask "Why not use $.ajax?" Well the answer is that WebKit
     * won't let you start a new XHR once the form is submitted. YAAY iFrames!
     */
        $('#myradiofrm-file-upload-iframe').attr(
            'src',
            myury.makeURL(
                'MyRadio', 'a-getuploadprogress', {
                    id: $('#UPLOAD_IDENTIFIER').val(),
                    1: (new Date()).getTime()
                }
            )
        );
    },
    init: function() {
        MyRadioForm.setUpRepeatingSets();
        MyRadioForm.setUpTinyMCEFields();
        MyRadioForm.setUpDateTimePickerFields();
        MyRadioForm.setUpDatePickers();
        MyRadioForm.setUpTimePickers();
        MyRadioForm.setUpMemberFields();
        MyRadioForm.setUpTrackFields();
        MyRadioForm.setUpArtistFields();
        MyRadioForm.setUpAlbumFields();
        MyRadioForm.setUpWeekSelectFields();
        MyRadioForm.setUpCheckboxGroups();
        MyRadioForm.setUpFileProgress();
        MyRadioForm.validate();
    }
};
$(document).ready(
    function() {
        MyRadioForm.init();
    }
);
