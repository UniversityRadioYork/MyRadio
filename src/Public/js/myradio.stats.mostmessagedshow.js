$(".twig-datatable").dataTable({
  bSort: false,
  "aoColumns": [
    //Show_id
    {
      "sTitle": "Show ID",
      "bVisible": false
    },
    //title
    {
      "sTitle": "Title",
      "sClass": "left"
    },
    //credits_string
    {
      "sTitle": "Credits",
      "bVisible": true
    },
    //credits
    {
      "bVisible": false
    },
    //description
    {
      "sTitle" : "Description",
      "bVisible": false
    },
     //show_type_id
    {
      "sTitle" : "Show Type ID",
      "bVisible": false
    },
    //seasons
    {
      "sTitle": "Seasons"
    },
    //editlink
    {
      "sTitle": "Edit",
      "bVisible": false
    },
    //applylink
    {
      "sTitle": "New Season",
      "bVisible": false
    },
    //micrositelink
    {
      "sTitle": "View Microsite",
      "bVisible": false
    },
    //photo
    {
      "sTitle": "Photo",
      "bVisible": false
    },
    //msg_count
    {
      "sTitle": "Message Count"
    },
  ],
  "bPaginate": false
});
