/* Obit */
var Obit = function() {
    var button = document.createElement('button');
    button.className = 'btn btn-danger';
    button.innerHTML = 'Start Obituary Procedure';

    return {
        name: 'Stop Broadcast',
        type: 'plugin',
        initialise: function() {
            this.appendChild(button);
        }
    }
}

sis.registerModule('obit', new Obit());
