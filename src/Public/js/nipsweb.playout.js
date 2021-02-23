/* global myradio */

$(document).ready(
  function() {
    myradio.callAPI("GET", "timeslot", "userselectedtimeslot", "", "", "",
      function(timeslot_data) {
        myradio.callAPI("GET", "timeslot", "playout", timeslot_data.payload.timeslot_id, "", "",
          function(playout_data) {
            $("#playout-check").prop("checked", playout_data.payload);
          });
      });
  }
);

$("#playout-check").change(function() {
  myradio.callAPI("GET", "timeslot", "userselectedtimeslot", "", "", "",
    function(timeslot_data) {
      myradio.callAPI("PUT", "timeslot", "playout", timeslot_data.payload.timeslot_id, "", { playout: $("#playout-check").is(":checked") });
    });
});
