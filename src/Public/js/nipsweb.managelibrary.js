/**
 * @todo This has a lot of duplicated code
 */
window.auxid = null;

function res_type_sel_change_handler() {
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
}

//Converts bytes to human readable numbers
function byteSize(size) {
    if (size > 1048576) {
        return (size / 1048576).toFixed(2) + 'MB';
    }
    if (size > 1024) {
        return (size / 1024).toFixed(2) + 'KB';
    }
    return size + 'B';
}

$(document).ready(function () {
    $('#res-type-sel').on('click', function () {
        res_type_sel_change_handler();
    });

    /** Central Database Handler **/
    $('#central-dragdrop').filedrop({
        url: myury.makeURL('NIPSWeb', 'upload_central'),
        paramname: 'audio',
        error: function (err, file) {
            switch (err) {
            case 'BrowserNotSupported':
                $('body').html('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>You need to use Google Chrome or Mozilla Firefox 3.6+ to upload files</div>');
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
            default:
                $('body').html('<div class="ui-state-error"><span class="ui-icon ui-icon-alert"></span>An unknown error occured: ' + err + '</div>');
            }
        },
        allowedfiletypes: ['audio/mpeg3', 'audio/x-mpeg-3', 'audio/mpeg', 'audio/x-mpeg',
            'audio/mp3', 'audio/x-mp3', 'audio/mpg', 'audio/mpg3', 'audio/mpegaudio'
        ],
        maxfiles: 20,
        maxfilesize: 100,
        queuefiles: 1,
        drop: function () {
            console.log('Drop detected (centraldb).');
            $('#central-status').html('Reading file (0%)...');
        },
        uploadStarted: function (i, file, total) {
            console.log('Upload started (centraldb).');
            $('#central-status').html('Uploading ' + file.name + '... (' + byteSize(file.size) + ')');
        },
        progressUpdated: function (i, file, progress) {
            $('#central-status').html('Reading ' + file.name + ' (' + progress + '%)...');
        },
        uploadFinished: function (i, file, response, time) {
            console.log(file.name + ' (id#' + i + ') has uploaded in ' + time);
            $('#central-status').html('Uploaded ' + file.name);

            var from_lastfm = true;

            if (response['status'] === 'FAIL') {
                //An error occurred
                $('#central-result').append('<div class="ui-state-error">' + file.name + ': ' + response['error'] + '</div>');
                return;
            } else if (response['status'] === 'NO_LASTFM_MATCH') {
                from_lastfm = false;
            }

            // Track info.
            var track_fileid = "";
            var track_title = "";
            var track_artist = "";
            var track_album = "";
            var track_position = "";

            // Build a list of tracks from the lastfm responses and store it in a drop
            // down list
            var track_data = "";
            if (from_lastfm) {
                track_data = $('<select></select>')
                    .attr('name', response.fileid).attr('id', 'centralupload-' + i);
                $.each(response.analysis, function (key, value) {
                    track_data.append('<option value="' + value.title + ':-:' + value.artist + '">' + value.title + ' by ' + value.artist + '</option>');
                });
            } else {
                track_data = $('<fieldset id="manualupload-' + i + '">' +
                    '<legend>' + file.name + ' - Track not found. Please enter the details manually:</legend>' +
                    '<div class="myradiofrmfield-container">' +
                    '   <label for="manualupload-' + i + '-title">Title</label>' +
                    '   <input type="text" class="myradiofrmfield required" name="manualupload-' + i + '-title" id="manualupload-' + i + '-title">' +
                    '   <label for="manualupload-' + i + '-artist">Artist</label>' +
                    '   <input type="text" class="myradiofrmfield required" name="manualupload-' + i + '-artist" id="manualupload-' + i + '-artist">' +
                    '   <label for="manualupload-' + i + '-album">Album</label>' +
                    '   <input type="text" class="myradiofrmfield required" name="manualupload-' + i + '-album" id="manualupload-' + i + '-album">' +
                    '   <label for="manualupload-' + i + '-position">Track Position</label>' +
                    '   <input type="text" class="myradiofrmfield required" name="manualupload-' + i + '-position" id="manualupload-' + i + '-position">' +
                    '</div>' +
                    '</fieldset>');
            }

            // The submit part
            var submit = $('<a href="javascript:">Save to Database</a>').click(function () {
                console.log('Saving track to database');

                if (from_lastfm) {
                    var select = $(this).parent().find('select').val();
                    track_fileid = $(this).parent().find('select').attr('name');
                    track_title = select.replace(/:-:.*$/, '');
                    track_artist = select.replace(/^.*:-:/, '');
                    track_album = "FROM_LASTFM";
                    track_position = "FROM_LASTFM";
                } else {
                    track_fileid = response.fileid;
                    track_title = $('#manualupload-' + i + '-title').val();
                    track_artist = $('#manualupload-' + i + '-artist').val();
                    track_album = $('#manualupload-' + i + '-album').val();
                    track_position = $('#manualupload-' + i + '-position').val();
                }

                $(this).hide().parent().append('<div id="confirminator-' + (track_fileid.replace(/\.mp3/, '')) + '">Saving (this may take a few minutes)...</div>');
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
                            $('#confirminator-' + data.fileid).html('<span class="ui-icon ui-icon-circle-check" style="float:left"></span>Upload Successful');
                        } else {
                            $('#confirminator-' + data.fileid).html('<span class="ui-icon ui-icon-alert" style="float:left"></span>' + data.error);
                        }
                    }
                });
            });

            var container = $('<div></div>');
            if (from_lastfm) {
                container.append('<label for="centralupload-' + i + '">' + file.name + '</label>');
            }
            container.append(track_data)
                .append(submit);
            $('#central-result').append(container);
        }
    });

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
