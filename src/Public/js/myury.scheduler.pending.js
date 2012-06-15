$('.twig-datatable').dataTable({
  "aoColumns": [
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
  //editlink
  {
    "sTitle": "Allocate",
    "bSortable" : false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);