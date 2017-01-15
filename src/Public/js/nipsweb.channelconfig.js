/* global myradio */
var ChannelConfigurator = function(player) {

  var getOrUpdateStoredDeviceMappings = function(devices) {
    var map = [];
    var unmapped = [];
    var mapped;

    var storedDevices = [];

    try {
      if (localStorage && localStorage.hasOwnProperty("nipsWebKnownDevices")) {
        storedDevices = localStorage.nipsWebKnownDevices;
      }
    } catch (e) {
      console.error("Local Storage is being mean.", e);
    }

    for (var i = 0; i < devices.length; i++) {
      if (devices[i].kind === "audiooutput" && devices[i].groupId !== "communications") {
        mapped = false;
        for (var j = 0; j < storedDevices.length; j++) {
          if (devices[i].groupId == storedDevices[j]) {
            // We've previously seen this device. Use the previous number.
            mapped = true;
            map[storedDevices[j]] = devices[i].groupId;
            break;
          }
        }
        if (!mapped) {
          // Ooh, this is a new device
          unmapped.push(devices[i]);
        }
      }
    }

    // For each unmapped device, shove it in at the end of the map.
    // Should we actually put it in the first free gap, perhaps?
    for (i = 0; i < unmapped.length; i++) {
      map.push(unmapped[i].groupId);
    }

    // And update localStorage
    try {
      localStorage.nipsWebKnownDevices = map;
    } catch (e) {
      console.error("Local Storage is being mean.", e);
    }

    return map;
  };

  var getOnDevicesReady = function(player, select) {
    return function(devices) {
      select.childNodes[0].remove();
      var map = getOrUpdateStoredDeviceMappings(devices);
      var option = document.createElement("option");

      option.textContent = "Default";
      option.value = "default";
      select.appendChild(option);

      for (var i = 0; i < map.length; i++) {
        // Skip missing numbers (i.e. removed devices)
        if (map[i].length) {
          option = document.createElement("option");
          option.textContent = "Output device #" + (i+1) + " (" + map[i].substring(0, 8) + ")";
          option.value = map[i];
          select.appendChild(option);
        }
      }

      // Set the current value, if there is one
      try {
        if (localStorage.hasOwnProperty("nipsWebDeviceMapping")) {
          var storedValue = JSON.parse(localStorage.nipsWebDeviceMapping)[player.nipswebId];
          if (storedValue) {
            select.value = storedValue;
          }
        }
      } catch (e) {
        console.error("Local Storage is being mean.", e);
      }

      select.addEventListener("change", function() {
        var sink = this.value;
        player.setSinkId(sink)
          .then(function() {
            console.log("Changed output successfully", player, sink);
            try {
              var nwdm = {};
              if (localStorage.hasOwnProperty("nipsWebDeviceMapping")) {
                nwdm = JSON.parse(localStorage.nipsWebDeviceMapping);
              }
              nwdm[player.nipswebId] = sink;
              localStorage.nipsWebDeviceMapping = JSON.stringify(nwdm);
            } catch (e) {
              console.error("Local Storage is being mean.", e);
            }
          })
          .catch(function(error) {
            console.error("Failed to change output", player, sink, error);
            select.value = "default";
          });
      });
    };
  };

  var getOnDevicesError = function(select) {
    select.childNodes[0].textContent = "Error loading devices. Sorry :-(";
  };

  var construct = function(player) {
    var container = document.createElement("div");
    var header = document.createElement("h4");
    var desc = document.createElement("p");
    var select = document.createElement("select");
    var loadingOption = document.createElement("option");
    myradio.createDialog("Configure Channel", container);
    header.textContent = "Output device";
    desc.textContent = "This feature, currently only available in Chrome, enables the mapping of channels to different actual outputs. This potentially allows the use of Show Planner as an actual BAPS replacement.";
    loadingOption.disabled = true;
    loadingOption.selected = true;
    loadingOption.textContent = "Asking nicely for devices (you may need to allow access to you microphone, but we won't use it, that's just how it's worded...)";

    container.appendChild(header);
    container.appendChild(desc);
    container.appendChild(select);
    select.appendChild(loadingOption);

    navigator.mediaDevices.getUserMedia({audio: true}).then(function() {
      navigator.mediaDevices.enumerateDevices()
        .then(getOnDevicesReady(player, select))
        .catch(getOnDevicesError(select));
    });
  };

  construct(player);
};
