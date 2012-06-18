$('.twig-datatable').dataTable({
  "aoColumns": [
    { "aDataSort": [ 1, 2], "aTargets": [ 1 ]},
    { "aDataSort": [ 2, 1], "aTargets": [ 2 ]},
  //memberid
  {
    "sTitle" : "",
    "bVisible": false
  },
  //fname
  {
    "sTitle": "First Name",
    "sClass": "left"
  },
  //sname
  {
    "sTitle": "Surname"
  },
  //college
  {
    "sTitle": "College"
  },
  //paid
  {
    "sTitle": "Amount Paid"
  },
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);