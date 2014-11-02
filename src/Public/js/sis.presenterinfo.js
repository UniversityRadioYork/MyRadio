/* Presenter Information */
var PresenterInfo = function() {
	var lastTime = 0;
	return {
		activeByDefault: true,
        name: 'Presenter Info',
        type: 'tab',
        initialise: function() {
            $(this).html('SIS is getting ready...');
            this.registerParam('presenterinfo-lasttime', lastTime);
        },
        update: function(data) {
        	lastTime = data.time;
        	$(this).html(data.info.content);
        	$(this).append('<hr>');
        	$(this).append('<footer>~ ' + data.info.author + ', ' + data.info.posted + '</footer>');
        	this.registerParam('presenterinfo-lasttime', lastTime);
        }
    }
}

sis.registerModule('presenterinfo', new PresenterInfo());
