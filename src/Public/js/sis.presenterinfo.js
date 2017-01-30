/* global sis */
/* Presenter Information */
var PresenterInfo = function () {
  var lastTime = 0;
  return {
    activeByDefault: true,
    name: "Presenter Info",
    type: "tab",
    initialise: function () {
      this.innerHTML = "SIS is getting ready...";
      this.registerParam("presenterinfo-lasttime", lastTime);
    },
    update: function (data) {
      lastTime = data.time;
      if (data.info) {
        this.innerHTML = data.info.content;
        $(this).append("<hr>");
        $(this).append("<footer>~ " + data.info.author + ", " + data.info.posted + "</footer>");
      } else {
        this.innerHTML = "There is no presenter information available at this time.";
      }
      this.registerParam("presenterinfo-lasttime", lastTime);
    }
  };
};

sis.registerModule("presenterinfo", new PresenterInfo());
