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
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);