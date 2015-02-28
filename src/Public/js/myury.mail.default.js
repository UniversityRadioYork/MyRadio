$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //listid
        {
            bVisible: false
        },
        //subscribed
        {
            "sTitle": "Subscribed",
            "sClass": "left"
        },
        //name
        {
            "sTitle": "Description"
        },
        //address
        {
            "sTitle" : "E-mail Address"
        },
        //recipient_count
        {
            bVisible: false
        },
        //optIn
        {
            "sTitle": "Opt In"
        },
        //optOut
        {
            "sTitle": "Opt Out"
        },
        //mail
        {
            "sTitle": "Contact"
        },
        //archive
        {
            sTitle: "View Archive"
        }
        ],
        "bPaginate": false
    }
);
