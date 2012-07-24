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

$('td.column-del a').on('click', function(e) {
  e.stopPropagation();
  var id = $(this).parent('tr').children('td:first').attr('id');
  console.log(id);
  
  $('<div title="Confirm Delete">Are you sure you want to remove this permission requirement? If this Action has no\n\
other permission settings defined, it will no longer work.</div>').dialog({modal:true}).append('body');
});