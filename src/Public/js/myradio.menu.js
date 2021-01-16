$(document).ready(function() {
    $(".main-container ul a").tooltip({ delay: 500, placement: "right" });
});

$(".broadcast-unread").click(function(e) {
    myradio.callAPI("GET", "user", "name", e.target.id.toString().split("-")[1], "", "", function(userData) {
        myradio.callAPI("GET", "broadcast", "userbroadcast", "", e.target.id.toString().split("-")[1], "", function(broadcastData) {
            myradio.createDialog(userData.payload + (userData.payload.charAt(userData.payload.length - 1) == "s" ? "'" : "'s") + " Broadcast", broadcastData.payload[0].path);
        })
    })
});