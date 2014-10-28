var NIPSWeb = function() {
    // If enabled, doesn't reload on error
    var debug = false;
    // Queue up processing client changes one at a time
    var changeQueue = $({});
    // Queue up sending ajax requests one at a time
    var ajaxQueue = $({});
    // Stores the clientid to enable multiple editors
    var clientid = null;
    // Stores whether this show plan is writable
    var writable = true;
    // Stores the actual player audio elements
    var players = [];
    // Store the interactive sliders/seek bars
    var sliders = [];


    if (writable) {
        //Get a client id to identify this session
        $.ajax({
            url: myury.makeURL('NIPSWeb', 'get_client_token'), 
            type: 'POST',
            success: function(data) {
                clientid = parseInt(data.token);
            },
            async: false
        });
    }

    /**
    * Returns number of minutes (zero padded) from a time in seconds
    * @param time in seconds
    */
    var timeMins = function(time) {
        var mins = Math.floor(time / 60) + "";
        if (mins.length < 2) {
            mins = '0' + mins;
        }
        return mins;
    };

    // Returns number of seconds (zero padded) less than mins from a time in seconds
    var timeSecs = function(time) {
        var secs = Math.floor(time % 60) + "";
        if (secs.length < 2) {
            secs = '0' + secs;
        }
        return secs;
    };

    // Gets the time of the current track in channel
    var getTime = function(channel) {
        var audio = getPlayer(channel);

        var elapsed = audio.currentTime; //Get the current playing position of the track
        //currentTime returns a value in seconds. Convert to minutes+seconds, pad zeros where appropriate.
        var minelap = timeMins(elapsed);
        var secelap = timeSecs(elapsed);
        // Sets the current time label
        $('#ch' + channel + '-elapsed').html(minelap + ':' + secelap);
    }

    // Gets the duration of the current track in channel
    var getDuration = function(channel) {
        var audio = getPlayer(channel);

        var duration = audio.duration; //Get the duration of the track
        //duration returns a value in seconds. Convert to minutes+seconds, pad zeros where appropriate.
        var mindur = timeMins(duration);
        var secdur = timeSecs(duration);
        // Sets the duration label
        $('#ch' + channel + '-duration').html(mindur + ':' + secdur);
    };

    var showAlert = function(text, type) {
        if (!type) {
            type = 'success';
        }

        var close = '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>';

        var alert = $('<div></div>').addClass('footer-alert').addClass('alert').addClass('alert-'+type).html(text + close);
        alert.alert();

        setTimeout(function() {alert.alert('close')}, 15000);

        $(document.body).append(alert);
    }

    /**
    * Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
    * ops: JSON changeset to send
    * addOp: If true, there has been an add operation. We currently make these syncronous.
    * pNext: Optional. Parent queue to process on completion.
    */
    var shipChanges = function(ops, addOp, pNext) {
        if (typeof addOp === 'undefined') {
            addOp = false;
        }

        ajaxQueue.queue(function(next) {
            $('#notice').html('Saving changes...').show();
            $.ajax({
                async: !addOp,
                cache: false,
                success: function(data) {
                    $('#notice').hide();
                    for (i in data) {
                        if (i === 'myury_errors') {
                            continue;
                        }
                        if (typeof data[i].timeslotitemid !== 'undefined') {
                            //@todo multiple AddItem ops in a jsonon set will make this break
                            $('ul.baps-channel li[timeslotitemid="findme"]').attr('timeslotitemid', data[i].timeslotitemid);
                        }
                        if (!data[i].status && !debug) {
                            window.location.reload();
                        }
                    }
                },
                complete: function() {
                    next();
                    if (typeof pNext !== 'undefined') {
                        pNext();
                    }
                },
                data: {
                    clientid: clientid,
                    ops: ops
                },
                dataType: 'json',
                type: 'POST',
                url: myury.makeURL('NIPSWeb', 'recv_ops')
            });
        });
    };

    /**
    * Detect what changes have been made to the show plan
    */
    var calcChanges = function (e, ui) {
        changeQueue.queue(function(next) {
            /**
            * Update the position of the item to its new values. If it doesn't have them, set them.
            */
            var oldChannel = ui.item.attr('channel');
            var oldWeight = ui.item.attr('weight');
            ui.item.attr('channel', ui.item.parent('ul').attr('channel') === 'res' ? 'res' : ui.item.parent().attr('channel') - 1);
            ui.item.attr('weight', ui.item.index());

            if (oldChannel !== ui.item.attr('channel') || oldWeight !== ui.item.attr('weight')) {
                /**
                * This item definitely isn't where it was before. Notify the server of the potential actions.
                */
                var ops = [];
                var addOp = false;
                if (oldChannel === 'res' && ui.item.attr('channel') !== 'res') {
                    addOp = true;
                    /**
                    * This item has just been added to the show plan. Send the server a AddItem operation.
                    * This operation will also send a number of MoveItem notifications - one for each item below this one in the
                    * channel, as their weights have now been increased to accomodate the new item.
                    * It will return a timeslotitemid from the server which then gets attached to the item.
                    */
                    var current = ui.item;
                    while (current.next().length === 1) {
                        current = current.next();
                        current.attr('weight', parseInt(current.attr('weight')) + 1);
                        ops.push({
                            op: 'MoveItem',
                            timeslotitemid: parseInt(current.attr('timeslotitemid')),
                            oldchannel: parseInt(current.attr('channel')),
                            oldweight: parseInt(current.attr('weight')) - 1,
                            channel: parseInt(current.attr('channel')),
                            weight: parseInt(current.attr('weight'))
                        });
                    }

                    // Do the actual Add Operation
                    // This is after the moves to ensure there aren't two items of the same weight
                    ops.push({
                        op: 'AddItem',
                        id: ui.item.attr('id'),
                        channel: parseInt(ui.item.attr('channel')),
                        weight: parseInt(ui.item.attr('weight'))
                    });
                    ui.item.attr('timeslotitemid', 'findme');

                } else if (ui.item.attr('channel') === 'res' || ui.item.attr('channel') === null) {
                    /**
                    * This item has just been removed from the Show Plan. Send the server a RemoveItem operation.
                    * This operation will also send a number of MoveItem notifications - one for each item below this one in the
                    * channel, as their weights have now been decreased to accomodate the removed item.
                    */
                    $('ul.baps-channel li[channel=' + oldChannel + ']').each(function() {
                        if (oldWeight - $(this).attr('weight') < 0) {
                            $(this).attr('weight', parseInt($(this).attr('weight')) - 1);
                            ops.push({
                                op: 'MoveItem',
                                timeslotitemid: parseInt($(this).attr('timeslotitemid')),
                                oldchannel: parseInt($(this).attr('channel')),
                                oldweight: parseInt($(this).attr('weight')) + 1,
                                channel: parseInt($(this).attr('channel')),
                                weight: parseInt($(this).attr('weight'))
                            });
                        }
                    });

                    // Do the actual Remove Operation
                    // This is after the moves to ensure there aren't two items of the same weight
                    ops.push({
                        op: 'RemoveItem',
                        timeslotitemid: parseInt(ui.item.attr('timeslotitemid')),
                        channel: parseInt(oldChannel),
                        weight: parseInt(oldWeight)
                    });

                    ui.item.attr('timeslotitemid', null);
                } else {
                    /**
                    * This item has just been moved from one position to another.
                    * This involves a large number of MoveItem ops being sent to the server:
                    * - Each item below its previous location must have a MoveItem to decrement the weight
                    * - Each item below its new location must have a MoveItem to increment the weight
                    * - The item must have its channel/weight setting updated for its new location
                    */
                    var inc = [];
                    var dec = [];

                    $('ul.baps-channel li[channel=' + oldChannel + ']').each(function() {
                        if (oldWeight - $(this).attr('weight') < 0
                            && $(this).attr('timeslotitemid') !== ui.item.attr('timeslotitemid')) {

                            dec.push($(this).attr('timeslotitemid'));
                            $(this).attr('weight', parseInt($(this).attr('weight')) - 1);
                        }
                    });

                    var current = ui.item;
                    while (current.next().length === 1) {
                        current = current.next();
                        var pos = $.inArray(current.attr('timeslotitemid'), dec);
                        //This is actually a no-op move.
                        if (pos >= 0) {
                            $('ui.baps-channel li[timeslotitemid=' + dec[pos] + ']').attr('weight',
                            parseInt($('ui.baps-channel li[timeslotitemid=' + dec[pos] + ']')) + 1)
                            dec[pos] = null;
                        } else {
                            inc.push(current.attr('timeslotitemid'));
                            current.attr('weight', parseInt(current.attr('weight')) + 1);
                        }
                    }

                    for (i in inc) {
                        var obj = $('ul.baps-channel li[timeslotitemid=' + inc[i] + ']');
                        ops.push({
                            op: 'MoveItem',
                            timeslotitemid: parseInt(inc[i]),
                            oldchannel: parseInt(obj.attr('channel')),
                            oldweight: parseInt(obj.attr('weight')) - 1,
                            channel: parseInt(obj.attr('channel')),
                            weight: parseInt(obj.attr('weight'))
                        });
                    }

                    for (i in dec) {
                        if (dec[i] === null) {
                            continue;
                        }
                        var obj = $('ul.baps-channel li[timeslotitemid=' + dec[i] + ']');
                        ops.push({
                            op: 'MoveItem',
                            timeslotitemid: parseInt(dec[i]),
                            oldchannel: parseInt(obj.attr('channel')),
                            oldweight: parseInt(obj.attr('weight')) + 1,
                            channel: parseInt(obj.attr('channel')),
                            weight: parseInt(obj.attr('weight'))
                        });
                    }

                    // Finally, we can add the item itself
                    ops.push({
                        op: 'MoveItem',
                        timeslotitemid: parseInt(ui.item.attr('timeslotitemid')),
                        oldchannel: parseInt(oldChannel),
                        oldweight: parseInt(oldWeight),
                        channel: parseInt(ui.item.attr('channel')),
                        weight: parseInt(ui.item.attr('weight'))
                    });
                }

                /**
                * The important bit - ship the change operations over to the server to update the remote datastructure,
                * the change log, and to propogate the changes to any other clients that may be active.
                */
                shipChanges(ops, addOp, next);
            }
        });
    };

    var registerItemClicks = function() {
        // Used by dragdrop - enables the selected item to move down on drag/drop
        $('ul.baps-channel li').off('mousedown.predrag').on('mousedown.predrag', function(e) {
            $(this).attr('nextSelect',
            typeof $(this).next().attr('id') !== 'undefined' ? $(this).next().attr('id') : $(this).prev().attr('id'));
        });
        $('ul.baps-channel li').off('click.playactivator').on('click.playactivator', function(e) {
            if ($(this).hasClass('undigitised')) {
                //Can't select the track - it isn't digitised
                showAlert($(this).html() + ' has not been digitised.', 'danger');
                e.stopPropagation();
                return false;
            }
            if ($(this).hasClass('unclean')) {
                //This track may have naughty words, but don't block selection
                showAlert($(this).html() + ' explicit. Do not broadcast before 9pm.', 'danger');
            }
            //Set this track as the active file for this channel
            //First, we need to remove the active class for any other file in the channel
            $(this).parent('ul').children().removeClass('selected');
            $(this).addClass('selected');
            loadItem($(this).parent('.baps-channel').attr('channel'));
        });
        $('ul.baps-channel li').tooltip({
            delay: 500,
            placement: 'right',
            container: 'body'
        });
    };

    // Sets up global listeners
    var setupGenericListeners = function() {
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
                    play(1);
                    trigger = true;
                    break;
                case keys.F2:
                    pause(1);
                    trigger = true;
                    break;
                case keys.F3:
                    stop(1);
                    trigger = true;
                    break;
                case keys.F5:
                    //Play channel 2
                    play(2);
                    trigger = true;
                    break;
                case keys.F6:
                    pause(2);
                    trigger = true;
                    break;
                case keys.F7:
                    stop(2);
                    trigger = true;
                    break;
                case keys.F9:
                    //Play channel 3
                    play(3);
                    trigger = true;
                    break;
                case keys.F10:
                    pause(3);
                    trigger = true;
                    break;
                case keys.F11:
                    stop(3);
                    trigger = true;
                    break;
            }
            if (trigger) {
                e.stopPropagation();
                e.preventDefault();
                return false;
            }
        });
    };

    var updateChannelTotalTimers = function() {
        $('.baps-channel').each(function() {
            var time = 0;
            $(this).children('li').each(function() {
                var tmp = $(this).attr('length').split(':');
                if (tmp.length !== 3) {
                    return;
                }
                time += parseInt(tmp[1]) * 60;
                time += parseInt(tmp[2]);
            });
            $('#' + $(this).attr('id') + '-total').html('(' + timeMins(time) + ':' + timeSecs(time) + ')');
        });
    };

    var configureContextMenus = function() {
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
                if (debug) {console.log(ul);}
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
                if ($(ul).attr('autoadvance') == 1) {
                    $(ul).attr('autoadvance', 0);
                } else {
                    $(ul).attr('autoadvance', 1);
                }
            } else if (menuId === "#autoPlay") {
                if ($(ul).attr('playonload') == 1) {
                    $(ul).attr('playonload', 0);
                } else {
                    $(ul).attr('playonload', 1);
                }
            }
            if (debug) {console.log("select " + menuId + " on " + $(target).attr('id'));}
        });
    };

    var initialiseUI = function() {
        if (writable) {
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
                        if (ui.item.attr('nextSelect') != null) {
                            $('#' + ui.item.attr('nextSelect')).click();
                        }
                    }
                    ui.item.nextSelect = null;
                },
                stop: function(e, ui) {
                    /**
                    * Update the channel timers
                    */
                    updateChannelTotalTimers();
                    calcChanges(e, ui);
                }
            });
        }

        registerItemClicks();
        setupGenericListeners();
        updateChannelTotalTimers();
        configureContextMenus();
    };

    // Create the player for the given channel
    var initialisePlayer = function (channel) {
        if (channel == 0) {
            channel = 'res';
        }

        sliders[(channel === 'res') ? 0 : channel] = playoutSlider(document.getElementById('progress-bar-' + channel));

        var a = new Audio();

        players[(channel === 'res') ? 0 : channel] = a;

        setupListeners(channel);
    };

    // Sets up listeners per channel
    var setupListeners = function(channel) {
        var player = getPlayer(channel);
        var slider = sliders[(channel === 'res') ? 0 : channel];

        $(player).on('ended', function() {
            stopping(channel);
            if ($('#baps-channel-' + channel).attr('autoadvance') == 1) {
                $('#' + $('#baps-channel-' + channel + ' li.selected')
                    .removeClass('selected')
                    .attr('nextselect'))
                .click();
            }
        });
        // Chrome sometimes stops playback after seeking
        $(player).on('seeked', function() {
            if (player.nwIsPlaying) {
                setTimeout("player.play()", 50);
            }
        });
        $(player).on('timeupdate', function() {
            getTime(channel);
            sliders[channel].position(player.currentTime);
        });

        $(player).on('durationchange', function() {
            getDuration(channel);
            sliders[channel].reset(
                player.duration,
                0,
                $('#baps-channel-' + channel + ' li.selected').attr('intro')
            );
        });

        slider.addEventListener("seeked", function(e) {
            player.currentTime = e.detail.time;
        });

        $('#ch' + channel + '-play').on('click', function() {play(channel)});
        $('#ch' + channel + '-pause').on('click', function() {pause(channel)});
        $('#ch' + channel + '-stop').on('click', function() {stop(channel)});
    };

    // Returns the player element for the given channel
    var getPlayer = function(channel) {
        if (channel === 'res') {
            channel = 0;
        }
        return players[channel];
    };

    var getRecTrackFromID = function(id) {
        id = id.split('-');

        var data = [];
        data[0] = id[0];
        data[1] = id[1];

        for (i = 2; i < id.length; i++) {
            data[1] = data[1] + '-' + id[i];
        }

        return data;
    };

    var loadItem = function (channel) {
        $('#ch' + channel + '-play, #ch' + channel + '-pause, #ch' + channel + '-stop').attr('disabled', 'disabled');
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
                    params = {
                        recordid: data[0],
                        trackid: data[1]
                    }
                    if (getPlayer(channel).canPlayType('audio/mpeg')) {
                        getPlayer(channel).type = 'audio/mpeg';
                    } else if (getPlayer(channel).canPlayType('audio/ogg')) {
                        getPlayer(channel).type = 'audio/ogg';
                        params.ogg = true;
                    } else {
                        $('#notice').html('Sorry, you need to use a modern browser to use Track Preview.').addClass('alert-error').show();
                    }
                    getPlayer(channel).src = myury.makeURL('NIPSWeb', 'secure_play', params);

                    $(getPlayer(channel)).on("canplaythrough", function() {
                        $('#ch' + channel + '-play').removeAttr('disabled');
                        /**
                        * Briefly play the track once it has started loading
                        * Workaround for http://code.google.com/p/chromium/issues/detail?id=111281
                        */
                        this.play();
                        var that = this; // That will stay in context for the timout
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
            getPlayer(channel).src = myury.makeURL(
                'NIPSWeb',
                'managed_play',
                {managedid: $('#' + audioid).attr('managedid')}
            );
            $(getPlayer(channel)).on('canplay', function() {
                $('#ch' + channel + '-play').removeAttr('disabled');
            });
        }
    };

    var playing = function(channel) {
        getPlayer(channel).nwIsPlaying = true;
        $('#ch' + channel + '-play').removeClass('btn-default').addClass('btn-primary');
        $('#ch' + channel + '-pause, #ch' + channel + '-stop')
                .removeAttr('disabled')
                .removeClass('btn-warning')
                .addClass('btn-default');
    };

    var stopping = function(channel) {
        getPlayer(channel).nwIsPlaying = false;
        $('#ch' + channel + '-play').removeClass('btn-primary').addClass('btn-default');
        $('#ch' + channel + '-pause').removeClass('btn-warning').addClass('btn-default').attr('disabled', 'disabled');
        $('#ch' + channel + '-stop').attr('disabled', 'disabled');
    }

    var play = function(channel) {
        player = getPlayer(channel);
        player.nwIsPlaying = true;
        player.play();
        playing(channel);
    };

    var pause = function(channel) {
        var player = getPlayer(channel);
        if (player.paused) {
            player.play();
            playing(channel);
        } else {
            player.pause();
            player.nwIsPlaying = false;
            $('#ch' + channel + '-play').removeClass('btn-primary').addClass('btn-default');
            $('#ch' + channel + '-pause').removeClass('btn-default').addClass('btn-warning');
            $('#ch' + channel + '-stop').removeAttr('disabled');
        }
    };

    var stop = function(channel) {
        var player = getPlayer(channel);
        player.pause();
        player.currentTime = 0;
        stopping(channel);
    };

    return {
        debug: debug,
        initialiseUI: initialiseUI,
        initialisePlayer: initialisePlayer,
        showAlert: showAlert,
        registerItemClicks: registerItemClicks
    };

};

var playoutSlider = function(e) {
    var duration = 0;
    var cue = 0;
    var intro = 0;
    var positionInt = 0;
    var isSliding = false;

    /** DOM ELEMENTS **/
    var sliderContainer = document.createElement('div');
    sliderContainer.className = 'playout-slider';

    var cueSlider = document.createElement('div');
    cueSlider.className = 'playout-slider-cue';
    var cueHandle = document.createElement('div');
    cueHandle.className = 'playout-handle';
    cueSlider.appendChild(cueHandle);
    sliderContainer.appendChild(cueSlider);

    var introSlider = document.createElement('div');
    introSlider.className = 'playout-slider-intro';
    var introHandle = document.createElement('div');
    introHandle.className = 'playout-handle';
    introSlider.appendChild(introHandle);
    sliderContainer.appendChild(introSlider);

    var positionSlider = document.createElement('div');
    positionSlider.className = 'playout-slider-position';
    var positionSliderLine = document.createElement('div');
    positionSliderLine.className = 'playout-slider-line';
    positionSlider.appendChild(positionSliderLine);
    var positionHandle = document.createElement('div');
    positionHandle.className = 'playout-handle';
    positionSlider.appendChild(positionHandle);
    sliderContainer.appendChild(positionSlider);

    /** HELPER FUNCTIONS **/
    var calculatePositionFromSeek = function(e) {
        positionSlider.style.width = e.clientX - getXOffset(e.currentTarget) + 3 + 'px';
        positionInt = parseInt(positionSlider.style.width.replace(/px$/, '')) / getPixelsPerSecond();
        sliderContainer.dispatchEvent(new CustomEvent('seeked', {detail: {time: positionInt}}));
    }

    var getXOffset = function(e) {
        var x = 0;
        while (e) {
            x += e.offsetLeft + e.clientLeft - e.scrollLeft;
            e = e.offsetParent;
        }
        return x;
    }

    /** EVENT BINDINGS **/
    var positionHandleDragStart = function() {
        if (!isSliding) {
            isSliding = true;

            var dragMove = function(e) {
                calculatePositionFromSeek(e);
                return false;
            }
            var dragEnd = function(e) {
                positionInt = parseInt(positionSlider.style.width.replace(/px$/, '')) / getPixelsPerSecond();
                sliderContainer.dispatchEvent(new CustomEvent('seeked', {detail: {time: positionInt}}));

                sliderContainer.removeEventListener('mousemove', dragMove);
                window.removeEventListener('mouseup', dragEnd);
                isSliding = false;
                return false;
            }
            sliderContainer.addEventListener('mousemove', dragMove);
            window.addEventListener('mouseup', dragEnd);
            return false;
        }
    }
    positionHandle.addEventListener('mousedown', positionHandleDragStart);

    var clickHandler = function(e) {
        if (!isSliding) {
            calculatePositionFromSeek(e);
        }
    }
    sliderContainer.addEventListener('click', clickHandler);

    var reset = function(newDuration, newCue, newIntro) {
        duration = parseInt(newDuration);
        cue = parseInt(newCue);
        intro = parseInt(newIntro);
        positionInt = 0;
        redraw();
    }

    var getPixelsPerSecond = function() {
        return (duration > 0 ? (sliderContainer.offsetWidth - 2)/duration : 0)
    }

    var position = function(newPosition) {
        if (newPosition !== undefined) {
            if (!isSliding) {
                positionInt = newPosition;
                redraw();
            }
        } else {
            return positionInt;
        }
    }

    var redraw = function() {
        cueSlider.style.width = cue * getPixelsPerSecond() + 'px';
        introSlider.style.width = intro * getPixelsPerSecond() + 'px';
        positionSlider.style.width = positionInt * getPixelsPerSecond() + 'px';
    }

    var addEventListener = function(a, b, c) {
        sliderContainer.addEventListener(a, b, c);
    }

    var removeEventListener = function(a, b, c) {
        sliderContainer.removeEventListener(a, b, c);
    }

    //Attach the seekbar to the DOM
    e.className = 'playout-slider-container';
    e.appendChild(sliderContainer);

    return {
        reset: reset,
        position: position,
        addEventListener: addEventListener,
        removeEventListener: removeEventListener
    }

}

playoutSlider.prototype = {
    constructor: playoutSlider
};
