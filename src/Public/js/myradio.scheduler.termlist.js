$(".twig-datatable").dataTable({
  "aaSorting": [[4, "desc"]],
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
      "bVisible": true
    },
    //week_names
    {
      "bVisible": false
    },
    //start
    {
      "sTitle": "Start Date"
    }
  ],
  "bPaginate": true
});