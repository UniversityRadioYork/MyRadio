$('.twig-datatable').dataTable({
  "aoColumns": [
  //title
  {
    "sTitle": "Title",
    "sClass": "left"
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
  //description
  {
    "sTitle" : "",
    "bVisible": false
  },
  //rejectlink
  {
    "sTitle": "Reject",
    "bSortable": false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false,
  "oColReorder": {
    "aiOrder": [0 , 4, 3, 2, 1]
  }
}
);