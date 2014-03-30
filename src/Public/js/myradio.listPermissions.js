$('.twig-datatable').dataTable({
  "aoColumns": [
  //value
  {
    "bVisible" : false
  },
  //text
  {
    "sTitle": "Title"
  },
  //usage
  {
    "sTitle": "Used For"
  },
  //assigned
  {
    sTitle: "Assigned To"
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
}
);
