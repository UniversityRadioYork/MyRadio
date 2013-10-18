window.timelord = {
  months: ["January", "February", "March", "April", "May", "June", "July",
    "August", "September", "October", "November", "December"],
  routeobinfo: {s1: false, s2: false, s3: false},
  news: false,
  /**
   * Shows a breaking news message
   * @param String message
   * @returns {window.timelord}
   */
  showMsg: function(message) {
    $('#breaking-container').html(message).show();
    $('#current-show, #next-show').hide();
    return this;
  },
  /**
   * Hides a breaking news message
   * @returns {window.timelord}
   */
  hideMsg: function() {
    $('#breaking-container').hide();
    $('#current-show, #next-show').show();
    return this;
  },
  /**
   * Updates the time and date display
   * @returns {window.timelord}
   */
  updateTime: function() {
    var date = new Date();
    $('#time').html(timelord.pad(date.getHours()) + ':' + timelord.pad(date.getMinutes()) +
            ':' + timelord.pad(date.getSeconds()));
    $('#date').html(date.getDate() + timelord.getDateSuffix(date.getDate()) + ' ' +
            timelord.months[date.getMonth()] + ' ' + date.getFullYear());

    return this;
  },
  /**
   * Add leading zero to make a 2 digit number.
   * @param int input
   * @returns {String|Window.timelord.pad.input}
   */
  pad: function(input) {
    input = input.toString();
    if (input.length === 1)
      input = '0' + input;
    return input;
  },
  /**
   * Returns the current date suffix, e.g. st, nd, rd, th
   * @param int day
   * @returns {String}
   */
  getDateSuffix: function(day) {
    if (day > 10 && day < 14)
      return 'th';
    day = day.toString();
    day = day.split("");
    var last = day.length - 1;
    if (day[last] > 3 || day[last] === 0)
      return 'th';
    if (day[last] === 1)
      return 'st';
    if (day[last] === 2)
      return 'nd';
    if (day[last] === 3)
      return 'rd';
  },
  /**
   * Update the current studio display
   * @param int num Sel value.
   * @returns {window.timelord}
   */
  setStudio: function(num) {
    $('#studio').removeClass('studio1').removeClass('studio2')
            .removeClass('studio3').removeClass('studio4');
    $('#studio').addClass('studio' + num);
    switch (num) {
      case 1:
      case 2:
        $('#studio').html('Studio ' + num + ' is On Air');
        break;
      case 3:
        $('#studio').html('Jukebox is On Air');
        break;
      case 4:
        $('#studio').html('Outside Broadcast');
        break;
      default:
        $('#studio').html('Unknown Output');
        break;
    }
    return this;
  },
  setCurrentShow: function(text) {
    $('#current-show-title').html(text);

    return this;
  },
  setNextShows: function(next) {
    if (next[0] == null) {
      $('#next-show').html('');
    } else {
      d0 = (new Date(next[0].start_time*1000));
      start0 = timelord.pad(d0.getHours())+':'+timelord.pad(d0.getMinutes());
      d1 = (new Date(next[0].start_time*1000));
      start1 = timelord.pad(d1.getHours())+':'+timelord.pad(d1.getMinutes());
      $('#next-show').html('Up Next: ' + next[0].title + ' @ ' + start0 + '<br>'
              + next[1].title + ' @ ' + start1);
    }

    return this;
  },
  /**
   * Resets any alert states
   * @param String alert An ID for one of the alert fields
   * @returns {window.timelord}
   */
  resetAlert: function(alert) {
    $('#' + alert).removeClass('bad').removeClass('good').removeClass('standby');
    return this;
  },
  /**
   * Sets an alert state
   * @param String alert
   * @param String state bad|standby|good
   * @returns {window.timelord}
   */
  setAlert: function(alert, state) {
    this.resetAlert(alert);
    $('#' + alert).addClass(state);
    return this;
  },
  /**
   * Update the view
   * @returns {window.timelord}
   */
  updateState: function() {
    $.ajax({url: myury.makeURL('Timelord', 'a-update'),
      success: function(data) {
        timelord.setStudio(data.selector.studio)
                .setCurrentShow(data.shows.current.title)
                .setNextShows(data.shows.next);

        //Update info message
        if (!timelord.news) {
          if (data.breaking !== null) {
            timelord.showMsg(data.breaking.content);
          } else {
            timelord.hideMsg();
          }
        }

        //Update Studio 1 Alert
        if (data.selector.studio === 1) {
          timelord.setAlert('power-s1', 'good');
        } else if (data.selector.power === 1 || data.selector.power === 3) {
          timelord.setAlert('power-s1', 'standby');
        } else {
          timelord.resetAlert('power-s1');
        }

        //Update Studio 2 Alert
        if (data.selector.studio === 2) {
          timelord.setAlert('power-s2', 'good');
        } else if (data.selector.power === 2 || data.selector.power === 3) {
          timelord.setAlert('power-s2', 'standby');
        } else {
          timelord.resetAlert('power-s2');
        }

        //Update OB Alerts
        for (i in timelord.routeobinfo) {
          if (data.ob[i]) {
            timelord.setAlert('routeob-' + i, 'good');
            if (timelord.routeobinfo[i] !== true
                    && timelord.routeobinfo[i] !== false) {
              clearTimeout(timelord.routeobinfo[i]);
            }
            timelord.routeobinfo[i] = true;
          } else if (timelord.routeobinfo[i] !== false) {
            timelord.setAlert('routeob-' + i, 'bad');
            if (timelord.routeobinfo[i] === true) {
              timelord.routeobinfo[i] = setTimeout("timelord.routeobinfo['" + i + "'] = false", 30000);
            }
          } else {
            timelord.resetAlert('routeob-' + i);
          }
        }

        //Update Silence Alert
        if (data.silence >= 5) {
          timelord.setAlert('silence', 'bad');
        } else {
          timelord.resetAlert('silence');
        }
      },
      complete: function() {
        setTimeout(timelord.updateState, 3000);
      }});

    return this;
  },
  /**
   * Displays a message when it's news time.
   * @returns {window.timelord}
   */
  newsWarn: function() {
    var date = new Date();
    if ((date.getMinutes() === 59 && date.getSeconds() >= 15)
            || (date.getMinutes() === 0 && date.getSeconds() <= 5)) {
      timelord.news = true;
      if (date.getSeconds() < 0 && date.getSeconds() >= 15) {
        timelord.showMsg('<span class="news">News intro in '
                +(45-date.getSeconds())+'...</span>');
        $('#next-show').show();
      } else {
        timelord.showMsg('<span class="news">Online at ury.org.uk and across'
                        +' campus on 1350am, <em>this</em> is URY News</span>');
      }
    } else {
      timelord.news = false;
    }
    return this;
  },
  /**
   * Startup
   * @returns {window.timelord}
   */
  init: function() {
    setInterval(timelord.updateTime, 250);
    setInterval(timelord.newsWarn, 250);
    //Chromium and its darn memory leaks.
    setTimeout("window.location = window.location.href", 18000000);
    timelord.updateState();
    return this;
  }
};

$(document).ready(function() {
  timelord.init();
});