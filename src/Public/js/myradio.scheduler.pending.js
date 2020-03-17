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
      bVisible: true
    },
    //seasons
    {
      "sTitle": "Seasons"
    },
    //editlink
    {
      "sTitle": "Edit",
      "bVisible": false
    },
    //applylink
    {
      "sTitle": "New Season",
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
      "sTitle": "Season #",
      "bVisible": false
    },
    //submitted
    {
      "sTitle": "Submitted"
    },
    //requestedtime
    {
      "sTitle": "Requested Time"
    },
    //firsttime
    {
      "sTitle": "First Episode",
      "bVisible": false
    },
    //numepisodes
    {
      "sTitle": "# of Episodes",
      "bVisible": false
    },
    //allocatelink
    {
      "sTitle": "Allocate",
      "bSortable": false
    },
    //rejectlink
    {
      "sTitle": "Reject",
      "bSortable": false
    }
  ],
  "bPaginate": false
});
