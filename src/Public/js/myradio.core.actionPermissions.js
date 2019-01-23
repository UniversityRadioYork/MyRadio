$(".twig-datatable").dataTable({
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
      "sTitle": "Module"
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
  "bPaginate": true
});
