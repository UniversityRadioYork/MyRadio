/*
 * This file is a supplement to the default forms system for the MyRadio Scheduler Shows System
 */
$(document).ready(function() {
  /*
   * Set up a title check - it's not essential that a show title is unique
   * but it would be nice
   */
  $('#sched_show-title').on('input propertychange', function() {
    if ($(this).val().length >= 3)
    {
      var value = $(this).val();
      $.ajax({
        url: myury.makeURL('Scheduler', 'a-findshowbytitle'),
        data: {term: value, limit: 100},
        success: function(data) {
          if (data.length >= 1) {
            var html = '<span class="ui-icon ui-icon-info fleft"></span>Similar to '+data[0].title;
          } else {
            var html = '<span class="ui-icon ui-icon-circle-check fleft"></span>That title is unique!';
          }
          if (data.length >= 2) {
            html = html + ' and '+(data.length-1)+' others';
          }
          $('#sched_show-title-hint').html('<div class="ui-state-highlight">'+html+'</div>');
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
