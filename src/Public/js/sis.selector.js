/* global myradio, sis */
/* Selector */
var Selector = function () {
  var studios = [
      "Studio 1",
      "Studio 2",
      "Jukebox",
      "Outside Broadcast"
    ],
    that,
    buttons = [],
    onAir = document.createElement("span"),
    lastTime = 0,
    currentStudio,
    locked = true,
    selectStudio = function () {
      var studio = this.getAttribute("studio");
      if (studio == currentStudio) {
        return;
      }
      if (this.getAttribute("on") == false) {
        return;
      }

      if (locked) {
        myradio.createDialog("Error", "Could not change studio.<br>Studio selector is currently locked out.");
        return;
      }
      $.get(
        myradio.makeURL("SIS", "selector.set"),
        {src: studio},
        function (data) {
          if (data["error"] == "locked") {
            myradio.createDialog("Selector Error", "Could not change studio; studio selector is currently locked out.");
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
      var liveStatus, s, studioNum, studioNumIndex, time = parseInt(data["lastmod"]);
      // Disregard data older than the latest update
      if (time <= lastTime) {
        return;
      }
      lastTime = parseInt(data["lastmod"]);
      // When called bet selectStudio, this isn't what I think it is
      // @todo, see if that can be bound nicer
      that.registerParam("selector-lasttime", lastTime);

      if (data["ready"]) {
        for (studioNum = 1; studioNum <= 4; studioNum++) {
          studioNumIndex = studioNum-1;
          if (studioNum != 3) {
            if (!data["s" + studioNum + "power"]) {
              powered = false;
            } else {
              powered = true;
            }
          } else {
            powered = true;
          }
          if (powered) {
            liveStatus = (data["studio"] == studioNum) ? "s" + studioNum + "on" : "s" + studioNum + "off";
            buttons[studioNumIndex].setAttribute("title", studios[studioNumIndex]);
            buttons[studioNumIndex].setAttribute("class", "selbtn poweredon " + liveStatus);
            buttons[studioNumIndex].setAttribute("on", "true");
          } else {
            buttons[studioNumIndex].setAttribute("title", studios[studioNumIndex] + " Powered Off");
            buttons[studioNumIndex].setAttribute("class", "selbtn poweredoff s" + studioNum + "off");
            buttons[studioNumIndex].setAttribute("on", "false");
          }
        }
        s = "<strong>" + studios[data["studio"] - 1] + "</strong> is On Air.";
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
        button.setAttribute("class", "selbtn s" + (i+1) + "off");
        button.setAttribute("id", "s" + i);
        button.setAttribute("title", studios[i]);
        button.setAttribute("studio", (i + 1));
        button.setAttribute("on", "true");
        button.addEventListener("click", selectStudio);
        row.appendChild(button);
        buttons.push(button);
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
