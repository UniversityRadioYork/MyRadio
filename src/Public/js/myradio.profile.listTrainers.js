$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //status_id
        {
            bVisible: false
        },
        //title
        {
            bVisible: false
        },
        //detail
        {
            bVisible: false
        },
        //depends
        {
            bVisible: false
        },
        //awarded_by
        {
            "sTitle": "Trained By",
        },
        //user_status_id
        {
            bVisible: false
        },
        //awarded_to
        {
            "sTitle": "Trainer"
        },
        //awarded_time
        {
            "sTitle": "Date Trained"
        },
        //revoked_by
        {
            bVisible: false
        },
        //revoked_time
        {
            bVisible: false
        },
        ],
        "bJQueryUI": true,
        "bPaginate": false,
        "aaSorting": [[ 7, "desc" ]]
    }
);
