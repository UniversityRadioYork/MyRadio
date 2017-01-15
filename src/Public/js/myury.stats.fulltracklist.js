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
    //trackid
    {
      bVisible: 0
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
  "bPaginate": false,
  aaSorting: []
});
