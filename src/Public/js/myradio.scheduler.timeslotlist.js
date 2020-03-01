$(".twig-datatable").dataTable({
  "aoColumns": [
    //show_id
    {
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
      "bVisible": false
    },
    //credits
    {
      "bVisible": false
    },
    //description
    {
      "sTitle" : "",
      "bVisible": false
    },
    //show_type_id
    {
      "bVisible": false
    },
    //subtype
    {
      sTitle: "Subtype",
      bVisible: false
    },
    //seasons
    {
      "sTitle": "Seasons",
      "bVisible": false
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
      "bVisible": false
    },
    //season_id
    {
      "bVisible": false
    },
    //season_num
    {
      "sTitle": "Season #",
      "bVisible": false
    },
    //submitted
    {
      "sTitle": "Submitted",
      "bVisible": false
    },
    //requested_time
    {
      "sTitle": "Requested Time",
      "bVisible": false
    },
    //first_time
    {
      "sTitle": "First Episode",
      "bVisible": false
    },
    //num_episodes
    {
      "sTitle": "# of Episodes",
      "bVisible": false
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
      "bVisible": true
    },
    //timeslot_id
    {
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
      "sTitle": "Time",
      "bVisible": false
    },
    //starttime
    {
      "sTitle": "Time",
      "iDataSort": 21
    },
    //duration
    {
      "sTitle": "Length"
    },
    //mixcloud status
    {
      "sTitle": "Mixcloud Status",
      "bVisible": false
    },
    //mixcloud custom start time
    {
      "sTitle": "Mixcloud Custom Start Time",
      "bVisible": false
    },
    //mixcloud custom end time
    {
      "sTitle": "Mixcloud Custom End Time",
      "bVisible": false
    }
  ],
  "bPaginate": false
});
