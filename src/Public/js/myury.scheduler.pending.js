$('.twig-datatable').dataTable({
  "aaData": tabledata,
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