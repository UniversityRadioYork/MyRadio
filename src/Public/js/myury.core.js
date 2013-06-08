window.myury = {
  makeURL: function(module, action) {
    if (mConfig.rewrite_url)
      return mConfig.base_url + module + '/' + action + '/';
    else
      return mConfig.base_url + '?module=' + module + '&action=' + action;
  }
};

$(document).ajaxError(function(e, xhr, settings, error) {
  console.log(error);
  console.log(e);
  $('<div></div>').attr('title', 'Error')
          .append('<p>Sorry, something just went a bit wrong.</p>')
          .append('<details>' + error + '</details>')
          .dialog({
    modal: true,
    buttons: {
      Ok: function() {
        $(this).dialog("close");
      },
      Report: function() {
        $(".ui-dialog-buttonpane button:contains('Report') span").text("Reporting...").addClass('ui-state-disabled');
        $.post(myury.makeURL('MyURY', 'errorReport'), [xhr, settings, error], function() {
          $(this).dialog("close");
        });
      }
    }
  });
});

$(document).ajaxSuccess(function(e, xhr, settings) {
  try {
    var data = $.parseJSON(xhr);
  } catch (e) {
    return; //Not JSON
  }
  if (data.myury_errors != null) {
    console.log(data.myury_errors);
    $('<div></div>').attr('title', 'Notice')
            .append('<p>It looks like that request works, but I got an error in the response.</p>')
            .append('<details>' + data.myury_errors + '</details>')
            .dialog({
      modal: true,
      buttons: {
        Ok: function() {
          $(this).dialog("close");
        },
        Report: function() {
          $(".ui-dialog-buttonpane button:contains('Report') span").text("Reporting...").addClass('ui-state-disabled');
          $.post(myury.makeURL('MyURY', 'errorReport'), [xhr, settings, data.myury_errors], function() {
            $(this).dialog("close");
          });
        }
      }
    });
  }
});