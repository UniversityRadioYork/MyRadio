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
  e.preventDefault();
  window.actionpermissionconfirmurl = $(this).attr('url');
  $('<div title="Confirm Deletion">Are you sure you want to remove this permission requirement? If this Action has no\n\
other permission settings defined, it will no longer work.</div>').dialog({
    modal:true,
    buttons: {
      "Delete Permission Allocation": function() {
        window.location = window.actionpermissionconfirmurl;
      },
      Cancel: function() {
        window.actionpermissionconfirmurl = null;
        $(this).dialog('close');
      }
    }
  });
});