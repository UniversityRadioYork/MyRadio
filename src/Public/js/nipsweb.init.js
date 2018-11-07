/* global NIPSWeb, myradio */
/* exported planner */
var planner = null;
$(document).ready(
  function () {
    planner = NIPSWeb(false); //If debug mode (stops reload)
    planner.initialisePlayer("0");
    planner.initialisePlayer("1");
    planner.initialisePlayer("2");
    planner.initialisePlayer("3");
    planner.initialiseUI();
    myradio.showAlert("Welcome to Show Planner!", "success");
  }
);
