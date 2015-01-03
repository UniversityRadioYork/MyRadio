/**
* Options:
* container - div that will hold the schedule view
* ui - Whether to show previous/next week buttons
* week - Week number to start at
* year - Year to start at (four digits)
*/
var ScheduleView = function(options) {
	var year;
	var week;
	var startTime;
	var ui = true;
	var scheduleContainerDiv = document.createElement('div');
	var pixelFactor = 3600 / 50;
	var loadWeek = function() {
		startTime = moment(year + '-' + week, 'YYYY-W');
		$.ajax({
			url: 'https://ury.org.uk/api-dev' + '/Timeslot/getWeekSchedule',
			data: {
				year: year,
				weekno: week,
				apiKey: 'leemCJbTdfjpmcdCUXo97N8XM1NFOGDqt8SnOcBgfHlarGT6yOTy6c72lrdx2GFubmg1O7WpCGApq7y4JD1W7ksF4vnrdeXDyYWXq0tzBXXBazR2YbXc2ZODJzqZMEHd'
			},
			success: function(data) {
				var currentDay;
				var dayDiv;
				var lastTime = startTime;
				resetView();
				for (var i = 0; i < data.payload.length; i++) {
					var show = data.payload[i];
					var time = moment(show.start_time, 'DD/MM/YYYY HH:mm');
					var day = moment(time).startOf('day');
					var showDiv = document.createElement('div');
					var showText = document.createTextNode(show.title);
					var timeDiv = document.createElement('time');
					var duration = moment('1970-01-01 ' + show.duration);

					if (!currentDay || !currentDay.isSame(day)) {
						currentDay = day;
						if (dayDiv) {
							scheduleContainerDiv.appendChild(dayDiv);
						}
						dayDiv = assembleDayDiv(currentDay);
						lastTime = currentDay;
					}

					//Fill in the gap
					if (lastTime.unix() !== time.unix()) {
						var fillerDiv = document.createElement('div');
						fillerDiv.className = 'filler';
						fillerDiv.style.height = (time.unix() - lastTime.unix()) / pixelFactor + 'px';
						dayDiv.appendChild(fillerDiv);
					}

					timeDiv.innerHTML = time.format('HH:mm');
					showDiv.appendChild(timeDiv);
					showDiv.appendChild(showText);

					showDiv.className = 'show';
					showDiv.style.height = (duration.unix() / pixelFactor) + 'px';
					dayDiv.appendChild(showDiv);
					lastTime = moment.unix(time.unix() + duration.unix());
				}
			}
		});
	};
	var resetView = function() {
		while (scheduleContainerDiv.hasChildNodes()) {
			scheduleContainerDiv.removeChild(scheduleContainerDiv.lastChild);
		}

		var titleDiv = document.createElement('div');
		titleDiv.className = 'scheduleview header';
		if (ui) {
			var previous = document.createElement('button');
			previous.className = 'btn btn-link';
			previous.innerHTML = 'Previous week';
			previous.addEventListener('click', function() {
				week = week - 1;
				if (week < 1) {
					year = year - 1;
					week = startTime.subtract(1, 'y').isoWeeksInYear();
				}
				loadWeek();
			});
			titleDiv.appendChild(previous);
		}
		titleDiv.appendChild(document.createTextNode('Schedule for week commencing ' + startTime.format('DD/MM/YYYY')));
		if (ui) {
			var next = document.createElement('button');
			next.className = 'btn btn-link';
			next.innerHTML = 'Next week';
			next.addEventListener('click', function() {
				week = week + 1;
				if (week > startTime.isoWeeksInYear()) {
					year = year + 1;
					week = 1;
				}
				loadWeek();
			});
			titleDiv.appendChild(next);
		}
		scheduleContainerDiv.appendChild(titleDiv);

		var timesDiv = document.createElement('div');
		timesDiv.className = 'scheduleview hours-list';

		var headerDiv = document.createElement('div');
		headerDiv.className = 'scheduleview day-header bg-info';
		headerDiv.innerHTML = 'Time';
		timesDiv.appendChild(headerDiv);

		for (var i = 0; i < 24; i++) {
			var hourDiv = document.createElement('div');
			hourDiv.innerHTML = i + ':00';
			hourDiv.className = 'hour';
			timesDiv.appendChild(hourDiv);
		}
		scheduleContainerDiv.appendChild(timesDiv);
	};
	var assembleDayDiv = function(time) {
		var div = document.createElement('div');
		var headerDiv = document.createElement('div');

		headerDiv.innerHTML = time.format('dddd DD/MM');
		headerDiv.className = 'scheduleview day-header bg-info';

		div.className = 'scheduleview view-day';
		div.appendChild(headerDiv);

		return div;
	};

	//Parse options
	if (options.hasOwnProperty('year')) {
		year = options.year;
	} else {
		year = moment().year();
	}
	if (options.hasOwnProperty('week')) {
		week = options.week;
	} else {
		week = moment().isoWeek();
	}

	if (options.hasOwnProperty('ui')) {
		ui = options.ui;
	}

	//Set up view
	scheduleContainerDiv.style.position = 'relative';
	options.container.appendChild(scheduleContainerDiv);
	loadWeek();

	return {

	};
}

ScheduleView.prototype = {
	constructor: ScheduleView
};

