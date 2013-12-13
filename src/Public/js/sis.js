
/* Selector */
    var selectorLastMod = 0;
    var updateSelector = function(data) {
        console.log('updateSelector triggered by server');
        
        selectorLocked = data['lock'];
        selectorPower = {
          's1': data['s1power'],
          's2': data['s2power'],
          's4': data['s4power']
        };
              
        if (!data['s1power']) {
          $('#s1').attr('title', 'Studio 1 Powered Off')
          .removeClass('s1on s1off poweredon')
          .addClass('poweredoff');
        }
        else {
          $('#s1').attr('title', 'Studio 1').removeClass('poweredoff').addClass('poweredon');

          if (data['studio'] == 1) {
            $('#s1').removeClass('s1off').addClass('s1on');
          }
          else {
            $('#s1').removeClass('s1on').addClass('s1off');
          }
        }

        if (!data['s2power']) {
          $('#s2').attr('title', 'Studio 2 Powered Off').removeClass('s2on s2off poweredon').addClass('poweredoff');
        }
        else {
          $('#s2').attr('title', 'Studio 2').removeClass('poweredoff').addClass('poweredon');
          if (data['studio'] == 2) {
            $('#s2').removeClass('s2off').addClass('s2on');
          }
          else {
            $('#s2').removeClass('s2on').addClass('s2off');
          }
        }
        if (data['studio'] == 3) {
          $('#s3').removeClass('s3off').addClass('s3on');
        }
        else {
          $('#s3').removeClass('s3on').addClass('s3off');
        }
              
        switch(data['studio']) {
          case 1:
          case 2: s = 'Studio '+data['studio']+' On Air'; break;
          case 3: s = 'Jukebox On Air'; break;
          case 4: s = 'Outside Broadcast On Air'; break;
          default:  s = 'Source '+data['studio']+' On Air'; break;
        }
        if (data['lock'] != '0') s = s + '<small> &mdash; Locked</small>';
          $('span#onair').html(s);

        //Update the lastmod time
        selectorLastMod = data['lastmod'];
        //Update the server's lastmod parameter
        server.register_param('selector_lastmod', selectorLastMod);
    };

    function selectStudio(s) {
        if ((s == 1) && (selectorPower['s1'] == '0')) return;
        if ((s == 2) && (selectorPower['s2'] == '0')) return;
        if ((s == 4) && (selectorPower['s4'] == '0')) return;
        if (selectorLocked != '0') { alert('Could not change studio.\nStudio selector is currently locked out.'); return; }
              
        $.get(myury.makeURL('SIS', 'selector.set'), {src: s}, function(data) {
          if (data['error'] == 'locked') { $('<div title="Selector Error">Could not change studio; studio selector is currently locked out.</div>').dialog({modal: true}); return; }
          if (data['error']) { $('<div title="Selector Error">'+data['error']+'</div>').dialog({modal: true}); return; }
          updateSelector(data);
        });
    }


/* Stats */
    function updateStats() {
        if (!$('div#plugin_body_stats').is(':visible')) return;
        $('img#urystats').attr('src', myury.makeURL('SIS','stats.graph',{'date':(new Date().valueOf())}));
    }


/* Webcam */
    var wcCurrentCam = 0;

    function setCam(newcam) {
      if (newcam === wcCurrentCam) {
        console.log('Ignoring setCam - Already current webcam.');
        return;
      }
      console.log('Updating webcam to '+newcam);
      $.get(myury.makeURL('SIS', 'webcam.set'), {'src':newcam, 'url':null});
    }

    var updateWebcam = function(data) {
      console.log('updateWebcam triggered by server');
      
      $('button[id^=setactive]:not([id=setactive'+ data['current']+'])').button("enable");
      $('button#setactive'+ data['current']).button("disable");
            
      //Only show side images for not-active cameras, or all for jukebox
      if ( data['current'] === 1) {
        $('#plugin_body_webcam figure').show();
      } else {
        $('#plugin_body_webcam figure').hide();
        $('#plugin_body_webcam figure:not(#webcam-stream-'+data['current']+')').show();
      }

      //Update current webcam data
      wcCurrentCam = data['current'];
      //Update the server's lastmod parameter
      server.register_param('webcam_id', wcCurrentCam);
    };


/* Messages */
    var highest_message_id = 0;
                                
    var updateMessages = function(data) {
      console.log('updateMessages triggered by server');
      for (var i in data) {
        //Add the content dialog div
        $('#message-data').append('<div id="mc'+data[i]['id']+'">'+data[i]['body']+'<footer></footer></div>');
        for (var l in data[i]['location']) {
          $('#mc'+data[i]['id']+' footer').append('<br>'+data[i]['location'][l]);
        }
        //Set some of the variables
        var img = "<img src='"+mConfig.base_url+"img/sis/"+data[i]['type']+".png' />";
        var msgdate = new Date(data[i]['time']*1000);
        var mins = msgdate.getMinutes(); if (mins < 10) mins = "0" + mins;
        var time = msgdate.getHours()+':'+mins+' '+msgdate.getDate()+'/'+(msgdate.getMonth()+1);
        var read = "";
        var classes = "";
        if (data[i]['read'] === false) {
          classes = classes + " unread";
          server.incrementUnreadEvents();
        }
        //Add the new row to the top of the messages table
        $('#messages table').prepend(
        '<tr class="td-msgitem'+classes+'" id="m'+data[i]['id']+'"><td>'+img+'</td><td>'+data[i]['title']+'</td><td>'+time+'</td></tr>');
        //Add the onclick handler for the new row
        $('#messages table tr:first').click(function() {
          var id = $(this).attr('id').replace('m', '');
          if ($(this).hasClass('unread')) {
            console.log('Marking message '+id+' as read');
            //This is the first time the message has been opened. Mark as read
            $.ajax({
              url: myury.makeURL('SIS', 'messages.markread', {'id': id})
            });
            server.decrementUnreadEvents();
            $(this).removeClass('unread');
          }
          $('.ui-dialog-content').dialog('close');
          $('#mc'+id).dialog({ width:550 });
        });
        //Increment the highest message id, if necessary
        highest_message_id = (highest_message_id < data[i]['id']) ? data[i]['id'] : highest_message_id;
      }
      //Update the server's highest id parameter
      server.register_param('messages_highest_id', highest_message_id);
    };


/* News */
    var news_url = myury.makeURL('SIS','news')+"NewsRoom.aspx";


/* Schedule */
    function updateSchedule() {
        $.getJSON(myury.makeURL('SIS','schedule.get'), function(data) {
            var currentStart = new Date(data.current.start_time*1000);
            var currentEnd = new Date(data.current.end_time*1000);
            $("#schedule-onair .showTime").text(formatTime(currentStart)+' - '+formatTime(currentEnd));
            $("#schedule-onair .showName").text(data.current.title);
            $("#schedule-onair .showPeople").text(data.current.presenters);
            $("#schedule-onair .showDesc").html(data.current.desc);

            $('#schedule-next').html('');
            data.next.forEach(function(e, i, data) {
                $('#schedule-next').append('<div id="schedule-item-'+i+'" class="schedule-item"> \
                    <hgroup class="clearfix"> \
                        <h3 class="showTime"></h3> \
                        <h3 class="showName"></h3> \
                        <h5 class="showPeople"></h5> \
                    </hgroup> \
                <div class="showDesc"></div></div>');
                var nextStart = new Date(data[i].start_time*1000);
                var nextEnd = new Date(data[i].end_time*1000);
                $("#schedule-item-"+i+" .showTime").text(formatTime(nextStart)+' - '+formatTime(nextEnd));
                $("#schedule-item-"+i+" .showName").text(data[i].title);
                $("#schedule-item-"+i+" .showPeople").text(data[i].presenters);
                $("#schedule-item-"+i+" .showDesc").html(data[i].desc);
            });
            $('.schedule-item:not(:first-child)').prepend('<hr>');
        });
    }

    function formatTime(d) {
        var HH = d.getHours();
        if ( HH < 10 ) HH = '0' + HH;

        var MM = d.getMinutes();
        if ( MM < 10 ) MM = '0' + MM;

        if (isNaN(HH) || isNaN(MM)) {
            return "";
        }
        return HH+':'+MM;
    }


/* Tracklist */
    var tracklist_highest_id = 0;
    
    var updateTrackListing = function(data) {
        console.log('updateTrackListing triggered by server');
        for (var i in data) {
            $('#tracklist-data').append('<div id="delsure'+data[i]['id']+'" title="Delete Track?">Are you sure you want to delete this track?</div>');

            var trackDate = new Date(data[i]['playtime']*1000);
            var secs = trackDate.getSeconds(); if (secs < 10) secs = "0" + secs;
            var mins = trackDate.getMinutes(); if (mins < 10) mins = "0" + mins;
            var month = trackDate.getMonth()+1; if (month < 10) month = "0" + month;
            var time = trackDate.getHours()+':'+mins+':'+secs+' '+trackDate.getDate()+'/'+month;
            //Add the new row to the top of the tracklist table
            $('#tracklist table').prepend(
                '<tr class="tlist-item" id="t'+data[i]['id']+'"> \
                    <td>'+time+'</td> \
                    <td>'+data[i]['title']+'</td> \
                    <td>'+data[i]['artist']+'</td> \
                    <td>'+data[i]['album']+'</td> \
                    <td class="delbutton"><span class="ui-icon ui-icon-trash" style="display:inline-block"></span></td></tr>');
            
            tracklist_highest_id = (tracklist_highest_id < data[i]['id']) ? data[i]['id'] : tracklist_highest_id;

            $('#t'+data[i]['id']+' .delbutton').click(function() {
                var id = $(this).parent().attr('id').replace('t', '');
                $('#delsure'+id).dialog({
                    resizable: false,
                    modal: true,
                    buttons: {
                        "Yes": function(){
                                console.log('Marking tracklog '+id+' as deleted ');
                                $.ajax({
                                    url: myury.makeURL('SIS','tracklist.delTrack', {'id': id})
                                });
                                $('#t'+id).hide();
                                $('#delsure'+id).dialog("close");
                        
                        },
                        Cancel: function(){
                                $('#delsure'+id).dialog("close");
                        }
                    }
                });
            });
        
            server.register_param('tracklist_highest_id', tracklist_highest_id);
        }
    };

    function submitTrackCancel() {
        $("#trackpick-tname").val("");
        $("#trackpick-album").val("");
        $("#trackpick-artist").val("");
        $("#tracklist-insert").dialog("close");
        $("#tracklist-insert-check").dialog("close");
    }

    function submitTrackNoLib() {
        var tname = $("#trackpick-tname").val();
        var album = $("#trackpick-album").val();
        var artist = $("#trackpick-artist").val();
        $.ajax({
            url: myury.makeURL('SIS','tracklist.checkTrack'),
            data: {tname: tname, album: album, artist: artist, where: "notrec"},
            type: 'get',
            dataType: 'json',
            success: function(output) {
                console.log(output);
                submitTrackCancel();
            }
        });
    }

    function submitTrack() {
        //  event.preventDefault();
        var tname = $("#trackpick-tname").val();
        var album = $("#trackpick-album").val();
        var artist = $("#trackpick-artist").val();
        $.ajax({
                url: myury.makeURL('SIS','tracklist.checkTrack'),
            data: {tname: tname, album: album, artist: artist, where: 'rec'},
            type: 'get',
            dataType: 'json',
            success: function(output){
                console.log(output);
                if(output.return == '1') {
                    $("#tracklist-insert-check").dialog("open");
                    $("#warntitle").text(tname);
                    $("#warnartist").text(artist);
                }
                if(output.return == '2') {
                    
                }
                if(output.return == '0') {
                    submitTrackCancel();
                }
            }
        });
        return false;
    }






$(document).ready(function() {
	$("#switcher").themeswitcher({
                    imgpath: "",
                    themepath: "//ury.org.uk/portal/css",
                    jqueryuiversion: "",
                    rounded: false,
                    themes: [
                        {
                            title: "MyURY",
                            name: "ury-purple",
                        },
                        {
                            title: "SIS2",
                            name: "ury-red",
                        }
                    ]
            });


    // Selector
    server.register_callback(updateSelector, 'selector');
    server.register_param('selector_lastmod', selectorLastMod);
    $('#s1').click(function(){selectStudio(1);});
    $('#s2').click(function(){selectStudio(2);});
    $('#s3').click(function(){selectStudio(3);});
    $('#s4').click(function(){selectStudio(4);});


    // Stats
    setInterval('updateStats()', 20000);


    // Webcam
    server.register_callback(updateWebcam, 'webcam');
    server.register_param('webcam_id', wcCurrentCam);

    $('button#setactive0').click(function(){setCam(0);});
    $('button#setactive2').click(function(){setCam(2);});
    $('button#setactive3').click(function(){setCam(3);});
    $('button#setactive4').click(function(){setCam(4);});

    $('button#setactive'+wcCurrentCam).button("disable");
      
    $('button#setactive6').click(function(){$('div#customcam').toggle('blind',200);});
    $('button#setcustomcam').click(function(){setCam(6,$('input#camurl').val());});


    // Help
    $('#hide-help').button().click(function() {
        $.ajax({
          url: myury.makeURL('SIS','help.hide'),
          success: function() {
            $('#gs_disabled').show();
          }
        });
      });


    // Messages
    server.register_callback(updateMessages, 'messages');
    server.register_param('messages_highest_id', highest_message_id);


    // News
    $(window).load(function() {
            setTimeout(function(){
                $('#ury-irn').attr('src', news_url);
            }, 2000);
        });


    // Schedule
    setInterval('updateSchedule()', 60000);
    updateSchedule();


    // Tracklist
    server.register_callback(updateTrackListing, 'tracklist');
    server.register_param('tracklist_highest_id', tracklist_highest_id);
    $('#tracklist-insert').dialog({
        autoOpen: false,
        height: 420,
        width: 350,
        modal: true,
        buttons: {
            "Submit": function() {
                submitTrack();
            },
        },
        close: function() {
            submitTrackCancel();
        }
    });

    $('#tracklist-insert-check').dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "Yes": function() {
                submitTrackNoLib();
            },
            "No": function() {
                $(this).dialog("close");
            }
        }
    });

    $( "#trackpick-artist" ).autocomplete({
        source: function (request, response) {
            var tname = $("#trackpick-tname").val();
            var album = $("#trackpick-album").val();
            var artist = $("#trackpick-artist").val();
            var box = "artist";
            $.getJSON(myury.makeURL('SIS','tracklist.findTrack'),
            {
                tname: tname,
                album: album,
                artist: artist,
                box: box
            }, response);
        }
    });

    $( "#trackpick-album" ).autocomplete({
        source: function (request, response) {
            var tname = $("#trackpick-tname").val();
            var album = $("#trackpick-album").val();
            var artist = $("#trackpick-artist").val();
            var box = "album";
            $.getJSON(myury.makeURL('SIS','tracklist.findTrack'),
            {
                tname: tname,
                album: album,
                artist: artist,
                box: box
            }, response);
        }
    });

    $( "#trackpick-tname" ).autocomplete({
        source: function (request, response) {
            var tname = $("#trackpick-tname").val();
            var album = $("#trackpick-album").val();
            var artist = $("#trackpick-artist").val();
            var box = "tname";
            $.getJSON(myury.makeURL('SIS','tracklist.findTrack'),
            {
                tname: tname,
                album: album,
                artist: artist,
                box: box
            }, response);
        }
    });

    $('#add-track').click(function() {
        $('#tracklist-insert').dialog("open");
    });


});