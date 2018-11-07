/* global ChannelConfigurator, myradio */

//reload the page after something good or bad happens
var reload = function() {
  location.reload();
};

/* exported NIPSWeb */
var NIPSWeb = function (d) {
  // If enabled, doesn't reload on error
  var debug = d;
  // Queue up processing client changes one at a time
  var changeQueue = $({});
  // Queue up sending ajax requests one at a time
  var ajaxQueue = $({});

  // Stores whether this show plan is writable
  var writable = true;
  // Stores the actual player audio elements
  var players = [];
  // Store the interactive sliders/seek bars
  var sliders = [];

  /**
   * Returns number of minutes (zero padded) from a time in seconds
   * @param time in seconds
   */
  var timeMins = function (time) {
    var mins = Math.floor(time / 60) + "";
    if (mins.length < 2) {
      mins = "0" + mins;
    }
    return mins;
  };

  // Returns number of seconds (zero padded) less than mins from a time in seconds
  var timeSecs = function (time) {
    var secs = Math.floor(time % 60) + "";
    if (secs.length < 2) {
      secs = "0" + secs;
    }
    return secs;
  };

  // Gets the time of the current track in channel
  var getTime = function (channel) {
    var audio = getPlayer(channel);

    var elapsed = audio.currentTime; //Get the current playing position of the track
    //currentTime returns a value in seconds. Convert to minutes+seconds, pad zeros where appropriate.
    var minelap = timeMins(elapsed);
    var secelap = timeSecs(elapsed);
    // Sets the current time label
    $("#ch" + channel + "-elapsed").html(minelap + ":" + secelap);
  };

  // Gets the duration of the current track in channel
  var getDuration = function (channel) {
    var audio = getPlayer(channel);

    var duration = audio.duration; //Get the duration of the track
    //duration returns a value in seconds. Convert to minutes+seconds, pad zeros where appropriate.
    var mindur = timeMins(duration);
    var secdur = timeSecs(duration);
    // Sets the duration label
    $("#ch" + channel + "-duration").html(mindur + ":" + secdur);
  };

  /**
   * Change shipping operates in a queue - this ensures that changes are sent atomically and sequentially.
   * ops: JSON changeset to send
   * pNext: Optional. Parent queue to process on completion.
   */
  var shipChanges = function (ops, pNext) {

    ajaxQueue.queue(
      function (next) {
        $.ajax({
          cache: false,
          success: function (data) {
            for (var i in data.payload) {
              if (i === "myradio_errors") {
                continue;
              }
              if (typeof data.payload[i].timeslotitemid !== "undefined") {
                $("ul.baps-channel li[timeslotitemid=\"findme\"]").attr("timeslotitemid", data.payload[i].timeslotitemid);
              }
              if (!data.payload[i].status) {
                myradio.showAlert("Save failed! Reloading in 5 seconds.", "danger");
                if (!debug) {
                  setTimeout(function(){ reload(); }, 5000);
                }
              } else {
                myradio.showAlert("Changes Saved Successfully", "success");
              }
            }
          },
          complete: function () {
            next();
            if (typeof pNext !== "undefined") {
              pNext();
            }
          },
          data: {
            ops: ops
          },
          type: "PUT",
          url: myradio.getAPIURL("timeslot", "updateshowplan", window.myradio.timeslotid, "")
        });
      }
    );
  };

  /**
   * Detect what changes have been made to the show plan
   */
  var calcChanges = function (li) {
    if (!li.hasOwnProperty("attr")) {
      li = $(li);
    }
    changeQueue.queue(
      function (next) {
        /**
         * Update the position of the item to its new values. If it doesn't have them, set them.
         */
        var oldChannel = li.attr("channel");
        var oldWeight = li.attr("weight");
        var newChannel;
        var current;
        var obj;
        if (li.parent("ul").attr("channel") !== undefined) {
          newChannel = li.parent("ul").attr("channel") === "res" ? "res" : li.parent("ul").attr("channel") - 1;
        } else {
          newChannel = null;
        }
        li.attr("channel", newChannel);
        li.attr("weight", li.index());
        if (oldChannel !== li.attr("channel") || oldWeight !== li.attr("weight")) {
          /**
           * This item definitely isn't where it was before. Notify the server of the potential actions.
           */
          myradio.showAlert("Saving changes to show plan...", "loading");
          var ops = [];
          if (oldChannel === "res" && li.attr("channel") !== "res") {
            /**
             * This item has just been added to the show plan. Send the server a AddItem operation.
             * This operation will also send a number of MoveItem notifications - one for each item below this one in the
             * channel, as their weights have now been increased to accomodate the new item.
             * It will return a timeslotitemid from the server which then gets attached to the item.
             */
            current = li;
            while (current.next().length === 1) {
              current = current.next();
              current.attr("weight", parseInt(current.attr("weight")) + 1, 10);
              ops.push({
                op: "MoveItem",
                timeslotitemid: parseInt(current.attr("timeslotitemid"), 10),
                oldchannel: parseInt(current.attr("channel"), 10),
                oldweight: parseInt(current.attr("weight"), 10) - 1,
                channel: parseInt(current.attr("channel"), 10),
                weight: parseInt(current.attr("weight"), 10)
              });
            }

            // Do the actual Add Operation
            // This is after the moves to ensure there aren't two items of the same weight
            ops.push({
              op: "AddItem",
              id: li.attr("id"),
              channel: parseInt(li.attr("channel"), 10),
              weight: parseInt(li.attr("weight"), 10)
            });
            li.attr("timeslotitemid", "findme");

          } else if ( oldChannel !== "res" && (li.attr("channel") === "res" || li.attr("channel") == null)) {
            /**
             * This item has just been removed from the Show Plan. Send the server a RemoveItem operation.
             * This operation will also send a number of MoveItem notifications - one for each item below this one in the
             * channel, as their weights have now been decreased to accomodate the removed item.
             * Only perform this operation if the previous item channel was not res.
             */
            $("ul.baps-channel li[channel=" + oldChannel + "]").each(function () {
              if (oldWeight - $(this).attr("weight") < 0) {
                $(this).attr("weight", parseInt($(this).attr("weight"), 10) - 1);
                ops.push({
                  op: "MoveItem",
                  timeslotitemid: parseInt($(this).attr("timeslotitemid"), 10),
                  oldchannel: parseInt($(this).attr("channel"), 10),
                  oldweight: parseInt($(this).attr("weight"), 10) + 1,
                  channel: parseInt($(this).attr("channel"), 10),
                  weight: parseInt($(this).attr("weight"), 10)
                });
              }
            });

            // Do the actual Remove Operation
            // This is after the moves to ensure there aren't two items of the same weight
            ops.push({
              op: "RemoveItem",
              timeslotitemid: parseInt(li.attr("timeslotitemid"), 10),
              channel: parseInt(oldChannel, 10),
              weight: parseInt(oldWeight, 10)
            });
            li.remove();
            //hide any lingering tooltips from the screen.
            $(".tooltip").fadeOut().remove();

          } else if (oldChannel !== "res" || li.attr("channel") !== "res") {
            /**
             * This item has just been moved from one position to another.
             * This involves a large number of MoveItem ops being sent to the server:
             * - Each item below its previous location must have a MoveItem to decrement the weight
             * - Each item below its new location must have a MoveItem to increment the weight
             * - The item must have its channel/weight setting updated for its new location
             */
            var inc = [];
            var dec = [];

            $("ul.baps-channel li[channel=" + oldChannel + "]").each(function () {
              if (oldWeight - $(this).attr("weight") < 0 &&
                $(this).attr("timeslotitemid") !== li.attr("timeslotitemid")) {
                dec.push($(this).attr("timeslotitemid"));
                $(this).attr("weight", parseInt($(this).attr("weight"), 10) - 1);
              }
            });

            current = li;
            while (current.next().length === 1) {
              current = current.next();
              var pos = $.inArray(current.attr("timeslotitemid"), dec);
              //This is actually a no-op move.
              if (pos >= 0) {
                $("ui.baps-channel li[timeslotitemid=" + dec[pos] + "]").attr(
                  "weight",
                  parseInt($("ui.baps-channel li[timeslotitemid=" + dec[pos] + "]"), 10) + 1
                );
                dec[pos] = null;
              } else {
                inc.push(current.attr("timeslotitemid"));
                current.attr("weight", parseInt(current.attr("weight"), 10) + 1);
              }
            }

            for (var i in inc) {
              obj = $("ul.baps-channel li[timeslotitemid=" + inc[i] + "]");
              ops.push({
                op: "MoveItem",
                timeslotitemid: parseInt(inc[i], 10),
                oldchannel: parseInt(obj.attr("channel"), 10),
                oldweight: parseInt(obj.attr("weight"), 10) - 1,
                channel: parseInt(obj.attr("channel"), 10),
                weight: parseInt(obj.attr("weight"), 10)
              });
            }

            for (i in dec) {
              if (dec[i] === null) {
                continue;
              }
              obj = $("ul.baps-channel li[timeslotitemid=" + dec[i] + "]");
              ops.push({
                op: "MoveItem",
                timeslotitemid: parseInt(dec[i], 10),
                oldchannel: parseInt(obj.attr("channel"), 10),
                oldweight: parseInt(obj.attr("weight"), 10) + 1,
                channel: parseInt(obj.attr("channel"), 10),
                weight: parseInt(obj.attr("weight"), 10)
              });
            }

            // Finally, we can add the item itself
            ops.push({
              op: "MoveItem",
              timeslotitemid: parseInt(li.attr("timeslotitemid"), 10),
              oldchannel: parseInt(oldChannel, 10),
              oldweight: parseInt(oldWeight, 10),
              channel: parseInt(li.attr("channel"), 10),
              weight: parseInt(li.attr("weight"), 10)
            });
          }
          /**
           * The important bit - ship the change operations over to the server to update the remote datastructure,
           * the change log, and to propogate the changes to any other clients that may be active.
           */
          if (ops != "") {
            shipChanges(ops, next);
          } else {
            $(this).dequeue();
            myradio.showAlert("No changes were made.", "success");
          }
        } else {
          $(this).dequeue();
          myradio.showAlert("No changes were made.", "success");
        }
      }
    );
  };

  var registerItemClicks = function () {
    // Used by dragdrop - enables the selected item to move down on drag/drop
    $("ul.baps-channel li").off("mousedown.predrag").on(
      "mousedown.predrag",
      function () {
        $(this).attr(
          "nextSelect",
          typeof $(this).next().attr("id") !== "undefined" ? $(this).next().attr("id") : $(this).prev().attr("id")
        );
      }
    );
    $("ul.baps-channel li").off("click.playactivator").on(
      "click.playactivator",
      function (e) {
        var channel = $(this).parent(".baps-channel").attr("channel");
        if (!getPlayer(channel).paused) {
          myradio.showAlert("Cannot load track whilst another is playing.", "warning");
          e.stopPropagation();
          return false;
        }
        if ($(this).hasClass("undigitised")) {
          //Can't select the track - it isn't digitised
          myradio.showAlert($(this).html() + " has not been digitised.", "danger");
          e.stopPropagation();
          return false;
        }
        if ($(this).hasClass("unclean")) {
          //This track may have naughty words, but don't block selection
          myradio.showAlert("<strong>" + $(this).html() + "</strong> is explicit. Do not broadcast before 9pm.", "danger");
        }
        //Set this track as the active file for this channel
        //First, we need to remove the active class for any other file in the channel
        $(this).parent("ul").children().removeClass("selected");
        $(this).addClass("selected");
        loadItem(channel);
      }
    );
    $("ul.baps-channel li").tooltip({
      delay: 500,
      placement: "auto right",
      container: "body"
    });
  };

  // Sets up global listeners
  var setupGenericListeners = function () {
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
    $(document).on(
      "keydown.bapscontrol",
      function (e) {
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
      }
    );
  };

  var updateChannelTotalTimers = function () {
    $(".baps-channel").each(function () {
      var time = 0;
      $(this).children("li").each(function () {
        var tmp = $(this).attr("length").split(":");
        if (tmp.length !== 3) {
          return;
        }
        time += parseInt(tmp[1], 10) * 60;
        time += parseInt(tmp[2], 10);
      });
      $("#" + $(this).attr("id") + "-total").html("(" + timeMins(time) + ":" + timeSecs(time) + ")");
    });
  };

  // Context Menu - Helper Functions
  // Adapted From https://github.com/callmenick/Custom-Context-Menu
  /**
   * Function to check if we clicked inside an element with a particular class
   * name.
   *
   * @param {Object} e The event
   * @param {String} className The class name to check against
   * @return {Boolean}
   */
  function clickInsideElement( e, className ) {
    var el = e.srcElement || e.target;

    if ( el.classList.contains(className) ) {
      return el;
    } else {
      while ( el == el.parentNode ) {
        if ( el.classList && el.classList.contains(className) ) {
          return el;
        }
      }
    }

    return false;
  }

  /**
   * Get's exact position of event.
   *
   * @param {Object} e The event passed in
   * @return {Object} Returns the x and y position
   */
  function getPosition(e) {
    var posx = 0;
    var posy = 0;

    if (!e) e = window.event;

    if (e.pageX || e.pageY) {
      posx = e.pageX;
      posy = e.pageY;
    } else if (e.clientX || e.clientY) {
      posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
      posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
    }

    return {
      x: posx,
      y: posy
    };
  }

  // Context Menu - Core Functions

  /**
   * Variables.
   */
  var contextMenuLinkClassName = "context-menu__link";
  var contextMenuActive = "context-menu--active";

  var taskItemClassName = "showplan-item";
  var channelClassName = "channel-list";
  var taskItemInContext;
  var channelInContext;
  var contextBapsChannel;

  var clickCoords;
  var clickCoordsX;
  var clickCoordsY;

  var menu = document.querySelector("#context-menu");
  var menuInner = document.querySelector(".context-menu__items");
  var menuState = 0;
  var menuWidth;
  var menuHeight;

  var windowWidth;
  var windowHeight;

  /**
   * Initialise our application's code.
   */
  function initContextMenu() {
    contextListener();
    clickListener();
    keyupListener();
    resizeListener();
  }

  /**
   * Listens for contextmenu events.
   */
  function contextListener() {
    document.addEventListener( "contextmenu", function(e) {
      channelInContext = clickInsideElement( e, channelClassName );
      taskItemInContext = clickInsideElement( e, taskItemClassName );
      if ( channelInContext || taskItemInContext) {
        e.preventDefault();
        if ( !taskItemInContext ) {
          contextBapsChannel = "#baps-channel-" + (channelInContext.getAttribute("channel"));
          $("#context-menu-delete").hide();
          $("#context-menu-edit").hide();
        } else {
          if (taskItemInContext.getAttribute("channel") == "res") {
            contextBapsChannel = "#baps-channel-res";
            $("#context-menu-delete").hide();
          } else {
            contextBapsChannel = "#baps-channel-" + (parseInt(taskItemInContext.getAttribute("channel"), 10)+1);
            $("#context-menu-delete").show();
          }
          if (taskItemInContext.getAttribute("type") == "central") {
          //will only show if user has permissions via twig template.
            $("#context-menu-edit").show();
          } else {
            $("#context-menu-edit").hide();
          }
        }
        $(".contextIcon-AutoAdvance").css("visibility", $(contextBapsChannel).attr("autoadvance")==1 ? "visible" : "hidden");
        $(".contextIcon-PlayOnLoad").css("visibility", $(contextBapsChannel).attr("playonload")==1 ? "visible" : "hidden");
        $(".contextIcon-Repeat0").css("visibility", $(contextBapsChannel).attr("repeat")==0 ? "visible" : "hidden");
        $(".contextIcon-Repeat1").css("visibility", $(contextBapsChannel).attr("repeat")==1 ? "visible" : "hidden");
        $(".contextIcon-Repeat2").css("visibility", $(contextBapsChannel).attr("repeat")==2 ? "visible" : "hidden");

        toggleMenuOn();
        positionMenu(e);
      } else {
        taskItemInContext = null;
        toggleMenuOff();
      }
    });
  }

  /**
   * Listens for click events.
   */
  function clickListener() {
    document.addEventListener( "click", function(e) {
      var clickeElIsLink = clickInsideElement( e, contextMenuLinkClassName );

      if ( clickeElIsLink ) {
        e.preventDefault();
        menuItemListener( clickeElIsLink );
      } else {
        var button = e.which || e.button;
        if ( button === 1 ) {
          toggleMenuOff();
        }
      }
    });
  }

  /**
   * Listens for keyup events.
   */
  function keyupListener() {
    window.onkeyup = function(e) {
      if ( e.keyCode === 27 ) {
        toggleMenuOff();
      }
    };
  }

  /**
   * Window resize event listener
   */
  function resizeListener() {
    window.onresize = function() {
      toggleMenuOff();
    };
  }

  /**
   * Turns the custom context menu on.
   */
  function toggleMenuOn() {
    if ( menuState !== 1 ) {
      menuState = 1;
      menu.classList.add( contextMenuActive );
    }
  }

  /**
   * Turns the custom context menu off.
   */
  function toggleMenuOff() {
    if ( menuState !== 0 ) {
      menuState = 0;
      menu.classList.remove( contextMenuActive );
    }
  }

  /**
   * Positions the menu properly.
   *
   * @param {Object} e The event
   */
  function positionMenu(e) {
    clickCoords = getPosition(e);
    clickCoordsX = clickCoords.x;
    clickCoordsY = clickCoords.y;
    menuWidth = menuInner.offsetWidth + 4;
    menuHeight = menuInner.offsetHeight + 4;

    windowWidth = window.innerWidth;
    windowHeight = window.innerHeight;

    if ( (windowWidth - clickCoordsX) < menuWidth ) {
      menu.style.left = windowWidth - menuWidth + "px";
    } else {
      menu.style.left = clickCoordsX + "px";
    }

    if ( (windowHeight - clickCoordsY) < menuHeight ) {
      menu.style.top = windowHeight - menuHeight + "px";
    } else {
      menu.style.top = clickCoordsY + "px";
    }
  }

  /**
   * The function that makes clicking options on the context menu work!
   *
   * @param {HTMLElement} link The link that was clicked
   */
  function menuItemListener( link ) {
    var currentAction = link.getAttribute("data-action");

    var invert = function (obj, attr) {
      if ($(obj).attr(attr) == 1) {
        $(obj).attr(attr, 0);
      } else {
        $(obj).attr(attr, 1);
      }
    };

    switch (currentAction) {

    case "Delete":
      var toDelete = taskItemInContext.parentNode.removeChild(taskItemInContext);
      calcChanges(toDelete);
      break;
    case "Edit":
      //get the track ID
      var toEdit = taskItemInContext.getAttribute("id").split("-")[1];
      window.open(myradio.makeURL("Library","editTrack", {trackid: toEdit}), "_blank");
      break;
    case "AutoAdvance":
      invert(contextBapsChannel, "autoadvance");
      break;
    case "PlayOnLoad":
      invert(contextBapsChannel, "playonload");
      break;
    case "Repeat0":
      $(contextBapsChannel).attr("repeat", 0);
      break;
    case "Repeat1":
      $(contextBapsChannel).attr("repeat", 1);
      break;
    case "Repeat2":
      $(contextBapsChannel).attr("repeat", 2);
      break;
    }
    toggleMenuOff();

  }

  //End of Context Menu

  // Begin auto cue

  var initAutoCue = function() {
    let nowIndicatorXPosition;
    let pixelsPerSecond;

    let calculateBarPositionFromSeek = function(startPosition, eventPosition, target) {
      // Not using nowIndicatorXPosition as that compensates for the container, these
      // values are relative to the page
      let drawPosition = eventPosition - startPosition;
      $(target).css({left: drawPosition});
      let timePosition = target.getBoundingClientRect().x - $("#baps-autocue-timebar")[0].getBoundingClientRect().x;
      let currentTimeSec = (new Date).getTime() / 1000;
      let currentOffsetSec = timePosition / pixelsPerSecond;
      let result = Math.round(currentTimeSec + currentOffsetSec);

      return result;
    };

    let scheduleBarDragStart = function (e) {
      let slider = this;
      let startPosition = e.clientX - slider.getBoundingClientRect().x;
      let startAt;
      let dragMove = function (e) {
        startAt = calculateBarPositionFromSeek(startPosition, e.clientX, slider);
        slider.querySelector(".start").innerHTML = moment(startAt * 1000).format("HH:mm:ss");
        slider.querySelector(".end").innerHTML = moment(startAt * 1000 + 60000).format("HH:mm:ss");
        return false;
      };
      let dragEnd = function () {
        slider.startAt = startAt;
        slider.isDragging = false;
        slider.parentNode.parentNode.removeEventListener("mousemove", dragMove);
        window.removeEventListener("mouseup", dragEnd);
        return false;
      };
      slider.parentNode.parentNode.addEventListener("mousemove", dragMove);
      slider.isDragging = true;
      window.addEventListener("mouseup", dragEnd);
      // Prevent bubbling the event up
      return false;
    };

    let onTimer = function() {
      nowIndicatorXPosition = $("#baps-autocue-timebar")[0].getBoundingClientRect().x - $("#baps-autocue-container")[0].getBoundingClientRect().x;
      $("#baps-autocue-time-display").text(moment().format("HH:mm:ss"));
      for (let i = 1; i <= 3; i++) {
        let scheduleBar = $("#baps-autocue-schedulebar-" + i);
        if (!players[i].duration || scheduleBar[0].isDragging) {
          continue;
        }
        let channelHasSchedule = !!scheduleBar[0].startAt;

        if (!players[i].paused) {
          scheduleBar[0].startAt = 0;
          scheduleBar.find(".start").text("");
          scheduleBar.find(".end").text(moment().add(players[i].duration - players[i].currentTime, "seconds").format("HH:mm:ss"));
          scheduleBar.css({
            left: nowIndicatorXPosition - (players[i].currentTime * pixelsPerSecond),
            backgroundColor: "#5bc0de"
          });
        } else if (!channelHasSchedule) {
          scheduleBar.find(".end").text("");
          scheduleBar.css({
            left: nowIndicatorXPosition - (players[i].currentTime * pixelsPerSecond),
            backgroundColor: "#777"
          });
        } else {
          let startAtOffset = scheduleBar[0].startAt - (new Date).getTime() / 1000;
          let startAtPositionOffset = startAtOffset * pixelsPerSecond;
          scheduleBar.find(".start").text(moment().add(startAtOffset, "seconds").format("HH:mm:ss"));
          scheduleBar.find(".end").text(moment().add(startAtOffset + players[i].duration, "seconds").format("HH:mm:ss"));
          scheduleBar.css({
            left: nowIndicatorXPosition + startAtPositionOffset,
            backgroundColor: "#5bc85c"
          });

          if (startAtOffset <= 0) {
            // The UI currently doesn't respond to the players be interacted with directly
            // So I did some evil. Other bits of this file do this too...
            $("#ch" + i + "-play").click();
          }
        }
      }
    };

    setInterval(onTimer, 50);

    for (let i = 1; i <= 3; i++) {
      players[i].addEventListener("durationchange", function() {
        if (!this.duration) {
          return;
        }

        this.isDragging = false;
        this.offset = 0;

        let totalDuration = (players[1].duration || 0) + (players[2].duration || 0) + (players[3].duration || 0);
        let availableWidth = $("#baps-autocue-container").width() - nowIndicatorXPosition;
        pixelsPerSecond = Math.min(availableWidth, availableWidth / totalDuration * 1.3);

        for (let j = 1; j <= 3; j++) {
          let thisDuration = players[j].duration;
          $("#baps-autocue-schedulebar-" + j).width(thisDuration * pixelsPerSecond);
        }
      });
      $("#baps-autocue-schedulebar-" + i).on("mousedown", scheduleBarDragStart);
    }
  };

  // End auto cue

  var initialiseUI = function () {
    if (writable) {
      $("ul.baps-channel").sortable({
        //connectWith allows drag and drop between the channels
        connectWith: "ul.baps-channel",
        //A distance dragged of 15 before entering the dragging state
        //Prevents accidentally dragging when clicking
        distance: 15,
        //Adds a placeholder highlight where the item will be dropped
        placeholder: "alert-warning",
        //Remove the "selected" class from the item - prevent multiple selected items in a channel
        //Also activate the next/previous item, if there is one
        start: function (e, ui) {
          if (ui.item.hasClass("selected")) {
            ui.item.removeClass("selected");
            if (ui.item.attr("nextSelect") != null) {
              $("#" + ui.item.attr("nextSelect")).click();
            }
          }
          ui.item.nextSelect = null;
        },
        stop: function (e, ui) {
          /**
           * Update the channel timers
           */
          updateChannelTotalTimers();
          calcChanges(ui.item);
        }
      });
    }

    registerItemClicks();
    setupGenericListeners();
    updateChannelTotalTimers();
    initContextMenu();
    initAutoCue();
  };

  var getChannelInt = function (channel) {
    if (channel == "res") {
      return 0;
    } else {
      return channel;
    }
  };

  // Create the player for the given channel
  var initialisePlayer = function (channel) {
    var outputDevice = "default";

    try {
      if (localStorage && localStorage.hasOwnProperty("nipsWebDeviceMapping")) {
        var audioSinks = JSON.parse(localStorage.nipsWebDeviceMapping);
        if (audioSinks[channel]) {
          outputDevice = audioSinks[channel];
        }
      }
    } catch (e) {
      console.info("Local Storage is being mean.", e);
    }

    if (channel == 0) {
      channel = "res";
    }

    sliders[getChannelInt(channel)] = playoutSlider(document.getElementById("progress-bar-" + channel));

    var a = new Audio();
    a.cueTime = 0;
    a.justStopped = false;
    a.nipswebId = getChannelInt(channel);

    players[a.nipswebId] = a;

    setupListeners(channel);

    var ul = document.getElementById("baps-channel-"+channel);
    ul.setAttribute("autoadvance", 1);
    ul.setAttribute("repeat", 0);
    ul.setAttribute("playonload", 0);

    if (outputDevice !== "default") {
      navigator.mediaDevices.getUserMedia({audio: true}).then(function() {
        a.setSinkId(outputDevice)
          .catch(function(error) {
            console.error("Failed to change output according to localStorage", a, outputDevice, error);
          });
      });
    }
  };

  // Sets up listeners per channel
  var setupListeners = function (channel) {
    var player = getPlayer(channel);
    var slider = sliders[(channel === "res") ? 0 : channel];
    var channelDiv = $("#baps-channel-" + channel);

    $(player).on(
      "ended",
      function () {
        var el = $("#baps-channel-" + channel + " li.selected");
        stopping(channel);
        if (channelDiv.attr("autoadvance") == 1 && parseInt(channelDiv.attr("repeat"), 10) !== 1) {
          var next = el.next("li");
          if (!next.length && el.parent().attr("repeat") == 2) {
            next = el.parent().children().first();
          }
          if (next && next.length) {
            el.removeClass("selected");
            next.click();
          }
        } else if (parseInt(channelDiv.attr("repeat"), 10) === 1) {
          player.currentTime = player.cueTime;
          player.play();
          playing(channel);
        } else {
          player.currentTime = player.cueTime;
        }
      }
    );

    $(player).on(
      "timeupdate",
      function () {
        getTime(channel);
        sliders[getChannelInt(channel)].position(player.currentTime);
      }
    );

    $(player).on(
      "durationchange",
      function () {
        getDuration(channel);
        if ($("#baps-channel-" + channel + " li.selected[type=\"central\"]").length !=0) {
          sliders[getChannelInt(channel)].reset(
            player.duration,
            0,
            $("#baps-channel-" + channel + " li.selected").attr("intro")
          );
        } else {
          sliders[getChannelInt(channel)].reset(
            player.duration,
            0,
            0
          );
        }
      }
    );

    $(slider).on(
      "seeked",
      function (e) {
        if (e.originalEvent.detail.time && isFinite(e.originalEvent.detail.time)) {
          player.currentTime = parseFloat(e.originalEvent.detail.time.toPrecision(12));
        }
      }
    );

    $(slider).on(
      "introChanged",
      function (e) {
        if ($(channelDiv).children(".selected[type=\"central\"]").length != 0) {
          var file;
          var trackid = getRecTrackFromID($(channelDiv).children(".selected")[0].getAttribute("id"))[1];
          $(channelDiv).children(".selected")[0].setAttribute("intro", parseInt(e.originalEvent.detail.time), 10);
          myradio.callAPI("PUT","track","intro",trackid, "", {duration: e.originalEvent.detail.time},
            function (data) {
              for (file in data) {
                if (file === "myradio_errors") {
                  continue;
                }
              }
              if (!data.status) {
                myradio.showAlert("We couldn't save that intro, try reloading.", "danger");
              } else {
                myradio.showAlert("Intro Updated Successfully", "success");
              }
            }
          );
        }
      }
    );

    $(slider).on(
      "cueChanged",
      function (e) {
        if (player.cueTime >= player.currentTime && player.paused) {
          player.currentTime = e.originalEvent.detail.time;
        }
        player.cueTime = e.originalEvent.detail.time;
      }
    );

    $("#ch" + channel + "-play").on(
      "click",
      function () {
        play(channel);
      }
    );

    $("#ch" + channel + "-pause").on(
      "click",
      function () {
        pause(channel);
      }
    );

    $("#ch" + channel + "-stop").on(
      "click",
      function () {
        stop(channel);
      }
    );

    $("#baps-channel-" + channel + "-name").on(
      "click",
      function() {
        new ChannelConfigurator(getPlayer(channel));
      }
    );
  };

  // Returns the player element for the given channel
  var getPlayer = function (channel) {
    if (channel === "res") {
      channel = 0;
    }
    return players[channel];
  };

  var getRecTrackFromID = function (id) {
    id = id.split("-");

    var data = [];
    data[0] = id[0];
    data[1] = id[1];

    for (var i = 2; i < id.length; i++) {
      data[1] = data[1] + "-" + id[i];
    }

    return data;
  };

  var loadItem = function (channel) {
    $("#ch" + channel + "-play, #ch" + channel + "-pause, #ch" + channel + "-stop").attr("disabled", "disabled");
    $("#ch" + channel + "-pause").removeClass("btn-warning").addClass("btn-default");
    //Find the active track for this channel
    var audioid = $("#baps-channel-" + channel + " li.selected").attr("id");
    var data = getRecTrackFromID(audioid);
    var type = $("#baps-channel-" + channel + " li.selected").attr("type");
    if (type === "central") {
      //Central Database Track
      $.ajax({
        url: myradio.makeURL("NIPSWeb", "create_token"),
        type: "post",
        data: "trackid=" + data[1] + "&recordid=" + data[0],
        success: function () {
          var params = {
            recordid: data[0],
            trackid: data[1]
          };
          if (getPlayer(channel).canPlayType("audio/mpeg")) {
            getPlayer(channel).type = "audio/mpeg";
          } else if (getPlayer(channel).canPlayType("audio/ogg")) {
            getPlayer(channel).type = "audio/ogg";
            params.ogg = true;
          } else {
            myradio.showAlert("Sorry, you need to use a modern browser to use Track Preview.", "danger");
          }
          getPlayer(channel).src = myradio.makeURL("NIPSWeb", "secure_play", params);
          $(getPlayer(channel)).off("canplay.forloaded").on(
            "canplay.forloaded",
            function () {
              $("#ch" + channel + "-play").removeAttr("disabled");
              if (this.justStopped === false && $("#baps-channel-" + channel).attr("playonload") == 1) {
                this.play();
                playing(channel);
              }
              this.justStopped = false;
            }
          );
        }
      });
    } else if (type === "aux") {
      getPlayer(channel).src = myradio.makeURL(
        "NIPSWeb",
        "managed_play",
        {managedid: $("#" + audioid).attr("managedid")}
      );
      $(getPlayer(channel)).on(
        "canplay",
        function () {
          $("#ch" + channel + "-play").removeAttr("disabled");
        }
      );
    }
    getPlayer(channel).cueTime = 0;
  };

  var playing = function (channel) {
    getPlayer(channel).nwIsPlaying = true;
    $("#ch" + channel + "-play").removeClass("btn-default").addClass("btn-primary");
    $("#ch" + channel + "-pause, #ch" + channel + "-stop")
      .removeAttr("disabled")
      .removeClass("btn-warning")
      .addClass("btn-default");
  };

  var stopping = function (channel) {
    getPlayer(channel).nwIsPlaying = false;
    $("#ch" + channel + "-play").removeClass("btn-primary").addClass("btn-default");
    $("#ch" + channel + "-pause").removeClass("btn-warning").addClass("btn-default").attr("disabled", "disabled");
    $("#ch" + channel + "-stop").attr("disabled", "disabled");
  };

  var play = function (channel) {
    var player = getPlayer(channel);
    player.nwIsPlaying = true;
    player.play();
    playing(channel);
  };

  var pause = function (channel) {
    var player = getPlayer(channel);
    if (player.paused) {
      player.play();
      playing(channel);
    } else {
      player.pause();
      player.nwIsPlaying = false;
      $("#ch" + channel + "-play").removeClass("btn-primary").addClass("btn-default");
      $("#ch" + channel + "-pause").removeClass("btn-default").addClass("btn-warning");
      $("#ch" + channel + "-stop").removeAttr("disabled");
    }
  };

  var stop = function (channel) {
    stopping(channel);
    var player = getPlayer(channel);
    player.pause();
    player.justStopped = true;
    player.currentTime = player.cueTime;
  };

  return {
    debug: debug,
    initialiseUI: initialiseUI,
    initialisePlayer: initialisePlayer,
    registerItemClicks: registerItemClicks
  };

};

var playoutSlider = function (e) {
  var duration = 0;
  var cue = 0;
  var intro = 0;
  var positionInt = 0;
  var isSliding = false;

  /**
   * DOM ELEMENTS
   */
  var sliderContainer = document.createElement("div");
  sliderContainer.className = "playout-slider";

  var cueSlider = document.createElement("div");
  cueSlider.className = "playout-slider-cue";
  var cueHandle = document.createElement("div");
  cueHandle.className = "playout-handle";
  var cueHandleCircle = document.createElement("div");
  cueHandleCircle.className = "playout-handle-circle";
  cueHandleCircle.title = "Drag to set the cue position";
  cueHandle.appendChild(cueHandleCircle);
  cueSlider.appendChild(cueHandle);
  sliderContainer.appendChild(cueSlider);

  var introSlider = document.createElement("div");
  introSlider.className = "playout-slider-intro";
  var introHandle = document.createElement("div");
  introHandle.className = "playout-handle";
  var introHandleCircle = document.createElement("div");
  introHandleCircle.className = "playout-handle-circle";
  introHandleCircle.title = "Drag to set the intro duration";
  introHandle.appendChild(introHandleCircle);
  introSlider.appendChild(introHandle);
  sliderContainer.appendChild(introSlider);

  var positionSlider = document.createElement("div");
  positionSlider.className = "playout-slider-position";
  var positionSliderLine = document.createElement("div");
  positionSliderLine.className = "playout-slider-line";
  positionSlider.appendChild(positionSliderLine);
  var positionHandle = document.createElement("div");
  positionHandle.className = "playout-handle";
  positionSlider.appendChild(positionHandle);
  sliderContainer.appendChild(positionSlider);

  /**
   * HELPER FUNCTIONS
   */
  var calculatePositionFromSeek = function (e, slider) {
    var result = e.clientX - getXOffset(e.currentTarget) + 3;
    if (result > sliderContainer.offsetWidth) {
      result = sliderContainer.offsetWidth;
    }
    slider.style.width = result + "px";
    return Math.max(0, Math.round(result / getPixelsPerSecond() * 100) / 100);
  };

  var getXOffset = function (e) {
    var x = 0;
    while (e) {
      x += e.offsetLeft + e.clientLeft - e.scrollLeft;
      e = e.offsetParent;
    }
    return x;
  };

  /**
   * EVENT BINDINGS
   **/
  var positionHandleDragStart = function () {
    var positionInt;
    if (!isSliding) {
      isSliding = true;

      var dragMove = function (e) {
        positionInt = calculatePositionFromSeek(e, positionSlider);
        return false;
      };
      var dragEnd = function () {
        sliderContainer.dispatchEvent(new CustomEvent("seeked", {detail: {time: positionInt}}));

        sliderContainer.removeEventListener("mousemove", dragMove);
        window.removeEventListener("mouseup", dragEnd);
        isSliding = false;
        return false;
      };
      sliderContainer.addEventListener("mousemove", dragMove);
      window.addEventListener("mouseup", dragEnd);
      return false;
    }
  };
  positionHandle.addEventListener("mousedown", positionHandleDragStart);

  var introHandleDragStart = function () {
    if (!isSliding) {
      isSliding = true;

      var dragMove = function (e) {
        intro = calculatePositionFromSeek({clientX: e.clientX, currentTarget: introSlider}, introSlider);
        return false;
      };
      var dragEnd = function () {
        sliderContainer.dispatchEvent(new CustomEvent("introChanged", {detail: {time: intro}}));

        sliderContainer.parentNode.parentNode.removeEventListener("mousemove", dragMove);
        window.removeEventListener("mouseup", dragEnd);
        isSliding = false;
        return false;
      };
      sliderContainer.parentNode.parentNode.addEventListener("mousemove", dragMove);
      window.addEventListener("mouseup", dragEnd);
      return false;
    }
  };
  introHandle.addEventListener("mousedown", introHandleDragStart);

  var cueHandleDragStart = function () {
    if (!isSliding) {
      isSliding = true;

      var dragMove = function (e) {
        cue = calculatePositionFromSeek({clientX: e.clientX, currentTarget: cueSlider}, cueSlider);
        return false;
      };
      var dragEnd = function () {
        sliderContainer.dispatchEvent(new CustomEvent("cueChanged", {detail: {time: cue}}));

        sliderContainer.parentNode.parentNode.removeEventListener("mousemove", dragMove);
        window.removeEventListener("mouseup", dragEnd);
        isSliding = false;
        return false;
      };
      sliderContainer.parentNode.parentNode.addEventListener("mousemove", dragMove);
      window.addEventListener("mouseup", dragEnd);
      return false;
    }
  };
  cueHandle.addEventListener("mousedown", cueHandleDragStart);

  // Needs to go after drag handlers to ensure they set isSliding first
  var clickHandler = function (e) {
    if (!isSliding) {
      var positionInt = calculatePositionFromSeek(e, positionSlider);
      sliderContainer.dispatchEvent(new CustomEvent("seeked", {detail: {time: positionInt}}));
      return false;
    }
  };
  sliderContainer.addEventListener("mousedown", clickHandler);

  var reset = function (newDuration, newCue, newIntro) {
    duration = parseInt(newDuration, 10);
    cue = parseInt(newCue, 10);
    intro = parseInt(newIntro, 10);
    positionInt = 0;
    redraw();
  };

  var getPixelsPerSecond = function () {
    return (duration > 0 ? (sliderContainer.offsetWidth - 2)/duration : 0);
  };

  var position = function (newPosition) {
    if (newPosition !== undefined) {
      if (!isSliding) {
        positionInt = newPosition;
        redraw();
      }
    } else {
      return positionInt;
    }
  };

  var redraw = function () {
    cueSlider.style.width = cue * getPixelsPerSecond() + "px";
    introSlider.style.width = intro * getPixelsPerSecond() + "px";
    positionSlider.style.width = positionInt * getPixelsPerSecond() + "px";
  };

  var addEventListener = function (a, b, c) {
    sliderContainer.addEventListener(a, b, c);
  };

  var removeEventListener = function (a, b, c) {
    sliderContainer.removeEventListener(a, b, c);
  };

  //Attach the seekbar to the DOM
  e.className = "playout-slider-container";
  e.appendChild(sliderContainer);

  //Detect resize
  window.addEventListener("resize", redraw);

  return {
    reset: reset,
    position: position,
    addEventListener: addEventListener,
    removeEventListener: removeEventListener
  };

};

playoutSlider.prototype = {
  constructor: playoutSlider
};
