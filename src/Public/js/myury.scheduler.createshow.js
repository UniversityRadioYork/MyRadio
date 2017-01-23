/* global myradio */
/*
 * This file is a supplement to the default forms system for the MyRadio Scheduler Shows System
 */
$(document).ready(
  function () {
    /*
     * Set up a title check - it's not essential that a show title is unique
     * but it would be nice
     */
    $("#sched_show-title").on(
      "input propertychange",
      function () {
        if ($(this).val().length >= 3) {
          var value = $(this).val();
          $.ajax({
            url: myradio.makeURL("Scheduler", "a-findshowbytitle"),
            data: {term: value, limit: 100},
            success: function (data) {
              var html = "";
              if (data.length >= 1) {
                html = "<span class=\"glyphicon glyphicon-info-sign fleft\"></span>Similar to "+data[0].title;
              } else {
                html = "<span class=\"glyphicon glyphicon-ok-sign fleft\"></span>That title is unique!";
              }
              if (data.length >= 2) {
                html = html + " and "+(data.length-1)+" others";
              }
              $("#sched_show-title-hint").html("<div class=\"alert alert-info\">"+html+"</div>");
            }
          });
        }
      }
    );
    /**
     * Hide the repeating add link for the credits input field
     */
    $("#sched_show-credits-repeater").hide();
    /**
     * Tell the credittype add link to trigger the credits add action
     */
    $("#sched_show-credittypes-repeater").on(
      "click",
      function () {
        $("#sched_show-credits-repeater").trigger("click");
      }
    );
  }
);
