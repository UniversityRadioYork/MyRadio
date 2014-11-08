function iTones_refreshLock() {
  $.ajax({
    url: myury.makeURL('iTones', 'refreshLock'),
    type: 'POST',
    data: {playlistid: $('#itones_playlistedit-myradiofrmedid').val()}
  });
}

$(document).ready(function() {
  setInterval(iTones_refreshLock, 15000);
});
