/**
 * The MyURY Standard Form JavaScript Tools
 * This file initialises jQuery validation, autocompletes and other resources
 * needed for a MyURY Form
 */

window.MyURYForm = {
  setUpMemberFields: function() {
    /**
     * Initialises the Member autocomplete pickers where necessary
     */
    $('fieldset.myuryfrm input.member-autocomplete').each(function() {
      $(this).autocomplete({
        minLength: 3,
        source: myury.makeURL('MyURY', 'a-findmember'),
        select: function(event, ui) {
          $(this).val(ui.item.fname + ' ' + ui.item.sname);
          $('#' + $(this).attr('id').replace(/-ui$/, '')).val(ui.item.memberid);
          return false;
        },
        //Prevent the field blanking out when an item is given focus
        focus: function(event, ui) {
          return false;
        }
      })
              .data("ui-autocomplete")._renderItem = function(ul, item) {
        return $('<li></li>').data('item.autocomplete', item)
                .append('<a>' + item.fname + ' ' + item.sname + '</a>')
                .appendTo(ul);
      };

      //If there's an existing value, load it in
      console.log($('#' + $(this).attr('id').replace(/-ui$/, '')).val());
      if ($('#' + $(this).attr('id').replace(/-ui$/, '')).val() != '') {
        $.ajax({
          url: myury.makeURL('MyURY', 'a-membernamefromid'),
          data: {term: $('#' + $(this).attr('id').replace(/-ui$/, '')).val()},
          context: this,
          success: function(data) {
            console.log($(this));
            $(this).val(data);
          }
        });
      }
    });
  },
  setUpTrackFields: function() {
    /**
     * Initialises the Track autocomplete pickers where necessary
     */
    $('fieldset.myuryfrm input.track-autocomplete').each(function() {
      $(this).autocomplete({
        minLength: 3,
        source: myury.makeURL('MyURY', 'a-findtrack'),
        select: function(event, ui) {
          $(this).val(ui.item.title);
          $('#' + $(this).attr('id').replace(/-ui$/, '')).val(ui.item.trackid);
          return false;
        },
        //Prevent the field blanking out when an item is given focus
        focus: function(event, ui) {
          return false;
        }
      })
              .data("ui-autocomplete")._renderItem = function(ul, item) {
        return $('<li></li>').data('item.autocomplete', item)
                .append('<a>' + item.title + '<br><span style="font-size:.8em">' + item.artist + '</span></a>')
                .appendTo(ul);
      };

      //If there's an existing ID value, load it in
      console.log($('#' + $(this).attr('id').replace(/-ui$/, '')).val());
      if ($(this).val() === '' && $('#' + $(this).attr('id').replace(/-ui$/, '')).val() !== '') {
        $.ajax({
          url: myury.makeURL('MyURY', 'a-findtrack'),
          data: {id: $('#' + $(this).attr('id').replace(/-ui$/, '')).val()},
          context: this,
          success: function(data) {
            $(this).val(data.title);
          }
        });
      }
    });
  },
  setUpArtistFields: function() {
    /**
     * Initialises the Artist autocomplete pickers where necessary
     */
    $('fieldset.myuryfrm input.artist-autocomplete').each(function() {
      $(this).autocomplete({
        minLength: 3,
        source: myury.makeURL('MyURY', 'a-findartist'),
        select: function(event, ui) {
          $(this).val(ui.item.title);
          $('#' + $(this).attr('id').replace(/-ui$/, '')).val(ui.item.artistid);
          return false;
        },
        //Prevent the field blanking out when an item is given focus
        focus: function(event, ui) {
          return false;
        }
      })
              .data("ui-autocomplete")._renderItem = function(ul, item) {
        return $('<li></li>').data('item.autocomplete', item)
                .append('<a>' + item.title + '</a>')
                .appendTo(ul);
      };
    });
  },
  setUpTimePickers: function() {
    /**
     * Initialises the Time pickers where necessary
     * @todo Make stepminute customisable?
     */
    $('fieldset.myuryfrm input.time').timepicker({
      stepMinute: 15
    });
  },
  validate: function() {
    /**
     * Validation
     */
    $('fieldset.myuryfrm form').validate({
      errorClass: 'ui-state-error',
      errorPlacement: function(error, element) {
        error.addClass('label-nofloat').appendTo(element.parent('div'));
      },
      submitHandler: function(form) {
        form.children('input[type=submit]').attr('disabled', 'disabled');
        form.submit();
      }
    });
  },
  init: function() {
    /**
     * Initialises TinyMCE fields
     */
    if (typeof tinymce !== 'undefined') {
      tinymce.init({
        selector: "textarea.myury-form-tinymce",
        plugins: "anchor autolink charmap code contextmenu fullscreen hr image link lists media tabfocus table wordcount"
      });
      /**
       * Initialises the Date pickers where necessary
       */
      $('fieldset.myuryfrm input.date').datepicker({
        dateFormat: "dd/mm/yy"
      });
    }
    /**
     * Initialises the Datetime pickers where necessary
     * @todo Make stepminute customisable?
     */
    $('fieldset.myuryfrm input.datetime').datetimepicker({
      dateFormat: "yy-mm-dd",
      timeFormat: "hh:mm:ss",
      stepMinute: 15
    });
    MyURYForm.setUpTimePickers();
    MyURYForm.setUpMemberFields();
    MyURYForm.setUpTrackFields();

    /**
     * Setup Checkbox Group select all / select none
     */
    $('fieldset a.checkgroup-all').click(function() {
      $(this).parents('fieldset:first').find('input[type=checkbox]').each(function() {
        $(this).attr('checked', 'checked');
      });
    });
    $('fieldset a.checkgroup-none').click(function() {
      $(this).parents('fieldset:first').find('input[type=checkbox]').each(function() {
        $(this).attr('checked', null);
      });
    });

    MyURYForm.validate();

    /**
     * Repeating elements
     */
    $('div.formfield-add-link a').click(function() {

      var origid = $(this).attr('id').replace(/-repeater$/, '');
      var newid = origid + $('#' + origid + '-counter').val();
      $('#' + origid + '-counter').val(parseInt($('#' + origid + '-counter').val()) + 1);

      var origobj = $('#' + origid).clone().attr('id', newid).val('');

      if (!$(origobj).parent('div').hasClass('nobr'))
        origobj.append('<br>');

      $(origobj).addClass('repeatedfield').removeClass('required').removeClass('hasDatepicker').insertBefore($(this).parent());

      //For autocomplete fields, they have a ui field which is what is very visible. This needs cloning and setting up
      var autocomplete = $('#' + origid + '-ui').clone().attr('id', newid + '-ui').val('').insertBefore($(this).parent());

      if (!$(origobj).parent('div').hasClass('nobr'))
        $('<br>').insertBefore($(this).parent());

      if ($(autocomplete).hasClass('member-autocomplete')) {
        MyURYForm.setUpMemberFields();
      }

      if ($('#' + newid).hasClass('time')) {
        MyURYForm.setUpTimePickers();
      }
    });

    //Set up tabular repeating sets
    $('.myury-form-add-row-button').on('click', function() {
      var new_id = $(this).attr('nextvalue');

      $('#' + $(this).attr('id').replace(/add-to-/, '') + ' tbody tr:first').clone()
        .addClass(parseInt(new_id) % 2 == 0 ? 'odd' : 'even')
        .find('input').each(function() {
        $(this).val('').attr('id', function(_, id) {
          return id.replace(/0/, new_id)
        });
      }).end().appendTo('#' + $(this).attr('id').replace(/add-to-/, '') + ' tbody');
      
      MyURYForm.setUpArtistFields();
      MyURYForm.setUpMemberFields();
      MyURYForm.setUpTrackFields();
      MyURYForm.setUpTimePickers();
      
      $(this).attr('nextvalue', parseInt(new_id)+1);
    });
    $('button.myuryfrm-remove-row').button({icons: {primary: "ui-icon-trash"}, text: false}).on('click', function() {
      $(this).closest('tr').remove();
      return false;
    });
    //And the dataTable that contains them
    $('table.myuryfrm-repeaterset-container').dataTable({
      bSort: true,
      bJQueryUI: true,
      bPaginate: false,
      bSearch: false
    }
    );
  }
};

$(document).ready(function() {
  MyURYForm.init();
});