/**
* Options:
* container - div that will hold the schedule view
* ui - Whether to show previous/next week buttons
* view - 'day' or 'week', default 'week'
* day - Only useful in 'day' view, sets the DOW
* week - Week number to start at
* year - Year to start at (four digits)
*/
var ScheduleView = function (options) {
    var year;
    var week;
    /**
    * Day is a value between 0 and 8 using the ISO standard for weeks
    * 1 is the Monday of the ISO week
    * 7 is the Sunday of the ISO week
    * 0 is the Sunday *preceeding* the ISO week
    * 8 is the Monday *after* the ISO week
    * 0 and 8 are automatically adjusted for.
    */
    var day;
    var startTime;
    var currentData;
    var ui = true;
    var view = 'week';
    var scheduleContainerDiv = document.createElement('div');
    var pixelFactor = 3600 / 50;
    var addNowMarker = function (dayDiv) {
        var marker = document.createElement('div');
        marker.innerHTML = 'Now (' + moment().format('HH:mm') + ')';
        marker.className = 'scheduleview now-marker bg-success';
        dayDiv.appendChild(marker);
    }
    var loadWeek = function () {
        var newStartTime = moment(year + '-' + week, 'YYYY-W');
        if (!newStartTime.isSame(startTime)) {
            startTime = newStartTime;
            $.ajax(
                {
                    url: mConfig.api_url + '/v2' + '/timeslot/9dayschedule/' + week,
                    data: {
                        year: year
                    },
                    success: updateView
                }
            );
        } else {
            updateView(currentData);
        }
    };
    var updateView = function (data) {
        currentData = data;
        var currentDay;
        var dayDiv;
        var lastTime = startTime;
        var nextDayAsISO;
        var nextFound = false;
        var targetDay = moment(startTime).isoWeekday(day);
        var targetNextDay = moment(targetDay).add(1, 'd');
        var today = targetDay.isSame(moment(), 'day');
        resetView();

        if (view === 'day') {
            dayDiv = document.createElement('div');
            scheduleContainerDiv.appendChild(dayDiv);
        }

        for (var i = 0; i < data.payload.length; i++) {
            var show = data.payload[i];
            var time = moment(show.start_time, 'DD/MM/YYYY HH:mm');
            var duration = moment.duration(show.duration);
            var endTime = moment(time).add(duration);

            var showDay = moment(time).startOf('day');
            var showDiv = document.createElement('div');
            var showText = document.createElement('div');
            var showCredits = document.createElement('span');
            var showDescription = document.createElement('div');
            var startTimeDiv = document.createElement('time');
            var endTimeDiv = document.createElement('time');
            var height = (duration.asSeconds() / pixelFactor) + 'px';

            if (view === 'week') {
                if (!currentDay || !currentDay.isSame(showDay)) {
                    currentDay = showDay;
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
            }

            if (view === 'week'
                || time.isSame(targetDay, 'day')
                || (time.isSame(targetNextDay, 'day') && time.hour() <= 6)
            ) {
                startTimeDiv.innerHTML = time.format('HH:mm');
                endTimeDiv.innerHTML = endTime.format('HH:mm');

                showCredits.innerHTML = ' with ' + show.credits_string;
                showCredits.className = 'show-credits';
                showText.className = 'show-title';
                showText.innerHTML = show.title;
                showText.appendChild(showCredits);

                showDescription.className = 'show-description';
                showDescription.innerHTML = show.description;

                showDiv.appendChild(startTimeDiv);
                showDiv.appendChild(document.createTextNode(' - '));
                showDiv.appendChild(endTimeDiv);
                showDiv.appendChild(showText);
                showDiv.appendChild(showDescription);

                showDiv.className = 'show';
                if (nextFound === false
                    && today
                    && (time.isAfter() || endTime.isAfter())
                ) {
                    showDiv.className = showDiv.className + ' nownext bg-warning';
                    addNowMarker(dayDiv);
                    nextFound = true;
                }
                if (view === 'week') {
                    showDiv.style.height = height;
                } else {
                    showDiv.style.minHeight = height;
                }
                dayDiv.appendChild(showDiv);
                lastTime = endTime;
            }
        }
    }
    var resetView = function () {
        var title,
            targetDay = moment(startTime).isoWeekday(day);

        while (scheduleContainerDiv.hasChildNodes()) {
            scheduleContainerDiv.removeChild(scheduleContainerDiv.lastChild);
        }

        var titleDiv = document.createElement('div');
        titleDiv.className = 'scheduleview header';
        if (ui) {
            var previous = document.createElement('button');
            previous.className = 'btn btn-link';
            previous.innerHTML = 'Previous ' + view;
            previous.addEventListener(
                'click',
                function () {
                    if (view === 'day') {
                        day = day - 1;
                    }
                    if (view === 'week' || day <= 0) {
                        week = week - 1;
                        if (week < 1) {
                            year = year - 1;
                            week = startTime.subtract(1, 'y').isoWeeksInYear();
                        }
                        day = 7;
                    }
                    loadWeek();
                }
            );
            titleDiv.appendChild(previous);
        }

        if (view === 'day') {
            title = 'Schedule for ' + targetDay.format('dddd, DD/MM/YYYY');
        } else if (view === 'week') {
            title = 'Schedule for week commencing ' + startTime.format('DD/MM/YYYY');
        }
        titleDiv.appendChild(document.createTextNode(title));

        if (ui) {
            var next = document.createElement('button');
            next.className = 'btn btn-link';
            next.innerHTML = 'Next ' + view;
            next.addEventListener(
                'click',
                function () {
                    if (view === 'day') {
                        day = day + 1;
                    }
                    if (view === 'week' || day >= 8) {
                        week = week + 1;
                        if (week > startTime.isoWeeksInYear()) {
                            year = year + 1;
                            week = 1;
                        }
                        day = 1;
                    }
                    loadWeek();
                }
            );
            titleDiv.appendChild(next);
        }
        scheduleContainerDiv.appendChild(titleDiv);

        if (view === 'week') {
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
        }
    };
    var assembleDayDiv = function (time) {
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
    if (options.hasOwnProperty('day')) {
        day = options.day;
    } else {
        day = moment().isoWeekday();
    }

    if (options.hasOwnProperty('ui')) {
        ui = options.ui;
    }
    if (options.hasOwnProperty('view')) {
        view = options.view;
    }

    //Set up view
    scheduleContainerDiv.style.position = 'relative';
    scheduleContainerDiv.className = 'scheduleview scheduleview-view-' + view;
    options.container.appendChild(scheduleContainerDiv);
    loadWeek();
}

ScheduleView.prototype = {
    constructor: ScheduleView
};

