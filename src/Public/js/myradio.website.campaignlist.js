$(".twig-datatable").dataTable({
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
    //num_timeslots
    {
      sTitle: "# of Timeslots"
    },
    //banner_location_id
    {
      bVisible: false
    },
    //edit_link
    {
      sTitle: "Edit"
    }
  ],
  "bPaginate": false,
  "aaSorting": [[ 1, "asc" ]]
});
