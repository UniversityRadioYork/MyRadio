$('.twig-datatable').dataTable(
    {
        "aoColumns": [
        //title
        {
            "sTitle": "Title"
        },
        //playlistid
        {
            bVisible: false
        },
        //description
        {
            "sTitle": "Description"
        },
        //edittrackslink
        {
            "sTitle": "Edit"
        },
        //configurelink
        {
            "sTitle": "Configure",
        },
        //revisionslink
        {
            "sTitle": "Revisions"
        },
        ],
        "bJQueryUI": true,
        "bPaginate": false
    }
);
