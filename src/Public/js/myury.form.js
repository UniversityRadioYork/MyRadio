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
  /**
   * Initialises the Member autocomplete pickers where necessary
   */
  $('fieldset.myuryfrm input.member-autocomplete').each(function() {
    $(this).autocomplete({
      minLength: 3,
      source: "index.php?module=Core&action=a-findmember",
      select: function(event, ui) {
        $(this).val(ui.item.fname + ' ' + ui.item.sname);
        $('#'+$(this).attr('id').replace(/-ui$/, '')).val(ui.item.memberid);
        return false;
      },
      //Prevent the field blanking out when an item is given focus
      focus: function (event, ui) {
        return false;
      }
    })
    .data("autocomplete")._renderItem = function(ul, item) {
      return $('<li></li>').data('item.autocomplete', item)
      .append('<a>' + item.fname + ' ' + item.sname + '</a>')
      .appendTo(ul);
    };
  });
  /**
  * Initialises the Track autocomplete pickers where necessary
  */
  $('fieldset.myuryfrm input.track-autocomplete').each(function() {
    $(this).autocomplete({
      minLength: 3,
      source: "index.php?module=Core&action=a-findtrack",
      select: function(event, ui) {
        $(this).val(ui.item.title);
        $('#'+$(this).attr('id').replace(/-ui$/, '')).val(ui.item.trackid);
        return false;
      },
      //Prevent the field blanking out when an item is given focus
      focus: function (event, ui) {
        return false;
      }
    })
    .data("autocomplete")._renderItem = function(ul, item) {
      return $('<li></li>').data('item.autocomplete', item)
      .append('<a>' + item.title + '<br><span style="font-size:.8em">' + item.artist + '</span></a>')
      .appendTo(ul);
    };
  });
  /**
   * Initialises the Artist autocomplete pickers where necessary
   */
  $('fieldset.myuryfrm input.artist-autocomplete').each(function() {
    $(this).autocomplete({
      minLength: 3,
      source: "index.php?module=Core&action=a-findartist",
      select: function(event, ui) {
        $(this).val(ui.item.title);
        $('#'+$(this).attr('id').replace(/-ui$/, '')).val(ui.item.artistid);
        return false;
      },
      //Prevent the field blanking out when an item is given focus
      focus: function (event, ui) {
        return false;
      }
    })
    .data("autocomplete")._renderItem = function(ul, item) {
      return $('<li></li>').data('item.autocomplete', item)
      .append('<a>' + item.title + '</a>')
      .appendTo(ul);
    };
  });
  
  /**
   * Setup Checkbox Group select all / select none
   */
  $('fieldset a.checkgroup-all').click(function() {
    $(this).parents('fieldset:first').find('input:[type=checkbox]').each(function(){
      $(this).attr('checked','checked');
    });
  });
  $('fieldset a.checkgroup-none').click(function() {
    $(this).parents('fieldset:first').find('input:[type=checkbox]').each(function(){
      $(this).attr('checked',null);
    });
  });
  
  /**
   * Validation
   */
  $('fieldset.myuryfrm form').validate({
    errorClass: 'ui-state-error',
    errorPlacement: function(error, element) {
      error.addClass('label-nofloat').appendTo(element.parent('div'));
    },
    submitHandler: function(form) {
      form.children('input[type=submit]').attr('disabled','disabled');
      form.submit();
    }
  });
  
  /**
   * Repeating elements
   */
  $('div.formfield-add-link a').click(function() {
    var origid = $(this).attr('id').replace(/-repeater$/, '');
    var newid = origid + $('#'+origid+'-counter').val();
    $('#'+origid+'-counter').val($('#'+origid+'-counter').val()+1);
    
    $('#'+origid).clone().attr('id', newid).val('').appendTo('#'+origid+'-container');
    $('#'+origid+'-ui').clone().attr('id', newid+'-ui').val('').appendTo('#'+origid+'-container');
    //$(document).ready();
  });
});