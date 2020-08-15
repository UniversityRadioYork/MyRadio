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
