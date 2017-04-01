$(".twig-datatable").dataTable({
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
      "bVisible": false
    },
    //Intro
    {
      "bVisible": false
    },
    //time
    {
      "sTitle": "Epoch Time",
      "bVisible": false
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
