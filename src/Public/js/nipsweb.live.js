/**
 * This file contains the necessary functions for the NIPSWeb BAPS Client
 */
window.NIPSWeb = {
  //Stores the change queue pointer for this object
  changeQueue: $({}),
  ajaxQueue: $({}),
  //Stores whether this Show is writable. If set to false before
  //initialising, dragdrop/saving will not be enabled.
  /**
   * @todo BRA is currently read only
   */
  writable: false,
  server: mConfig.bra_uri,
  user: mConfig.bra_user,
  pass: mConfig.bra_pass,
  audioNodes: [],
  /**
   * @todo Rewrite for BRA
   * @param {type} e
   * @param {type} ui
   * @returns {undefined}
   */
  calcChanges: function(e, ui) {
    return;
  },
  /**
   * Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
   * ops: JSONON to send
   * addOp: If true, there has been an add operation. We currently make these syncronous.
   * pNext: Optional. Parent queue to process on completion.
   * @todo Rewrite for BRA
   */
  shipChanges: function(ops, addOp, pNext) {
    return;
  },
  /**
   * Initialises with latest data from BRA
   */
  initData: function() {
    options = NIPSWeb.baseReq('channels');
    options.success = NIPSWeb.drawChannels;
    $.ajax(options);
  },
  /**
   * Uses a BRA data response to redraw channel content.
   * @param json data
   * @returns {undefined}
   */
  drawChannels: function(data) {
    for (i in data.channels) {
      
    }
  },
  baseReq: function(command) {
    return {
      url: NIPSWeb.server+'/'+command,
      accept: 'application/json',
      beforeSend: function(request) {
        b64 = window.btoa(NIPSWeb.user+':'+NIPSWeb.pass);
        request.withCredentials = true;
        request.setRequestHeader("Authorization", "Basic "+b64);
      },
      cache: false,
      dataType: 'json',
      password: NIPSWeb.pass,
      username: NIPSWeb.user
    };
  }
};

manualSeek = true;
window.debug = true;

function initialiseUI() {
  // Setup UI elements
  $('button.play').button({
    icons: {
      primary: 'ui-icon-play'
    },
    text: false
  }).addClass('ui-state-disabled');
  $('button.pause').button({
    icons: {
      primary: 'ui-icon-pause'
    },
    text: false
  }).addClass('ui-state-disabled');
  $('button.stop').button({
    icons: {
      primary: 'ui-icon-stop'
    },
    text: false
  }).addClass('ui-state-disabled');

  if (NIPSWeb.writable) {
    $('ul.baps-channel').sortable({
      //connectWith allows drag and drop between the channels
      connectWith: 'ul.baps-channel',
      //A distance dragged of 15 before entering the dragging state
      //Prevents accidentally dragging when clicking
      distance: 15,
      //Adds a placeholder highlight where the item will be dropped
      placeholder: "ui-state-highlight",
      //Remove the "selected" class from the item - prevent multiple selected items in a channel
      //Also activate the next/previous item, if there is one
      start: function(e, ui) {
        if (ui.item.hasClass('selected')) {
          ui.item.removeClass('selected');
          if (ui.item.attr('nextSelect') != null)
            $('#' + ui.item.attr('nextSelect')).click();
        }
        ui.item.nextSelect = null;
      },
      stop: function(e, ui) {
        /**
         * Update the channel timers
         */
        NIPSWeb.calcChanges(e, ui);

      }

    });
  }

  registerItemClicks();
  setupGenericListeners();
  configureContextMenus();
}

function configureContextMenus() {
  $(document).contextmenu({
    delegate: 'ul.baps-channel',
    menu: [
      {title: "Delete Item", cmd: "itemDel", uiIcon: ""},
      {title: "Automatic Advance", cmd: "autoAdv", uiIcon: ""},
      {title: "Play On Load", cmd: "autoPlay", uiIcon: ""},
      {title: "Repeat None", cmd: "rptNone", uiIcon: "ui-icon-check"},
      {title: "Repeat One", cmd: "rptOne", uiIcon: ""},
      {title: "Repeat All", cmd: "rptAll", uiIcon: ""},
      {title: "Reset Channel", cmd: "reset", uiIcon: "ui-icon-trash"},
      {title: "Save Channel As...", cmd: "savePreset", uiIcon: "ui-icon-disk"},
      {title: "Load Channel", cmd: "loadPreset", uiIcon: "ui-icon-folder-open"}
    ],
    position: {my: "left top", at: "center"},
    beforeOpen: function(event) {
      var ul = ($(event.relatedTarget).is('li') ? $(event.relatedTarget).parent('ul') : event.relatedTarget);
      console.log(ul);
      //Enable/disable Delete item depending on if it's an li - lis are items, ul would be container
      $(document).contextmenu("enableEntry", "itemDel", $(event.relatedTarget).is('li'));
      $(document).contextmenu("setEntry", "autoAdv",
              {title: "Automatic Advance", cmd: "autoAdv", uiIcon: $(ul).attr('autoadvance') == 1 ? "ui-icon-check" : ""}),
      $(document).contextmenu("setEntry", "autoPlay",
              {title: "Play On Load", cmd: "autoPlay", uiIcon: $(ul).attr('playonload') == 1 ? "ui-icon-check" : ""})
    },
    show: {effect: "slideDown", duration: 100}
  });

  $(document).bind("contextmenuselect", function(event, ui) {
    var menuId = ui.item.find(">a").attr("href"),
            target = event.relatedTarget,
            ul = ($(event.relatedTarget).is('li') ? $(event.relatedTarget).parent('ul') : event.relatedTarget);

    if (menuId === "#autoAdv") {
      if ($(ul).attr('autoadvance') == 1)
        $(ul).attr('autoadvance', 0);
      else
        $(ul).attr('autoadvance', 1);
    } else if (menuId === "#autoPlay") {
      if ($(ul).attr('playonload') == 1)
        $(ul).attr('playonload', 0);
      else
        $(ul).attr('playonload', 1);
    }
    console.log("select " + menuId + " on " + $(target).attr('id'));
  });
}

function initialisePlayer(channel) {

  if (channel == 0)
    channel = 'res';

  $("#progress-bar-" + channel).slider({
    range: "min",
    value: 0,
    min: 0
  });

  if (channel === 'res') {
    var a = new Audio();

    $(a).on('ended', function() {
      if ($('#baps-channel-' + channel).attr('autoadvance') == 1) {
        $('#' + $('#baps-channel-' + channel + ' li.selected').removeClass('selected').attr('nextselect')).click();
      }
    });

    NIPSWeb.audioNodes[(channel === 'res') ? 0 : channel] = a;
  }

  setupListeners(channel);
}

// Initialises Variables for functions - This is called at the start of each function
function playerVariables(channel) {
  if (channel === 'res')
    channel = 0;
  return NIPSWeb.audioNodes[channel];
}



/**
 * Player Functions
 * @param channel 1, 2, 3 or res
 */
// Loads the selected track into the player for the designated channel
function previewLoad(channel) {
  if (channel !== 'res') {
    
  } else {
    $('#ch' + channel + '-play, #ch' + channel + '-pause, #ch' + channel + '-stop').addClass('ui-state-disabled');
    //Find the active track for this channel
    var audioid = $('#baps-channel-' + channel + ' li.selected').attr('id');
    var data = getRecTrackFromID(audioid);
    var type = $('#baps-channel-' + channel + ' li.selected').attr('type');
    if (type === 'central') {
      //Central Database Track
      $.ajax({
        url: myury.makeURL('NIPSWeb', 'create_token'),
        type: 'post',
        data: 'trackid=' + data[1] + '&recordid=' + data[0],
        success: function() {
          if (playerVariables(channel).canPlayType('audio/mpeg')) {
            playerVariables(channel).type = 'audio/mpeg';
            playerVariables(channel).src = mConfig.base_url + '?module=NIPSWeb&action=secure_play&recordid=' + data[0] + '&trackid=' + data[1];
          } else if (playerVariables(channel).canPlayType('audio/ogg')) {
            playerVariables(channel).type = 'audio/ogg';
            playerVariables(channel).src = mConfig.base_url + '?module=NIPSWeb&action=secure_play&ogg=true&recordid=' + data[0] + '&trackid=' + data[1];
          } else {
            alert('Sorry, you need to use a modern browser to use Track Preview.');
          }
          $(playerVariables(channel)).on("canplay", function() {
            $('#ch' + channel + '-play').removeClass('ui-state-disabled');
            /**
             * Briefly play the track once it has started loading
             * Workaround for http://code.google.com/p/chromium/issues/detail?id=111281
             */
            this.play();
            var that = this; //Hack so that timeout is in context
            this.volume = 0;
            setTimeout(function() {
              that.pause();
              that.volume = 1;
              if ($('#baps-channel-' + channel).attr('playonload') == 1) {
                that.play();
              }
            }, 10);
          });
        }
      });
    } else if (type === 'aux') {
      playerVariables(channel).src = mConfig.base_url + '?module=NIPSWeb&action=managed_play&managedid=' + $('#' + audioid).attr('managedid');
      $(playerVariables(channel)).on('canplay', function() {
        $('#ch' + channel + '-play').removeClass('ui-state-disabled');
      });
    }
  }
}
// Plays the loaded track from the designated channel
function previewPlay(channel) {
  var audio = playerVariables(channel);

  audio.play();
  playing(channel);
}
// Pauses the currently playing track from the designated channel
function previewPause(channel) {
  var audio = playerVariables(channel);

  if (audio.readyState) {
    if (audio.paused) {
      audio.play();
      playing(channel);
    }
    else {
      audio.pause();
      pausing(channel);
    }
  }
}
// Stops the currently playing track from the designated channel
function previewStop(channel) {
  var audio = playerVariables(channel);

  audio.pause();
  audio.currentTime = 0;
  stopping(channel);
}

/**
 * UI Functions
 * @param channel 1, 2, 3 or res
 */
function playing(channel) {
  $('#ch' + channel + '-play').addClass('ui-state-active').removeClass('ui-state-disabled');
  $('#ch' + channel + '-pause').removeClass('ui-state-disabled');
  $('#ch' + channel + '-stop').removeClass('ui-state-disabled');
}
function pausing(channel) {
  $('#ch' + channel + '-play');
  $('#ch' + channel + '-pause').addClass('ui-state-active');
  $('#ch' + channel + '-stop');
}
function stopping(channel) {
  $('#ch' + channel + '-play').removeClass('ui-state-active');
  $('#ch' + channel + '-pause').removeClass('ui-state-active').addClass('ui-state-disabled');
  $('#ch' + channel + '-stop').addClass('ui-state-disabled');
}

// Gets the duration of the current track in channel
function getDuration(channel) {
  var audio = playerVariables(channel);

  var duration = audio.duration; //Get the duration of the track
  //duration returns a value in seconds. Convert to minutes+seconds, pad zeros where appropriate.
  var mindur = timeMins(duration);
  var secdur = timeSecs(duration);
  // Sets the duration label
  $('#ch' + channel + '-duration').html(mindur + ':' + secdur);
}
// Gets the time of the current track in channel
function getTime(channel) {
  var audio = playerVariables(channel);

  var elapsed = audio.currentTime; //Get the current playing position of the track
  //currentTime returns a value in seconds. Convert to minutes+seconds, pad zeros where appropriate.
  var minelap = timeMins(elapsed);
  var secelap = timeSecs(elapsed);
  // Sets the current time label
  $('#ch' + channel + '-elapsed').html(minelap + ':' + secelap);
}


/**
 * Event Listeners
 */

// Sets up generic listeners
function setupGenericListeners() {
  // Setup key bindings
  var keys = {
    F1: 112,
    F2: 113,
    F3: 114,
    F4: 115,
    F5: 116,
    F6: 117,
    F7: 118,
    F8: 119,
    F9: 120,
    F10: 121,
    F11: 122
  };

  // Sets up key press triggers
  $(document).on('keydown.bapscontrol', function(e) {
    var trigger = false;
    switch (e.which) {
      case keys.F1:
        //Play channel 1
        previewPlay(1);
        trigger = true;
        break;
      case keys.F2:
        previewPause(1);
        trigger = true;
        break;
      case keys.F3:
        previewStop(1);
        trigger = true;
        break;
      case keys.F5:
        //Play channel 2
        previewPlay(2);
        trigger = true;
        break;
      case keys.F6:
        previewPause(2);
        trigger = true;
        break;
      case keys.F7:
        previewStop(2);
        trigger = true;
        break;
      case keys.F9:
        //Play channel 3
        previewPlay(3);
        trigger = true;
        break;
      case keys.F10:
        previewPause(3);
        trigger = true;
        break;
      case keys.F11:
        previewStop(3);
        trigger = true;
        break;
    }
    if (trigger) {
      e.stopPropagation();
      e.preventDefault();
      return false;
    }
  });
}
// Sets up listeners per channel
function setupListeners(channel) {
  if (channel !== 'res') {

  } else {
    /**
     * Use local play for Library pane
     */
    var audio = playerVariables(channel);
    $(playerVariables(channel)).on('timeupdate', function() {
      getTime(channel);
      $('#progress-bar-' + channel).slider({value: audio.currentTime});
      //A mouse-over click doesn't set this properly on play
      if (audio.currentTime > 0.1)
        $('#ch' + channel + '-play').addClass('ui-state-active');
    });
    $('#progress-bar-' + channel).slider({
      value: 0,
      step: 0.01,
      orientation: "horizontal",
      range: "min",
      max: audio.duration,
      animate: true,
      stop: function(e, ui) {
        audio.currentTime = ui.value;
      }
    });
    $(playerVariables(channel)).on('durationchange', function() {
      getDuration(channel);
      $('#progress-bar-' + channel).slider({max: audio.duration});
    });

    $("#progress-bar-" + channel).on("slide", function(event, ui) {
      $('#previewer' + channel).currentTime = ui.value;
    });
  }
}

/**
 * Generic Functions
 */
/**
 * Returns number of minutes (zero padded) from a time in seconds
 * @param time in seconds
 */
function timeMins(time) {
  var mins = Math.floor(time / 60) + "";
  if (mins.length < 2) {
    mins = '0' + mins;
  }
  return mins;
}
// Returns number of seconds (zero padded) less than mins from a time in seconds
function timeSecs(time) {
  var secs = Math.floor(time % 60) + "";
  if (secs.length < 2) {
    secs = '0' + secs;
  }
  return secs;
}

function registerItemClicks() {
  // Used by dragdrop - enables the selected item to move down on drag/drop
  $('ul.baps-channel li').off('mousedown.predrag').on('mousedown.predrag', function(e) {
    $(this).attr('nextSelect',
            typeof $(this).next().attr('id') !== 'undefined' ? $(this).next().attr('id') : $(this).prev().attr('id'));
  });
  $('ul.baps-channel li').off('click.playactivator').on('click.playactivator', function(e) {
    if ($(this).hasClass('unclean')) {
      //This track may have naughty words, but don't block selection
      $('#footer-tips').html('This track is explicit. Do not broadcast before 9pm.').addClass('ui-state-error').show();
      setTimeout("$('#footer-tips').removeClass('ui-state-error').fadeOut();", 5000);
    }
    //Set this track as the active file for this channel
    //First, we need to remove the active class for any other file in the channel
    $(this).parent('ul').children().removeClass('selected');
    $(this).addClass('selected');
    previewLoad($(this).parent('.baps-channel').attr('channel'));
  });
  $('ul.baps-channel').tooltip({
    items: "li",
    show: {delay: 500},
    hide: false,
    content: function() {
      return $(this).html() + ($(this).attr('length') == null ? '' : ' (' + $(this).attr('length') + ')');
    }
  });
}
function getRecTrackFromID(id) {
  id = id.split('-');

  var data = [];
  data[0] = id[0];
  data[1] = id[1];

  for (i = 2; i < id.length; i++) {
    data[1] = data[1] + '-' + id[i];
  }

  return data;
}
