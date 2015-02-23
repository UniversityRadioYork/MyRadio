/* Schedule */
var Schedule = function() {
	var lastTime = 0;
	return {
		activeByDefault: true,
        name: 'Schedule',
        type: 'tab',
        initialise: function() {
            var s = document.createElement('script');
            s.type = "text/javascript";
            s.src = mConfig.base_url + 'js/myradio.schedule.view.js';
            document.body.appendChild(s);

            var self = this;
            s.onload = function() {
                ScheduleView({
                    view: 'day',
                    container:self
                });
            }
        }
    }
}

sis.registerModule('schedule', new Schedule());
