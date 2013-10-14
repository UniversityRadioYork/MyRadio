$('.twig-datatable').dataTable({
  "aoColumns": [
  //memberid
  {
    bVisible: false
  },
  //locked
  {
    bVisible: false
  },
  //college
  {
    bVisible: false
  },
  //fname
  {
    "sTitle": "First Name"
  },
  //surname
  {
    sTitle: "Last Name"
  },
  //sex
  {
    "bVisible": false
  },
  //receive_email
  {
    bVisible: false
  },
  //public_email
  {
    sTitle: "Email"
  },
  //url
  {
    bVisible: false
  }
  ]
}
);