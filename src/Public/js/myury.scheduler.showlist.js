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
    "bSortable": false,
    "bVisible": false
  },
  //applylink
  {
    "sTitle": "New Season",
    "bSortable": false
  },
  //micrositelink
  {
    "sTitle": "Microsite",
    "bSortable": false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);