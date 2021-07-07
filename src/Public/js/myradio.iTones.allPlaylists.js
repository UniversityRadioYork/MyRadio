$("#categoryPicker").change(function() {
  $("#categoryForm").submit();
});

$(".twig-datatable").dataTable({
  "aaSorting": [4, "asc"],
  "aoColumns": [
    //title
    {
      "sTitle": "Title"
    },
    //playlistid
    {
      bVisible: false
    },
    //description
    {
      "sTitle": "Description"
    },
    // category
    {
      sTitle: "Category"
    },
    //archived
    {
      sTitle: "State"
    },
    //edittrackslink
    {
      bVisible: false
    },
    //configurelink
    {
      "sTitle": "Configure",
    },
    //revisionslink
    {
      bVisible: false
    },
  ],
  "bPaginate": false
});
