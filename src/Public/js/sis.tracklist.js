/* Tracklist */
var Tracklist = function() {
    var tracklist_highest_id = 0,
        table,
        self = this,
        add_track_dialog,
        get_delete_func = function(id, title, artist) {
            return function() {
                var confirmButton = document.createElement('button');
                confirmButton.className = 'btn btn-danger';
                confirmButton.innerHTML = 'Remove';
                confirmButton.setAttribute('data-dismiss', 'modal');
                confirmButton.addEventListener(
                    'click', function() {
                        $.post(
                            myury.makeURL('SIS', 'tracklist.delTrack'),
                            {id: id},
                            function() {
                                var row = document.getElementById('t' + id);
                                row.parentNode.removeChild(row);
                            }
                        );
                    }
                );
                myury.createDialog(
                    'Confirm removal',
                    'Are you sure you want to remove ' + title + ' by ' + artist + ' from the tracklist?',
                    [confirmButton, myury.closeButton()]
                );
            };
        };

    return {
        name: 'Tracklist',
        type: 'tab',
        initialise: function() {
            var recStatusDiv,
                recStatusReset,
                recStatusCheckBad,
                trackResetAndCheck,
                addTitle,
                addArtist,
                addAlbum,
                submitButton,
                addButton,
                header,
                content,
                trackLookup,
                trackLookupId;

            recStatusReset = function() {
                recStatusDiv.className = 'alert alert-info';
                recStatusDiv.innerHTML = 'Start entering track information...';
                submitButton.setAttribute('disabled', 'disabled');
            };

            recStatusCheckBad = function() {
                if (!trackLookupId) {
                    if (addArtist.value && addAlbum.value) {
                        recStatusDiv.className = 'alert alert-danger';
                        recStatusDiv.innerHTML =
                            'This track is not currently in the library, but will be logged anyway.' + 
                            '<br>Think it is? Try searching using the title box.';
                        submitButton.removeAttribute('disabled');
                    } else {
                        recStatusReset();
                    }
                }
            };

            trackResetAndCheck = function() {
                trackLookupId = NaN;
                recStatusCheckBad();
            };

            //Build the dom elements - a helper div and some inputs
            recStatusDiv = document.createElement('div');
            recStatusDiv.className = 'alert alert-info';
            recStatusDiv.innerHTML = 'Start entering track information...';

            addTitle = document.createElement('input');
            addTitle.className = 'myradiofrmfield typeahead';
            addTitle.setAttribute('name', 'trk-title');
            addTitle.setAttribute('id', 'trk-title');
            addTitle.setAttribute('placeholder', 'Title');
            addTitle.addEventListener('blur', recStatusCheckBad);

            addArtist = document.createElement('input');
            addArtist.className = 'myradiofrmfield';
            addArtist.setAttribute('name', 'trk-artist');
            addArtist.setAttribute('id', 'trk-artist');
            addArtist.setAttribute('placeholder', 'Artist');
            addArtist.addEventListener('change', trackResetAndCheck);

            addAlbum = document.createElement('input');
            addAlbum.className = 'myradiofrmfield';
            addAlbum.setAttribute('name', 'trk-album');
            addAlbum.setAttribute('id', 'trk-album');
            addAlbum.setAttribute('placeholder', 'Album');
            addAlbum.addEventListener('change', trackResetAndCheck);

            submitButton = document.createElement('button');
            submitButton.className = 'btn btn-primary';
            submitButton.innerHTML = 'Add track';
            submitButton.setAttribute('disabled', 'disabled');
            submitButton.setAttribute('data-dismiss', 'modal');
            submitButton.addEventListener(
                'click', function() {
                    $.post(
                        myury.makeURL('SIS', 'tracklist.checkTrack'),
                        {
                            title: addTitle.value,
                            artist: addArtist.value,
                            album: addAlbum.value,
                            trackid: trackLookupId ? trackLookupId : null
                        }
                    );
                }
            );

            content = document.createElement('div');
            content.appendChild(recStatusDiv);
            content.appendChild(addTitle);
            content.appendChild(addArtist);
            content.appendChild(addAlbum);
            content.appendChild(submitButton);

            // Put all the elements into a dialog
            // don't open it straight away
            add_track_dialog = myury.createDialog(
                'Add track to tracklist',
                content,
                [myury.closeButton()],
                true
            );

            // Set up typeahead.js on the title field
            // the title needs to be in the DOM before this works, hence it being so far down
            trackLookup = new Bloodhound(
                {
                    datumTokenizer: function(i) {
                        return Bloodhound.tokenizers.whitespace(i.title)
                        .concat(Bloodhound.tokenizers.whitespace(i.artist));
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    limit: 20,
                    //Seperated out otherwise % gets urlescaped
                    remote: myury.makeURL('MyRadio', 'a-findtrack', {limit: 20, term: ''}) + '%QUERY'
                }
            );
            trackLookup.initialize();

            $(addTitle).typeahead(
                {
                    hint: false,
                    highlight: true,
                    minLength: 1
                },
                {
                    displayKey: function(i) {
                        return i.title;
                    },
                    source: trackLookup.ttAdapter(),
                    templates: {
                        suggestion: function(i) {
                            //Fix typeahead not showing after hiding
                            //TODO: Report this @ https://github.com/twitter/typeahead.js/
                            $('input:focus').parent().children('.tt-dropdown-menu').removeClass('hidden');
                            return '<p>' + i.title + '<br><span style="font-size:.8em">' + i.artist + '</span></p>';
                        }
                    }
                }
            )
            .on(
                'typeahead:selected', function(e, obj) {
                    recStatusDiv.className = 'alert alert-success';
                    recStatusDiv.innerHTML = 'This is a track in our library.';
                    submitButton.removeAttribute('disabled');
                    trackLookupId = obj.trackid;
                    addArtist.value = obj.artist;
                    addAlbum.value = obj.album.title;
                }
            );

            // Add a button that opens the dialog when clicked
            addButton = document.createElement('button');
            addButton.className = 'btn btn-link';
            addButton.innerHTML = 'Add track';
            addButton.addEventListener(
                'click', function() {
                    addTitle.value = addAlbum.value = addArtist.value = '';
                    recStatusReset();
                    trackLookupId = NaN;
                    add_track_dialog.modal();
                }
            );

            // Create a table that stores the current tracklist items
            table = document.createElement('table');
            table.setAttribute('class', 'tracklist');
            header = document.createElement('tr');
            header.innerHTML = '<th>Title</th><th>Artist</th><th>Album</th><th>Time</th><th>Remove</th>';
            table.appendChild(header);

            this.appendChild(addButton);
            this.appendChild(table);
        },
        update: function(data) {
            for (var i in data) {
                var time,
                    newRow = document.createElement('tr'),
                    titleTd = document.createElement('td'),
                    artistTd = document.createElement('td'),
                    albumTd = document.createElement('td'),
                    timeTd = document.createElement('td'),
                    deleteTd = document.createElement('td'),
                    deleteButton = document.createElement('button');

                time = moment.unix(data[i].playtime);
                newRow.className = 'td-tracklistitem';
                newRow.setAttribute('id', 't'+data[i].id);
                newRow.setAttribute('trackid', data[i].trackid);

                deleteButton.className = 'btn btn-danger';
                deleteButton.innerHTML = '<span class="glyphicon glyphicon-trash"></span>';
                deleteButton.addEventListener('click', get_delete_func(data[i].id, data[i].title, data[i].artist));

                titleTd.innerHTML = data[i].title;
                artistTd.innerHTML = data[i].artist;
                albumTd.innerHTML = data[i].album;
                timeTd.innerHTML = time.format('HH:mm');
                deleteTd.appendChild(deleteButton);

                newRow.appendChild(titleTd);
                newRow.appendChild(artistTd);
                newRow.appendChild(albumTd);
                newRow.appendChild(timeTd);
                newRow.appendChild(deleteTd);
                
                table.appendChild(newRow);

                //Increment the highest message id, if necessary
                tracklist_highest_id = (tracklist_highest_id < data[i].id) ? data[i].id : tracklist_highest_id;
            }
            //Update the server's highest id parameter
            this.registerParam('tracklist_highest_id', tracklist_highest_id);
        }
    };
};

sis.registerModule('tracklist', new Tracklist());
