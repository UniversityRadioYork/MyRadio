$('.twig-datatable').dataTable({
  "aoColumns": [
  //actpermissionid
  {
    "sTitle" : "",
    "bVisible": false
  },
  //service
  {
    "sTitle": "Service",
    "sClass": "left"
  },
  //model
  {
    "sTitle": "Model"
  },
  //action
  {
    "sTitle": "Action"
  },
  //editlink
  {
    "sTitle": "Remove",
    "bSortable": false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);