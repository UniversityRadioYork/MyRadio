$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //show_id
        {
            bVisible: false
        },
        //title
        {
            "sTitle" : "Title"
        },
        //credits_string
        {
            "sTitle": "Credits"
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
            bVisible: false
        },
        //seasons
        {
            "sTitle": "Seasons"
        },
        //editlink
        {
            "sTitle": "Edit",
            "bSortable": false,
            "bVisible": true
        },
        //applylink
        {
            "sTitle": "New Season",
            "bSortable": false
        },
        //micrositelink
        {
            "sTitle": "Site",
            "bSortable": false
        },
        //photo
        {
            bVisible: false
        }
        ],
        "bPaginate": false
    }
);
