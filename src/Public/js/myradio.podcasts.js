/* global moment */
$(".twig-datatable").dataTable({
  "aoColumns": [
    //podcast_id
    {
      "bVisible": false
    },
    //title
    {
      "sTitle": "Title",
    },
    //description
    {
      "sTitle": "Description",
    },
    //status
    {
      "sTitle": "Status"
    },
    //time
    {
      "sTitle": "Time Submitted"
    },
    // uri
    {
      "bVisible": false
    },
    //editlink
    {
      "sTitle": "Edit",
      "bSortable": false,
      "bVisible": true
    },
    //micrositelink
    {
      "sTitle": "Site",
      "bSortable": false,
      "bVisible": true
    },
    //suspendlink
    {
      "sTitle": "",
      "bVisible": true,
      "bSortable": false
    },
    //photo
    {
      "bVisible": false
    }
  ],
  "bPaginate": true,
  "aaSorting": [[4, "desc"]] //sort by submitted
});

$(".column-time").text(function (i, old) {
  return moment.unix(old).format("DD/MM/YYYY HH:mm");
});
