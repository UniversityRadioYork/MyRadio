var date = new Date();
$("#shortcut-lastday").on(
  "click",
  function () {
    $("#rangesel-endtime").val(date.toUTCString());
    date.setDate(date.getDate() - 1);
    $("#rangesel-starttime").val(date.toUTCString());
    $("#timeselfrm").submit();
  }
);
$("#shortcut-lastweek").on(
  "click",
  function () {
    $("#rangesel-endtime").val(date.toUTCString());
    date.setDate(date.getDate() - 7);
    $("#rangesel-starttime").val(date.toUTCString());
    $("#timeselfrm").submit();
  }
);
$("#shortcut-lastfortnight").on(
  "click",
  function () {
    $("#rangesel-endtime").val(date.toUTCString());
    date.setDate(date.getDate() - 14);
    $("#rangesel-starttime").val(date.toUTCString());
    $("#timeselfrm").submit();
  }
);
$("#shortcut-lastmonth").on(
  "click",
  function () {
    $("#rangesel-endtime").val(date.toUTCString());
    date.setDate(date.getDate() - 28);
    $("#rangesel-starttime").val(date.toUTCString());
    $("#timeselfrm").submit();
  }
);
