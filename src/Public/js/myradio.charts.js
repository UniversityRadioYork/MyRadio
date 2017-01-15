$('.twig-datatable').dataTable({
  "aoColumns": [
    //name
    {
      "bVisible" : false
    },
    //description
    {
      sTitle: "Chart Name"
    },
    //releases
    {
      sTitle: "Releases"
    },
    //editlink
    {
      sTitle: "Edit"
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
