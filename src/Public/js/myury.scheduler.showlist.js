$('.twig-datatable').dataTable({
  "aoColumns": [
  //title
  {
    "sTitle" : "Title"
  },
  //credits
  {
    "sTitle" : "Credits"
  },
  //description
  {
    "sTitle" : "Description"
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