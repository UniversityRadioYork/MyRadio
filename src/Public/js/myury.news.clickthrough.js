$(document).ready(function() {
  $('.myury-news-alert').dialog({
    //Prevent closing without clicking continue
    closeOnEscape: false,
    open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog || ui).hide(); },
    buttons: {
      'Continue': function() {
        alert('TODO: Mark read');
      }
    }
  });
});