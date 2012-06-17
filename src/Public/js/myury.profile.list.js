$('.twig-datatable').dataTable({
  "aoColumns": [
  //memberid
  {
    "sTitle" : "",
    "bVisible": false
  },
  //name
  {
    "sTitle": "Name",
    "sClass": "left"
  },
  //paidamount
  {
    "sTitle": "Amount Paid"
  },
  //officer
  {
    "sTitle": "Officer"
  },
  ],
  "bJQueryUI": true,
  "bPaginate": false
}
);