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
    //submitted
    {
      "sTitle": "Time Submitted"
    },
    // file
    {
      "bVisible": false
    },
    // photo
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
      "bVisible": true
    }
  ],
  "bPaginate": true,
  "aaSorting": [[4, "desc"]] //sort by submitted
});

$(".column-time").text(function (i, old) {
  return moment.unix(old).format("DD/MM/YYYY HH:mm");
});
