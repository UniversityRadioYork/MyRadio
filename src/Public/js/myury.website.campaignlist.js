$('.twig-datatable').dataTable({
  "aoColumns": [
    //banner_campaign_id
    {
      "bVisible" : false
    },
    //created_by
    {
      bVisible: false
    },
    //approved_by
    {
      bVisible: false
    },
    //effective_from
    {
      sTitle: "Campaign Start"
    },
    //effective_to
    {
      sTitle: "Campaign End"
    },
    //banner_location_id
    {
      bVisible: false
    },
    //num_timeslots
    {
      sTitle: "# of Timeslots"
    },
    //edit_link
    {
      sTitle: 'Edit'
    },
    //timeslots
    {
      bVisible: false
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
