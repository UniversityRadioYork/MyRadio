$('.twig-datatable').dataTable(
    {
        "aaSorting": [[2, 'desc']],
        "aoColumns": [
            //termid
            {
                "bVisible": false
        },
            //start
            {
                "sTitle": "Start Date"
        },
            //descr
            {
                "sTitle": "Name"
        }
        ],
        "bPaginate": true
    }
);
