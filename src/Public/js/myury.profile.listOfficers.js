$('.twig-datatable').dataTable({
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
  //edit
  {
    "sTitle": "Edit"
  },
  ],
  "bSort": false,
  "bPaginate": false
}
);
