$('.twig-datatable').dataTable(
    {
        "aoColumns": [
            //podcast_id
            {
                "bVisible": false
        },
            //title
            {
                "sTitle": "Title",
        },
            //description
            {
                "sTitle": "Description",
        },
            //status
            {
                "sTitle": "Status"
        },
            //editlink
            {
                "sTitle": "Edit",
                "bSortable": false,
                "bVisible": true
        }
        ],
        "bPaginate": true
    }
);
