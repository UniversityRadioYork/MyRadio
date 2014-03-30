/**
 * Handles the interactivityness of timeslot selection
 */
$('#shows').on('change', function() {
    $('#seasons').empty();
    $('#timeslots').empty();
    $('#signin-list').empty();
    var seriesno = 1;
    for (series in window.showdata[$(this).val()]) {
        $('#seasons').append('<option value="' + (seriesno - 1) + '">Season ' + seriesno + '</option>');
        seriesno++;
    }
});
$('#seasons').on('change', function() {
    $('#timeslots').empty();
    $('#signin-list').empty();
    var season = window.showdata[$('#shows').val()][$(this).val()];
    for (timeslot in season) {
        var a = (new Date(season[timeslot][1] * 1000));
        $('#timeslots').append('<option value="' + season[timeslot][0] + '">'
                + (a.getDate() < 10 ? '0' : '') + a.getDate() + '/'
                + (a.getMonth()+1 < 10 ? '0' : '') + parseInt(a.getMonth()+1) + '/'
                + a.getFullYear() + ' '
                + (a.getHours() < 10 ? '0' : '') + a.getHours() + ':'
                + (a.getMinutes() < 10 ? '0' : '') + a.getMinutes() + '</option>');
    }
});
$('#timeslots').on('change', function() {
    if ($(this).val() !== null) {
        $('#signin-submit').show();
        //Okay, now if the show is <> 2hours, let them sign in
        timeslots = window.showdata[$('#shows').val()][$('#seasons').val()];
        var start = 0;
        var end = 0;
        for (id in timeslots) {
            if (timeslots[id][0] == $(this).val()) {
                console.log(timeslots[id]);
                start = timeslots[id][1] * 1000;
                end = timeslots[id][2] * 1000;
                break;
            }
        }
        var now = (new Date).getTime();
        if (start > now - (3600000 * 2) && end < now + (3600000 * 2)) {
            $('#signin-list').show().html('Loading...');
            $.ajax({
                url: myury.makeURL('MyRadio', 'a-timeslotSignin'),
                data: {timeslotid: $(this).val()},
                success: function(data) {
                    $('#signin-list').html('Sign in to your show:<br>');
                    for (row in data) {
                        var check = $('<input type="checkbox"></input>');
                        var label = $('<label></label>');
                        console.log(data[row]);
                        check.attr('name', 'signin[]')
                                .attr('id', 'signin_'+data[row]['user']['memberid'])
                                .attr('value', data[row]['user']['memberid']);
                        label.attr('for', 'signin_'+data[row]['user']['memberid'])
                                .html(data[row]['user']['fname'] + ' ' + data[row]['user']['sname']);
                        if (data[row]['signedby'] != null) {
                            check.attr('checked', 'checked')
                                    .attr('disabled', 'true');
                            label.append(' (Signed in by '+data[row]['signedby']['fname'] + ' '+data[row]['signedby']['sname'] + ')');
                        } else if (data[row]['user']['memberid'] == window.memberid) {
                            check.attr('checked', 'checked');
                        }
                        $('#signin-list').append(check).append(label).append('<br>');
                    }
                }
            });
        } else {
            $('#signin').hide();
        }
    } else {
        $('#signin,#signin-submit').hide();
    }
});

//Now we're going to select the closest timeslot
var closest = [null, null, null, null];
var seconds = (new Date).getTime() / 1000;
shows = window.showdata;
for (show in shows) {
    for (season in shows[show]) {
        for (timeslot in shows[show][season]) {
            var drift = Math.abs(shows[show][season][timeslot][1] - seconds);
            if (closest[0] === null || drift < closest[0]) {
                closest[0] = drift;
                closest[1] = show;
                closest[2] = season;
                closest[3] = shows[show][season][timeslot][0];
            }
        }
    }
}
if (closest[0] !== null) {
    $('#shows').val(closest[1]).trigger('change');
    $('#seasons').val(closest[2]).trigger('change');
    $('#timeslots').val(closest[3]).trigger('change');
}
