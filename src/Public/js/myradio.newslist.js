$(".twig-datatable").dataTable({
  "aoColumns": [
    //newsentryid
    {
      "bVisible" : false
    },
    //author
    {
      "sTitle": "Author"
    },
    //nickname
    {
      "sTitle": "nickname",
      "sClass": "left",
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
});
