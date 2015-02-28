$('.twig-datatable').dataTable(
    {
        bSort: false,
        "aoColumns": [
        //title
        {
            "sTitle": "Title",
            "sClass": "left"
        },
        //credits
        {
            "sTitle": "Credits",
            "bVisible": true
        },
        //description
        {
            "sTitle" : "Description",
            "bVisible": false
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
        //id
        {
            "bVisible": false
        },
        //season_num
        {
            "sTitle": "Season #",
            "bVisible": false
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
            "bVisible": true
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
        //timeslotnum
        {
            "sTitle": "Episode #"
        },
        //starttime
        {
            "sTitle": "Time"
        },
        //duration
        {
            "sTitle": "Length"
        },
        //listeners
        {
            "sTitle": "Listener Count"
        }
        ],
        "bPaginate": false
    }
);
