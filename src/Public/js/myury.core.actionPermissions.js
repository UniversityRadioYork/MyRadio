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
  //permission
  {
    "sTitle": "Required Permission"
  },
  //Remove link
  {
    "sTitle": "",
    "bSortable": false
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);