var thread_active;
var base_url = $("base").attr("href");
let uri_prop = base_url + "chat/load_prop";
const loading_chat =
    '<div class="loading-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>';
const loading_message = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`;

var channel = pusher.subscribe("chat-channel");
channel.bind("event-notif", function (data) {
    threat_id = $("body").find(".active").attr("id");
    load_prop("pusher", threat_id);
});

// javascript for chatting coreui

$("body").on("click", ".card-threat", function () {
    threat_id = $(this).attr("id");

    $("body").find(".card-threat").removeClass("active");
    $(this).addClass("active");

    load_prop("user", threat_id);
    $(".card-chat").find(".card-header").addClass("d-none");
    $(".card-chat").find(".card-body").html(loading_chat);
});

$("body").on("submit", "#chat-form", function () {
    form = $(this);
    buttonSubmit = form.find("button[type=submit]").html();
    threat_id = $("body").find(".active").attr("id");
    form.find("button[type=submit]").attr("disabled", true);
    form.find("button[type=submit]").html(loading_message);

    $.ajax({
        type: "post",
        url: $(this).attr("action"),
        data: $(this).serialize(),
        success: function (data) {
            load_prop("user", threat_id);
            form.find(":input[name=isi]").val("");
            form.find(":input[name=isi]").focus();
            form.find("button[type=submit]").attr("disabled", false);
            form.find("button[type=submit]").html(buttonSubmit);
        },
        error: function () {
            load_prop("user", threat_id);
            form.find("button[type=submit]").attr("disabled", false);
            form.find("button[type=submit]").html(buttonSubmit);
        },
    });
    return false;
});

// this.$chatHistory.scrollTop(this.$chatHistory[0].scrollHeight);

function load_prop(type, threat) {
    // LOAD CHATTING
    // REMOVE BADGE THREAT
    // CHANGE BADGE MENU
    uri = uri_prop + "?type=" + type;
    uri = uri + "&threat=" + threat;

    $.getJSON(uri, function (data) {
        // BADGE MENU OBROLAN
        if (data.notifMenu) {
            $(".notif-chatmenu").text(data.notifMenu);
        } else {
            $(".notif-chatmenu").text("");
        }

        // LOAD THREAD ROOM LIST
        $(".threat-body").html(data.loadThreat);

        if (type == "user") {
            $("#room-name").text(data.roomName);
            $(".card-chat").find(".card-header").removeClass("d-none");
            $(".card-chat").find(".card-footer").removeClass("d-none");
        }
        if (threat) {
            active_room = threat;
            $("#chat-form").find("input[name=room_id]").val(threat);
            $(".card-chat").find(".card-body").html(data.chatHistory);

            $(".card-chat")
                .find(".card-body")
                .scrollTop($(".card-chat .card-body")[0].scrollHeight);
        }
    });
}
