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
    },
    //unix
    {
      "bVisible": false
    }
  ],
  "bPaginate": true,
  "aaSorting": [[4, "desc"]]
});
