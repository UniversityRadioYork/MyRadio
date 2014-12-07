$('.twig-datatable').dataTable({
  "aoColumns": [
    //id
    {
      bVisible: false
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
      sTitle: "From"
    },
    //effective_to
    {
      sTitle: "Until"
    },
    //num_timeslots
    {
      sTitle: "Times"
    },
    //timeslots
    {
      bVisible: false
    },
    //playlist
    {
      bVisible: false
    },
    //weight
    {
      sTitle: "Weight"
    },
    //edit
    {
      sTitle: "Edit"
    }
  ],
  bPaginate: false
}
);
