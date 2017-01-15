$('.twig-datatable').dataTable({
  "aoColumns": [
    //photoid
    {
      "bVisible" : false
    },
    //date_added
    {
      sTitle: 'Date Created'
    },
    //format
    {
      bVisible: false
    },
    //owner
    {
      bVisible: false
    },
    //url
    {
      bVisible: false
    },
    //banner_id
    {
      bVisible: false
    },
    //alt
    {
      "sTitle": "Title"
    },
    //target
    {
      bVisible: false
    },
    //num_campaigns
    {
      sTitle: '# of Campaigns',
      bVisible: false
    },
    //is_active
    {
      sTitle: 'Active?'
    },
    //edit_link
    {
      sTitle: "Edit"
    },
    //campaigns_link
    {
      sTitle: "View Campaigns"
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
