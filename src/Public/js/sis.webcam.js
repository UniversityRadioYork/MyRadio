/* Webcam */
var Webcam = function() {
    var webcams = [],
        that,
        buttonContainer = document.createElement('div'),
        figureContainer = document.createElement('div'),
        onAir = document.createElement('span'),
        currentWebcam,
        selectWebcam = function(newcam) {
            if (newcam === currentWebcam) {
                return;
            }
            $.get(myury.makeURL('SIS', 'webcam.set'), {src: newcam});
        },
        update = function(data) {

            if (currentWebcam === undefined) {
                this.show();
            }

            if (data['status']['current'] === -1) {
                this.innerHTML = 'It looks like webcams haven\'t been set up yet.';
            } else {
                for (i in data['streams']) {
                    if (!webcams.hasOwnProperty(data['streams'][i]['streamid'])) {
                        var button = document.createElement('button'),
                            figure = document.createElement('figure'),
                            caption = document.createElement('figcaption'),
                            img = document.createElement('img'),
                            streamid = data['streams'][i]['streamid'],
                            clickHandler = function(streamid) {
                                return function() {
                                    selectWebcam(streamid);
                                }
                            }(streamid);

                        webcams[data['streams'][i]['streamid']] = {
                            button: button,
                            figure: figure
                        };

                        button.innerHTML = data['streams'][i]['streamname'];
                        button.className = 'btn btn-default';
                        button.addEventListener('click', clickHandler);
                        
                        img.setAttribute('src', data['streams'][i]['liveurl']);
                        caption.innerHTML = data['streams'][i]['streamname'];

                        figure.className = 'webcam-stream-container';
                        if (streamid === 1) {
                            figure.className = figure.className + ' live';
                        } else {
                            buttonContainer.appendChild(button);
                        }
                        figure.appendChild(img);
                        figure.appendChild(caption);

                        figureContainer.appendChild(figure);
                    }
                }

                for (i in webcams) {
                    if (i == data['status']['current']) {
                        webcams[i]['button'].setAttribute('disabled', 'disabled');
                        webcams[i]['figure'].style.display = 'none';
                    } else {
                        webcams[i]['button'].removeAttribute('disabled', 'disabled');
                        webcams[i]['figure'].style.display = 'inline-block';
                    }
                }
            }

            currentWebcam = data['status']['current'];
            onAir.innerHTML = data['streams'][currentWebcam]['streamname'] + ' is On Air';
            this.registerParam('webcam-id', currentWebcam);
        };

	return {
        name: 'Webcam Selector',
        type: 'plugin',
        initialise: function() {
            onAir.innerHTML = 'Webcam unavailable &mdash; Loading';
            this.appendChild(buttonContainer);
            this.appendChild(figureContainer);
            this.appendChild(onAir);
            this.registerParam('webcam-id', 0);
        },
        update: update
    }
}

sis.registerModule('webcam', new Webcam());
