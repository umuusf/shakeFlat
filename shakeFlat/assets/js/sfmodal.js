let _modal = {};
function sfModal(id, userOptions)
{
    if (_modal[id]) return _modal[id];

    let options = {
        title           : "Modal Title",
        dialogClass     : "",                   // "modal-lg",
        dialogStyle     : "",                   // "width: 800px",
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

    _modal[id] = $("<div>").addClass("modal fade").attr("tabindex", "-1").attr("aria-labelledby", options.title).attr("aria-hidden", "true").attr("id", id).appendTo("body");
    let modalDialog = $("<div>").addClass("modal-dialog modal-dialog-centered").appendTo(_modal[id]);
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

    return _modal[id];
}