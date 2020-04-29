$(".twig-datatable").dataTable({
  "aaSorting": [[2, "desc"]],
  "aoColumns": [
    //quoteID
    {
      "bVisible": false
    },
    //source
    {
      "bVisible": false
    },
    // source_name
    {
      "sTitle": "Source",
      "sClass": "left"
    },
    //date
    {
      "sTitle": "Date",
      "aDataSort": [ 0 ]
    },
    //text
    {
      "bVisible": false
    },
    //html
    {
      "sTitle": "Quote"
    },
    //editlink
    {
      "sTitle": "Edit",
      "bSortable": false,
      "bVisible": true
    }
  ],
  "bPaginate": true
});
