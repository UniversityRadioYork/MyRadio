$(document).ready(function() {
	$("#switcher").themeswitcher({
                    imgpath: "",
                    themepath: "//ury.org.uk/portal/css",
                    jqueryuiversion: "",
                    rounded: false,
                    
                    themes: [
                        {
                            title: "MyURY",
                            name: "ury-purple",
                            icon: null
                        },
                        {
                            title: "SIS2",
                            name: "ury-red",
                            icon: null
                        }
                    ]
            });
});