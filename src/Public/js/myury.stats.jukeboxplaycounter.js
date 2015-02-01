$('.twig-datatable').dataTable(
    {
        bSort: true,
        "aoColumns": [
        //title
        {
            "sTitle": "Title",
            "sClass": "left"
        },
        //artist
        {
            "sTitle": "Artist"
        },
        //type
        {
            "sTitle" : "Type",
            "bVisible": false
        },
        //album
        {
            "sTitle": "Album",
            "bVisible": false
        },
        //trackid
        {
            "sTitle": "TrackID",
            "bVisible": false
        },
        //length
        {
            "sTitle": "Length",
            "bVisible": false
        },
        //clean
        {
            "sTitle": "Clean",
            "bVisible": false
        },
        //digitised
        {
            "sTitle": "Digitised",
            "bVisible": false
        },
        //editlink
        {
            "sTitle": "Edit",
            "bVisible": false
        },
        //Delete
        {
            "sTitle": "Delete",
            "bVisible": false
        },
        //num_plays
        {
            "sTitle": "Play Count"
        },
        //total_playtime
        {
            "sTitle": "Total Playtime"
        },
        //in_playlists
        {
            "sTitle": "Playlist Membership"
        }
        ],
        "bJQueryUI": true,
        "bPaginate": false,
        aaSorting: [[10, "desc"]]
    }
);
