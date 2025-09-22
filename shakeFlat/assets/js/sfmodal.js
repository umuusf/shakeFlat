let _modal = {};
function sfModal(id, userOptions)
{
    if (_modal[id]) return _modal[id];

    let options = {
        title           : "Modal Title",
        dialogClass     : "",                   // "modal-lg",
        dialogStyle     : "",                   // "width: 800px",
        animation       : true,
        verticalCenter  : true,
        zIndex          : -1,
        header          : {
            enable  : true,
            title   : "",
            close   : true,
            class   : "",
            style   : "",
        },
        footer          : {
            enable  : true,
            submit  : { enable: false, text: "확인", class: "btn-primary", callback: null },
            close   : { text: "닫기", class: "btn-secondary" },
        }
    }
    $.extend(true, options, userOptions);

    let zIndex = 0;
    if (options.zIndex > 0) zIndex = options.zIndex;
    else zIndex = 1050 + $(".modal-backdrop").length * 10;

    _modal[id] = $("<div>").addClass("modal").attr("tabindex", "-1").attr("aria-labelledby", options.title).attr("aria-hidden", "true").attr("id", id).css("z-index", zIndex).appendTo("body");
    if (options.animation) _modal[id].addClass("fade");
    let modalDialog = $("<div>").addClass("modal-dialog").appendTo(_modal[id]);
    if (options.verticalCenter) modalDialog.addClass("modal-dialog-centered");
    if (options.dialogClass) modalDialog.addClass(options.dialogClass);
    if (options.dialogStyle) modalDialog.attr("style", options.dialogStyle);
    let content = $("<div>").addClass("modal-content").appendTo(modalDialog);

    if (options.header.enable) {
        if (!options.header.title) options.header.title = options.title;
        let header = $("<div>").addClass("modal-header").appendTo(content);
        if (options.header.class) header.addClass(options.header.class);
        if (options.header.style) header.attr("style", options.header.style);
        $("<h5>").addClass("modal-title").text(options.header.title).appendTo(header);
        if (options.header.close) $("<button>").addClass("btn-close").attr("type", "button").attr("data-bs-dismiss", "modal").attr("aria-label", "Close").appendTo(header);
    }

    $("<div>").addClass("modal-body").appendTo(content);

    if (options.footer.enable) {
        let footer = $("<div>").addClass("modal-footer").appendTo(content);
        if (options.footer.submit.enable) {
            let btn = $("<button>").addClass("btn btn-sm " + options.footer.submit.class).text(options.footer.submit.text).appendTo(footer);
            if (options.footer.submit.callback) btn.on("click", options.footer.submit.callback);
        }
        if (options.footer.close) {
            $("<button>").addClass("btn btn-sm " + options.footer.close.class).attr("data-bs-dismiss", "modal").text(options.footer.close.text).appendTo(footer);
        }
    }

    _modal[id].on("hide.bs.modal", function() {
        $(document.activeElement).blur();
    });

    _modal[id].on("shown.bs.modal", function() {
        $(".modal-backdrop").last().css("z-index", zIndex-1);
    });

    return _modal[id];
}