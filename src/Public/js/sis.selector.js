/* Selector */
var Selector = function() {
    var studios = [
            'Studio 1',
            'Studio 2',
            'Jukebox',
            'Outside Broadcast'
        ],
        that,
        buttons = [],
        onAir = document.createElement('span'),
        lastTime = 0,
        currentStudio,
        locked = true,
        selectStudio = function() {
            var studio = this.getAttribute('studio');
            if (studio == currentStudio) {
                return;
            }
            if (this.getAttribute('on') == false) {
                return;
            }
            
            if (locked) {
                myury.createDialog('Error', 'Could not change studio.<br>Studio selector is currently locked out.');
                return;
            }

            $.get(myury.makeURL('SIS', 'selector.set'), {src: studio}, function(data) {
                if (data['error'] == 'locked') {
                    myury.createDialog('Selector Error', 'Could not change studio; studio selector is currently locked out.');
                    return;
                }
                if (data['error']) {
                    myury.createDialog('Selector Error', data['error']);
                    return;
                }
                update.call(this, data);
            });
        },
        update = function(data) {
            var liveStatus, s;
            lastTime = parseInt(data['lastmod']);
            // When called bet selectStudio, this isn't what I think it is
            // @todo, see if that can be bound nicer
            that.registerParam('selector-lasttime', lastTime);

            if (!data['s1power']) {
                buttons[0].setAttribute('title', studios[0] + ' Powered Off');
                buttons[0].setAttribute('class', 'selbtn poweredoff');
                buttons[0].setAttribute('on', 'false');
            } else {
                liveStatus = (data['studio'] == 1) ? 's1on' : 's1off';
                buttons[0].setAttribute('title', studios[0]);
                buttons[0].setAttribute('class', 'selbtn poweredon ' + liveStatus);
                buttons[0].setAttribute('on', 'true');
            }

            if (!data['s2power']) {
                buttons[1].setAttribute('title', studios[1] + ' Powered Off');
                buttons[1].setAttribute('class', 'selbtn poweredoff');
                buttons[1].setAttribute('on', 'false');
            } else {
                liveStatus = (data['studio'] == 2) ? 's2on' : 's2off';
                buttons[1].setAttribute('title', studios[1]);
                buttons[1].setAttribute('class', 'selbtn poweredon ' + liveStatus);
                buttons[1].setAttribute('on', 'true');
            }

            liveStatus = (data['studio'] == 3) ? 's3on' : 's3off';
            buttons[2].setAttribute('class', 'selbtn poweredon ' + liveStatus);

            s = studios[data['studio'] - 1] + ' On Air';
            currentStudio = data['studio'];
            if (data['lock'] != 0) {
              s = s + '<small> &mdash; Locked</small>';
              locked = true;
            } else {
                locked = false;
            }
            onAir.innerHTML = s;
        };

	return {
        name: 'Studio Selector',
        type: 'plugin',
        initialise: function() {
            var table = document.createElement('table'),
                row = document.createElement('tr'),
                button;
            that = this;
            table.setAttribute('id', 'selector-buttons');
            table.appendChild(row);

            for (i in studios) {
                i = parseInt(i);
                button = document.createElement('td');
                button.setAttribute('class', 'selbtn s' + (i+1) + 'off');
                button.setAttribute('id', 's' + i);
                button.setAttribute('title', studios[i]);
                button.setAttribute('studio', (i + 1));
                button.setAttribute('on', 'true');
                button.addEventListener('click', selectStudio);
                row.appendChild(button);
                buttons.push(button);
            }
            
            onAir.innerHTML = 'Selector unavailable &mdash; Locked';
            this.appendChild(table);
            this.appendChild(onAir);
            this.registerParam('selector-lasttime', lastTime);
        },
        update: update
    }
}

sis.registerModule('selector', new Selector());
