$(".twig-datatable").dataTable({
  "aaSorting": [[5, "desc"]],
  "aoColumns": [
    //term_id
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
