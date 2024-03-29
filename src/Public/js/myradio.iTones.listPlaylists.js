$("#categoryPicker").change(function() {
  $("#categoryForm").submit();
});

$(".twig-datatable").dataTable({
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
      bVisible: false
    },
    //edittrackslink
    {
      "sTitle": "Edit"
    },
    //configurelink
    {
      "sTitle": "Configure",
    },
    //revisionslink
    {
      "sTitle": "Revisions"
    },
  ],
  "bPaginate": false
});
