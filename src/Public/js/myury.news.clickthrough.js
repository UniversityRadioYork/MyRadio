$(document).ready(
    function() {
        var body = document.createElement('div');

        var footer = document.createElement('footer');
        var author = document.createElement('author');
        author.innerHTML = news.author;
        var time = document.createElement('time');
        time.setAttribute('datetime', news.posted);
        time.innerHTML = moment(news.posted, "DD/MM/YYYY HH:mm").fromNow();

        footer.appendChild(author);
        footer.appendChild(document.createTextNode(', '));
        footer.appendChild(time);
        body.innerHTML = news.content;
        body.appendChild(footer);

        var button = myury.closeButton();
        button.className = 'btn btn-primary';
        button.innerHTML = 'Got it!';
        button.addEventListener(
            'click', function() {
                $.ajax(
                    {
                        url: myury.makeURL('MyRadio', 'a-readnews'),
                        type: 'post',
                        data: 'newsentryid='+news.newsentryid
                    }
                );
            }
        );

        myury.createDialog("Latest news", body, [button, myury.closeButton()]);
    }
);
