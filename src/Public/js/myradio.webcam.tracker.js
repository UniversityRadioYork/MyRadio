/* global myradio */

/**
 * Sends a notice to the webcam logger every 15 seconds that this user is still watching
 */
function webcamTrackViewer() {
  $.ajax({
    type: "get",
    cache: false,
    url: myradio.makeURL("Webcam", "a-trackViewer"),
    statusCode: {
      // API returns 400 when timedelta is too big (minimised tab or whatever) clientside should ignore.
      400: function () {}
    },
    success: function (data) {
      var sub = 0;
      var time = "";
      if (data >= 7*24*60*60) {
        sub = Math.floor(data/(7*24*24*60));
        time = time + sub + " weeks, ";
        data -= sub*7*24*24*60;
      }
      if (data >= 24*60*60) {
        sub = Math.floor(data/(24*60*60));
        time = time + sub +" days, ";
        data -= sub*24*60*60;
      }
      if (data >= 3600) {
        sub = Math.floor(data/3600);
        time = time + sub + " hours, ";
        data -= sub*3600;
      }
      if (data >= 60) {
        sub = Math.floor(data/60);
        time = time + sub + " minutes, ";
        data -= sub*60;
      }
      time = time + data + " seconds";
      $("#webcam-time-counter-value").html(time);
    }
  });
}

$(document).ready(
  function () {
    setInterval(webcamTrackViewer, 15000);
  }
);
