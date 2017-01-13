$('.twig-datatable').dataTable(
    {
        "aoColumns": [
          //Title
          {
                "sTitle": "Title"
          },
          //Artist
          {
                "sTitle": "Artist"
          },
          //Type
          {
                "bVisible": false
          },
          //Album
          {
                "sTitle": "Album"
          },
          //TrackID
          {
                "sTitle": "Track ID"
          },
          //Length
          {
                "sTitle": "Length"
          },
          //Intro
          {
                "sTitle": "Title"
          },
          //Clean
          {
                "sTitle": "Clean"
          },
          //Digitised
          {
                "sTitle": "Digitised"
          },
          //EditLink
          {
                "sTitle": "Edit"
          },
          //DeleteLink
          {
                "sTitle": "Delete"
          },

        ],
        "bPaginate": true,
        "searching": false
    }
);