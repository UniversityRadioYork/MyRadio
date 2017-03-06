/* global NIPSWeb */
/* exported planner */
var planner = null;
$(document).ready(
  function () {
    planner = NIPSWeb(false);
    planner.initialiseUI();
    planner.initialisePlayer("0");
    planner.initialisePlayer("1");
    planner.initialisePlayer("2");
    planner.initialisePlayer("3");
    showAlert("Welcome to Show Planner!", 'success');
  }
);
