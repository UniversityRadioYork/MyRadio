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
            $.get(myury.makeURL('SIS', 'webcam.set'), {src: newcam}, function(data) {
                if (data['error']) {
                    myury.createDialog('Webcam Error', data['error']);
                    return;
                }
                update.call(this, data);
            });
        },
        update = function(data) {
            for (i in data['streams']) {
                if (!webcams.hasOwnProperty(data['streams'][i]['streamid'])) {
                    var button = document.createElement('button'),
                        figure = document.createElement('figure'),
                        streamid = data['streams'][i]['streamid'];
                    webcams[i['streamid']] = {
                        button: button,
                        figure: figure
                    };

                    button.innerHTML(data['streams'][i]['streamname']);
                    button.addEventListener('click', function() {
                        selectWebcam(streamid);
                    });
                    
                    figure.setAttribute('src', data['streams'][i]['liveurl']);

                    buttonContainer.appendChild(button);
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

            currentWebcam = data['status']['current'];
            this.registerParam('webcam_id', currentWebcam);
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
