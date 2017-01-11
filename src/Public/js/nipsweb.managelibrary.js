var Library = function () {
    var auxid = null;
    var allowed_mp3 = ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio'];
    var allowed_all = ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio', 'audio/wav', 'audio/x-wav',
            'audio/mp4a-latm', 'audio/mp4', 'audio/aac'];

    var icon_error = '<div class="glyphicon glyphicon-exclamation-sign"></div>&nbsp;';
    var icon_ok = '<div class="glyphicon glyphicon-ok"></div>&nbsp;';
    var icon_ok = '<div class="glyphicon glyphicon-ok"></div>&nbsp;';
    var icon_loading = '<div class="glyphicon glyphicon-refresh gly-spin"></div>&nbsp;';
    var icon_close = '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    //Converts bytes to human readable numbers
    var byteSize = function (size) {
        if (size > 1048576) {
            return (size / 1048576).toFixed(2) + 'MB';
        }
        if (size > 1024) {
            return (size / 1024).toFixed(2) + 'KB';
        }
        return size + 'B';
    }

    // Handles change events for the library dropdown
    var res_type_sel_change_handler = function () {
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

    var filedrop_error_handler = function (err, file) {
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

    var centralDbInit = function () {
        /**
 * Central Database Handler
**/
        $('#central-dragdrop').filedrop(
            {
                url: myradio.makeURL('NIPSWeb', 'upload_central'),
                paramname: 'audio',
                error: filedrop_error_handler,
                allowedfiletypes: allowed_mp3,
                maxfiles: 1,
                maxfilesize: mConfig.audio_upload_max_size,
                queuefiles: 1,
                drop: function () {
                    $('#central-status').html(icon_loading + 'Reading file (0%)...');
                },
                uploadStarted: function (i, file, total) {
                    $('#central-status').html(icon_loading + 'Uploading ' + file.name + '... (' + byteSize(file.size) + ')');
                },
                progressUpdated: function (i, file, progress) {
                    $('#central-status').html(icon_loading + 'Reading ' + file.name + ' (' + progress + '%)...');
                },
                uploadFinished: function (i, file, response, time) {
                    var status = icon_ok + 'Uploaded ' + file.name;
                    $('#central-status').html(status);

                    setTimeout(
                        function () {
                            if ($('#central-status').html() == status) {
                                $('#central-status').html(icon_ok + 'Ready');
                            }
                        },
                        5000
                    )

                    var manual_track = false;
                    var manual_div = document.getElementById('track-manual-entry');
                        if (manual_div !== null) {
                            if (response['status'] !== 'OK' && response['submittable'] == true ) {
                                
                                    // If the div exists, then the user has permission to upload a track
                                    // manually, so display the div and set manual_track to true.
                                    $(manual_div).slideDown();
                                    manual_track = true;
                                
                            } else if (response['status'] !== 'OK' && response['submittable'] != true ) {
                                    $(manual_div).slideUp();
                                    manual_track = false;
                            } 
                            //Prevents any previous uploaded but not submmited tracks from being incorrectly submitted.
                            $('.current-track span').html(icon_error)
                            $('.current-track').append('File was not submitted. ');
                            $('.current-track a').remove();
                            $('.current-track').removeClass('alert-info').addClass('alert-danger').removeClass('current-track'); 
                        }       
                    var result = $('<div class="alert"></div>');

                    if (response['status'] !== 'OK') {
                        if (response['status'] === 'FAIL') {
                            //An error occurred
                            result.addClass('alert-danger').append('<span class="error">' + icon_error + response['message'] + ': </span>');
                        } else if (response['status'] === 'INFO') {
                            //An info message has been sent (A song is now being edited)
                            result.addClass('alert-info current-track').append('<span class="error">' + response['message'] + ': </span>');
                        }
                    }

                    // Track info.
                    var track_fileid = "";
                    var track_title = "";
                    var track_artist = "";
                    var track_album = "";
                    var track_position = "";


                    if (manual_track) {
                        if (response.analysis) {
                            //Otherwise, if we got an analysis from the ID3 tags, prefill the textboxes.
                            function decodeHtml(html) {
                                //Removes special HTML &xxx; from recieved data
                                var txt = document.createElement("textarea");
                                txt.innerHTML = html;
                                return txt.value;
                            }
                            document.getElementById('track-manual-entry-title').value = decodeHtml(response.analysis.title);
                            document.getElementById('track-manual-entry-artist').value = decodeHtml(response.analysis.artist);
                            document.getElementById('track-manual-entry-album').value = decodeHtml(response.analysis.album);
                            document.getElementById('track-manual-entry-position').value = decodeHtml(response.analysis.position);
                            document.getElementById('track-manual-entry-explicit').checked = response.analysis.explicit;
                        } else {
                            //If we didn't get an analysis for some reason, just make the textboxes empty.
                            document.getElementById('track-manual-entry-title').value = '';
                            document.getElementById('track-manual-entry-artist').value = '';
                            document.getElementById('track-manual-entry-album').value = '';
                            document.getElementById('track-manual-entry-position').value = '';
                            document.getElementById('track-manual-entry-explicit').checked = false;
                        }
                    }

                    // The submit part
                    var submit = $('<a href="javascript:">Save' + (manual_track ? ' using below metadata' : '') + '</a>').click(
                        function () {
                            if (manual_track) {
                                track_fileid = response.fileid;
                                track_title = document.getElementById('track-manual-entry-title').value;
                                track_artist = document.getElementById('track-manual-entry-artist').value;
                                track_album = document.getElementById('track-manual-entry-album').value;
                                track_position = document.getElementById('track-manual-entry-position').value;
                                track_explicit = document.getElementById('track-manual-entry-explicit').checked;

                                if (!track_title) {
                                    $('.form-error').html(icon_error + 'Please enter a title.').slideDown();
                                    $('#track-manual-entry-title').focus();
                                    return;
                                }
                                if (!track_artist) {
                                    $('.form-error').html(icon_error + 'Please enter an artist.').slideDown();
                                    $('#track-manual-entry-artist').focus();
                                    return;
                                }
                                if (!track_album) {
                                    $('.form-error').html(icon_error + 'Please enter an album.').slideDown();
                                    $('#track-manual-entry-album').focus();
                                    return;
                                }
                                if (!track_position) {
                                    $('.form-error').html(icon_error + 'Please enter a track number.').slideDown();
                                    $('#track-manual-entry-position').focus();
                                    return;
                                }
                            }

                            result.removeClass('alert-danger')
                            .addClass('alert-info')
                            .html(icon_loading + '<strong>' + track_title + '</strong> is saving...').slideDown();
                            $('#track-manual-entry').slideUp();
                            
                            $.ajax(
                                {
                                    url: myradio.makeURL('NIPSWeb', 'confirm_central_upload'),
                                    data: {
                                        title: track_title,
                                        artist: track_artist,
                                        album: track_album,
                                        position: track_position,
                                        fileid: track_fileid,
                                        explicit: track_explicit
                                    },
                                    dataType: 'json',
                                    type: 'get',
                                    success: function (data) {
                                        data.fileid = data.fileid.replace(/\.mp3/, '');
                                        if (data.status == 'OK') {
                                            result.removeClass('alert-info')
                                            .removeClass('current-track')
                                            .addClass('alert-success alert-dismissable')
                                            .html(icon_close + icon_ok + '<strong>' + track_title + '</strong> uploaded successfully.');
                                        } else {
                                            result.removeClass('alert-info')
                                            .removeClass('current-track')
                                            .addClass('alert-danger alert-dismissable')
                                            .html(icon_close + icon_error + '<strong>' + track_title + '</strong> ' + data.error);
                                        }
                                    }
                                }
                            );
                        }
                    );

                    result.append('<label for="centralupload-' + i + '">' + file.name + ' &nbsp;</label>');
                    //.append(select)
                    if (manual_track == true) {
                        result.append(submit);
                    }
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
                    $('#res-status').html(icon_loading + 'Reading file (0%)...');
                },
                uploadStarted: function (i, file, total) {
                    $('#res-status').html(icon_loading + 'Uploading ' + file.name + '... (' + byteSize(file.size) + ')');
                },
                progressUpdated: function (i, file, progress) {
                    $('#res-status').html(icon_loading + 'Reading ' + file.name + ' (' + progress + '%)...');
                },
                uploadFinished: function (i, file, response, time) {
                    $('#res-status').html(icon_ok + 'Uploaded ' + file.name);

                    var result = $('<div class="alert"></div>');
                    if (response['status'] == 'FAIL') {
                        //An error occurred, probably bitrate is too low.
                        result.addClass('alert-danger alert-dismissable').append(icon_error + icon_close + '<strong>' + file.name + ':</strong> <span class="error">' + response['error'] + '</span>');
                        $('#res-result').append(result);
                    } else {
                        result.addClass('alert-info').append(
                            '<div id="resupload-' + i + '" class="row">' + 
                                '<label for="resuploadname-' + i + '" class="col-xs-4 control-label">' +
                                    file.name + 
                                ':</label>' +
                                '<div class="col-xs-6">' + 
                                    '<input type="text" class="title form-control" name="' +
                                        response.fileid + '" id="resuploadname-' + i +
                                    '" placeholder="Enter a helpful name..." />' +
                                '</div>' +
                            '</div>'
                                
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
                            .append('<strong>Leave blank to never expire</strong>&nbsp;&nbsp;');
                        }
                        result.append('<div id="confirminator-' + (response.fileid.replace(/\.mp3/, '')) + '"></div>');
                        result.find('.row').append(
                            $('<div class="col-xs-2"><button type="button" class="btn btn-primary save-button">Save</button></div>').click(
                                function () {
                                    var title = result.find('input.title').val();
                                    var expire = result.find('input.date').val() || null;
                                    var fileid = result.find('input.title').attr('name');

                                    result.html(icon_loading + 'Adding <strong>' + title + '</strong> to library...');
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
                                                    .addClass('alert-success alert-dismissable')
                                                    .html(icon_ok + icon_close + '<strong>' + title + ':</strong> was added to library.');
                                                } else {
                                                    result.removeClass('alert-info')
                                                    .addClass('alert-danger alert-dismissable')
                                                    .html(
                                                        icon_error + icon_close + '<strong>' + title + ':</strong> could not be added to library.<br>Error: '
                                                        + data.error
                                                    );
                                                }
                                            }
                                        }
                                    );
                                }
                            )
                        );
                    }
                    $('#res-result').append(result);
                }
            }
        );
    };

    var initialise = function () {
        $('#res-type-sel').on('change', res_type_sel_change_handler);
        $('#res-type-sel').on('click', res_type_sel_change_handler);
        $('#res-type-sel').on('keyup', res_type_sel_change_handler);
        centralDbInit();
        auxDbInit();
        $('#central-status, #res-status').html('<div class="glyphicon glyphicon-ok"></div>&nbsp;Ready');
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
