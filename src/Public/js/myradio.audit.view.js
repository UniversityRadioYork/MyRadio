$(".twig-datatable").dataTable({
  bSort: true,
  "aoColumns": [
    //time
    {
      "sTitle": "Time"
    },
    //actor
    {
      "sTitle": "Actor"
    },
    //event_type
    {
      "sTitle": "Event Type"
    },
    //target_type
    {
      "sTitle": "Target Type"
    },
    //target_id
    {
      "sTitle": "Target ID"
    },
    //payload
    {
      "sTitle": "Payload"
    },
  ],
  "bPaginate": false,
  aaSorting: []
});
