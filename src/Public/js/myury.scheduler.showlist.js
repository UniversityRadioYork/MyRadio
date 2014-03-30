$('.twig-datatable').dataTable({
  "aoColumns": [
  //show_id
  {
    bVisible: false
  },
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
  //show_type_id
  {
    bVisible: false
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
  },
  //photo
  {
    bVisible: false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);
