$('.twig-datatable').dataTable({
  "aoColumns": [
  //team
  {
    "sTitle" : "Team",
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
  "bJQueryUI": true,
  "bSort": false,
  "bPaginate": false
}
);