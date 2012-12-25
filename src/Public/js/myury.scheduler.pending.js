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
    "sTitle": "# Seasons"
  },
  //editlink
  {
    "sTitle": "Allocate",
    "bSortable": false
  },
  //applylink
  {
    "sTitle": "New Season",
    "bVisible": false
  },
  //micrositelink
  {
    "sTitle": "View Microsite",
    "bSortable": false
  },
  //id
  {
    "bVisible": false
  },
  //createddate
  {
    "sTitle": "Submitted"
  },
  //requestedtime
  {
    "sTitle": "Requested Time"
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