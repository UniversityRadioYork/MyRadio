$('.twig-datatable').dataTable({
  "aoColumns": [
  //memberid
  {
    "sTitle" : "",
    "bVisible": false
  },
  //fname
  {
    "sTitle": "First Name",
    "sClass": "left",
    "aDataSort": [ 1, 2], "aTargets": [ 1 ]
  },
  //sname
  {
    "sTitle": "Surname",
    "aDataSort": [ 2, 1], "aTargets": [ 2 ]
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