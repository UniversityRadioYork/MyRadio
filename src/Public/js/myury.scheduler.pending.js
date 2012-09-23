$('.twig-datatable').dataTable({
  "aoColumns": [
  //credits
  {
    "sTitle" : "",
    "bVisible": false
  },
  //entryid
  {
    "sTitle" : "",
    "bVisible": false
  },
  //summary
  {
    "sTitle": "Title",
    "sClass": "left"
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
  //editlink
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