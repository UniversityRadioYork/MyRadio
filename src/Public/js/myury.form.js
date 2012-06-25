/**
 * The MyURY Standard Form JavaScript Tools
 * This file initialises jQuery validation, autocompletes and other resources
 * needed for a MyURY Form
 */
$(document).ready(function() {
  /**
   * Initialises the Date pickers where necessary
   */
  $('fieldset.myuryfrm input.myuryfrmfield date').datepicker();
});