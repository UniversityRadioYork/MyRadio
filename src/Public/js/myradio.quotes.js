$('.twig-datatable').dataTable(
    {
        "aaSorting": [[2, 'desc']],
        "aoColumns": [
            //quoteID
            {
                "bVisible": false
        },
            //source
            {
                "sTitle": "Source",
                "sClass": "left",
        },
            //date
            {
                "sTitle": "Date",
                "aDataSort": [ 0 ]
        },
            //text
            {
                "sTitle": "Quote"
        },
            //editlink
            {
                "sTitle": "Edit",
                "bSortable": false,
                "bVisible": true
        }
        ],
        "bJQueryUI": true,
        "bPaginate": true
    }
);
