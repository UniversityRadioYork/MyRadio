$('.twig-datatable').dataTable({
  "aoColumns": [
  //title
  {
    "sTitle" : "Title"
  },
  //seasons
  {
    "sTitle": "Seasons"
  },
  //editlink
  {
    "sTitle": "",
    "bSortable": false
  },
  //applylink
  {
    "sTitle": "",
    "bSortable": false
  },
  //micrositelink
  {
    "sTitle": "",
    "bSortable": false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);