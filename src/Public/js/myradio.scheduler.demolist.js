$(".twig-datatable").dataTable({
  "aoColumns": [
    //demo id
    {
      "bVisible" : false
    },
    // online/in person
    {
      "sTitle": "",
      "bSortable": false
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
    },
    //finish and marked trained
    {
      "sTitle": "",
      "bSortable": false
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
