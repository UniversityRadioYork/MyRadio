/* global moment, myradio */
/**
 * Handles the interactivityness of timeslot selection
 */
$("#shows").on(
  "change",
  function () {
    $("#seasons").empty();
    $("#timeslots").empty();
    $("#signin-list").empty();
    $("#signin-submit").hide();
    var seriesno = 1;
    for (var series in window.showdata[$(this).val()]) {
      $("#seasons").append("<option value=\"" + (seriesno - 1) + "\">Season " + seriesno + "</option>");
      seriesno++;
    }
  }
);
$("#seasons").on(
  "change",
  function () {
    $("#timeslots").empty();
    $("#signin-list").empty();
    $("#signin-submit").hide();
    var season = window.showdata[$("#shows").val()][$(this).val()];
    for (var timeslot in season) {
      var time = moment.unix(season[timeslot][1]);
      $("#timeslots").append("<option value=\"" + season[timeslot][0] + "\">" + time.format("DD/MM/YYYY HH:mm") + "</option>");
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
      var timeslots = window.showdata[$("#shows").val()][$("#seasons").val()];
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
              $("#signin-list").html("Sign in to your show:<br>");
              var used_memberids = [];
              for (var row in data) {
                if (used_memberids.indexOf(data[row].user.memberid) === -1) {
                  var check = $("<input type=\"checkbox\"></input>");
                  var label = $("<label></label>");
                  check.attr("name", "signin[]")
                    .attr("id", "signin_"+data[row].user.memberid)
                    .attr("value", data[row].user.memberid);
                  label.attr("for", "signin_"+data[row].user.memberid)
                    .html(data[row].user.fname + " " + data[row].user.sname);
                  if (data[row].signedby !== null) {
                    check.attr("checked", "checked")
                      .attr("disabled", "true");
                    label.append(" (Signed in by "+data[row].signedby.fname + " "+data[row].signedby.sname + ")");
                  } else if (data[row].user.memberid == window.memberid) {
                    check.attr("checked", "checked");
                  }
                  $("#signin-list").append(check).append(label).append("<br>");
                  used_memberids.push(data[row].user.memberid);
                }
              }
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
    var shows = window.showdata;
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
