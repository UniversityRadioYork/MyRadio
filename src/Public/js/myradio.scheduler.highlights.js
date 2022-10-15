$(".twig-datatable").dataTable({
  aoColumns: [
    //highlight_id
    {
      bVisible: false,
    },
    //timeslot
    {
      bVisible: false,
    },
    //start_time
    {
      sTitle: "Start Time",
    },
    // end_time
    {
      sTitle: "End Time"
    },
    // notes
    {
      sTitle: "Notes",
    },
    // autoviz_clip
    {
      bVisible: false,
    },
    // loggerlink
    {
      sTitle: "Audio Clip"
    },
    // autovizlink
    {
      sTitle: "Video Clip",
    }
  ],
  bSort: false,
  bPaginate: false,
});
