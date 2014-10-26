var Library = function() {
    var auxid = null;
    var allowed_mp3 = ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio'];

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
                message = file.name + ' is too big. Please upload files smaller than 100MB.';
                break;
            case 'FileTypeNotAllowed':
                message = 'That file is not a valid audio file.';
                break;
            default:
                message = 'An unknown error occured: ' + err;
        };

        $('.result-container:visible').append('<div class="alert alert-danger">' + message + '</div>');
    }

    var centralDbInit = function() {
        /** Central Database Handler **/
        $('#central-dragdrop').filedrop({
            url: myury.makeURL('NIPSWeb', 'upload_central'),
            paramname: 'audio',
            error: filedrop_error_handler,
            allowedfiletypes: allowed_mp3,
            maxfiles: 20,
            maxfilesize: 100,
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

                setTimeout(function() {
                    if ($('#central-status').html() == status) {
                        $('#central-status').html('Ready');
                    }
                }, 5000)

                var manual_track = false;
                if (response['status'] !== 'OK' || response.analysis.length === 0) {
                    var manual_div = document.getElementById('track-manual-entry');
                    if (manual_div !== null) {
                        // If the div exists, then the user has permission to upload a track
                        // manually, so display the div and set manual_track to true.
                        manual_div.style.display = 'block';
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
                    $.each(response.analysis, function (key, value) {
                        select.append('<option value="' + value.title + ':-:' + value.artist + '">' + value.title + ' by ' + value.artist + '</option>');
                    });
                }

                // The submit part
                var submit = $('<a href="javascript:">Save' + (manual_track ? ' using below metadata' : '') + '</a>').click(function () {
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
                    $.ajax({
                        url: myury.makeURL('NIPSWeb', 'confirm_central_upload'),
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
                                    .html('<div class="glyphicon glyphicon-exclamation-sign">&nbsp;' + track_title + ': ' + data.error);
                            }
                        }
                    });
                });

                result.append('<label for="centralupload-' + i + '">' + file.name + ':&nbsp;</label>')
                    .append(select)
                    .append(submit);
                $('#central-result').append(result);
            }
        });
    }

    var initialise = function() {
        $('#res-type-sel').on('click', res_type_sel_change_handler);
        centralDbInit();
        $('#central-status, #res-status').html('Ready');
    };

    return {
        auxid: auxid,
        initialise: initialise
    };
}



$(document).ready(function () {
    library = Library();
    library.initialise();


    /** Auxillary Database Handler **/
    $('#res-dragdrop').filedrop({
        url: myury.makeURL('NIPSWeb', 'upload_aux'),
        paramname: 'audio',
        error: function (err, file) {
            switch (err) {
            case 'BrowserNotSupported':
                $('body').html('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>Something went wrong - are you sure you are using Google Chrome or Mozilla Firefox? We\'ve had reports of issues with Firefox on Linux too...</div>');
                break;
            case 'TooManyFiles':
                $('body').prepend('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>Please don\'t upload too many files at once</div>');
                break;
            case 'FileTooLarge':
                $('body').prepend('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>That file (' + file.name + ') is too big. Please upload files smaller than 100MB</div>');
                break;
            case 'FileTypeNotAllowed':
                $('body').prepend('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>That file is not a valid audio file</div>');
                break;
            case 'NoFileDropped':
                $('body').prepend('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>Hmm, I didn\'t see a file get dropped... please make sure you are using Google Chrome or Mozilla Firefox for best results.</div>');
                break;
            case 'NoFilesVar':
                $('body').prepend('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>Odd, my files have gone missing... please make sure you are using Google Chrome or Mozilla Firefox for best results.</div>');
                break;
            default:
                $('body').html('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>An unknown error occured: ' + err + '</div>');
            }
        },
        allowedfiletypes: ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio', 'audio/wav', 'audio/x-wav',
            'audio/mp4a-latm', 'audio/mp4', 'audio/aac'
        ],
        maxfiles: 20,
        maxfilesize: 100,
        queuefiles: 1,
        drop: function () {
            console.log('Drop detected (auxdb).');
            $('#res-status').html('Reading file (0%)...');
        },
        uploadStarted: function (i, file, total) {
            console.log('Upload started (auxdb).');
            $('#res-status').html('Uploading ' + file.name + '... (' + byteSize(file.size) + ')');
        },
        progressUpdated: function (i, file, progress) {
            $('#res-status').html('Reading ' + file.name + ' (' + progress + '%)...');
        },
        uploadFinished: function (i, file, response, time) {
            console.log(file.name + ' (id#' + i + ') has uploaded in ' + time);
            $('#res-status').html('Uploaded ' + file.name);

            if (response['status'] == 'FAIL') {
                //An error occurred
                $('#res-result').append('<div class="ui-state-error">' + file.name + ': ' + response['error'] + '</div>');
                return;
            } else {
                $('#res-result').append('<div id="resupload-' + i + '">' + file.name + ': <input type="text" class="title" name="' + response.fileid + '" id="resuploadname-' + i + '" placeholder="Enter a helpful name..." /></div>');
                if (window.auxid.match(/^aux-\d+$/)) {
                    //This is a central one - it can have an expiry
                    $('#resupload-' + i).append(
                        $('<input type="text" placeholder="Expiry date" />').addClass('date').attr('id', 'resuploaddate-' + i).datepicker({
                            dateFormat: 'dd/mm/yy'
                        }))
                        .append('<em>Leave blank to never expire</em>&nbsp;&nbsp;');
                }
                $('#res-result').append('<div id="confirminator-' + (response.fileid.replace(/\.mp3/, '')) + '"></div>');
                $('#resupload-' + i).append($('<a href="javascript:">Save</a>').click(function () {
                    var title = $(this).parent().find('input.title').val();
                    var expire = $(this).parent().find('input.date').val() || null;
                    var fileid = $(this).parent().find('input.title').attr('name');
                    $(this).parent().remove();
                    $.ajax({
                        url: myury.makeURL('NIPSWeb', 'confirm_aux_upload'),
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
                                $('#confirminator-' + data.fileid).html('<span class="ui-icon ui-icon-circle-check" style="float:left"></span>' + data.title + ': Upload Successful');
                            } else {
                                $('#confirminator-' + data.fileid).html('<span class="ui-icon ui-icon-alert" style="float:left"></span>' + data.title + ': ' + data.error);
                            }
                        }
                    });
                }));
            }
        }
    });
});
