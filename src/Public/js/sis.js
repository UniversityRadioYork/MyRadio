/* global myradio */
var SIS = function (container) {
  var sisContainer = container,
    tabContainer = document.createElement("div"),
    tabTabsContainer = document.createElement("ul"),
    tabContentContainer = document.createElement("div"),
    pluginContainer = document.createElement("div"),
    defaultActiveFound = false,
    params = {},
    callbacks = {},
    /**
     * Starts the AJAX Comet request to the server. Will call itself after the
     * first time it is run. When the request is complete, it will call the
     * required callback functions from plugins.
     */
    connect = function () {
      $.ajax({
        url: myradio.makeURL("SIS", "remote"),
        method: "POST",
        data: params,
        cache: false,
        dataType: "json",
        //The timeout here is to prevent stack overflow
        complete: function () {
          setTimeout(connect, 100);
        },
        success: handleResponse,
        statusCode: {
          // See SIS/remote.php for why this is necessary
          400: function () {
            window.location = myradio.makeURL(
              'MyRadio',
              'timeslot',
              {
                next: window.location.pathname,
                message: window.btoa('Your session has expired, please pick a Timeslot to continue.'),
              }
            );
          }
        }
      });
    },
    /**
     * Used by connect, this function deals with the JSON object returned from the
     * server
     * @param data The JSON object returned from the server
     */
    handleResponse = function (data) {
      for (var namespace in data) {
        //Handle the Debug namespace - log the message
        if (namespace == "debug") {
          for (var message in data[namespace]) {
            console.log(data[namespace][message]);
          }
          continue;
        } else if (typeof(callbacks[namespace]) != "undefined") {
          //This namespace is registered. Execute the callback function
          callbacks[namespace](data[namespace]);
        }
      }
    },
    generateTabContainer = function (id, name) {
      var tabTab = document.createElement("li"),
        tabLink = document.createElement("a"),
        tabBadge = document.createElement("span");

      tabTab.setAttribute("role", "presentation");
      tabLink.setAttribute("role", "tab");
      tabLink.setAttribute("data-toggle", "tab");
      tabLink.setAttribute("href", "#" + id);
      tabLink.innerHTML = name + "&nbsp;";
      tabBadge.setAttribute("class", "badge");
      tabLink.appendChild(tabBadge);
      tabTab.appendChild(tabLink);
      tabTabsContainer.appendChild(tabTab);

      var container = document.createElement("div");
      container.setAttribute("class", "tab-pane");
      container.setAttribute("role", "tabpanel");
      container.setAttribute("id", id);
      tabContentContainer.appendChild(container);

      $(tabLink).click(
        function (e) {
          e.preventDefault();
          $(this).tab("show");
        }
      );

      container.setUnread = function (num) {
        if (num === 0) {
          tabBadge.innerHTML = "";
        } else {
          tabBadge.innerHTML = num;
        }
      };

      container.registerParam = function (key, value) {
        params[key] = value;
      };

      return {
        container: container,
        link: tabLink
      };
    },
    generatePluginContainer = function (id, name) {
      var panel = document.createElement("div"),
        heading = document.createElement("div"),
        title = document.createElement("h4"),
        titleLink = document.createElement("a"),
        titleBadge = document.createElement("span"),
        contentHolder = document.createElement("div"),
        content = document.createElement("div");

      panel.setAttribute("class", "panel panel-default");

      // Sets up panel header
      heading.setAttribute("class", "panel-heading");
      heading.setAttribute("role", "tab");
      heading.setAttribute("id", "heading-" + id);
      title.setAttribute("class", "panel-title");
      titleLink.setAttribute("data-toggle", "collapse");
      titleLink.setAttribute("data-parent", "#sis-plugincontainer");
      titleLink.setAttribute("href", "#collapse-" + id);
      titleLink.setAttribute("aria-expanded", "false");
      titleLink.setAttribute("aria-controls", "collapse-" + id);
      titleLink.innerHTML = name + "&nbsp;";
      titleBadge.setAttribute("class", "badge");
      titleLink.appendChild(titleBadge);
      title.appendChild(titleLink);
      heading.appendChild(title);
      panel.appendChild(heading);

      // Sets up panel content
      contentHolder.setAttribute("id", "collapse-" + id);
      contentHolder.setAttribute("class", "panel-collapse collapse");
      contentHolder.setAttribute("role", "tabpanel");
      contentHolder.setAttribute("aria-labelledby", "heading-" + id);
      content.setAttribute("class", "panel-body");
      contentHolder.appendChild(content);
      panel.appendChild(contentHolder);

      pluginContainer.appendChild(panel);
      $(contentHolder).collapse({toggle:false});

      content.setUnread = function (num) {
        if (num === 0) {
          titleBadge.innerHTML = "";
        } else {
          titleBadge.innerHTML = num;
        }
      };

      content.registerParam = function (key, value) {
        params[key] = value;
      };

      content.hide = function () {
        $(contentHolder).collapse("hide");
      };

      content.show = function () {
        $(contentHolder).collapse("show");
      };

      return {
        container: content,
        link: titleLink
      };
    };

  tabContainer.setAttribute("class", "sis-tabcontainer col-md-9");
  tabTabsContainer.setAttribute("class", "nav nav-tabs");
  tabTabsContainer.setAttribute("role", "tablist");
  tabContainer.appendChild(tabTabsContainer);
  tabContentContainer.setAttribute("class", "tab-content");
  tabContainer.appendChild(tabContentContainer);

  pluginContainer.setAttribute("class", "sis-plugincontainer col-md-3 panel-group");
  pluginContainer.setAttribute("role", "tablist");
  sisContainer.appendChild(pluginContainer);
  sisContainer.appendChild(tabContainer);

  connect();

  return {
    registerModule: function (id, module, type) {
      if (!module.hasOwnProperty("initialise") || !module.hasOwnProperty("name") || !module.hasOwnProperty("type")) {
        console.error("Cannot load " + id + " as it is invalid.");
        return;
      }

      var objs;
      if (module.type == "tab") {
        objs = generateTabContainer(id, module.name);
      } else if (module.type == "plugin") {
        objs = generatePluginContainer(id, module.name);
      }
      // Make it the active module if it is set to be
      if (defaultActiveFound === false && module.hasOwnProperty("activeByDefault") && module.activeByDefault) {
        defaultActiveFound = true;
        $(objs.link).click();
      }

      if (module.hasOwnProperty("update")) {
        callbacks[id] = function (data) {
          module.update.call(objs.container, data);
        };
      }

      module.initialise.call(objs.container, objs);
    }
  };
};
