/* 
 * This file is a supplement to the default forms system for the MyURY Scheduler Shows System
 */
$(document).ready(function() {
  /**
   * Hide the repeating add link day/start time
   */
  $('#sched_season-day-repeater, #sched_season-stime-repeater').hide();
  /**
   * Tell the credittype add link to trigger the credits add action
   */
  $('#sched_season-etime-repeater').on('click', function() {
    $('#sched_season-day-repeater, #sched_season-stime-repeater').trigger('click');
  });
});