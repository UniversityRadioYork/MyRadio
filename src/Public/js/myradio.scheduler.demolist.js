$(".twig-datatable").dataTable({
  "aoColumns": [
    //timeslot
    {
      "bVisible" : false
    },
    // online/in person
    {
      "sTitle": ""
    },
    //training type
    {
      "sTitle": "Training"
    },
    //start time
    {
      "sTitle": "Time"
    },
    //member
    {
      "sTitle": "Trainer"
    },
    //attending
    {
      "sTitle": "Attending"
    },
    //attend
    {
      "sTitle": "",
      "bSortable": false
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
