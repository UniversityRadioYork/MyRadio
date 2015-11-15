var Library = function() {
    var auxid = null;
    var allowed_mp3 = ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio'];
    var allowed_all = ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio', 'audio/wav', 'audio/x-wav',
            'audio/mp4a-latm', 'audio/mp4', 'audio/aac']

    //Converts bytes to human readable numbers
    var byteSize = function(size) {
        if (size > 1048576) {
            return (size / 1048576).toFixed(2) + 'MB';
        }
        if (size > 1024) {
            return (size / 1024).toFixed(2) + 'KB';
        }
        return size + 'B';
    }

    // Handles change events for the library dropdown
    var res_type_sel_change_handler = function() {
        $('div.res-container').hide();
        if ($('#res-type-sel').val() === null) {
            return;
        }
        if ($('#res-type-sel').val() === 'central') {
            $('#central-container').show();
            return;
        }
        if ($('#res-type-sel').val().match(/^managed-.*/)) {
            $('#managed-container').show();
            return;
        }
        window.auxid = $('#res-type-sel').val().replace(/^res-/, '');
        $('#res-container').show();
    };

    var filedrop_error_handler = function(err, file) {
        var message;
        switch (err) {
        case 'BrowserNotSupported':
            message = 'You need to use Google Chrome or Mozilla Firefox 3.6+ to upload files.';
                break;
        case 'TooManyFiles':
            message = 'Please don\'t upload too many files at once.';
                break;
        case 'FileTooLarge':
            message = file.name + ' is too big. Please upload files smaller than ' + mConfig.audio_upload_max_size + 'MB.';
                break;
        case 'FileTypeNotAllowed':
            message = file.name + ' is not a valid audio file.';
                break;
        default:
            message = 'An unknown error occured: ' + err;
        };

        $('.result-container:visible').append('<div class="alert alert-danger">' + message + '</div>');
    }

    var centralDbInit = function() {
        /**
 * Central Database Handler 
**/
        $('#central-dragdrop').filedrop(
            {
                url: myradio.makeURL('NIPSWeb', 'upload_central'),
                paramname: 'audio',
                error: filedrop_error_handler,
                allowedfiletypes: allowed_mp3,
                maxfiles: 20,
                maxfilesize: mConfig.audio_upload_max_size,
                queuefiles: 1,
                drop: function () {
                    $('#central-status').html('Reading file (0%)...');
                },
                uploadStarted: function (i, file, total) {
                    $('#central-status').html('Uploading ' + file.name + '... (' + byteSize(file.size) + ')');
                },
                progressUpdated: function (i, file, progress) {
                    $('#central-status').html('Reading ' + file.name + ' (' + progress + '%)...');
                },
                uploadFinished: function (i, file, response, time) {
                    var status = 'Uploaded ' + file.name;
                    $('#central-status').html(status);

                    setTimeout(
                        function() {
                            if ($('#central-status').html() == status) {
                                $('#central-status').html('Ready');
                            }
                        }, 5000
                    )

                    var manual_track = false;
                    if (response['status'] !== 'OK' || response.analysis.length === 0) {
                        var manual_div = document.getElementById('track-manual-entry');
                        if (manual_div !== null) {
                            // If the div exists, then the user has permission to upload a track
                            // manually, so display the div and set manual_track to true.
                            $(manual_div).show();
                            manual_track = true;
                        }
                    }

                    var result = $('<div class="alert"></div>');

                    if (response['status'] !== 'OK') {
                        //An error occurred
                        result.addClass('alert-danger').append('<span class="error">' + response['error'] + '</span>');
                        if (manual_track) {
                            result.append('<br>');
                        }
                    } else {
                        result.addClass('alert-info');
                    }

                    // Track info.
                    var track_fileid = "";
                    var track_title = "";
                    var track_artist = "";
                    var track_album = "";
                    var track_position = "";

                    // Build a list of tracks from the lastfm responses and store it in a drop
                    // down list
                    var select = "";
                    if (!manual_track) {
                        select = $('<select></select>')
                        .attr('name', response.fileid).attr('id', 'centralupload-' + i);
                        $.each(
                            response.analysis, function (key, value) {
                                select.append('<option value="' + value.title + ':-:' + value.artist + '">' + value.title + ' by ' + value.artist + '</option>');
                            }
                        );
                    }

                    // The submit part
                    var submit = $('<a href="javascript:">Save' + (manual_track ? ' using below metadata' : '') + '</a>').click(
                        function () {
                            if (!manual_track) {
                                var select = $(this).parent().find('select').val();
                                track_fileid = $(this).parent().find('select').attr('name');
                                track_title = select.replace(/:-:.*$/, '');
                                track_artist = select.replace(/^.*:-:/, '');
                                track_album = "FROM_LASTFM";
                                track_position = "FROM_LASTFM";
                            } else {
                                track_fileid = response.fileid;
                                track_title = document.getElementById('track-manual-entry-title').value;
                                track_artist = document.getElementById('track-manual-entry-artist').value;
                                track_album = document.getElementById('track-manual-entry-album').value;
                                track_position = document.getElementById('track-manual-entry-position').value;

                                if (!track_title) {
                                    result.find('.error').html('Please enter a title');
                                    $('#track-manual-entry-title').focus();
                                    return;
                                }
                                if (!track_artist) {
                                    result.find('.error').html('Please enter an artist');
                                    $('#track-manual-entry-artist').focus();
                                    return;
                                }
                                if (!track_album) {
                                    result.find('.error').html('Please enter an album');
                                    $('#track-manual-entry-album').focus();
                                    return;
                                }
                                if (!track_position) {
                                    result.find('.error').html('Please enter a track number');
                                    $('#track-manual-entry-position').focus();
                                    return;
                                }
                            }

                            result.removeClass('alert-danger')
                            .addClass('alert-info')
                            .html(track_title + ': Saving');
                            $.ajax(
                                {
                                    url: myradio.makeURL('NIPSWeb', 'confirm_central_upload'),
                                    data: {
                                        title: track_title,
                                        artist: track_artist,
                                        album: track_album,
                                        position: track_position,
                                        fileid: track_fileid
                                    },
                                    dataType: 'json',
                                    type: 'get',
                                    success: function (data) {
                                        data.fileid = data.fileid.replace(/\.mp3/, '');
                                        if (data.status == 'OK') {
                                            result.removeClass('alert-info')
                                            .addClass('alert-success')
                                            .html('<div class="glyphicon glyphicon-ok"></div>&nbsp;' + track_title + ' uploaded');
                                        } else {
                                            result.removeClass('alert-info')
                                            .addClass('alert-danger')
                                            .html('<div class="glyphicon glyphicon-exclamation-sign"></div>&nbsp;' + track_title + ': ' + data.error);
                                        }
                                    }
                                }
                            );
                        }
                    );

                    result.append('<label for="centralupload-' + i + '">' + file.name + ':&nbsp;</label>')
                    .append(select)
                    .append(submit);
                    $('#central-result').append(result);
                }
            }
        );
    };

    /**
 * Auxillary Database Handler 
**/
    var auxDbInit = function () {
        $('#res-dragdrop').filedrop(
            {
                url: myradio.makeURL('NIPSWeb', 'upload_aux'),
                paramname: 'audio',
                error: filedrop_error_handler,
                allowedfiletypes: allowed_all,
                maxfiles: 20,
                maxfilesize: mConfig.audio_upload_max_size,
                queuefiles: 1,
                drop: function () {
                    $('#res-status').html('Reading file (0%)...');
                },
                uploadStarted: function (i, file, total) {
                    $('#res-status').html('Uploading ' + file.name + '... (' + byteSize(file.size) + ')');
                },
                progressUpdated: function (i, file, progress) {
                    $('#res-status').html('Reading ' + file.name + ' (' + progress + '%)...');
                },
                uploadFinished: function (i, file, response, time) {
                    $('#res-status').html('Uploaded ' + file.name);

                    var result = $('<div class="alert"></div>');
                    if (response['status'] == 'FAIL') {
                        //An error occurred
                        result.addClass('alert-danger').append('<span class="error">' + response['error'] + '</span>');
                    } else {
                        result.addClass('alert-info').append(
                            '<div id="resupload-' + i + '">' +
                            file.name + ': <input type="text" class="title" name="' +
                            response.fileid + '" id="resuploadname-' + i +
                            '" placeholder="Enter a helpful name..." /></div>'
                        );

                        if (window.auxid.match(/^aux-\d+$/)) {
                            //This is a central one - it can have an expiry
                            result.append(
                                $('<input type="text" placeholder="Expiry date" />').addClass('date').attr('id', 'resuploaddate-' + i).datetimepicker(
                                    {
                                        pickTime: 'false'
                                    }
                                )
                            )
                            .append('<em>Leave blank to never expire</em>&nbsp;&nbsp;');
                        }
                        result.append('<div id="confirminator-' + (response.fileid.replace(/\.mp3/, '')) + '"></div>');
                        result.append(
                            $('<a href="javascript:">Save</a>').click(
                                function () {
                                    var title = result.find('input.title').val();
                                    var expire = result.find('input.date').val() || null;
                                    var fileid = result.find('input.title').attr('name');

                                    result.html('Adding <em>' + title + '</em> to library');
                                    $.ajax(
                                        {
                                            url: myradio.makeURL('NIPSWeb', 'confirm_aux_upload'),
                                            data: {
                                                auxid: window.auxid,
                                                fileid: fileid,
                                                title: title,
                                                expires: expire
                                            },
                                            dataType: 'json',
                                            type: 'get',
                                            success: function (data) {
                                                data.fileid = data.fileid.replace(/\.mp3/, '');
                                                if (data.status == 'OK') {
                                                    result.removeClass('alert-info')
                                                    .addClass('alert-success')
                                                    .html('<div class="glyphicon glyphicon-ok"></div><em>' + title + '</em> added to library');
                                                } else {
                                                    result.removeClass('alert-info')
                                                    .addClass('alert-danger')
                                                    .html(
                                                        '<div class="glyphicon glyphicon-exclamation-sign"></div><em>' + title + '</em> could not be added to library<br>'
                                                        + data.error
                                                    );
                                                }
                                            }
                                        }
                                    );
                                }
                            )
                        );

                        $('#res-result').append(result);
                    }
                }
            }
        );
    };

    var initialise = function() {
        $('#res-type-sel').on('change', res_type_sel_change_handler);
        $('#res-type-sel').on('click', res_type_sel_change_handler);
        $('#res-type-sel').on('keyup', res_type_sel_change_handler);
        centralDbInit();
        auxDbInit();
        $('#central-status, #res-status').html('Ready');
    };

    return {
        auxid: auxid,
        initialise: initialise
    };
}



$(document).ready(
    function () {
        library = Library();
        library.initialise();
    }
);
