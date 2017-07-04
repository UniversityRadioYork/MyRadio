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
    //season_id
    {
      "sTitle": "Season ID",
      "bVisible": false
    },
    //season_num
    {
      "sTitle": "Season #",
    },
    //createddate
    {
      "sTitle": "Submitted",
      "bVisible": false
    },
    //requestedtime
    {
      "sTitle": "Requested Time",
      "bVisible": false
    },
    //firsttime
    {
      "sTitle": "First Episode",
      "bVisible": false
    },
    //numepisodes
    {
      "sTitle": "# of Episodes",
    },
    //allocatelink
    {
      "sTitle": "Allocate",
      "bSortable": false,
      "bVisible": false
    },
    //rejectlink
    {
      "sTitle": "Cancel",
      "bSortable": false,
      "bVisible": false
    },
    //timeslot_id
    {
      "sTitle": "Timeslot id",
      "bVisible": false
    },
    //timeslot_num
    {
      "sTitle": "Episode #"
    },
    //tags
    {
      "sTitle": "Tags",
      "bVisible": false
    },
    //time
    {
      "sTitle": "Epoch Start Time",
      "bVisible": false
    },
    //starttime
    {
      "sTitle": "Time"
    },
    //duration
    {
      "sTitle": "Length"
    },
    //mixcloud_status
    {
      "sTitle": "MixCloud Status",
      "bVisible": false
    },
    //msg_count
    {
      "sTitle": "Message Count"
    },
  ],
  "bPaginate": false
});
