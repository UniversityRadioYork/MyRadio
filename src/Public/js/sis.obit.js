/* Obit */
var Obit = function() {
    var button = document.createElement('button');
    button.className = 'btn btn-danger';
    button.innerHTML = 'Start Obituary Procedure';
    button.addEventListener('click', function() {
        window.open(myury.makeURL('Scheduler', 'stop'), 'Stop Broadcast');
    });

    return {
        name: 'Stop Broadcast',
        type: 'plugin',
        initialise: function() {
            this.appendChild(button);
        }
    }
}

sis.registerModule('obit', new Obit());
