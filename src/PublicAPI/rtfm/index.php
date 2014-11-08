<!DOCTYPE html>
<html>
<head>
  <title>MyRadiopi Documentation</title>
  <link href='https://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'/>
  <link href='css/reset.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='css/reset.css' media='print' rel='stylesheet' type='text/css'/>
  <link href='css/screen.css' media='print' rel='stylesheet' type='text/css'/>
  <link rel="stylesheet" type="text/css" href="https://ury.org.uk/portal/css/main.css" />
  <script type="text/javascript" src="lib/shred.bundle.js"></script>
  <script src='lib/jquery-1.8.0.min.js' type='text/javascript'></script>
  <script src='lib/jquery.slideto.min.js' type='text/javascript'></script>
  <script src='lib/jquery.wiggle.min.js' type='text/javascript'></script>
  <script src='lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
  <script src='lib/handlebars-1.0.0.js' type='text/javascript'></script>
  <script src='lib/underscore-min.js' type='text/javascript'></script>
  <script src='lib/backbone-min.js' type='text/javascript'></script>
  <script src='lib/swagger.js' type='text/javascript'></script>
  <script src='swagger-ui.js' type='text/javascript'></script>
  <script src='lib/highlight.7.3.pack.js' type='text/javascript'></script>

  <!-- enabling this will enable oauth2 implicit scope support -->
  <script src='lib/swagger-oauth.js' type='text/javascript'></script>

  <script type="text/javascript">
    $(function () {
      window.swaggerUi = new SwaggerUi({
      url: "https://<?php echo $_SERVER['HTTP_HOST'].str_replace('/rtfm','',$_SERVER['REQUEST_URI']); ?>resources/resources",
      dom_id: "swagger-ui-container",
      supportedSubmitMethods: ['get', 'post', 'put'],
      onComplete: function(swaggerApi, swaggerUi){
        log("Loaded SwaggerUI");

        if(typeof initOAuth == "function") {
          /*
          initOAuth({
            clientId: "your-client-id",
            realm: "your-realms",
            appName: "your-app-name"
          });
          */
        }
        $('pre code').each(function(i, e) {
          hljs.highlightBlock(e)
        });
      },
      onFailure: function(data) {
        log("Unable to Load SwaggerUI");
      },
      docExpansion: "none"
    });

    $('#input_apiKey').change(function() {
      var key = $('#input_apiKey')[0].value;
      log("key: " + key);
      if(key && key.trim() != "") {
        log("added key " + key);
        window.authorizations.add("key", new ApiKeyAuthorization("api_key", key, "query"));
      }
    })
    window.swaggerUi.load();
    $('form').on('submit', function(e) {
      e.preventDefault();
      $('#explore').click();
    });
  });
  </script>
</head>

<body>
    <header id="pageHeader" class="clearfix">
        <img id="logo-img" alt="University Radio York" src="//ury.org.uk/static/img/logo.png" />
        <h1 style='color:white;font-family:helvetica,arial,sans-serif;font-size:32px;padding:0 0 0 20px'>University Radio York</h1>
    </header>
    <div class="main transBG clearfix" id="grid">
        <header id="content-header">
            <h2 style="color:white;font-family:helvetica,arial,sans-serif;padding:0;font-weight:bold">API Documentation</h2>
        </header>
        <div id="content-body" class="swagger-section">
            <div id='header' style="background: none;">
              <div class="swagger-ui-wrap">
                <form id='api_selector'>
                  <div class='input'><input placeholder="http://example.com/api" id="input_baseUrl" name="baseUrl" type="hidden"/></div>
                  <div class='input'><input id="input_apiKey" name="apiKey" type="text" placeholder="Leave blank for MyRadio Session auth" style="width:350px" /></div>
                  <div class='input'><a id="explore" href="#" style="background-color: #363d5f;">Explore</a></div>
                </form>
              </div>
            </div>

            <div id="message-bar" class="swagger-ui-wrap"></div>
            <div class="swagger-ui-wrap">
                Hello and welcome to MyRadiopi, URY's basic REST Service. It's a very basic system at the moment,
                and this documentation is all updated automatically, so may sometimes appear a bit wonky.
                <p>Questions? Email lpw [at] ury.org.uk</p>
            </div>
            <div id="swagger-ui-container" class="swagger-ui-wrap"></div>
        </div>
    </div>
    <footer id="pageFooter" class="poster clearfix">
        Powered By <a target="_blank" href="https://github.com/wordnik/swagger-ui">SwaggerUI</a>
        <div style="float:right">Maintained by <a href="mailto:webmaster@ury.org.uk">URY Computing Team</a></div>
    </footer>

</html>
