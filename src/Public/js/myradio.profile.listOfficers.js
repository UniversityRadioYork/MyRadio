$(".twig-datatable").dataTable({
  "aoColumns": [
    //team
    {
      "sTitle" : "Team",
    },
    //type
    {
      bVisible: false
    },
    //officership
    {
      "sTitle": "Officership",
    },
    // numPlaces
    {
      bVisible: false
    },
    //name
    {
      "sTitle": "Name"
    },
    //memberid
    {
      "sTitle": "",
      "bVisible": false
    },
    //officerrid
    {
      "sTitle": "",
      "bVisible": false
    },
    //view
    {
      "sTitle": "View"
    },
    //edit
    {
      "sTitle": "Edit"
    },
    //assign
    {
      "sTitle": "Assign"
    },
  ],
  "bSort": false,
  "bPaginate": false
});
