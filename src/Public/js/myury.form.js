/**
 * The MyURY Standard Form JavaScript Tools
 * This file initialises jQuery validation, autocompletes and other resources
 * needed for a MyURY Form
 */
$(document).ready(function() {
  /**
   * Initialises the Date pickers where necessary
   */
  $('fieldset.myuryfrm input.date').datepicker({
    dateFormat:"dd/mm/yy"
  });
  /**
   * Initialises the Datetime pickers where necessary
   * @todo Make stepminute customisable?
   */
  $('fieldset.myuryfrm input.datetime').datetimepicker({
    dateFormat:"dd/mm/yy",
    stepMinute: 15
  });
});