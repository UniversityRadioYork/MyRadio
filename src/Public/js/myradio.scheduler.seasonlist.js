$(".twig-datatable").dataTable({
  "aoColumns": [
    //show_id
    {
      bVisible: false
    },
    //title
    {
      "sTitle": "Title",
      "sClass": "left"
    },
    //credits_string
    {
      "sTitle": "Credits"
    },
    //credits
    {
      "bVisible": false
    },
    //description
    {
      "sTitle" : "",
      "bVisible": false
    },
    //show_type_id
    {
      bVisible: false
    },
    //subtype
    {
      sTitle: "Subtype",
      bVisible: true // subtype can be overridden per-season
    },
    //seasons
    {
      "sTitle": "Seasons",
      "bVisible": false
    },
    //editlink
    {
      "sTitle": "Edit",
      "bVisible": true
    },
    //applylink
    {
      "sTitle": "New Season",
      "bVisible": false
    },
    //uploadlink
    {
      "sTitle": "Show Art",
      "bVisible": false
    },
    //micrositelink
    {
      "sTitle": "View Microsite",
      "bVisible": false
    },
    //photo
    {
      bVisible: false
    },
    //id
    {
      "bVisible": false
    },
    //season_num
    {
      "sTitle": "Season #"
    },
    //createddate
    {
      "sTitle": "Submitted",
      "bVisible": false
    },
    //requestedtime
    {
      "sTitle": "Requested Time",
      "bVisible": false
    },
    //firsttime
    {
      "sTitle": "First Episode"
    },
    //numepisodes
    {
      "sTitle": "# of Episodes"
    },
    //addEpisodelink
    {
      "sTitle": "Add Episode"
    },
    //allocatelink
    {
      "sTitle": "Allocate",
      "bSortable": false,
      "bVisible": false
    },
    //rejectlink
    {
      "sTitle": "Reject",
      "bSortable": false,
      "bVisible": false
    }
  ],
  "bPaginate": false
});

var table = $(".twig-datatable").DataTable();
// Replace epoch 0 with Not Scheduled.
table.column(15).nodes().each(function (node) {
  if (table.cell(node).data().indexOf("01/01/1970") >= 0) {
    table.cell(node).data("Not Scheduled");
  }
});
