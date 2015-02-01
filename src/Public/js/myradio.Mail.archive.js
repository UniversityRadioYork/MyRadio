$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //email_id
        {
            bVisible: false
        },
        //from
        {
            "sTitle": "Sender"
        },
        //timestamp
        {
            "sTitle": "Time"
        },
        //subject
        {
            "sTitle": "Subject"
        },
        //view
        {
            "sTitle": "View",
        },
        ],
        "bJQueryUI": true,
        "bPaginate": true,
        "aaSorting": [[ 2, "desc" ]]
    }
);
