/* global myradio */
$("#myradio-joyride").joyride({
  autoStart: true,
  expose: true,
  postRideCallback: function (index) {
    if (index === $("#myradio-joyride li").length-1) {
      //This was the last element of the page. Unless it's the last page, don't send the kill signal
      if (window.myradio.action !== "listSessions") {
        return;
      }
    }
    $.get(myradio.makeURL("MyRadio", "a-endjoyride"));
  }
});
