/* Messages */
var Messages = function() {
    var highest_message_id = 0,
        unreadMessages = 0,
        glyphicons = ['question-sign', 'envelope', 'phone', 'globe'],
        table = document.createElement('table'),
        self = this,
        clickHandler = function(context, row, message) {
            $(row).click(
                function() {
                    if ($(this).hasClass('unread')) {
                        //This is the first time the message has been opened. Mark as read
                        $.ajax(
                            {
                                url: myury.makeURL('SIS', 'messages.markread', {'id': message['id']})
                            }
                        );
                        unreadMessages--;
                        context.setUnread(unreadMessages);
                        $(this).removeClass('unread');
                    }
                    var location;
                    if (message.location) {
                        location = message.location[0];
                    }
                    myury.createDialog('Message', message.body + '<hr>' + location, [myury.closeButton()]);
                }
            );
        };

    table.setAttribute('class', 'messages');

    return {
        name: 'Messages',
        type: 'tab',
        initialise: function() {
            $(this).append(table);
        },
        update: function(data) {
            for (var i in data) {
                var locationStr,
                    img,
                    msgdate,
                    mins,
                    time,
                    read,
                    classes,
                    newRow = document.createElement('tr'),
                    imgTd = document.createElement('td'),
                    titleTd = document.createElement('td'),
                    dateTd = document.createElement('td'),
                    dateDate = document.createElement('date'),
                    handler;
                //Add the content dialog div
                locationStr = "";
                for (var l in data[i]['location']) {
                    locationStr = locationStr + '<br>'+data[i]['location'][l];
                }
                //Set some of the variables
                img = "<div class='glyphicon glyphicon-" + glyphicons[data[i]['type']] + "'></div>";
                msgdate = moment.unix(data[i]['time']);
                time = msgdate.fromNow();
                read = "";
                classes = "";
                if (data[i]['read'] === false) {
                    classes = classes + " unread";
                    unreadMessages++;
                    this.setUnread(unreadMessages);
                }
                newRow.className = 'td-msgitem'+classes
                newRow.setAttribute('id', 'm'+data[i]['id']);

                imgTd.innerHTML = img;
                titleTd.innerHTML = data[i]['title'];

                dateDate.innerHTML = time;
                dateDate.setAttribute('datetime', msgdate.toISOString);
                dateTd.appendChild(dateDate);

                newRow.appendChild(imgTd);
                newRow.appendChild(titleTd);
                newRow.appendChild(dateTd);
                
                //Add the new row to the top of the messages table
                $(table).prepend(newRow);

                //Add the onclick handler for the new row
                handler = clickHandler(this, newRow, data[i]);

                //Increment the highest message id, if necessary
                highest_message_id = (highest_message_id < data[i]['id']) ? data[i]['id'] : highest_message_id;
            }
            //Update the server's highest id parameter
            this.registerParam('messages_highest_id', highest_message_id);
        }
    }
}

sis.registerModule('messages', new Messages());
