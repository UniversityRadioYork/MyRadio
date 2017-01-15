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
                "bVisible": false
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
                "bVisible": false
          },
          //Clean
          {
                "sTitle": "Clean",
                "bVisible": false
          },
          //Digitised
          {
                "sTitle": "Digitised",
                "bVisible": false
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