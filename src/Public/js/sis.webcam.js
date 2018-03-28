/* global myradio, sis */
/* Webcam */
var Webcam = function () {
  var webcams = [],
    buttonContainer = document.createElement("div"),
    figureContainer = document.createElement("div"),
    onAir = document.createElement("span"),
    currentWebcam,
    selectWebcam = function (newcam) {
      if (newcam === currentWebcam) {
        return;
      }
      $.get(myradio.makeURL("SIS", "webcam.set"), {src: newcam});
    },
    update = function (data) {
      if (currentWebcam === undefined) {
        this.show();
      }

      if (data["status"]["current"] === -1) {
        this.innerHTML = "It looks like webcams haven't been set up yet.";
      } else {
        for (var i in data["streams"]) {
          if (!webcams.hasOwnProperty(data["streams"][i]["streamid"])) {
            var button = document.createElement("button"),
              figure = document.createElement("figure"),
              caption = document.createElement("figcaption"),
              img = document.createElement("img"),
              streamid = data["streams"][i]["streamid"],
              camera = data["streams"][i]["camera"],
              clickHandler = function (camera) {
                return function () {
                  selectWebcam(camera);
                };
              }(camera);

            webcams[data["streams"][i]["streamid"]] = {
              button: button,
              figure: figure
            };

            button.innerHTML = data["streams"][i]["streamname"];
            button.className = "btn btn-default";
            button.addEventListener("click", clickHandler);

            img.setAttribute("src", data["streams"][i]["liveurl"]);

            setInterval(function (image, source) {
              image.setAttribute("src", source + "?_=" + Date.now());
            }, 5000, img, data["streams"][i]["liveurl"]);

            caption.innerHTML = data["streams"][i]["streamname"];

            figure.className = "webcam-stream-container";
            if (streamid === 1) {
              figure.className = figure.className + " live";
            } else {
              buttonContainer.appendChild(button);
            }
            figure.appendChild(img);
            figure.appendChild(caption);

            figureContainer.appendChild(figure);
          }
        }

        for (var j in webcams) {
          if (j == data["status"]["current"]) {
            webcams[j]["button"].setAttribute("disabled", "disabled");
            webcams[j]["figure"].style.display = "none";
          } else {
            webcams[j]["button"].removeAttribute("disabled", "disabled");
            webcams[j]["figure"].style.display = "inline-block";
          }
        }
      }

      currentWebcam = data["status"]["camera"];
      if (currentWebcam !== -1) {
        onAir.innerHTML = "<strong>" + data["status"]["location"] + "</strong> is selected.";
      } else {
        onAir.innerHTML = "Webcam information is not available at this time.";
      }
      this.registerParam("webcam-id", currentWebcam);
    };

  return {
    name: "Webcam Selector",
    type: "plugin",
    initialise: function () {
      onAir.innerHTML = "Webcam unavailable &mdash; Loading";
      this.appendChild(buttonContainer);
      this.appendChild(figureContainer);
      this.appendChild(onAir);
      this.registerParam("webcam-id", 0);
    },
    update: update
  };
};

sis.registerModule("webcam", new Webcam());
