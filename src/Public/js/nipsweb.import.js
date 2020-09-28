/* global myradio */
/* exported importSelectedTracks,selectNone,selectAll,reload */

// Queue up sending ajax requests one at a time
var ajaxQueue = $({});

var currentShowPlan;

// Stage 1) Load the current user's shows.
$(document).ready(
  function () {
    myradio.callAPI("GET","user","currentuser", "","","",
      function (data) {

        for (item in data) {
          if (item === "myradio_errors") {
            continue;
          }
        }
        getUserShows(data.payload.memberid);
      }
    );
  }
);

function getUserShows(currentUserID) {
  myradio.callAPI("GET","user","shows",currentUserID,"","",
    function (data) {
      var show;
      for (show in data) {
        if (show === "myradio_errors") {
          continue;
        }
      }
      //we don't check for no shows here, since you're already in a show planner show.
      for (show in data.payload) {
        $("#import-show-selector").append("<option value='" + data.payload[show].show_id + "'>" + data.payload[show].title + "</option>");
      }
      $("#import-show-selector").prop("disabled", false);

      //If we've only got one show, select that automatically.
      if (data.payload.length == 1 ) {
        $("#import-show-selector option").last().prop("selected",true);
        updateSeasonList();
      }
    }
  );
};

// Stage 2) Select the show & load its seasons

$("#import-show-selector").change(function() {
  updateSeasonList();
});

function updateSeasonList() {
  var selectedShowID = $("#import-show-selector").find(":selected").attr("value");
  //remove all the previously shown seasons (if any), without removing the placeholder option.
  $("#import-season-selector option:not(:disabled)").remove();
  myradio.callAPI("GET","show","allseasons",selectedShowID,"","",
    function (data) {
      var season;
      for (season in data) {
        if (season === "myradio_errors") {
          continue;
        }
      }
      if (data.payload.length > 0) {
        for (season in data.payload) {
          $("#import-season-selector").append("<option value='" + data.payload[season].season_id + "'>Season " + data.payload[season].season_num + "</option>");
        }
        //apparently chrome doesn't reset back to the first (and only) option sometimes.
        $("#import-season-selector option").first().prop("selected",true);
        $("#import-season-selector").prop("disabled", false);
        //If we've only got one season, select that automatically.
        if (data.payload.length == 1 ) {
          $("#import-season-selector option").last().prop("selected",true);
        }
      } else {
        $("#import-season-selector").append("<option>No seasons in this show.</option>");
        $("#import-season-selector option").last().prop("selected",true);
        $("#import-season-selector").prop("disabled", true);
      }
      updateTimeslotList();
    }
  );
}

// Stage 3) Select this show's season & load its timeslots.
$("#import-season-selector").change(function() {
  updateTimeslotList();
});

function updateTimeslotList() {
  var selectedSeasonID = $("#import-season-selector").find(":selected").attr("value");
  //remove all the previously shown timeslots (if any), without removing the placeholder option.
  $("#import-timeslot-selector option:not(:disabled)").remove();
  if (selectedSeasonID != "null" && typeof selectedSeasonID != "undefined") {
    myradio.callAPI("GET","season","alltimeslots",selectedSeasonID,"","",
      function (data) {
        var timeslot;
        for (timeslot in data) {
          if (timeslot === "myradio_errors") {
            continue;
          }
        }
        if (data.payload.length > 0) {
          for (timeslot in data.payload) {
            $("#import-timeslot-selector").append("<option value='" + data.payload[timeslot].timeslot_id + "'>Episode " + data.payload[timeslot].timeslot_num + " (" + data.payload[timeslot].start_time + ")</option>");
          }
          //apparently chrome doesn't reset back to the first (and only) option sometimes.
          $("#import-timeslot-selector option").first().prop("selected",true);
          $("#import-timeslot-selector").prop("disabled", false);
          //If we've only got one timeslot, select that automatically.
          if (data.payload.length == 1 ) {
            $("#import-timeslot-selector option").last().prop("selected",true);
            loadShowPlan();
            $("#import-showplan").fadeIn();
          }
        } else {
          $("#import-timeslot-selector").append("<option>No episodes in this season.</option>");
          $("#import-timeslot-selector option").last().prop("selected",true);
          $("#import-timeslot-selector").prop("disabled", true);
          $("#import-showplan").fadeOut();
        }
      }
    );
  } else {
    $("#import-timeslot-selector option").first().prop("selected",true);
    $("#import-timeslot-selector").prop("disabled", true);
    $("#import-showplan").fadeOut();
  }
}

// Stage 4) Select the timeslot & load the showplan from it.
$("#import-timeslot-selector").change(function() {
  loadShowPlan();
  $("#import-showplan").fadeIn();
});

function loadShowPlan() {
  var selectedTimeslotID = $("#import-timeslot-selector").find(":selected").attr("value");
  myradio.callAPI("GET", "timeslot", "showplan", selectedTimeslotID, "", "",
    function (data) {
      var item;
      for (item in data) {
        if (item === "myradio_errors") {
          continue;
        }
      }
      currentShowPlan = data.payload;
      loadChannelList();
    });
}

function loadChannelList() {
  $("#import-channel-list").empty();
  var selectedChannelNo = $("#import-channel-selector a.active").attr("channel");
  var item, itemid, cleanStars, extraString, expired, disabled;
  if (currentShowPlan[selectedChannelNo] == undefined || currentShowPlan[selectedChannelNo].length == 0) {
    $("#import-channel-list").append("<p>No items in this channel.</p>");
    $("#import-channel-filter-btns").fadeOut();
  } else {
    for (item in currentShowPlan[selectedChannelNo]) {
      item = currentShowPlan[selectedChannelNo][item];
      if (item.type == "central") {
        if (!item.clean) {
          cleanStars = "**";
        } else {
          cleanStars = "";
        }
        expired = !(item.digitised);
        extraString = " - " + item.artist + " - " + item.album.title + " (" + item.length + ")";
        itemid = item["album"].recordid + "-" + item.trackid;
      } else {
        cleanStars = "";
        extraString = " ";
        itemid = "ManagedDB-" + item.managedid;
        expired = item.expired;
      }
      disabled = (expired ? "disabled": "");
      $("#import-channel-list").append("<li class=\"" + disabled + "\"><input type=\"checkbox\" class=\"channel-list-item\" "
        + disabled + " value=\""+ itemid + "\">"
        + cleanStars + item.title + extraString + "</li>");
      $("#import-channel-filter-btns").fadeIn();
    }
  }
}

// Stage 5) Select a channel to import from.

$("#import-channel-selector a").on("click","", function() {
  selectChannel($(this).attr("channel"));
}
);

function selectChannel(channelNo) {
  $("#import-channel-selector a").removeClass("active");
  $("#import-channel-" + channelNo).addClass("active");
  loadChannelList();
}

// Stage 6) Filter the tracks quickly.

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

function startImport(channelNo) {
  myradio.callAPI("GET", "timeslot", "userselectedtimeslot", "", "", "",
  function (data) {
    var item;
    for (item in data) {
      if (item === "myradio_errors") {
        continue;
      }
    }
    getExistingShowPlan(data.payload.show_id, channelNo);
  });
}

// Stage 7) Get the new weights that new channel items need to be added to
function getExistingShowPlan(timeslotID, channelNo) {

  let nextChannelWeights = [];
  myradio.callAPI("GET", "timeslot", "showplan", timeslotID, "", "",
  function (data) {
    var item;
    for (item in data) {
      if (item === "myradio_errors") {
        continue;
      }
    }
    currentShowPlan = data.payload;
    for (channel in currentShowPlan) {
      if (channel.length > 0) {
        nextChannelWeights.push((channel[-1].weight)+1)
      } else {
        nextChannelWeights.push(0);
      }
    }

    calculateOps(channelNo, nextChannelWeights);
  });
}

// Stage 8) Actually import the selected tracks.

function calculateOps(channelNo, nextChannelWeights) {



  var ops = [];
  if ($("input[type=checkbox]:checked").length > 0) {
    myradio.showAlert("Importing Tracks...", "warning");
    $("input[type=checkbox]:checked").each(function () {
      const weight = newChannelWeights[channelNo];
      newChannelWeights[weight]++;

      ops.push({
        op: "AddItem",
        id: $(this).val(), //format: album-track
        channel: parseInt(channelNo, 10),
        weight: weight
      });
    });
    shipChanges(currentTimeslotID, ops);
  } else {
    myradio.showAlert("No tracks were selected.", "danger");
  }
}
/**
 * Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
 * ops: JSON changeset to send
 */
var shipChanges = function (timeslotID, ops) {


  ajaxQueue.queue(
    function (next) {
      $.ajax({
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
          $("#import-to-channel-selector").fadeOut();
          var text = "Tracks imported! <a onClick=\"reload()\" title=\"Reload the page\">Please reload your show plan.</a>";
          myradio.showAlert(text, "success");
          //Just in case the user clicks the "x" on the import modal.
          parent.myradio.showAlert(text, "success");
        },
        data: {
          ops: ops
        },
        dataType: "json",
        type: "PUT",
        url: myradio.getAPIURL("timeslot", "updateshowplan", timeslotID, "")
      });
    }
  );
};

// Stage 8) reload the page to show your newly imported tracks. Clicked from a showAlert.
var reload = function() {
  parent.location.reload();
};
