$('.twig-datatable').dataTable({
  "aoColumns": [
  //timeslot
  {
    "bVisible" : false
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
    "sTitle": "# Joined"
  },
  //attend
  {
    "sTitle": ""
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);