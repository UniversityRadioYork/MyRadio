$(".twig-datatable").dataTable({
  "aoColumns": [
    //short_url_id
    {
      "bVisible" : false
    },
    //alt
    {
      "sTitle": "Slug"
    },
    //is_active
    {
      sTitle: "Redirect To"
    },
    //edit_link
    {
      sTitle: "Edit"
    },
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
