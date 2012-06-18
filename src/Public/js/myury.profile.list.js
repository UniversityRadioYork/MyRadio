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
    "aDataSort": [ 1, 2 ]
  },
  //sname
  {
    "sTitle": "Surname",
    "aDataSort": [ 2, 1 ]
  },
  //college
  {
    "sTitle": "College"
  },
  //paid
  {
    "sTitle": "Amount Paid",
    "aDataSort": [ 4, 2, 1 ]
  },
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);