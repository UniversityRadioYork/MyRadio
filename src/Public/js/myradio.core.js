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
    if (successFunc == undefined) {
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
  }
};

var errorVisible = false;
$(document).ajaxError(
  function (e, xhr, settings, error) {
    if (xhr.status == 401) {
      //Session timed out - need to login
      window.location = myradio.makeURL("MyRadio", "login", {next: window.location.pathname, message: window.btoa("Your session has expired and you need to log in again to continue.")});
    } else if (!errorVisible) {
      var close = myradio.closeButton();
      var report = myradio.reportButton(xhr, settings, error);
      var message = "";

      if (xhr.responseJSON && xhr.responseJSON.error) {
        message = xhr.responseJSON.error;
      } else if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      }

      var errorVisibleReset = function () {
        errorVisible = false;
      };

      close.addEventListener("click", errorVisibleReset);
      report.addEventListener("click", errorVisibleReset);

      myradio.createDialog(
        "Error",
        "<p>Sorry, just went a bit wrong and I'm not sure what to do about it.</p><details>" + error + "<br>" + message + "</details>",
        [close, report]
      );
      errorVisible = true;
    }
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
    if (data.hasOwnProperty("myradio_errors") && data.myradio_errors.length > 0) {
      myradio.errorReport(data.myradio_errors, e, xhr, settings);
    }
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
