$(".twig-datatable").dataTable({
  "aoColumns": [
    //presenterstatusid
    {
      "sTitle": "Training",
    },
    //dateadded
    {
      "sTitle": "Added"
    },
    //join/leave
    {
      "sTitle": "",
      "bSortable": false
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
