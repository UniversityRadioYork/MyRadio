/**
 * Controls the master server poller. Tabs and plugins can register callback
 * functions and namespaces
 */
var server = {
  callbacks: [],
  params: [],
  unreadEvents: 0,
  /**
   * Registers or replaces JS Comet callback.
   * The callback_func will be executed when the given namespace exists in a
   * coment response
   * @param callback_func A function to call that takes the contents
   * of the namespace as a parameter
   * @param namespace A root array within the JSON response which will
   * trigger callback_func if nonempty
   */
  register_callback: function (callback_func, namespace) {
    server.callbacks[namespace] = callback_func;
  },
  /**
   * Sets or updates a server paramater. These are sent as part of the Comet
   * POST request. Ideally, each plugin/tab should prefix with its namespace in
   * order to prevent conflicts,
   * i.e. messages params should start with messages_ to
   * @param key The name of the parameter to send to the server
   * @param value The value of the parameter to send to the server
   */
  register_param: function (key, value) {
    server.params[key] = value;
  },
  /**
   * Starts the AJAX Comet request to the server. Will call itself after the
   * first time it is run. When the request is complete, it will call the
   * required callback functions from plugins.
   */
  connect: function () {
    $.ajax(
      {
        url: myradio.makeURL('SIS', 'remote'),
        method: 'POST',
        data: server.getQueryString(),
        cache: false,
        dataType: 'json',
        //The timeout here is to prevent stack overflow
        complete: function () {
          setTimeout('server.connect()', 100);},
        success: server.handleResponse
        statusCode: {
          // See SIS/remote.php for why this is necessary
          400: function () {
            window.location = myradio.makeURL(
              'MyRadio',
              'timeslot',
              {
                next: window.location.pathname,
                message: window.btoa('Your session has expired, please pick a Timeslot to continue.')
              }
            );
          }
        }
      }
    );
  },
  /**
   * Used by connect, this takes all the current registered server parameters
   * and returns them as a concatenated query string
   */
  getQueryString: function () {
    var qString = '';
    var first = true;
    for (var key in server.params) {
      if (!first) {
        qString += "&";
      } else {
        first = false;
      }
      qString += key + "=" + server.params[key];
    }
    return qString;
  },
  /**
   * Used by connect, this function deals with the JSON object returned from the
   * server
   * @param data The JSON object returned from the server
   */
  handleResponse: function (data) {
    for (var namespace in data) {
      //Handle the Debug namespace - log the message
      if (namespace == 'debug') {
        for (var message in data[namespace]) {
          console.log(data[namespace][message]);
        }
        continue;
      } else if (typeof(server.callbacks[namespace]) != 'undefined') {
        console.log('Callback for '+namespace+' found');
        //This namespace is registered. Execute the callback function
        server.callbacks[namespace](data[namespace]);
      } else {
        console.log('api.error.client No Callback for namespace '+namespace);
      }
    }
  },
  /**
   * Used to set the number of unread events in the page
   */
  incrementUnreadEvents: function () {
    server.unreadEvents++;
    server.updateTitle();
  },
  decrementUnreadEvents: function () {
    server.unreadEvents--;
    server.updateTitle();
  },
  /**
   * Displays the number of unread events in the title
   */
  updateTitle: function () {
    var prefix = "";
    if (server.unreadEvents !== 0) {
      prefix = '('+server.unreadEvents+') ';
    }
    document.title = prefix+'Studio Information Service';
  }
};

//The timeout give functions time to register
$(document).ready(
  function () {
    setTimeout("server.connect()", 1000);
  }
);
