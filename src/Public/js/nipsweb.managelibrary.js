/* global myradio, mConfig */
var Library = function () {
  var auxid = null;
  const ALLOWED_MP3 = ["audio/mpeg3", "audio/x-mpeg-3", "audio/mpeg", "audio/x-mpeg",
    "audio/mp3", "audio/x-mp3", "audio/mpg", "audio/mpg3", "audio/mpegaudio"];
  const ALLOWED_ALL = ["audio/mpeg3", "audio/x-mpeg-3", "audio/mpeg", "audio/x-mpeg",
    "audio/mp3", "audio/x-mp3", "audio/mpg", "audio/mpg3", "audio/mpegaudio", "audio/wav", "audio/x-wav",
    "audio/mp4a-latm", "audio/mp4", "audio/aac"];

  const ICON_ERROR = "<div class='glyphicon glyphicon-exclamation-sign'></div>&nbsp;";
  const ICON_OK = "<div class='glyphicon glyphicon-ok'></div>&nbsp;";
  const ICON_LOADING = "<div class='glyphicon glyphicon-refresh gly-spin'></div>&nbsp;";
  const ICON_CLOSE = "<a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>";
  //Converts bytes to human readable numbers
  var byteSize = function (size) {
    if (size > 1048576) {
      return (size / 1048576).toFixed(2) + "MB";
    }
    if (size > 1024) {
      return (size / 1024).toFixed(2) + "KB";
    }
    return size + "B";
  };

  // Handles change events for the library dropdown
  var res_type_sel_change_handler = function () {
    $("div.res-container").hide();
    if ($("#res-type-sel").val() === null) {
      return;
    }
    if ($("#res-type-sel").val() === "central") {
      $("#central-container").show();
      return;
    }
    if ($("#res-type-sel").val().match(/^managed-.*/)) {
      $("#managed-container").show();
      return;
    }
    window.auxid = $("#res-type-sel").val().replace(/^res-/, "");
    $("#res-container").show();
  };

  var filedrop_error_handler = function (err, file) {
    var message;
    switch (err) {
    case "BrowserNotSupported":
      message = "You need to use Google Chrome or Mozilla Firefox 3.6+ to upload files.";
      break;
    case "TooManyFiles":
      message = "Please don't upload too many files at once.";
      break;
    case "FileTooLarge":
      message = "<strong>" + file.name + "</strong> is too big. Please upload files smaller than " + mConfig.audio_upload_max_size + "MB.";
      break;
    case "FileTypeNotAllowed":
      message = "The file you uploaded is not an accepted audio file type.";
      break;
    default:
      message = "An unknown error occured: " + err;
    }

    $(".result-container:visible").append("<div class='alert alert-danger'>" + ICON_ERROR + message + "</div>");
  };

  var centralDbInit = function () {
    /**
     * Central Database Handler
     */
    $("#central-dragdrop").filedrop({
      url: myradio.makeURL("NIPSWeb", "upload_central"),
      paramname: "audio",
      error: filedrop_error_handler,
      allowedfiletypes: ALLOWED_MP3,
      maxfiles: 1,
      maxfilesize: mConfig.audio_upload_max_size,
      queuefiles: 1,
      drop: function () {
        $("#central-status").html(ICON_LOADING + "Reading file (0%)...");
      },
      uploadStarted: function (i, file, total) { // eslint-disable-line no-unused-vars
        $("#central-status").html(ICON_LOADING + "Uploading " + file.name + "... (" + byteSize(file.size) + ")");
      },
      progressUpdated: function (i, file, progress) {
        $("#central-status").html(ICON_LOADING + "Reading " + file.name + " (" + progress + "%)...");
      },
      uploadFinished: function (i, file, response, time) { // eslint-disable-line no-unused-vars
        var status = ICON_OK + "Uploaded " + file.name;
        $("#central-status").html(status);

        setTimeout(function () {
          if ($("#central-status").html() == status) {
            $("#central-status").html(ICON_OK + "Ready");
          }
        }, 5000);

        var submittable_track = false;
        var manual_div = document.getElementById("track-manual-entry");
        if (manual_div !== null) {
          if (response["status"] !== "OK" && response["submittable"]) {
            // If the div exists, then the user has permission to upload a track
            // manually, so display the div and set submittable_track to true.
            $(manual_div).slideDown();
            submittable_track = true;
          } else if (response["status"] !== "OK" && response["submittable"] != true ) {
            $(manual_div).slideUp();
            submittable_track = false;
          }
          //Prevents any previous uploaded but not submitted tracks from being incorrectly submitted.
          $(".current-track span").html(ICON_ERROR);
          $(".current-track").append("File was not submitted. ");
          $(".current-track a").remove();
          $(".current-track").removeClass("alert-info").addClass("alert-danger").removeClass("current-track");
          //Hide any previous form fill errors.
          $(".form-error").slideUp();
          $("#track-manual-entry button").remove();
        }
        var result = $("<div class='alert'></div>");

        if (response["status"] !== "OK") {
          if (response["status"] === "FAIL") {
            //An error occurred
            result.addClass("alert-danger").append("<span class='error'>" + ICON_ERROR + response["message"] + ": </span>");
          } else if (response["status"] === "INFO") {
            //An info message has been sent (A song is now being edited)
            result.addClass("alert-info current-track").append("<span class='error'>" + response["message"] + ": </span>");
          }
        }

        // Track info.
        var track_fileid = "";
        var track_title = "";
        var track_artist = "";
        var track_album = "";
        var track_position = "";
        var track_explicit = false;

        if (submittable_track) {
          if (response.analysis) {
            //Otherwise, if we got an analysis from the ID3 tags, prefill the textboxes.
            var decodeHtml = function (html) {
              //Removes special HTML &xxx; from recieved data
              var txt = document.createElement("textarea");
              txt.innerHTML = html;
              return txt.value;
            };
            document.getElementById("track-manual-entry-title").value = decodeHtml(response.analysis.title);
            document.getElementById("track-manual-entry-artist").value = decodeHtml(response.analysis.artist);
            document.getElementById("track-manual-entry-album").value = decodeHtml(response.analysis.album);
            document.getElementById("track-manual-entry-position").value = decodeHtml(response.analysis.position);
            document.getElementById("track-manual-entry-explicit").checked = response.analysis.explicit;
          } else {
            //If we didn't get an analysis for some reason, just make the textboxes empty.
            document.getElementById("track-manual-entry-title").value = "";
            document.getElementById("track-manual-entry-artist").value = "";
            document.getElementById("track-manual-entry-album").value = "";
            document.getElementById("track-manual-entry-position").value = "";
            document.getElementById("track-manual-entry-explicit").checked = false;
          }
        }

        // The submit part
        var submit = $("<button type=\"button\" class=\"btn btn-primary\">Save Track</button>").click(function () {
          if (submittable_track) {
            track_fileid = response.fileid;
            track_title = document.getElementById("track-manual-entry-title").value;
            track_artist = document.getElementById("track-manual-entry-artist").value;
            track_album = document.getElementById("track-manual-entry-album").value;
            track_position = document.getElementById("track-manual-entry-position").value;
            track_explicit = document.getElementById("track-manual-entry-explicit").checked;

            if (!track_title) {
              $(".form-error").html(ICON_ERROR + "Please enter a title.").slideDown();
              $("#track-manual-entry-title").focus();
              return;
            }
            if (!track_artist) {
              $(".form-error").html(ICON_ERROR + "Please enter an artist.").slideDown();
              $("#track-manual-entry-artist").focus();
              return;
            }
            if (!track_album) {
              $(".form-error").html(ICON_ERROR + "Please enter an album.").slideDown();
              $("#track-manual-entry-album").focus();
              return;
            }
            if (!track_position) {
              $(".form-error").html(ICON_ERROR + "Please enter a track number.").slideDown();
              $("#track-manual-entry-position").focus();
              return;
            }
          }

          result.removeClass("alert-danger")
            .addClass("alert-info")
            .removeClass("current-track")
            .html(ICON_LOADING + "<strong>" + track_title + "</strong> is saving...").slideDown();
          $("#track-manual-entry").slideUp();
          submit.remove();
          $(".form-error").hide();

          $.ajax({
            url: myradio.makeURL("NIPSWeb", "confirm_central_upload"),
            data: {
              title: track_title,
              artist: track_artist,
              album: track_album,
              position: track_position,
              fileid: track_fileid,
              explicit: track_explicit
            },
            dataType: "json",
            type: "get",
            success: function (data) {
              data.fileid = data.fileid.replace(/\.mp3/, "");
              if (data.status == "OK") {
                result.removeClass("alert-info")
                  .addClass("alert-success alert-dismissable")
                  .html(ICON_CLOSE + ICON_OK + "<strong>" + track_title + "</strong> uploaded successfully.");
              } else {
                result.removeClass("alert-info")
                  .addClass("alert-danger alert-dismissable")
                  .html(ICON_CLOSE + ICON_ERROR + "<strong>" + track_title + "</strong> " + data.error);
              }
            }
          });
        });

        result.append("<label for='centralupload-" + i + "'>" + file.name + " &nbsp;</label>");
        if (submittable_track) {
          $("#track-manual-entry form").append(submit);
        }
        $("#central-result").append(result);
      }
    });
  };

  /**
   * Auxillary Database Handler
   **/
  var auxDbInit = function () {
    $("#res-dragdrop").filedrop(
      {
        url: myradio.makeURL("NIPSWeb", "upload_aux"),
        paramname: "audio",
        error: filedrop_error_handler,
        allowedfiletypes: ALLOWED_ALL,
        maxfiles: 20,
        maxfilesize: mConfig.audio_upload_max_size,
        queuefiles: 1,
        drop: function () {
          $("#res-status").html(ICON_LOADING + "Reading file (0%)...");
        },
        uploadStarted: function (i, file, total) { // eslint-disable-line no-unused-vars
          $("#res-status").html(ICON_LOADING + "Uploading " + file.name + "... (" + byteSize(file.size) + ")");
        },
        progressUpdated: function (i, file, progress) {
          $("#res-status").html(ICON_LOADING + "Reading " + file.name + " (" + progress + "%)...");
        },
        uploadFinished: function (i, file, response, time) { // eslint-disable-line no-unused-vars
          $("#res-status").html(ICON_OK + "Uploaded " + file.name);

          var result = $("<div class='alert'></div>");
          if (response["status"] == "FAIL") {
            //An error occurred, probably bitrate is too low.
            result.addClass("alert-danger alert-dismissable").append(ICON_ERROR + ICON_CLOSE + "<strong>" + file.name + ":</strong> <span class='error'>" + response["error"] + "</span>");
            $("#res-result").append(result);
          } else {
            result.addClass("alert-info").append(
              "<div id='resupload-" + i + "' class='row'>" +
              "<label for='resuploadname-" + i + "' class='col-xs-4 control-label'>" +
              file.name +
              ":</label>" +
              "<div class='col-xs-6'>" +
              "<input type='text' class='title form-control' name='" +
              response.fileid + "' id='resuploadname-" + i +
              "' placeholder='Enter a helpful name...' />" +
              "</div>" +
              "</div>"

            );

            if (window.auxid.match(/^aux-\d+$/)) {
              //This is a central one - it can have an expiry
              result.append("<div class=\"row\"><label for=\"resuploaddate-" + i + "\" class=\"col-xs-4 control-label\">Expiry Date: </label><div class=\"col-xs-6 expiry-input\"></div></div>");
              result.find(".expiry-input").html(
                $("<input type=\"text\" placeholder=\"Leave blank to never expire.\" />")
                  .addClass("date form-control")
                  .attr("id", "resuploaddate-" + i)
                  .datetimepicker({pickTime: "false"})
              );
            }
            result.append("<div id=\"confirminator-" + (response.fileid.replace(/\.mp3/, "")) + "\"></div>");
            result.find(".row:first-of-type").append(
              $("<div class=\"col-xs-2\"><button type=\"button\" class=\"btn btn-primary save-button\">Save</button></div>").click(function () {
                var title = result.find("input.title").val();
                var expire = result.find("input.date").val() || null;
                var fileid = result.find("input.title").attr("name");

                if (!title) {
                  $(".form-error").html(ICON_ERROR + "Please enter a title.").slideDown();
                  //Flash the empty input
                  result.find("input.title").prop("disabled", true);
                  setTimeout(
                    function () {
                      result.find("input.title").prop("disabled", false);
                      result.find("input.title").focus();
                    },
                    500
                  );
                  return;
                }

                result.html(ICON_LOADING + "Adding <strong>" + title + "</strong> to library...");
                $.ajax({
                  url: myradio.makeURL("NIPSWeb", "confirm_aux_upload"),
                  data: {
                    auxid: window.auxid,
                    fileid: fileid,
                    title: title,
                    expires: expire
                  },
                  dataType: "json",
                  type: "get",
                  success: function (data) {
                    data.fileid = data.fileid.replace(/\.mp3/, "");
                    if (data.status == "OK") {
                      result.removeClass("alert-info")
                        .addClass("alert-success alert-dismissable")
                        .html(ICON_OK + ICON_CLOSE + "<strong>" + title + ":</strong> was added to library.");
                    } else {
                      result.removeClass("alert-info")
                        .addClass("alert-danger alert-dismissable")
                        .html(
                          ICON_ERROR + ICON_CLOSE + "<strong>" + title + ":</strong> could not be added to library.<br>Error: "
                          + data.error
                        );
                    }
                  }
                });
              })
            );
          }
          $("#res-result").append(result);
        }
      }
    );
  };

  var initialise = function () {
    $("#res-type-sel").on("change", res_type_sel_change_handler);
    $("#res-type-sel").on("click", res_type_sel_change_handler);
    $("#res-type-sel").on("keyup", res_type_sel_change_handler);
    centralDbInit();
    auxDbInit();
    $("#central-status, #res-status").html("<div class='glyphicon glyphicon-ok'></div>&nbsp;Ready");
  };

  return {
    auxid: auxid,
    initialise: initialise
  };
};

$(document).ready(
  function () {
    var library = Library();
    library.initialise();
  }
);
