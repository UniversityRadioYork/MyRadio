$(".twig-datatable").dataTable({
  aoColumns: [
    //title
    {
      sTitle: "Type",
    },
    //start_time
    {
      sTitle: "Start Time",
    },
    //end_time
    {
      sTitle: "End Time",
    },
    // downloadlink
    {
      sTitle: "Download",
    },
  ],
  bSort: false,
  bPaginate: false,
});
