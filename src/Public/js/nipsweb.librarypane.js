//Still can't believe js/jquery doesn't have escaping built-in!
var entityMap = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
  '/': '&#x2F;',
  '`': '&#x60;',
  '=': '&#x3D;'
};

function escapeHTML (string) {
  return String(string).replace(/[&<>"'`=\/]/g, function (s) {
    return entityMap[s];
  });
}

/* global Bloodhound, myradio, planner, ulsort */
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
function updateCentralSearch()
{
  var file;
  $.ajax({
    url: mConfig.api_url + "/v2/track/search/",
    type: 'get',
    data: {
      artist: $("#res-filter-artist").val(),
      title: $("#res-filter-track").val(),
      limit: 100,
      digitised: true
    },
    success: function (data) {
      $("#baps-channel-res").empty();
      for (file in data.payload) {
        if (file === "myradio_errors") {
          continue;
        }
        var classes = "";
        var cleanStars = "";
        var tooltip = "";
        if (!data.payload[file].clean) {
          classes = classes + " unclean";
          cleanStars = "**";
          tooltip = "This track is explicit. Do not broadcast before 9PM. ";
        }
        tooltip += escapeHTML(data.payload[file].title) + " - " + escapeHTML(data.payload[file].artist)+ " (" + data.payload[file].length + ")";
        $("#baps-channel-res").append(
          "<li id='" + data.payload[file].album.recordid + "-" + data.payload[file].trackid +
          "' intro='" + data.payload[file].intro + "' title='" + tooltip + "'" +
          "' channel='res' weight='0' type='central' class='" + classes + "' length='" + data.payload[file].length + "'>" +
          cleanStars + escapeHTML(data.payload[file].title) + " - " + escapeHTML(data.payload[file].artist) + "</li>"
        );
      }
      planner.registerItemClicks();
    }
  });
}

/**
 *Deal with the Resources Library selector being changed
 */
$(document).ready(function () {
  $("#res-type-sel").change(function () {
    var file;
    showAlert("Loading results...", "warning");
    //Show the relevent filter forms
    if ($(this).val() === "central") {
      $("#res-filter-name").hide();
      $("#res-filter-artist-container, #res-filter-track").fadeIn();
      //This doesn't auto-load any files until search paramaters are set

    } else if ($(this).val().match(/managed-.*/)) {
      //Load a managed playlist
      $("#res-loading").show();
      $.ajax({
        url: mConfig.api_url + "/v2/track/search/",
        type: "get",
        data: {itonesplaylistid: $(this).val().replace(/managed-/, ""), digitised: true, limit: 0},
        success: function (data) {
          for (file in data) {
            if (file === "myradio_errors") {
              continue;
            }
          }
          for (file in data.payload) {
            var classes = "";
            if (!data.payload[file].clean) {
              classes = classes + " unclean";
            }
            $("#baps-channel-res").append(
              "<li id='" + data.payload[file].album.recordid + "-" + data.payload[file].trackid +
              "' title='" + escapeHTML(data.payload[file].title) + " (" + data.payload[file].length + ")" +
              "' intro='" + data.payload[file].intro + "'" +
              "' class='" + classes + "'" +
              "' channel='res' weight='0' type='central' length='" + data.payload[file].length + "'>" +
              escapeHTML(data.payload[file].title) + " - " + escapeHTML(data.payload[file].artist) + "</li>"
            );
          }
          $("#res-loading").hide();
          //Enable name filtering
          ulsort.List.Filter("#res-filter-name", "#baps-channel-res>li");
          //Make them activatable
          planner.registerItemClicks();
        }
      });
      $("#res-filter-artist-container, #res-filter-track").fadeOut();
      $("#res-filter-name").fadeIn();

    } else if ($(this).val().match(/auto-.*/)) {
      //Load an auto playlist
      $("#res-loading").show();
      $.ajax({
        url: myradio.makeURL("NIPSWeb", "load_auto_managed"),
        type: "get",
        data: "playlistid=" + $(this).val(),
        success: function (data) {
          for (file in data) {
            if (file === "myradio_errors") {
              continue;
            }
            var classes = "";
            if (!data[file].clean) {
              classes = classes + " unclean";
            }
            $("#baps-channel-res").append(
              "<li id='" + data[file].album.recordid + "-" + data[file].trackid +
              "' title='" + escapeHTML(data[file].title) + " - " + escapeHTML(data[file].artist) + " (" + data[file].length + ")" +
              "' intro='" + data[file].intro + "'" +
              "' class='" + classes + "'" +
              "' channel='res' weight='0' type='central' length='" + data[file].length + "'>" +
              escapeHTML(data[file].title) + " - " + escapeHTML(data[file].artist) + "</li>"
            );
          }
          $("#res-loading").hide();
          //Enable name filtering
          ulsort.List.Filter("#res-filter-name", "#baps-channel-res>li");
          //Make them activatable
          planner.registerItemClicks();
        }
      });
      $("#res-filter-artist-container, #res-filter-track").fadeOut();
      $("#res-filter-name").fadeIn();

    } else if ($(this).val().match(/^aux-\d+|^user-.*/)) {
      $("#res-loading").show();
      $.ajax({
        url: myradio.makeURL("NIPSWeb", "load_aux_lib"),
        type: "get",
        data: "libraryid=" + $(this).val(),
        success: function (data) {
          for (file in data) {
            if (data[file].meta == true) {
              $("#baps-channel-res").append("<span>" + data[file].title + "</span><br>");
            } else {
              $("#baps-channel-res").append(
                "<li id='ManagedDB-" + data[file].managedid +
                "' length='" + data[file].length +
                "' title='" + escapeHTML(data[file].title) + " (" + data[file].length + ")" +
                "' channel='res' weight='0' type='aux' managedid='" + data[file].managedid + "'>" +
                escapeHTML(data[file].title) + "</li>"
              );
            }
          }
          $("#res-loading").hide();
          //Enable name filtering
          ulsort.List.Filter("#res-filter-name", "#baps-channel-res>li");
          //Make them activatable
          planner.registerItemClicks();
        }
      });
      $("#res-filter-artist-container, #res-filter-track").fadeOut();
      $("#res-filter-name").fadeIn();
    }
    showAlert("Loading Complete", "success");
    //Clear the current list
    $("#baps-channel-res").empty();
    //Makes the artist search autocompleting. When an artist is selected it'll filter
    var artistLookup = new Bloodhound({
      datumTokenizer: function (datum) {
        return Bloodhound.tokenizers.whitespace(datum.title);
      },
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 5,
      dupDetector: function (remote, local) {
        return local.title == remote.title;
      },
      prefetch: {
        url: mConfig.api_url + "/v2/artist/findbyname/?title=null&limit=500",
        filter: function (data) {
          return $.map(data.payload, function (artist) {
            return {
              title: artist.title,
            };
          });
        }
      },
      remote: {
        url: mConfig.api_url + "/v2/artist/findbyname/?title=%QUERY&limit=5", //Seperated out otherwise % gets urlescaped
        filter: function (data) {
          return $.map(data.payload, function (artist) {
            return {
              title: artist.title,
            };
          });
        }
      }
    });
    artistLookup.initialize();
    $("#res-filter-artist").typeahead(
      {
        highlight: true,
        minLength: 1
      },
      {
        displayKey: "title",
        source: artistLookup.ttAdapter(),
        templates: {
          //Only needed for workaround
          suggestion: function (i) {
            //Fix typeahead not showing after hiding
            //TODO: Report this @ https://github.com/twitter/typeahead.js/
            $("input:focus").parent().children(".tt-dropdown-menu").removeClass("hidden");
            return "<p>" + i.title + "</p>";
          }
        }
      }
    )
      .on("typeahead:selected", updateCentralSearch);
  });

  //Bind the central search function
  $("#res-filter-track").on(
    "keyup",
    function () {
      clearTimeout(searchTimerRef);
      searchTimerRef = setTimeout(updateCentralSearch, 500);
    }
  );

  /**
   * Handler for activating the Manage Library link
   */
  $("#menu-track-upload").click(function () {
    var url = $(this).attr("href");
    myradio.createDialog("Upload to Library", "<iframe src='" + url + "' width='570' height='500' frameborder='0'></iframe>");
    return false;
  });

  $("#menu-import").click(function () {

    //work out the channel
    channel0lastweight = $("#baps-channel-1 li:last").attr("weight");
    channel1lastweight = $("#baps-channel-2 li:last").attr("weight");
    channel2lastweight = $("#baps-channel-3 li:last").attr("weight");
    if (channel0lastweight == undefined) {
      channel0lastweight = -1;
    }
    if (channel1lastweight == undefined) {
      channel1lastweight = -1;
    }
    if (channel2lastweight == undefined) {
      channel2lastweight = -1;
    }
    var url = $(this).attr("href") + "?clientid=" + clientid + "&channel0lastweight=" + channel0lastweight + "&channel1lastweight=" + channel1lastweight + "&channel2lastweight=" + channel2lastweight;
    myradio.createDialog("Import from another show", "<iframe src='" + url + "' width='570' height='500' frameborder='0'></iframe>");
    return false;
  });

});
