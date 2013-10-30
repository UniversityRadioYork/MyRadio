/* 
 * This file is a supplement to the default forms system for the MyRadio Scheduler Shows System
 */
$(document).ready(function() {
  /**
   * Hide the repeating add link day/start time
   */
  $('#sched_season-day-repeater, #sched_season-stime-repeater').hide();
  /**
   * This is all horrible.
   */
  $('#sched_season-etime-repeater').on('click', function(e) {
    $('#sched_season-day-repeater').trigger('click');
    //$('#sched_season-stime-repeater').parent().parent().find('label:first').clone().insertBefore($('#sched_season-stime-repeater').parent());
    $('#sched_season-stime-repeater').trigger('click');
    //$('#sched_season-etime-repeater').parent().parent().find('label:first').clone().insertBefore($('#sched_season-etime-repeater').parent().parent().children('input[type="text"]:last'));
  });
});