/**
 * Sends a notice to the webcam logger every 15 seconds that this user is still watching
 */

$(document).ready(function() {
  setInterval('webcam.trackViewer()', 15000);
});

var webcam = {
  trackViewer: function() {
    $.ajax({
      type: 'get',
      cache: false,
      url: 'index.php?module=Webcam&action=a-trackViewer',
      success: function(data) {
        var sub = 0;
        var time = '';
        if (data >= 24*60*60) {
          sub = Math.floor(data/24*60*60);
          time = time + sub +' days, '
          data -= sub;
        }
        if (data >= 3600) {
          sub = Math.floor(data/3600);
          time = time + sub + ' hours, ';
          data -= sub;
        }
        if (data >= 60) {
          sub = Math.floor(data/60);
          time = time + sub + ' minutes, ';
          data -= sub;
        }
        time = time + data + ' seconds';
        $('#webcam-time-counter-value').html(time);
      }
    });
  }
};