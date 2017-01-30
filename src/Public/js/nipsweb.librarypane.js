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
  $("#notice").html("Searching...").show();
  $.ajax({
    url: myradio.makeURL("MyRadio", "a-findtrack"),
    type: "post",
    data: {
      artist: $("#res-filter-artist").val(),
      term: $("#res-filter-track").val(),
      limit: 100,
      require_digitised: true
    },
    success: function (data) {
      $("#baps-channel-res").empty();
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
          "' intro='" + data[file].intro + "'" +
          "' channel='res' weight='0' type='central' class='" + classes + "' length='" + data[file].length + "'>" +
          data[file].title + " - " + data[file].artist + "</li>"
        );
      }
      planner.registerItemClicks();
      $("#notice").hide();
    }
  });
}

/**
 *Deal with the Resources Library selector being changed
 */
$(document).ready(function () {
  $("#res-type-sel").change(function () {
    var file;
    //Show the relevent filter forms
    if ($(this).val() === "central") {
      $("#res-filter-name").hide();
      $("#res-filter-artist, #res-filter-track").fadeIn();
      //This doesn't auto-load any files until search paramaters are set

    } else if ($(this).val().match(/managed-.*/)) {
      //Load a managed playlist
      $("#res-loading").show();
      $.ajax({
        url: myradio.makeURL("MyRadio", "a-findtrack"),
        type: "get",
        data: {itonesplaylistid: $(this).val().replace(/managed-/, ""), digitised: true, limit: 0},
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
              "' title='" + data[file].title + "(" + data[file].length + ")" +
              "' intro='" + data[file].intro + "'" +
              "' class='" + classes + "'" +
              "' channel='res' weight='0' type='central' length='" + data[file].length + "'>" +
              data[file].title + " - " + data[file].artist + "</li>"
            );
          }
          $("#res-loading").hide();
          //Enable name filtering
          ulsort.List.Filter("#res-filter-name", "#baps-channel-res>li");
          //Make them activatable
          planner.registerItemClicks();
        }
      });
      $("#res-filter-artist, #res-filter-track").hide();
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
              "' title='" + data[file].title + "(" + data[file].length + ")" +
              "' intro='" + data[file].intro + "'" +
              "' class='" + classes + "'" +
              "' channel='res' weight='0' type='central' length='" + data[file].length + "'>" +
              data[file].title + " - " + data[file].artist + "</li>"
            );
          }
          $("#res-loading").hide();
          //Enable name filtering
          ulsort.List.Filter("#res-filter-name", "#baps-channel-res>li");
          //Make them activatable
          planner.registerItemClicks();
        }
      });
      $("#res-filter-artist, #res-filter-track").hide();
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
                "' title='" + data[file].title + "(" + data[file].length + ")" +
                "' channel='res' weight='0' type='aux' managedid='" + data[file].managedid + "'>" +
                data[file].title + "</li>"
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
      $("#res-filter-artist, #res-filter-track").hide();
      $("#res-filter-name").fadeIn();
    }
    //Clear the current list
    $("#baps-channel-res").empty();
    //Makes the artist search autocompleting. When an artist is selected it'll filter
    var artistLookup = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace("title"),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 5,
      dupDetector: function (remote, local) {
        return local.title == remote.title;
      },
      prefetch: {
        url: myradio.makeURL("MyRadio", "a-findartist", {term: null, limit: 500})
      },
      remote: myradio.makeURL("MyRadio", "a-findartist", {limit: 5, term: ""}) + "%QUERY" //Seperated out otherwise % gets urlescaped
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
  $("#a-manage-library").click(function () {
    var url = $(this).children("a").attr("href");
    myradio.createDialog("Manage Library", "<iframe src='" + url + "' width='580' height='500' frameborder='0'></iframe>");
    return false;
  });
});
