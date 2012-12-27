$('.twig-datatable').dataTable({
  "aoColumns": [
  //title
  {
    "sTitle": "Title",
    "sClass": "left"
  },
  //credits
  {
    "sTitle": "Credits"
  },
  //description
  {
    "sTitle" : "",
    "bVisible": false
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
    "sTitle": "Submitted"
  },
  //requestedtime
  {
    "sTitle": "Requested Time"
  },
  //firsttime
  {
    "sTitle": "First Episode"
  },
  //numepisodes
  {
    "sTitle": "# of Episodes"
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
  "bJQueryUI": true,
  "bPaginate": false
}
);