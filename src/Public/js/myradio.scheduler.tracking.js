$(".twig-datatable").dataTable({
  "aoColumns": [
    //type
    {
      "sTitle": ""
    },
    //Info
    {
      "sTitle": "Information"
    },
    //location
    {
      "sTitle": "Location"
    },
    //time
    {
      "sTitle": "Time"
    }
  ],
  "bPaginate": true,
  "aaSorting": [[3, "desc"]]
});
