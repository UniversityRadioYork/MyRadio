$('.twig-datatable').dataTable({
  "aoColumns": [
  //newsentryid
  {
    "bVisible" : false
  },
  //author
  {
    "sTitle": "Author"
  },
  //posted
  {
    "sTitle": "Time"
  },
  //body
  {
    bVisible: false
  },
  //seen
  {
    "sTitle": "Seen"
  }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
}
);
