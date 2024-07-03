$(".twig-datatable").dataTable({
  "aaSorting": [[5, "desc"]],
  "aoColumns": [
    //termid
    {
      "bVisible": false
    },
    //descr
    {
      "sTitle": "Name"
    },
    //num_weeks
    {
      "bVisible": false
    },
    //week_names
    {
      "bVisible": false,
    },
    //start
    {
      "sTitle": "Start Date"
    }
  ],
  "bPaginate": true
});
