/* global myradio */
/* exported startImport,selectNone,selectAll,reload */




// The functions in this file are laid out in order of being called.
// Because calling API resources should be asyncronous, most functions are called from the previous, once the result has been received.
// myradio.X() calls are imported separately from the myradio.core.js file in this folder.

var sourceShowPlan;

//////////////////////////////////////
// Stage 1) Opening the importer page.
//////////////////////////////////////

// Work out who is logged in right now.
$(document).ready(
  function () {
    myradio.callAPI("GET", "user", "currentuser", "", "", "",
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

// Get the shows they're credited on, so the user can select one to import previous items from. (First dropdown)
function getUserShows(currentUserID) {
  myradio.callAPI("GET", "user", "shows", currentUserID, "", "",
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
      if (data.payload.length == 1) {
        $("#import-show-selector option").last().prop("selected", true);
        updateSeasonList();
      }
    }
  );
};

//////////////////////////////////////
// Stage 2) Selecting a source Show Plan to import
//////////////////////////////////////

// When the user selects a show, trigger polling that show for seasons.
$("#import-show-selector").change(function () {
  updateSeasonList();
});

// Poll the selected show for it's seasons and populate the second dropdown.
function updateSeasonList() {
  var selectedShowID = $("#import-show-selector").find(":selected").attr("value");
  //remove all the previously shown seasons (if any), without removing the placeholder option.
  $("#import-season-selector option:not(:disabled)").remove();
  myradio.callAPI("GET", "show", "allseasons", selectedShowID, "", "",
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
        $("#import-season-selector option").first().prop("selected", true);
        $("#import-season-selector").prop("disabled", false);
        //If we've only got one season, select that automatically.
        if (data.payload.length == 1) {
          $("#import-season-selector option").last().prop("selected", true);
        }
      } else {
        $("#import-season-selector").append("<option>No seasons in this show.</option>");
        $("#import-season-selector option").last().prop("selected", true);
        $("#import-season-selector").prop("disabled", true);
      }
      updateTimeslotList();
    }
  );
}

// When the user selects a specific season from the second dropdown, load it's timeslots.
$("#import-season-selector").change(function () {
  updateTimeslotList();
});

// Poll for the season's timeslots and populate the third drop down.
function updateTimeslotList() {
  var selectedSeasonID = $("#import-season-selector").find(":selected").attr("value");
  //remove all the previously shown timeslots (if any), without removing the placeholder option.
  $("#import-timeslot-selector option:not(:disabled)").remove();
  if (selectedSeasonID != "null" && typeof selectedSeasonID != "undefined") {
    myradio.callAPI("GET", "season", "alltimeslots", selectedSeasonID, "", "",
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
          $("#import-timeslot-selector option").first().prop("selected", true);
          $("#import-timeslot-selector").prop("disabled", false);
          //If we've only got one timeslot, select that automatically.
          if (data.payload.length == 1) {
            $("#import-timeslot-selector option").last().prop("selected", true);
            loadShowPlan();
            $("#import-showplan").fadeIn();
          }
        } else {
          $("#import-timeslot-selector").append("<option>No episodes in this season.</option>");
          $("#import-timeslot-selector option").last().prop("selected", true);
          $("#import-timeslot-selector").prop("disabled", true);
          $("#import-showplan").fadeOut();
        }
      }
    );
  } else {
    $("#import-timeslot-selector option").first().prop("selected", true);
    $("#import-timeslot-selector").prop("disabled", true);
    $("#import-showplan").fadeOut();
  }
}

// When the user selects the timeslot from the 3rd drop down, trigger loading the showplan for it.
$("#import-timeslot-selector").change(function () {
  loadShowPlan();
  $("#import-showplan").fadeIn();
});

// Poll the API for the show plan and save it globally in sourceShowPlan. Trigger displaying the first channel.
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
      sourceShowPlan = data.payload;
      loadChannelList();
    });
}

// Take the show plan we just got from loadShowPlan() and display the selected channel's items.
function loadChannelList() {
  $("#import-channel-list").empty();
  var selectedChannelNo = $("#import-channel-selector a.active").attr("channel");
  var item, itemid, cleanStars, extraString, expired, disabled;

  if (sourceShowPlan[selectedChannelNo] == undefined || sourceShowPlan[selectedChannelNo].length == 0) {
    $("#import-channel-list").append("<p>No items in this channel.</p>");
    $("#import-channel-filter-btns").fadeOut(); // Hide the select all / none buttons.
  } else {
    // This channel has items. Loop through them
    for (item in sourceShowPlan[selectedChannelNo]) {
      item = sourceShowPlan[selectedChannelNo][item];

      // Central items (music tracks) can be explicit, place the **'s, they also have artists and albums to display.
      if (item.type == "central") {
        cleanStars = item.clean ? "" : "**"
        expired = !(item.digitised);
        extraString = " - " + item.artist + " - " + item.album.title + " (" + item.length + ")";
        itemid = item["album"].recordid + "-" + item.trackid;
      } else {
        cleanStars = "";
        extraString = " ";
        itemid = "ManagedDB-" + item.managedid;
        expired = item.expired;
      }
      // Undigitised items have been removed for one reason or another, so we're gonna mark these disabled.
      disabled = (expired ? "disabled" : "");

      // Mush together the <li> element tick box and add it to the list.
      $("#import-channel-list").append("<li class=\"" + disabled + "\"><input type=\"checkbox\" class=\"channel-list-item\" "
        + disabled + " value=\"" + itemid + "\">"
        + cleanStars + item.title + extraString + "</li>");
    }
    $("#import-channel-filter-btns").fadeIn(); // Fade in the Select All / None buttons.
  }
}

// If the user selects a different channel to import from, trigger populating that channel's items;
$("#import-channel-selector a").on("click", "", function () {
  selectChannel($(this).attr("channel"));
}
);

function selectChannel(channelNo) {
  $("#import-channel-selector a").removeClass("active");
  $("#import-channel-" + channelNo).addClass("active");
  loadChannelList();
}

// Filter the tracks quickly (from the select all/none buttons).
var selectAll = function () {  //"select all" change
  $(".channel-list-item").each(function () { //iterate all listed checkbox items
    this.checked = true; //change checkbox checked status
  });
};
var selectNone = function () {  //"select none" change
  $(".channel-list-item").each(function () { //iterate all listed checkbox items
    this.checked = false; //change checkbox checked status
  });
};

//////////////////////////////////////
// Stage 3) Importing the selected source show plan items
//////////////////////////////////////

// Triggered from the destination channel import buttons.
// Poll the API for the timeslot the user currently has selected (from the navbar)
function startImport(channelNo) {
  myradio.callAPI("GET", "timeslot", "userselectedtimeslot", "", "", "",
    function (data) {
      var item;
      for (item in data) {
        if (item === "myradio_errors") {
          continue;
        }
      }
      getExistingShowPlan(data.payload.timeslot_id, channelNo);
    });
}

// To add items to a show plan, we need to know what index (weights) these new items are going to.
// Get the selected destination show plan, and calculate the new starting weight for each channel based on the last existing item +1.
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
      sourceShowPlan = data.payload;
      for (channelKey in sourceShowPlan) {
        const channel = sourceShowPlan[channelKey];
        console.log(channel);
        if (channel.length > 0) {
          nextChannelWeights.push((channel[channel.length - 1].weight) + 1)
        } else {
          nextChannelWeights.push(0);
        }
      }

      calculateOps(timeslotID, channelNo, nextChannelWeights);
    });
}

// Calculate the list of add item operations we need to do for the items selected.
function calculateOps(timeslotID, channelNo, nextChannelWeights) {

  var ops = [];
  if ($("input[type=checkbox]:checked").length > 0) {
    myradio.showAlert("Importing items...", "warning");
    // For each checked item, give it a consecutive weight,
    $("input[type=checkbox]:checked").each(function () {
      const weight = nextChannelWeights[channelNo];
      nextChannelWeights[weight]++;

      // Add this item's operation to the list.
      ops.push({
        op: "AddItem",
        id: $(this).val(), //format: album-track
        channel: parseInt(channelNo, 10),
        weight: weight
      });
    });

    // Now let's fire off these changes to the server!
    shipChanges(timeslotID, ops);
  } else {
    myradio.showAlert("No items were selected.", "danger");
  }
}

// Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
// ops: JSON changeset to send
var shipChanges = function (timeslotID, ops) {

  // Queue up sending ajax requests one at a time
  var ajaxQueue = $({});

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
          var text = "Items imported! <a onClick=\"reload()\" title=\"Reload the page\">Please reload your show plan.</a>";
          myradio.showAlert(text, "success");
          // Just in case the user clicks the "x" on the import modal, display the import sucess on the parent window (only used for show planner).
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

// Reload the page to show your newly imported item. Clicked from a showAlert.
var reload = function () {
  window.parent.postMessage("reload_showplan")
};
