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
    "sTitle": "Ran By"
  },
  //attending
  {
    "sTitle": "Attendees"
  },
  //attend
  {
    "sTitle": "Join this Session"
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);