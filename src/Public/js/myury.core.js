window.myury = {
  makeURL: function(module, action, params) {
    qstring = (params == null) ? '' : $.param(params);
    if (mConfig.rewrite_url)
      return mConfig.base_url + module + '/' + action + '/' + (qstring == '' ? '' : '?' + qstring);
    else
      return mConfig.base_url + '?module=' + module + '&action=' + action + (qstring == '' ? '' : '&' + qstring);
  }
};

$(document).ajaxError(function(e, xhr, settings, error) {
  console.log(error);
  console.log(e);
  $('<div></div>').attr('title', 'Error').attr('id', 'error-dialog')
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
        $.post(myury.makeURL('MyRadio', 'errorReport'), JSON.stringify({xhr: xhr, settings: settings, error: error}), function() {
          $('#error-dialog').dialog("close");
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
    $('<div></div>').attr('title', 'Notice').attr('id', 'error-dialog')
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
          $.post(myury.makeURL('MyRadio', 'errorReport'), JSON.stringify({xhr: xhr, settings: settings, error: data.myury_errors}), function() {
            $('#error-dialog').dialog("close");
          });
        }
      }
    });
  }
});