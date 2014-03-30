/**
 * This file contains the necessary functions for the NIPSWeb BAPS Live Client
 */
window.NIPSWeb = {
    //Key bindings
    keys: {
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
    },
    //Stores the change queue pointer for this object
    changeQueue: $({}),
    ajaxQueue: $({}),
    //Stores an internal ID counter - since BAPSs are somewhat... variable
    idCounter: 0,
    //Store the number of times the WebSocket has needed to be reset.
    //Suggests falling back to REST mode after multiple failures.
    resetCounter: 0,
    resetLimit: 10,
    //The reference to the WebSocket connection check interval
    streamChecker: null,
    //Stores whether this Show is writable. If set to false before
    //initialising, dragdrop/saving will not be enabled.
    /**
     * @todo Make this variable do something
     */
    writable: false,
    server: mConfig.bra_uri,
    user: mConfig.bra_user,
    pass: mConfig.bra_pass,
    audioNodes: [],
    braStream: null,

    /**
     * Initialises with latest data from BRA using POST.
     */
    initData: function() {
        //First we get the contents of all playlists
        options = NIPSWeb.baseReq('playlists');
        options.done = NIPSWeb.drawChannels;
        $.ajax(options);
        //Now we get the status of the players
        options = NIPSWeb.baseReq('players');
        options.done = NIPSWeb.updatePlayers;
        $.ajax(options);
    },
    /**
     * Initialises the connection to the BRA WebSocket Stream
     */
    initStream: function() {
        NIPSWeb.resetCounter++;
        if (NIPSWeb.braStream) {
            NIPSWeb.braStream.close();
        }
        NIPSWeb.braStream = new WebSocket(
                "wss://" + NIPSWeb.server + "/stream/"
                );
        NIPSWeb.braStream.onopen = function(e) {
            NIPSWeb.braStream.send('{"type":"auth","username":"' + NIPSWeb.user + '","password":"' + NIPSWeb.pass + '"}');
            //Populate with initial data
            //Don't do this first - what happens to events in between?
            NIPSWeb.initData();
        };
        NIPSWeb.braStream.onmessage = function(data) {
            NIPSWeb.processStream(data);
        };
        /**
         * Automatically recover the WebSocket connection if something goes fishy.
         */
        if (!NIPSWeb.streamChecker) {
            NIPSWeb.streamChecker = setInterval(function() {
                if (NIPSWeb.braStream.readyState !== 1) {
                    if (NIPSWeb.resetCounter == NIPSWeb.resetLimit) {
                        console.log(NIPSWeb.resetLimit + ' WebSocket retries exceeded. Suggesting REST fallback.');
                        $('<div></div>').attr('title', 'Wonky Connection').attr('id', 'error-dialog')
                                .append('<p>Sorry, I seem to be having some trouble connecting to the server right now. Would you like me to try a slower connection, or keep trying with the faster one?</p>')
                                .dialog({
                                    modal: true,
                                    buttons: {
                                        'Try a Slower Connection': function() {
                                            NIPSWeb.initFallback(0);
                                            $(this).dialog("close");
                                        },
                                        'Keep Trying': function() {
                                            NIPSWeb.initStream();
                                            $('#init-overlay-fallback').show();
                                            $(this).dialog("close");
                                        }
                                    },
                                    width: 600,
                                    closeOnEscape: false,
                                    open: function(e, ui) {
                                        $(".ui-dialog-titlebar-close", ui.dialog).hide()
                                    }
                                });
                        $('#init-overlay').hide();
                        clearInterval(NIPSWeb.streamChecker);
                        NIPSWeb.streamChecker = null;
                    } else {
                        //Connection hasn't happened yet, or is dead
                        console.log('WebSocket connection lost. Reconnecting...');
                        $('#init-overlay').show();
                        NIPSWeb.initStream();
                    }
                }
            }, 1500);
        }
    },
    /**
     * If WebSockets don't seem to be working, this uses REST polling to update
     * state repeatedly. This can be stopped by resetting NIPSWeb.resetCounter
     * to 0.
     */
    initFallback: function(part) {
        //Stop if we're trying sockets again
        if (NIPSWeb.resetCounter != NIPSWeb.resetLimit) {
            return false;
        }
        if (part === 1) {
            //First we get the contents of all playlists
            coptions = NIPSWeb.baseReq('playlists');
            coptions.done = function(x) {
                setTimeout("NIPSWeb.initFallback(1)", 2500);
                NIPSWeb.drawChannels(x);
            };
            coptions.error = NIPSWeb.fallbackFail;
            $.ajax(coptions);
        } else if (part === 2) {
            //Now we get the status of the players
            poptions = NIPSWeb.baseReq('players');
            poptions.done = function(x) {
                setTimeout("NIPSWeb.initFallback(2)", 500);
                NIPSWeb.updatePlayers(x);
            };
            poptions.error = NIPSWeb.fallbackFail;
            $.ajax(poptions);
        } else {
            NIPSWeb.initFallback(1);
            NIPSWeb.initFallback(2);
            $('#notice').show();
        }
    },
    /**
     * Deals with the REST Fallback Mode not working
     */
    fallbackFail: function() {
        $('<div></div>').attr('title', 'Server Unavailable').attr('id', 'error-dialog')
                .append('<p>I have tried as hard as I can, but it looks like there\'s currently a problem with the playout system in this studio. Please contact faults to report this.</p>')
                .dialog({
                    modal: true,
                    buttons: {
                        'Go Back': function() {
                            window.location = myury.makeURL(mConfig.default_module, mConfig.default_action);
                        }
                    },
                    width: 600,
                    closeOnEscape: false,
                    open: function(e, ui) {
                        $(".ui-dialog-titlebar-close", ui.dialog).hide()
                    }
                });
    },
    /**
     * Handles changes to client state over the BRA WebSocket Stream
     */
    processStream: function(data) {
        var obj = $.parseJSON(data.data)

        //{type: "update", /players/0/position: 173818}
        if (obj.type === "update") {
            var component = null;
            var key = null;
            for (k in obj) {
                if (k !== "type") {
                    component = k.split('/');
                    key = k;
                    break;
                }
            }
            if (!component) {
                console.log('Invalid UPDATE response (1).');
                console.log(obj);
                return false;
            }

            //Handle the different primary component type
            //([0] is null because initial /)
            if (component[1] === "players") {
                //Changing a player state
                var cid = parseInt(component[2]) + 1;
                //What are we changing?
                if (component[3] === "position") {
                    //Changing position
                    NIPSWeb.setChannelPosition(cid, obj[key]);
                } else if (component[3] === "state"
                        || component[3] === "load_state") {
                    //Changing play state
                    NIPSWeb.setChannelState(cid, obj[key]);
                } else if (component[3] === "item") {
                    //Changing the loaded item
                    $('#baps-channel-' + cid).children().removeClass('selected');
                    if (obj[key] !== null) {
                        var bidx = obj[key].origin.replace(/^playlist:\/\/[0-9]\//, '');
                        $($('#baps-channel-' + cid).children()[bidx]).addClass('selected');
                        NIPSWeb.setChannelDuration(cid, obj[key].duration);
                    }
                } else {
                    console.log('Invalid UPDATE response (2).');
                    console.log(obj);
                    return false;
                }

            } else if (component[1] === "playlists") {
                //Something's changed, in the BAPS playlist
                //Who you gonna call?
                var cid = parseInt(component[2]) + 1;
                var pid = parseInt(component[3]);
                var items = $('#baps-channel-' + cid).children();
                //Are we changing an item, or adding?
                if (items.length >= pid + 1) {
                    //Changing/removing item.
                    if (obj[key] === null) {
                        //Removing.
                        $(items[pid]).remove();
                    } else {
                        //Changing
                        $(items[pid]).attr('id', 'bapsidx-' + NIPSWeb.getID())
                                .html(NIPSWeb.parseItemName(obj[key].name))
                                .attr('duration', NIPSWeb.parseTime(obj[key].duration));
                    }
                } else {
                    //Adding item.
                    $('#baps-channel-' + cid).append(NIPSWeb.makeItem(obj[key]));
                    NIPSWeb.initListClick();
                }
            } else {
                console.log('Invalid UPDATE response (3).');
                console.log(obj);
                return false;
            }
        } else if (obj.type === "auth") {
            /**
             * @todo Verify response was success
             */
            $('#init-overlay').hide();
        } else {
            console.log('Invalid STREAM response (4).');
            console.log(obj);
            return false;
        }
    },
    /**
     * Uses a BRA data response to redraw channel content from a REST request
     * @param json data
     * @returns {undefined}
     */
    drawChannels: function(data) {
        $('ul.baps-channel').empty();
        for (i in data) {
            var channel = $('#baps-channel-' + (parseInt(i) + 1));
            for (j = 0; j < data[i].length; j++) {
                channel.append(NIPSWeb.makeItem(data[i][j]));
            }
        }
        NIPSWeb.initListClick();
    },
    /**
     * Updates player state based on the result of a players REST request.
     */
    updatePlayers: function(data) {
        for (i in data) {
            //Update the player state
            var cid = parseInt(i) + 1;
            if (data[i].item) {
                NIPSWeb.setChannelDuration(cid, data[i].item.duration);
                NIPSWeb.setChannelPosition(cid, data[i].position);
                //Highlight the current item
                var bidx = data[i].item.origin.replace(/^playlist:\/\/[0-9]\//, '');
                $('#baps-channel-' + cid).children().removeClass('selected');
                $($('#baps-channel-' + cid).children()[bidx]).addClass('selected');
            } else {
                NIPSWeb.setChannelDuration(cid, 0);
                NIPSWeb.setChannelPosition(cid, 0);
                $('#baps-channel-' + cid).children().removeClass('selected');
            }
            NIPSWeb.setChannelState(cid, data[i].state);
        }
    },
    /**
     * Creates a standard BRAPI request framework. Pass your response handler
     * as .done - .success is used for internal request handling.
     */
    baseReq: function(command) {
        return {
            url: 'https://' + NIPSWeb.server + '/' + command,
            accept: 'application/json',
            beforeSend: function(request) {
                b64 = window.btoa(NIPSWeb.user + ':' + NIPSWeb.pass);
                request.withCredentials = true;
                request.setRequestHeader("Authorization", "Basic " + b64);
            },
            success: function(data) {
                /**
                 * @todo Handle 'status' parameter
                 */
                return this.done(data['value']);
            },
            //Custom parameter - called by success once response handled
            done: function(data) {
                return true;
            },
            cache: false,
            dataType: 'json',
            password: NIPSWeb.pass,
            username: NIPSWeb.user,
            global: false //It's better if you don't know how the global handlers respond to BRA
        };
    },
    /**
     * Returns the next incremental ID from the local ID store
     */
    getID: function() {
        return ++NIPSWeb.idCounter;
    },
    /**
     * Strips out unwanted bits of an item name (Managed Items and Personal
     * Items are exposed to BAPS as [name]_[manageditemid])
     */
    parseItemName: function(str) {
        return str.replace(/_[0-9]+$/, '');
    },
    /**
     * Returns number of minutes (zero padded) from a time in seconds
     * @param time in seconds
     */
    timeMins: function(time) {
        var mins = Math.floor(time / 60) + "";
        if (mins.length < 2) {
            mins = '0' + mins;
        }
        return mins;
    },
    /**
     * Returns number of seconds (zero padded) less than mins from a time in seconds
     */
    timeSecs: function(time) {
        var secs = Math.floor(time % 60) + "";
        if (secs.length < 2) {
            secs = '0' + secs;
        }
        return secs;
    },
    /**
     * Returns a pretty formatted time (min:sec) from a BAPS time (millisec)
     */
    parseTime: function(time) {
        var t = Math.round(time / 1000);
        return t ? NIPSWeb.timeMins(t) + ':' + NIPSWeb.timeSecs(t) : '--:--';
    },
    setChannelDuration: function(cid, time) {
        $('#ch' + cid + '-duration').html(NIPSWeb.parseTime(time));
        $('#progress-bar-' + cid).slider({max: time});
    },
    setChannelPosition: function(cid, time) {
        $('#ch' + cid + '-elapsed').html(NIPSWeb.parseTime(time));
        $('#progress-bar-' + cid).slider({value: time});
    },
    /**
     * Valid values: playing, stopped, loading, ok (loading finished), paused
     */
    setChannelState: function(cid, state) {
        if (state === "playing") {
            $('#ch' + cid + '-play').button('enable').addClass('ui-state-highlight');
            $('#ch' + cid + '-pause').button('enable').removeClass('ui-state-highlight');
            $('#ch' + cid + '-stop').button('enable');
        } else if (state === "stopped" || state === "ok") {
            $('#ch' + cid + '-play').button('enable').removeClass('ui-state-highlight');
            $('#ch' + cid + '-pause').button('disable').removeClass('ui-state-highlight');
            $('#ch' + cid + '-stop').button('disable');
        } else if (state === "paused") {
            $('#ch' + cid + '-play').button('disable').removeClass('ui-state-highlight');
            $('#ch' + cid + '-pause').button('enable').addClass('ui-state-highlight');
            $('#ch' + cid + '-stop').button('enable');
        } else if (state === "loading") {
            $('#ch' + cid + '-play').button('disable');
            $('#ch' + cid + '-pause').button('disable').removeClass('ui-state-highlight');
            $('#ch' + cid + '-stop').button('disable');
        }
    },
    /**
     * Sets up UI elements such as dialogs and progressbars
     * @todo Currently assumes 3 broadcast channels
     */
    initUI: function() {
        //Loading bar
        $('#init-progressbar').progressbar({value: false});
        //Fallback notice dialog
        $('#notice').on('click', function() {
            $('<div></div>').attr('title', 'Fallback Mode Enabled').attr('id', 'error-dialog')
                    .append('<p>I\'ve put you into Fallback Mode right now because of problems connected to our Live server, or because you\'re using an old web browser. You will still be able to use BAPS, but the screen will update slower and things may just generally not work as well.</p>')
                    .dialog({
                        modal: true,
                        buttons: {
                            'Stay in Fallback Mode': function() {
                                $(this).dialog("close");
                            },
                            'Switch back to Live Mode': function() {
                                NIPSWeb.resetCounter = 0;
                                NIPSWeb.initStream();
                                $('#notice').hide();
                                $(this).dialog("close");
                            }
                        },
                        width: 600
                    });
        });
        //Fallback button on loading dialog
        $('#init-overlay-fallback button').on('click', function() {
            clearTimeout(NIPSWeb.streamChecker);
            NIPSWeb.streamChecker = null;
            NIPSWeb.resetCounter = NIPSWeb.resetLimit;
            NIPSWeb.initFallback(0);
            $('#init-overlay').hide();
        });
        /** Initialise player boxes */
        //Play/Pause/Stop (clicks handled by onClick in DOM)
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

        //Progress Slider
        $('.channel-position-slider').slider({
            range: "min",
            value: 0,
            min: 0
        }).on("slidestop", function(e, ui) {
            var cid = parseInt($(this).attr('id').replace(/^progress\-bar\-/,''))-1;
            var options = NIPSWeb.baseReq('players/'+cid);
            options.method = 'POST';
            options.data = '{"position":"'+ui.value+'"}';
            $.ajax(options);
        });

        //Funtion key press
        $(document).on('keydown.bapsplayers', function(e) {
            var trigger = false;
            switch (e.which) {
                case NIPSWeb.keys.F1:
                    //Play channel 1
                    NIPSWeb.play(1);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F2:
                    NIPSWeb.pause(1);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F3:
                    NIPSWeb.stop(1);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F5:
                    //Play channel 2
                    NIPSWeb.play(2);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F6:
                    NIPSWeb.pause(2);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F7:
                    NIPSWeb.stop(2);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F9:
                    //Play channel 3
                    NIPSWeb.play(3);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F10:
                    NIPSWeb.pause(3);
                    trigger = true;
                    break;
                case NIPSWeb.keys.F11:
                    NIPSWeb.stop(3);
                    trigger = true;
                    break;
            }
            if (trigger) {
                e.stopPropagation();
                e.preventDefault();
                return false;
            }
        });

        /** Initialise Movement Operations **/
        $('ul.baps-channel').not('#baps-channel-res').sortable();
    },
    initListClick: function() {
        $('ul.baps-channel li').off('click.playerLoad').on('click.playerLoad', function(e) {
            /**
             * @todo Look into implementing this cool stuff
             */
            if ($(this).hasClass('unclean')) {
                //This track may have naughty words, but don't block selection
                $('#footer-tips').html('This track is explicit. Do not broadcast before 9pm.').addClass('ui-state-error').show();
                setTimeout("$('#footer-tips').removeClass('ui-state-error').fadeOut();", 5000);
            }

            //Send a load request to BRA
            var cid = parseInt($(this).parent('ul').attr('channel')) - 1;
            var pid = $(this).index();
            var options = NIPSWeb.baseReq('players/' + cid);
            options.method = 'POST';
            options.data = '{"item":"playlist://' + cid + '/' + pid + '"}';
            $.ajax(options);
        });
    },
    makeItem: function(data) {
        var li = $('<li></li>');
        li.attr('id', 'bapsidx-' + NIPSWeb.getID());
        li.attr('duration', NIPSWeb.parseTime(data.duration))
        li.html(NIPSWeb.parseItemName(data.name));
        return li;
    },
    //Handles a play request for (ch-1)
    play: function(ch) {
        var cid = parseInt(ch) - 1;
        var options = NIPSWeb.baseReq('players/' + cid);
        options.method = 'POST';
        options.data = '{"state":"playing"}';
        $.ajax(options);
    },
    //Handles a pause request for (ch-1)
    pause: function(ch) {
        var cid = parseInt(ch) - 1;
        var options = NIPSWeb.baseReq('players/' + cid);
        options.method = 'POST';
        if ($('#ch' + ch + '-pause').hasClass('ui-state-highlight')) {
            options.data = '{"state":"playing"}';
        } else {
            options.data = '{"state":"paused"}';
        }
        $.ajax(options);
    },
    //Handles a stop request for (ch-1)
    stop: function(ch) {
        var cid = parseInt(ch) - 1;
        var options = NIPSWeb.baseReq('players/' + cid);
        options.method = 'POST';
        options.data = '{"state":"stopped"}';
        $.ajax(options);
    }
};

$(document).ready(NIPSWeb.initUI);
$(document).ready(NIPSWeb.initStream);
manualSeek = true;
window.debug = true;
/**
 function initialiseUI() {
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
 *//*
  NIPSWeb.calcChanges(e, ui);
  }

  });
  }

  registerItemClicks();
  setupGenericListeners();
  configureContextMenus();
  }
  */

function configureContextMenus() {
    return;
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

/**
 * Generic Functions
 */

function registerItemClicks() {
// Used by dragdrop - enables the selected item to move down on drag/drop
    $('ul.baps-channel li').off('mousedown.predrag').on('mousedown.predrag', function(e) {
        $(this).attr('nextSelect',
                typeof $(this).next().attr('id') !== 'undefined' ? $(this).next().attr('id') : $(this).prev().attr('id'));
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
