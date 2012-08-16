/* 
 * This file is a supplement to the default forms system for the MyURY Scheduler Shows System
 */
$(document).ready(function() {
  /*
   * Set up a title check - it's not essential that a show title is unique
   * but it would be nice
   */
  $('#sched_show-title').on('input propertychange', function() {
    if ($(this).val().length >= 3)
    {
      $.ajax({
        url: '?module=Scheduler&action=a-findshowbytitle&term='+$(this).val()+'&limit=100',
        success: function(data) {
          console.log(data);
          console.log(data.length);
          if (data.length >= 1) {
            var html = 'Similar to '+data[0].title;
          } else {
            var html = 'That title is unique!';
          }
          if (data.length >= 2) {
            html = html + ' and '+(data.length-1)+' others';
          }
          $('sched_show-title-hint').html(html)
        }
      });
    }
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