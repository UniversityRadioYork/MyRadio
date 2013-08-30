$('.twig-datatable').dataTable({
  bSort: true,
  "aoColumns": [
  //title
  {
    "sTitle": "Title"
  },
  //artist
  {
    "sTitle": "Artist"
  },
  //album
  {
    "sTitle": "Album"
  },
  //starttime
  {
    "sTitle": "Start Time"
  },
  //label
  {
    sTitle: "Label"
  }
  ],
  "bJQueryUI": true,
  "bPaginate": false,
  aaSorting: []
}
);