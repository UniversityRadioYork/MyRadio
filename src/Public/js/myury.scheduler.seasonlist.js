$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //show_id
        {
            bVisible: false
        },
        //title
        {
            "sTitle": "Title",
            "sClass": "left"
        },
        //credits
        {
            "sTitle": "Credits"
        },
        //description
        {
            "sTitle" : "",
            "bVisible": false
        },
        //show_type_id
        {
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
            "bVisible": true
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
            bVisible: false
        },
        //id
        {
            "bVisible": false
        },
        //season_num
        {
            "sTitle": "Season #"
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
            "sTitle": "First Episode"
        },
        //numepisodes
        {
            "sTitle": "# of Episodes"
        },
        //allocatelink
        {
            "sTitle": "Allocate",
            "bSortable": false,
            "bVisible": false
        },
        //rejectlink
        {
            "sTitle": "Reject",
            "bSortable": false,
            "bVisible": false
        }
        ],
        "bPaginate": false
    }
);
