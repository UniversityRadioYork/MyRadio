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
    "sClass": "left"
  },
  //sname
  {
    "sTitle": "Surname",
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