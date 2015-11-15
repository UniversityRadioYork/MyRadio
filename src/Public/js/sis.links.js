/* Links */
var Links = function() {
    var links = [
        {
            'href': myradio.makeURL('Mail', 'send', {list: 30}),
            'desc': 'Report a Fault'
    },
        {
            'href': myradio.makeURL('Mail', 'send', {list: 44}),
            'desc': 'Contact Management'
    },
        {
            'href': myradio.makeURL('Mail', 'send', {list: 56}),
            'desc': 'Contact Presenting Team'
    }
    ];
    return {
        activeByDefault: true,
        name: 'Useful Links',
        type: 'plugin',
        initialise: function() {
            var ul = document.createElement('ul'),
                li,
                a;
            for (i in links) {
                li = document.createElement('li');
                a = document.createElement('a');

                a.setAttribute('target', '_blank');
                a.setAttribute('href', links[i]['href']);
                a.innerHTML = links[i]['desc'];

                li.appendChild(a);
                ul.appendChild(li);
            }
            this.appendChild(ul);
            this.show();
        }
    }
}

sis.registerModule('links', new Links());
