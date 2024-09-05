$(".twig-datatable").dataTable({
  "aaSorting": [[2, "desc"]],
  "aoColumns": [
    //term_id
    {
      "bVisible": false
    },
    //start
    {
      "sTitle": "Start Date"
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
    }
  ],
  "bPaginate": true
});
