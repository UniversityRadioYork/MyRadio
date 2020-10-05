/* global NIPSWeb, myradio */
/* exported planner */
var planner = null;
$(document).ready(
  function () {
    planner = NIPSWeb(false); //If debug mode (stops reload)
    planner.initialiseUI();
    planner.initialisePlayer("0");
    planner.initialisePlayer("1");
    planner.initialisePlayer("2");
    planner.initialisePlayer("3");
    myradio.showAlert("Welcome to Show Planner!", "success");

    window.addEventListener("message", (event) => {
      if (!event.origin.includes("ury.org.uk")) {
        return;
      }
      if (event.data == "reload_showplan") {
        window.location.reload();
      };
    }, false);
  }
);
