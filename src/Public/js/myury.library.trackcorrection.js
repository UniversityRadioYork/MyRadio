$('#trackcorrect_fpreview').attr('src', myury.makeURL('NIPSWeb', 'secure_play', {
    trackid: parseInt({{correction.trackid}}),
    recordid: parseInt({{correction.album.recordid}})
  }));