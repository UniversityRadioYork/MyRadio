var Highlights = function() {
    const TIMES = [10, 5, 2];

    const buttons = [];
    for (const time of TIMES) {
        const btn = document.createElement("button");
        btn.className = "btn btn-default";
        btn.innerText = "Last " + time + " minutes";
        btn.addEventListener("click", function() {
            $.post(myradio.makeURL("SIS", "highlight"), { duration: time * 60 });
        });
        buttons.push(btn);
    }

    const lastBtn = document.createElement("button");
    lastBtn.className = "btn btn-primary";
    lastBtn.innerText = "Last segment";
    lastBtn.addEventListener("click", function() {
        $.post(myradio.makeURL("SIS", "highlight"), { lastSegment: true });
    });
    buttons.push(lastBtn);

    return {
        name: "Highlights",
        type: "plugin",
        initialise: function() {
            for (const btn of buttons) {
                this.appendChild(btn);
            }
        }
    }
};
sis.registerModule("highlights", new Highlights());
