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
    "sTitle" : "Description",
    "bVisible": false
  },
  //seasons
  {
    "sTitle": "Seasons"
  },
  //editlink
  {
    "sTitle": "Edit",
    "bSortable": false,
    "bVisible": true
  },
  //applylink
  {
    "sTitle": "New Season",
    "bSortable": false
  },
  //micrositelink
  {
    "sTitle": "Site",
    "bSortable": false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);