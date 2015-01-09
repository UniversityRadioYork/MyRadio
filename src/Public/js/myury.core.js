window.myury = {
    makeURL: function(module, action, params) {
        qstring = (params === undefined) ? '' : $.param(params);
        if (mConfig.rewrite_url) {
            return mConfig.base_url + module + '/' + action + '/' + (qstring === '' ? '' : '?' + qstring);
        } else {
            return mConfig.base_url + '?module=' + module + '&action=' + action + (qstring === '' ? '' : '&' + qstring);
        }
    },
    errorReport: function(myradio_errors, e, xhr, settings) {
        console.log(myradio_errors);
        myury.createDialog(
            'Error',
            '<p>It looks like that request worked, but things might not quite work as expected.</p><details>' + myradio_errors + '</details>',
            [myury.closeButton(), myury.reportButton(xhr, settings, myradio_errors)]
        );
    },
    createDialog: function(title, text, buttons) {
        if (!buttons) {
            buttons = [];
        }
        var modal = $('<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><h4 class="modal-title">' + title + '</h4></div><div class="modal-body"></div><div class="modal-footer"></div></div></div></div>');
        modal.find('.modal-body').append(text);
        modal.find('.modal-footer').append(buttons);
        modal.appendTo('body');
        modal.modal();
        return modal;
    },
    closeButton: function() {
        var closeButton = document.createElement('button');
        closeButton.className = 'btn btn-link';
        closeButton.innerHTML = 'Close';
        closeButton.setAttribute('data-dismiss', 'modal');
        return closeButton;
    },
    reportButton: function(xhr, settings, error) {
        var reportButton = document.createElement('button');
        reportButton.className = 'btn btn-primary';
        reportButton.innerHTML = 'Report';
        reportButton.setAttribute('data-dismiss', 'modal');
        reportButton.addEventListener('click', function() {
            $.post(myury.makeURL('MyRadio', 'errorReport'), JSON.stringify({xhr: xhr, settings: settings, error: error}));
        });
        return reportButton;
    }
};

var errorVisible = false;
$(document).ajaxError(function(e, xhr, settings, error) {
    if (xhr.status == 401) {
        //Session timed out - need to login
        window.location = myury.makeURL('MyRadio', 'login', {next: window.location.href, message: window.btoa('Your session has expired and you need to log in again to continue.')});
    } else if (!errorVisible) {
        errorVisible = true;
        var close = myury.closeButton();
        var report = myury.reportButton(xhr, settings, error);

        var errorVisibleReset = function() {
            errorVisible = false;
        }

        close.addEventListener('click', errorVisibleReset);
        report.addEventListener('click', errorVisibleReset);

        myury.createDialog(
            'Error',
            '<p>Sorry, just went a bit wrong and I\'m not sure what to do about it.</p><details>' + error + '</details>',
            [close, report]
        );
    }
});

$(document).ajaxSuccess(function(e, xhr, settings) {
    var data;
    if (xhr === null) {
        return;
    }
    try {
        data = $.parseJSON(xhr);
    } catch (error) {
        return; //Not JSON
    }
    if (data.myury_errors !== null) {
        myury.errorReport(data.myury_errors, e, xhr, settings);
    }
});

/** Use bootstrap show/hide helpers **/
jQuery.fn.show = function() {
    $(this).removeClass('hidden')
        .css('display', 'block')
        .css('visibility', 'visible');
    return this;
}

jQuery.fn.hide = function() {
    $(this).addClass('hidden');
    return this;
}
