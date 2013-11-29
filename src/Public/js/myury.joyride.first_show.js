$('#myury-joyride').joyride({
  autoStart: true,
  expose: true,
  postRideCallback: function(index) {
    if (index === $('#myury-joyride li').length-1) {
      //This was the last element of the page. Unless it's the last page, don't send the kill signal
      if ('{{action}}' !== 'listSessions') {
        return;
      }
    }
    
    $.get(myury.makeURL('MyRadio', 'a-endjoyride'));
  }
});