/* global myradio, mConfig */
// Queue up sending ajax requests one at a time
var ajaxQueue = $({});

//Get the GET variables for clientid & nextWeightChanneln .
$.urlParam = function(name){
  var results = new RegExp("[\?&]" + name + "=([^&#]*)").exec(window.location.href);
  return results[1] || 0;
};
var clientid;
var nextWeightChannel0;
var nextWeightChannel1;
var nextWeightChannel2;

$(document).ready(
  function () {

    clientid = parseInt($.urlParam("clientid"));
    nextWeightChannel0 = parseInt($.urlParam("channel0lastweight"))+1;
    nextWeightChannel1 = parseInt($.urlParam("channel1lastweight"))+1;
    nextWeightChannel2 = parseInt($.urlParam("channel2lastweight"))+1;

    $.ajax({
      url: mConfig.api_url + "/v2/user/" + window.memberid + "/shows/",
      type: "get",
      success: function (data) {
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
    });

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
    this.checked = status; //change ".checkbox" checked status
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
  $.ajax({
    url: mConfig.api_url + "/v2/timeslot/" + selectedTimeslotID + "/showplan",
    type: "get",
    success: function (data) {
      for (item in data) {
        if (item === "myradio_errors") {
          continue;
        }
      }
      $("#import-channel-list").empty();
      var selectedChannelNo = $("#import-channel-selector a.active").attr("channel");
      var item, itemid, extraString;
      for (item in data.payload[selectedChannelNo]) {
        if (data.payload[selectedChannelNo][item].type == "central") {
          extraString = " - " + data.payload[selectedChannelNo][item].artist;
          itemid = data.payload[selectedChannelNo][item]["album"].recordid + "-" + data.payload[selectedChannelNo][item].trackid;
        } else {
          extraString = "";
          itemid = "ManagedDB-" + data.payload[selectedChannelNo][item].managedid;
        }
        $("#import-channel-list").append("<input type=\"checkbox\" class=\"channel-list-item\" value=\""+ itemid + "\">" + data.payload[selectedChannelNo][item].title + extraString + "<br>");
      }
      if (data.payload[selectedChannelNo] == undefined || data.payload[selectedChannelNo].length == 0) {
        $("#import-channel-list").append("<li>No items in this channel.</li>");
      }
    }
  });
}

$("#import-show-selector").change(function() {
  updateSeasonList();
});

$("#import-season-selector").change(function() {
  updateTimeslotList();
});

$("#import-timeslot-selector").change(function() {
  $("#import-channel-selector").removeClass("hidden");
  $("#import-channel-list").removeClass("hidden");
  loadChannelList();
  $("#import-to-channel-selector").removeClass("hidden");
});

function updateSeasonList() {
  var selectedShowID = $("#import-show-selector").find(":selected").attr("value");
  $("#import-season-selector").prop("disabled", "disabled");
  $("#import-season-selector option:not(:disabled)").remove();
  if (selectedShowID != "null") {
    $.ajax({
      url: mConfig.api_url + "/v2/show/" + selectedShowID + "/allseasons",
      type: "get",
      success: function (data) {
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
    });
  }
}

function updateTimeslotList() {
  var selectedSeasonID = $("#import-season-selector").find(":selected").attr("value");
  $("#import-timeslot-selector").prop("disabled", "disabled");
  $("#import-timeslot-selector option:not(:disabled)").remove();
  if (selectedSeasonID != "null") {
    $.ajax({
      url: mConfig.api_url + "/v2/season/" + selectedSeasonID + "/alltimeslots",
      type: "get",
      success: function (data) {
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
    });
  }
}

function importSelectedTracks(channelNo) {
  var ops = [];
  $("input[type=checkbox]").each(function () {
    if (this.checked) {
      var weight;
      if (channelNo == 0){
        weight = nextWeightChannel0;
        nextWeightChannel0++;
      } else if (channelNo == 1){
        weight = nextWeightChannel1;
        alert(weight);
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
    }
  });
  shipChanges(ops, clientid);
}
/**
 * Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
 * ops: JSON changeset to send
 * addOp: If true, there has been an add operation. We currently make these syncronous.
 * pNext: Optional. Parent queue to process on completion.
 */
var shipChanges = function (ops, clientid) {
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
          clientid: clientid,
          ops: ops
        },
        dataType: "json",
        type: "POST",
        url: myradio.makeURL("NIPSWeb", "recv_ops")
      });
    }
  );
};
