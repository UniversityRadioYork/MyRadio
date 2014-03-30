/**
 * This file contains the necessary functions for browsing searching and adding
 * items in the Library pane of the main interface
 * @todo Includes length attribute to list items
 */

/**
 * Search the central library using the input criteria, rendering the response
 * in the search panel
 */
var searchTimerRef = null;
function updateCentralSearch() {
  $('#res-loading').show();
  $.ajax({
    url: myury.makeURL('MyRadio', 'a-findtrack'),
    type: 'post',
    data: {
      artist: $('#res-filter-artist').val(),
      term: $('#res-filter-track').val(),
      limit: 100,
      require_digitised: true
    },
    success: function(data) {
      $('#baps-channel-res').empty();
      for (file in data) {
        var classes = '';
        if (!data[file].clean)
          classes = classes + ' unclean';

        $('#baps-channel-res').append(
                '<li id="' + data[file].album.recordid + '-' + data[file].trackid +
                '" channel="res" weight="0" type="central" class="' + classes + '" length="' + data[file].length + '">'
                + data[file].title + ' - ' + data[file].artist + '</li>'
                );
      }
      registerItemClicks();
      $('#res-loading').hide();
    },
    error: function() {
      $('#res-loading').html('Error loading library').addClass('ui-state-error');
    }
  });
}

/**
 *Deal with the Resources Library selector being changed
 */
$(document).ready(function() {
  $('#res-type-sel').change(function() {
    //Show the relevent filter forms
    if ($(this).val() === 'central') {
      $('#res-filter-name').hide();
      $('#res-filter-artist, #res-filter-track').fadeIn();
      //This doesn't auto-load any files until search paramaters are set
    } else if ($(this).val().match(/managed-.*/)) {
      //Load a managed playlist
      $('#res-loading').show();
      $.ajax({
        url: myury.makeURL('MyRadio', 'a-findtrack'),
        type: 'post',
        data: {itonesplaylistid: $(this).val().replace(/managed-/, ''), digitised: true, limit: 0},
        success: function(data) {
          for (file in data) {
            $('#baps-channel-res').append(
                    '<li id="' + data[file].album.recordid + '-' + data[file].trackid +
                    '" channel="res" weight="0" type="central" length="' + data[file].length + '">'
                    + data[file].title + ' - ' + data[file].artist + '</li>'
                    );
          }
          $('#res-loading').hide();
          //Enable name filtering
          ulsort.List.Filter('#res-filter-name', '#baps-channel-res>li');
          //Make them activatable
          registerItemClicks();
        },
        error: function() {
          $('#res-loading').html('Error loading library').addClass('ui-state-error');
        }
      });
      $('#res-filter-artist, #res-filter-track').hide();
      $('#res-filter-name').fadeIn();
    } else if ($(this).val().match(/auto-.*/)) {
      //Load an auto playlist
      $('#res-loading').show();
      $.ajax({
        url: myury.makeURL('NIPSWeb', 'load_auto_managed'),
        type: 'post',
        data: 'playlistid=' + $(this).val(),
        success: function(data) {
          for (file in data) {
            $('#baps-channel-res').append(
                    '<li id="' + data[file].album.recordid + '-' + data[file].trackid +
                    '" channel="res" weight="0" type="central" length="' + data[file].length + '">'
                    + data[file].title + ' - ' + data[file].artist + '</li>'
                    );
          }
          $('#res-loading').hide();
          //Enable name filtering
          ulsort.List.Filter('#res-filter-name', '#baps-channel-res>li');
          //Make them activatable
          registerItemClicks();
        },
        error: function() {
          $('#res-loading').html('Error loading library').addClass('ui-state-error');
        }
      });
      $('#res-filter-artist, #res-filter-track').hide();
      $('#res-filter-name').fadeIn();
    } else if ($(this).val().match(/^aux-\d+|^user-.*/)) {
      $('#res-loading').show();
      $.ajax({
        url: myury.makeURL('NIPSWeb', 'load_aux_lib'),
        type: 'post',
        data: 'libraryid=' + $(this).val(),
        success: function(data) {
          for (file in data) {
            if (data[file].meta == true) {
              $('#baps-channel-res').append('<span>' + data[file].title + '</span><br>');
            } else {
              $('#baps-channel-res').append(
                      '<li id="ManagedDB-' + data[file].managedid +
                      '" length="' + data[file].length +
                      '" channel="res" weight="0" type="aux" managedid="' + data[file].managedid + '">'
                      + data[file].title + '</li>'
                      );
            }
          }
          $('#res-loading').hide();
          //Enable name filtering
          ulsort.List.Filter('#res-filter-name', '#baps-channel-res>li');
          //Make them activatable
          registerItemClicks();
        },
        error: function() {
          $('#res-loading').html('Error loading library').addClass('ui-state-error');
        }
      });
      $('#res-filter-artist, #res-filter-track').hide();
      $('#res-filter-name').fadeIn();
    }
    //Clear the current list
    $('#baps-channel-res').empty();
    //Makes the artist search autocompleting. When an artist is selected it'll filter
    $('#res-filter-artist').autocomplete({
      source: myury.makeURL('MyRadio', 'a-findartist'),
      data: {limit: 50},
      minLength: 2,
      select: function(event, ui) {
        $(this).val(ui.item.title);
        //Let the autocomplete update the value of the filter
        setTimeout("updateCentralSearch()", 50);
        return false;
      }
    }).data("ui-autocomplete")._renderItem = function(ul, item) {
      return $('<li></li>').data('item.autocomplete', item)
              .append('<a>' + item.title + '</a>')
              .appendTo(ul);
    };
    ;
  });

  //Bind the central search function
  $('#res-filter-track').on('keyup', function() {
    clearTimeout(searchTimerRef);
    searchTimerRef = setTimeout(updateCentralSearch, 500);
  });

  /**
   * Handler for activating the Manage Library link
   */
  $('#a-manage-library').click(function() {
    var url = $(this).children('a').attr('href');
    var dialog = $('<div style="display:none"><iframe src="' + url + '" width="800" height="500" frameborder="0"></iframe></div>').appendTo('body');
    dialog.dialog({
      close: function(event, ui) {
        dialog.remove();
      },
      modal: true,
      title: 'Library Manager',
      width: 850,
      minHeight: 550,
      position: {my: "center center", at: "center center", of: window}
    });
    return false;
  });
});
