$(".twig-datatable").dataTable({
  "aoColumns": [
    //title
    {
      "sTitle" : "Show",
    },
    //start_time
    {
      "sTitle": "Start Time",
    },
    //togglelink
    {
      "sTitle": "Enable/Disable"
    },
    // clipslink
    {
      "sTitle": "Clips"
    }
  ],
  "bSort": false,
  "bPaginate": false
});
