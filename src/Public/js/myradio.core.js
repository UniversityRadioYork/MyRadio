/* global mConfig */
var myradio = {
  makeURL: function (module, action, params) {
    var qstring = (params === undefined) ? "" : $.param(params);
    if (mConfig.rewrite_url) {
      return mConfig.base_url + module + "/" + action + "/" + (qstring === "" ? "" : "?" + qstring);
    } else {
      return mConfig.base_url + "?module=" + module + "&action=" + action + (qstring === "" ? "" : "&" + qstring);
    }
  },
  errorReport: function (myradio_errors, e, xhr, settings) {
    console.log(myradio_errors);
    myradio.createDialog(
      "Error",
      "<p>It looks like that request worked, but things might not quite work as expected.</p><details>" + myradio_errors + "</details>",
      [myradio.closeButton(), myradio.reportButton(xhr, settings, myradio_errors)]
    );
  },
  createDialog: function (title, text, buttons, startHidden) {
    if (!buttons) {
      buttons = [];
    }
    var modal = $("<div class=\"modal fade\" tabindex=\"-1\" role=\"dialog\" aria-hidden=\"true\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\"><span aria-hidden=\"true\">&times;</span><span class=\"sr-only\">Close</span></button><h4 class=\"modal-title\">" + title + "</h4></div><div class=\"modal-body\"></div><div class=\"modal-footer\"></div></div></div></div>");
    modal.find(".modal-body").append(text);
    modal.find(".modal-footer").append(buttons);
    modal.appendTo("body");
    if (!startHidden) {
      modal.modal();
    }
    return modal;
  },
  closeButton: function () {
    var closeButton = document.createElement("button");
    closeButton.className = "btn btn-link";
    closeButton.innerHTML = "Close";
    closeButton.setAttribute("data-dismiss", "modal");
    return closeButton;
  },
  reportButton: function (xhr, settings, error) {
    var reportButton = document.createElement("button");
    reportButton.className = "btn btn-primary";
    reportButton.innerHTML = "Report";
    reportButton.setAttribute("data-dismiss", "modal");
    reportButton.addEventListener(
      "click",
      function () {
        $.post(myradio.makeURL("MyRadio", "errorReport"), JSON.stringify({xhr: xhr, settings: settings, error: error}));
      }
    );
    return reportButton;
  },
  callAPI: function (method, module, action, id, firstParam, options, successFunc) {
    if (typeof successFunc === "undefined") {
      successFunc = function(){};
    }
    $.ajax({
      url: myradio.getAPIURL(module, action, id, firstParam),
      data: options,
      method: method,
      success: successFunc
    });
  },
  getAPIURL: function (module, action, id, firstParam) {
    var url = mConfig.api_url;
    url += "/v2/" + module;
    if (id !== "") {
      url += "/" + id;
    }
    url += "/" + action;
    if (firstParam !== "") {
      url += "/" + firstParam;
    }
    return url;
  },
  //Global popup alert controller for new alert popups.
  showAlert: function (text, type) {
    // Stores fancy message notice icons.
    const ICON_ERROR = "<div class='glyphicon glyphicon-exclamation-sign'></div>&nbsp;";
    const ICON_OK = "<div class='glyphicon glyphicon-ok'></div>&nbsp;";
    const ICON_LOADING = "<div class='glyphicon glyphicon-refresh gly-spin'></div>&nbsp;";
    if (!type) {
      type = "success";
    }
    var icon;
    if (type == "success") {
      icon = ICON_OK;
    } else if (type == "loading"){
      icon = ICON_LOADING;
      type = "warning"; //override so still orange alert :)
    } else if (type == "danger" || type == "warning") {
      icon = ICON_ERROR;
    }

    $("#showAlert").removeClass(function (index, className) {
      return (className.match (/(^|\s)alert-\S+/g) || []).join(" ");
    }).addClass("alert-"+type).html(icon + text);
  },
  // Set to ingore a error status code for the next API call.
  ignoreErrorStatus: function(statusCode) {
    if (window.ignoreErrorStatuses && window.ignoreErrorStatuses.length > 0) {
      window.ignoreErrorStatuses.push(statusCode);
    } else {
      window.ignoreErrorStatuses = [statusCode];
    }
  }

};



var errorVisible = false;
$(document).ajaxError(
  function (e, xhr, settings, error) {
    if (xhr.status == 401) {
      //Session timed out - need to login
      window.location = myradio.makeURL("MyRadio", "login", {next: window.location.pathname, message: window.btoa("Your session has expired and you need to log in again to continue.")});
    } else if (window.ignoreErrorStatuses && window.ignoreErrorStatuses.length > 0 && window.ignoreErrorStatuses.indexOf(xhr.status) >= 0) {
      //This API call return value was expected. We should ignore it this time.

    } else if (!errorVisible) {

      // We weren't expecting this error, make a popup.
      var close = myradio.closeButton();
      var report = myradio.reportButton(xhr, settings, error);
      var message = "";

      const response = xhr.responseJSON;
      if (response) {
        if (response.error) {
          message = response.error;
        } else if (response.message) {
          message = response.message;
        } else if (response.myradio_errors) {
          message = response.myradio_errors;
        } else if (response.status == "FAIL") {
          message = "FAIL: " + response.payload;
        }
      }

      var errorVisibleReset = function () {
        errorVisible = false;
      };

      close.addEventListener("click", errorVisibleReset);
      report.addEventListener("click", errorVisibleReset);

      myradio.createDialog(
        "API Error",
        `<p>Sorry, something just went a bit wrong. Please report this issue if this is your first time seeing this message! Why not try again if you haven't done so already, too.</p>
        <details>
          <strong>Endpoint:</strong> `+ settings.url +`<br>
          <strong>Status Code:</strong> `+ xhr.status + `<br>
          <strong>Response:</strong> ` + error + `<br>
          ` + message + `
        </details>`,
        [close, report]
      );
      errorVisible = true;
    }
    window.ignoreErrorStatuses = null;
  }
);

$(document).ajaxSuccess(
  function (e, xhr, settings) {
    var data;
    if (xhr === null) {
      return;
    }
    try {
      data = $.parseJSON(xhr);
    } catch (error) {
      return; //Not JSON
    }

    if (Object.prototype.hasOwnProperty.call(data, "myradio_errors") && data.myradio_errors.length > 0) {
      myradio.errorReport(data.myradio_errors, e, xhr, settings);
    }

    window.ignoreErrorStatuses = null;
  }
);

/**
 * Use bootstrap show/hide helpers
 */
jQuery.fn.show = function () {
  $(this).removeClass("hidden")
    .css("visibility", "visible");
  for (var i = 0; i < this.length; i++) {
    if (this[i].style.display === "none") {
      $(this[i]).css("display", "");
    }
    if (window.getComputedStyle(this[i]).display === "none") {
      $(this[i]).css("display", "block");
    }
  }
  return this;
};

jQuery.fn.hide = function () {
  $(this).addClass("hidden");
  return this;
};
