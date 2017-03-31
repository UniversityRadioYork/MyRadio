/* global myradio, mConfig */
// Queue up sending ajax requests one at a time
var ajaxQueue = $({});

//Get the GET variables for clientid & nextWeightChanneln .
$.urlParam = function(name){
  var results = new RegExp("[\?&]" + name + "=([^&#]*)").exec(window.location.href);
  return results[1] || 0;
};
var nextWeightChannel0;
var nextWeightChannel1;
var nextWeightChannel2;

$(document).ready(
  function () {

    nextWeightChannel0 = parseInt($.urlParam("channel0lastweight"))+1;
    nextWeightChannel1 = parseInt($.urlParam("channel1lastweight"))+1;
    nextWeightChannel2 = parseInt($.urlParam("channel2lastweight"))+1;

    myradio.callAPI("GET","user","shows", window.myradio.memberid,"","",
      function (data) {
        var show;
        for (show in data) {
          if (show === "myradio_errors") {
            continue;
          }
        }
        for (show in data.payload) {
          $("#import-show-selector").append("<option value='" + data.payload[show].show_id + "'>" + data.payload[show].title + "</option>");
        }
        $("#import-show-selector").prop("disabled", false);
        //if it's going to auto select the only element...
        if (data.payload.length <= 1 ) {
          updateSeasonList();
        }
      }
    );

    $("#import-channel-selector a").on("click","", function() {
      selectChannel($(this).attr("channel"));
    }
    );
  }
);

//reload the page after something good or bad happens
var reload = function() {
  parent.location.reload();
};

var selectAll = function() {  //"select all" change
  $(".channel-list-item").each(function(){ //iterate all listed checkbox items
    this.checked = true; //change checkbox checked status
  });
};
var selectNone = function() {  //"select none" change
  $(".channel-list-item").each(function(){ //iterate all listed checkbox items
    this.checked = false; //change checkbox checked status
  });
};

//popup alert controller.
var showAlert = function (text, type, reload) {
  // Stores fancy message notice icons.
  const ICON_ERROR = "<div class='glyphicon glyphicon-exclamation-sign'></div>&nbsp;";
  const ICON_OK = "<div class='glyphicon glyphicon-ok'></div>&nbsp;";
  const ICON_LOADING = "<div class='glyphicon glyphicon-refresh gly-spin'></div>&nbsp;";
  if (!type) {
    type = "success";
  }
  var icon;
  if (type == "success") {
    icon = ICON_OK;
  } else if (type == "warning"){
    icon = ICON_LOADING;
  } else if (type == "danger") {
    icon = ICON_ERROR;
  }
  if (reload) {
    text = text + " <a onClick=\"reload()\" title=\"Reload the page\">Please reload your show plan.</a>";
  }

  $("#alert").removeClass(function (index, className) {
    return (className.match (/(^|\s)alert-\S+/g) || []).join(" ");
  }).addClass("alert-"+type).html(icon + text);

  parent.showAlert(text, type, reload);
};

function selectChannel(channelNo) {
  $("#import-channel-selector a").removeClass("active");
  $("#import-channel-" + channelNo).addClass("active");
  loadChannelList();
}

function loadChannelList() {
  var selectedTimeslotID = $("#import-timeslot-selector").find(":selected").attr("value");
  myradio.callAPI("GET","timeslot","showplan",selectedTimeslotID,"","",
    function (data) {
      for (item in data) {
        if (item === "myradio_errors") {
          continue;
        }
      }
      $("#import-channel-list").empty();
      var selectedChannelNo = $("#import-channel-selector a.active").attr("channel");
      var item, itemid, cleanStars, extraString;
      for (item in data.payload[selectedChannelNo]) {
        if (data.payload[selectedChannelNo][item].type == "central") {
          if (!data.payload[selectedChannelNo][item].clean) {
            cleanStars = "**";
          } else {
            cleanStars = "";
          }
          extraString = " - " + data.payload[selectedChannelNo][item].artist + " - " + data.payload[selectedChannelNo][item].album.title + " (" + data.payload[selectedChannelNo][item].length + ")";
          itemid = data.payload[selectedChannelNo][item]["album"].recordid + "-" + data.payload[selectedChannelNo][item].trackid;
        } else {
          cleanStars = "";
          extraString = "";
          itemid = "ManagedDB-" + data.payload[selectedChannelNo][item].managedid;
        }
        $("#import-channel-list").append("<input type=\"checkbox\" class=\"channel-list-item\" value=\""+ itemid + "\">" + cleanStars + data.payload[selectedChannelNo][item].title + extraString + "<br>");
        $("#import-channel-filter-btns").fadeIn();
      }
      if (data.payload[selectedChannelNo] == undefined || data.payload[selectedChannelNo].length == 0) {
        $("#import-channel-list").append("<p>No items in this channel.</p>");
        $("#import-channel-filter-btns").fadeOut();
      }
    }
  );
}

$("#import-show-selector").change(function() {
  updateSeasonList();
});

$("#import-season-selector").change(function() {
  updateTimeslotList();
});

$("#import-timeslot-selector").change(function() {
  $("#import-channel-selector").fadeIn();
  $("#import-channel-list").fadeIn();
  loadChannelList();
  $("#import-to-channel-selector").fadeIn();
});

function updateSeasonList() {
  var selectedShowID = $("#import-show-selector").find(":selected").attr("value");
  $("#import-season-selector").prop("disabled", "disabled");
  $("#import-season-selector option:not(:disabled)").remove();
  if (selectedShowID != "null") {
    myradio.callAPI("GET","show","allseasons",selectedShowID,"","",
      function (data) {
        var season;
        for (season in data) {
          if (season === "myradio_errors") {
            continue;
          }
        }
        for (season in data.payload) {
          $("#import-season-selector").append("<option value='" + data.payload[season].season_id + "'>Season " + data.payload[season].season_num + "</option>");
        }
        $("#import-season-selector").prop("disabled", false);
        //if it's going to auto select the only element...
        if (data.payload.length <= 1) {
          updateTimeslotList();
        }
      }
    );
  }
}

function updateTimeslotList() {
  var selectedSeasonID = $("#import-season-selector").find(":selected").attr("value");
  $("#import-timeslot-selector").prop("disabled", "disabled");
  $("#import-timeslot-selector option:not(:disabled)").remove();
  if (selectedSeasonID != "null") {
    myradio.callAPI("GET","season","alltimeslots",selectedSeasonID,"","",
      function (data) {
        var timeslot;
        for (timeslot in data) {
          if (timeslot === "myradio_errors") {
            continue;
          }
        }
        for (timeslot in data.payload) {
          $("#import-timeslot-selector").append("<option value='" + data.payload[timeslot].timeslot_id + "'>Episode " + data.payload[timeslot].timeslot_num + " (" + data.payload[timeslot].start_time + ")</option>");
        }
        $("#import-timeslot-selector").prop("disabled", false);
      }
    );
  }
}

function importSelectedTracks(channelNo) {
  var ops = [];
  if ($("input[type=checkbox]:checked").length > 0) {
    showAlert("Importing Tracks...", "warning");
    $("input[type=checkbox]:checked").each(function () {
      var weight;
      if (channelNo == 0){
        weight = nextWeightChannel0;
        nextWeightChannel0++;
      } else if (channelNo == 1){
        weight = nextWeightChannel1;
        nextWeightChannel1++;
      } else if (channelNo == 2){
        weight = nextWeightChannel2;
        nextWeightChannel2++;
      }
      ops.push({
        op: "AddItem",
        id: $(this).val(), //format: album-track
        channel: parseInt(channelNo),
        weight: weight
      });
    });
    shipChanges(ops);
  } else {
    showAlert("No tracks were selected.", "danger");
  }
}
/**
 * Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
 * ops: JSON changeset to send
 */
var shipChanges = function (ops) {
  ajaxQueue.queue(
    function (next) {
      $.ajax({
        async: false,
        cache: false,
        success: function (data) {
          for (var i in data) {
            if (i === "myradio_errors") {
              continue;
            }
          }
        },
        complete: function () {
          next();
          showAlert("Tracks imported!", "success", true);
        },
        data: {
          ops: ops
        },
        dataType: "json",
        type: "PUT",
        url: myradio.getAPIURL("timeslot", "updateshowplan", window.myradio.timeslotid, "")
      });
    }
  );
};
