$('.twig-datatable').dataTable({
  "aoColumns": [
  //actpermissionid
  {
    "sTitle" : "",
    "bVisible": false
  },
  //action
  {
    "sTitle": "Action",
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