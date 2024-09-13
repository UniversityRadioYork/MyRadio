//combines the first,nickname,last name of the user together
function formatName(data) {
  if (empty(data[row].user.nname) != false) {
    return data[row].user.fname + ' "' + data[row].user.nname  + '" ' + data[row].user.sname;
  }
  else {
    return data[row].user.fname + " " + data[row].user.sname;
  }
}


/* global moment, myradio */
/**
 * Handles the interactivityness of timeslot selection
 */
// phpcs:disable
$("#shows").on(
  "change",
  function () {
    $("#seasons").empty();
    $("#timeslots").empty();
    $("#signin-list").empty();
    $("#signin-submit").hide();
    for (var seriesno = 0; seriesno < window.myradio.showdata[$(this).val()].length; seriesno++) {
      $("#seasons").append("<option value='" + seriesno + "'>Season " + (seriesno + 1) + "</option>");
    }
  }
);
$("#seasons").on(
  "change",
  function () {
    $("#timeslots").empty();
    $("#signin-list").empty();
    $("#signin-submit").hide();
    var season = window.myradio.showdata[$("#shows").val()][$(this).val()];
    for (var timeslot in season) {
      var time = moment.unix(season[timeslot][1]);
      $("#timeslots").append("<option value='" + season[timeslot][0] + "'>" + time.format("DD/MM/YYYY HH:mm") + "</option>");
    }
  }
);

$("#timeslots").on(
  "change",
  function () {
    if ($(this).val() !== null) {
      $("#signin-list").empty();
      $("#signin-submit").show();
      //Okay, now if the show is <> 2hours, let them sign in
      var timeslots = window.myradio.showdata[$("#shows").val()][$("#seasons").val()];
      var start;
      var end;
      for (var id in timeslots) {
        if (timeslots[id][0] == $(this).val()) {
          start = moment.unix(timeslots[id][1]);
          end = moment.unix(timeslots[id][2]);
          break;
        }
      }

      if (start) {
        var lowerThreshold = moment().subtract(2, "hours");
        var upperThreshold = moment().add(2, "hours");
        if (start.isBetween(lowerThreshold, upperThreshold) || end.isBetween(lowerThreshold, upperThreshold)) {
          $("#signin-list").show().html("Loading...");
          $.ajax({
            url: myradio.makeURL("MyRadio", "a-timeslotSignin"),
            data: {timeslotid: $(this).val()},
            success: function (data) {
              $("#signin-list")
                .html("Sign in to your show:<br>" +
                  "<div id='member-signins'></div><div id='guest-signins'></div><br>");
              var used_memberids = [];
              for (var row in data) {
                if ("user" in data[row]) {
                  // member
                  if (used_memberids.indexOf(data[row].user.memberid) === -1) {
                    var check = $("<input type=\"checkbox\"></input>");
                    var label = $("<label></label>");
                    check.attr("name", "signin[]")
                      .attr("id", "signin_"+data[row].user.memberid)
                      .attr("value", data[row].user.memberid);
                    label.attr("for", "signin_"+data[row].user.memberid)
                      .html(formatName(data));
                    if (data[row].signedby !== null) {
                      check.attr("checked", "checked")
                        .attr("disabled", "true");
                      if (empty(data[row].signedby.nname) != false ) {
                        label.append(" (Signed in by "+data[row].signedby.fname + ' "' + data[row].signedby.nname + '" ' + data[row].signedby.sname + ")");  
                      }
                      else {
                        label.append(" (Signed in by "+data[row].signedby.fname + " " + data[row].signedby.sname + ")");
                      }
                      
                    } else if (data[row].user.memberid == window.myradio.memberid) {
                      check.attr("checked", "checked");
                    }
                    $("#member-signins").append(check).append(label).append("<br>");
                    used_memberids.push(data[row].user.memberid);
                  }
                } else {
                  // guest
                  if ($("#guest-signins").is(":empty")) {
                    $("#guest-signins").append("Guest data has been added by:<br>");
                  }
                  if (empty(data[row].signedby.nname) != false) {
                    $("#guest-signins").append(
                      $("<span>")
                        .text(data[row].signedby.fname + ' "'  + data[row].signedby.nname + '" ' + data[row].signedby.sname
                        + " (" + moment.unix(data[row].time).fromNow() + ")")
                        .append("<br>")
                    );
                  }
                  else {
                    $("#guest-signins").append(
                      $("<span>")
                        .text(data[row].signedby.fname + " " + data[row].signedby.sname
                        + " (" + moment.unix(data[row].time).fromNow() + ")")
                        .append("<br>")
                    );
                  }
                }
              }
              $("#signin-list").append(
                $("<button>")
                  .addClass("btn btn-default")
                  .attr("id", "registerGuestsBtn")
                  .text("Register Guests (for studio shows only)")
                  .click(function() {
                    var wrapper = $("<div>");
                    wrapper.append(
                      $("<p>Please enter the names of all guests on your show, one on each line. " +
                        "If any are not students, please also enter their phone numbers.</p>")
                    );
                    wrapper.append(
                      $("<textarea rows='8' cols='50'>")
                        .attr("name", "guest_info")
                    );
                    $(this)
                      .replaceWith(wrapper);
                  })
              )
                .append("<br>")
                .append(
                  $("<label>")
                    .text("Show Location")
                );
              var locSel = $("<select name='location'>");
              var selectOption = $("<option selected='true' value='unselected' disabled>Please Select</option>");
              locSel.append(selectOption);
              for (var loc of window.myradio.locations) {
                locSel.append(
                  $("<option>")
                    .attr("value", loc.value)
                    .text(loc.text)
                );
              }
              $("#signin-list")
                .append(locSel);
            }
          });
        } else {
          $("#signin").hide();
        }
      }
    } else {
      $("#signin,#signin-submit").hide();
    }
  }
);


$(document).ready(
  function () {
    //Now we're going to select the closest timeslot
    var closest = [null, null, null, null];
    var seconds = (new Date()).getTime() / 1000;
    var shows = window.myradio.showdata;
    for (var show in shows) {
      for (var season in shows[show]) {
        for (var timeslot in shows[show][season]) {
          var drift = Math.abs(shows[show][season][timeslot][1] - seconds);
          if (closest[0] === null || drift < closest[0]) {
            closest[0] = drift;
            closest[1] = show;
            closest[2] = season;
            closest[3] = shows[show][season][timeslot][0];
          }
        }
      }
    }
    if (closest[0] !== null) {
      $("#shows").val(closest[1]).trigger("change");
      $("#seasons").val(closest[2]).trigger("change");
      $("#timeslots").val(closest[3]).trigger("change");
    }
  }
);
