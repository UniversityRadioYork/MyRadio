/* global myradio, sis */
/* Selector */
var Selector = function () {
  var studios = {
      1: "Studio 1",
      2: "Studio 2",
      3: "Jukebox",
      4: "Outside Broadcast",
      8: "Off Air"
    },
    that,
    buttons = [],
    onAir = document.createElement("span"),
    lastTime = 0,
    currentStudio,
    locked = true,
    confirm = 0, //Used to confirm switching to Off Air.
    selectStudio = function () {
      var studio = this.getAttribute("studio");
      if (studio == currentStudio) {
        return;
      }
      if (this.getAttribute("on") == false) {
        return;
      }

      if (locked) {
        myradio.createDialog("Selector Error", "Could not change studio.<br>Studio Selector is currently locked out.");
        return;
      }

      if (studio == 8) {
        if (confirm == 0) {
          confirm = studio;
          myradio.createDialog("Selector Confirmation", "Click source again to confirm switch to <strong>" + studios[studio] + "</strong>.");
        } else {
          if (confirm == studio) {
            selStudio(studio);
          }
          confirm = 0; // reset and let it continue.
        }
      } else {
        selStudio(studio);
      }
    },
    selStudio = function (studio) {
      $.get(
        myradio.makeURL("SIS", "selector.set"),
        {src: studio},
        function (data) {
          if (data["error"] == "locked") {
            myradio.createDialog("Selector Error", "Could not change studio.<br>Studio Selector is currently locked out.");
            return;
          }
          if (data["error"]) {
            myradio.createDialog("Selector Error", data["error"]);
            return;
          }
          update.call(this, data);
        }
      );
    },
    update = function (data) {
      var liveStatus, s, studioNum, time = parseInt(data["lastmod"]);
      // Disregard data older than the latest update
      if (time <= lastTime) {
        return;
      }
      lastTime = time;
      // When called bet selectStudio, this isn't what I think it is
      // @todo, see if that can be bound nicer
      that.registerParam("selector-lasttime", lastTime);

      if (data["ready"]) {
        for (studioNum in studios) {
          if (data["s" + studioNum + "power"]) {
            liveStatus = (data["studio"] == studioNum) ? "s" + studioNum + "on" : "s" + studioNum + "off";
            buttons[studioNum].setAttribute("title", studios[studioNum]);
            buttons[studioNum].setAttribute("class", "selbtn poweredon " + liveStatus);
            buttons[studioNum].setAttribute("on", "true");
          } else {
            buttons[studioNum].setAttribute("title", studios[studioNum] + " (Powered Off)");
            buttons[studioNum].setAttribute("class", "selbtn poweredoff s" + studioNum + "off");
            buttons[studioNum].setAttribute("on", "false");
          }
        }
        if (studios[data["studio"]] == undefined) {
          studios[data["studio"]] = "Source " + data["studio"] + " (Unknown)";
        }
        s = "<strong>" + studios[data["studio"]] + "</strong> is selected.";
        currentStudio = data["studio"];
        if (data["lock"] != 0) {
          s = s + "<small> &mdash; Locked</small>";
          locked = true;
        } else {
          locked = false;
        }
        onAir.innerHTML = s;
      } else {
        this.innerHTML = "It looks like Selector hasn't been set up yet.";
      }
    };

  return {
    name: "Studio Selector",
    type: "plugin",
    initialise: function () {
      var table = document.createElement("table"),
        row = document.createElement("tr"),
        button;
      that = this;
      table.setAttribute("id", "selector-buttons");
      table.appendChild(row);

      for (var i in studios) {
        i = parseInt(i);
        button = document.createElement("td");
        button.setAttribute("class", "selbtn s" + i + "off");
        button.setAttribute("id", "s" + i);
        button.setAttribute("title", studios[i]);
        button.setAttribute("studio", i);
        button.setAttribute("on", "true");
        button.innerText = i; //Button Label Numbers
        button.addEventListener("click", selectStudio);
        row.appendChild(button);
        buttons[i] = button;
      }

      onAir.innerHTML = "Selector unavailable &mdash; Locked";
      this.appendChild(table);
      this.appendChild(onAir);
      this.registerParam("selector-lasttime", lastTime);
    },
    update: update
  };
};

sis.registerModule("selector", new Selector());
