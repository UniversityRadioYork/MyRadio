$(".twig-datatable").dataTable({
  "aoColumns": [
    //memberid
    {
      "sTitle" : "",
      "bVisible": false
    },
    //name
    {
      "sTitle": "Name",
      "sClass": "left",
      "aDataSort": [ 1, 2 ]
    },
    //nickname
    {
      "sTitle": "Nickname",
      "sClass": "left",
      "aDataSort": [ 2, 1 ]
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
  "bPaginate": false
});
