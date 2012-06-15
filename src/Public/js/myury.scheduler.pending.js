$('.twig-datatable').dataTable({
  "aaData": window.tabledata,
  "aoColumns": [
  {
    "sTitle" : ""
  },
  {
    "sTitle": "Summary"
  },
  {
    "sTitle": "Submitted"
  },
  {
    "sTitle": "Requested Time"
  }
  ]
  }
);