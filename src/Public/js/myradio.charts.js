$('.twig-datatable').dataTable(
    {
        "aoColumns": [
            //name
            {
              "bVisible" : false
            },
            //description
            {
              sTitle: "Chart Name"
            },
            //releases
            {
              sTitle: "Releases"
            },
            //editlink
            {
              sTitle: "Edit"
            }
        ],
        "bJQueryUI": true,
        "bPaginate": false,
        "aaSorting": [[ 1, "asc" ]]
    }
);
