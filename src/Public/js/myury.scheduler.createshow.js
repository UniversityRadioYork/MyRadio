/* 
 * This file is a supplement to the default forms system for the MyURY Scheduler Shows System
 */
$(document).ready(function() {
  /*
   * Set up a title check - it's not essential that a show title is unique
   * but it would be nice
   */
  $('#sched_show-title').on('input propertychange', function() {
    $.ajax({
      url: '?module=Scheduler&action=a-findshowbytitle&term='+$(this).val(),
      success: function(data) {
        
      }
    });
  });
  /**
   * Hide the repeating add link for the credits input field
   */
  $('#sched_show-credits-repeater').hide();
  /**
   * Tell the credittype add link to trigger the credits add action
   */
  $('#sched_show-credittypes-repeater').on('click', function() {
    $('#sched_show-credits-repeater').trigger('click');
  });
});