/* global myradio, moment, sis */
/* Messages */
var Messages = function () {
  var highest_message_id = 0,
    unreadMessages = 0,
    glyphicons = ["question-sign", "envelope", "phone", "globe"],
    locationNames = ["unknown method", "email", "text message", "the website"],
    table = document.createElement("table"),
    clickHandler = function (context, row, message) {
      $(row).click(function () {
        if ($(this).hasClass("unread")) {
          //This is the first time the message has been opened. Mark as read
          $.ajax({
            url: myradio.makeURL("SIS", "messages.markread", {"id": message["id"]})
          });
          unreadMessages--;
          context.setUnread(unreadMessages);
          $(this).removeClass("unread");
        }
        var locationName;
        locationName = locationNames[message.type];
        var dateTime;
        var time;
        dateTime = moment.unix(message.time);
        time = dateTime.format("HH:mm");
        var location = "";
        if (message.location) {
          location = message.location[0];
          if (message.location.length >= 2) {
            location = location + " (" + message.location[1] + ")";
          }
        }
        myradio.createDialog("Message", "<blockquote><p>" +message.body + "</p><footer>Listener via " + locationName + " at <cite>" + time + "</cite>.</footer></blockquote>" + location,  [myradio.closeButton()]);
      });
    };

  table.setAttribute("class", "messages");

  return {
    name: "Messages",
    type: "tab",
    initialise: function () {
      $(this).append(table);
    },
    update: function (data) {
      for (var i in data) {
        var locationStr,
          icon_location,
          icon_unread,
          msgdate,
          time,
          classes,
          newRow = document.createElement("tr"),
          locationTd = document.createElement("td"),
          unreadTd = document.createElement("td"),
          titleTd = document.createElement("td"),
          dateTd = document.createElement("td"),
          dateDate = document.createElement("date");

        //Add the content dialog div
        locationStr = "";
        for (var l in data[i]["location"]) {
          locationStr = locationStr + "<br>"+data[i]["location"][l];
        }

        //Set some of the variables
        icon_location = "<div class='glyphicon glyphicon-" + glyphicons[data[i]["type"]] + "' title='Location: " + locationNames[data[i]["type"]] + ", click for more details.'></div>";
        icon_unread = "<div class='unread-dot' title='Message unread, click to mark read.'></div>";
        msgdate = moment.unix(data[i]["time"]);
        time = msgdate.format("HH:mm");
        classes = "";
        if (data[i]["read"] === false) {
          classes = classes + " unread";
          unreadMessages++;
          this.setUnread(unreadMessages);
        }
        newRow.className = "td-msgitem"+classes;
        newRow.setAttribute("id", "m"+data[i]["id"]);

        locationTd.innerHTML = icon_location;
        unreadTd.innerHTML = icon_unread;

        var longTitle = String(data[i]["title"]);
        var trimmedTitle = longTitle.substring(0, 120);
        if (longTitle != trimmedTitle) {
          trimmedTitle = trimmedTitle + "... <a>Read more</a>";
        }
        titleTd.innerHTML = trimmedTitle;

        dateDate.innerHTML = time;
        dateDate.setAttribute("datetime", msgdate.toISOString());
        dateTd.appendChild(dateDate);

        newRow.appendChild(unreadTd);
        newRow.appendChild(locationTd);
        newRow.appendChild(titleTd);
        newRow.appendChild(dateTd);

        //Add the new row to the top of the messages table
        $(table).prepend(newRow);

        //Add the onclick handler for the new row
        clickHandler(this, newRow, data[i]);

        //Increment the highest message id, if necessary
        highest_message_id = (highest_message_id < data[i]["id"]) ? data[i]["id"] : highest_message_id;
      }
      //Update the server's highest id parameter
      this.registerParam("messages_highest_id", highest_message_id);
    }
  };
};

sis.registerModule("messages", new Messages());
