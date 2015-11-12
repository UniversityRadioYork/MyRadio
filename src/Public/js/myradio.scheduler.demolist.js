$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //timeslot
        {
            "bVisible" : false
        },
        //start time
        {
            "sTitle": "Time"
        },
        //member
        {
            "sTitle": "Trainer"
        },
        //attending
        {
            "sTitle": "Attending"
        },
        //attend
        {
            "sTitle": "",
            "bSortable": false
        }
        ],
        "bPaginate": false,
        "aaSorting": [[ 1, "asc" ]]
    }
);
