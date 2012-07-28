$(document).ready(function() {
  $('.myury-news-alert').dialog({
    //Prevent closing without clicking continue
    closeOnEscape: false,
    open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); },
    //Make the page unusable
    modal: true,
    //Pretty button
    buttons: {
      'Continue': function() {
        alert('TODO: Mark read');
      }
    },
    width: 600
  });
});