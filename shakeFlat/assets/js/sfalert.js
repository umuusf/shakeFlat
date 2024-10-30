function alert(msg) { sfAlert(msg); }
function alertJump(msg, url) { sfAlertJump(msg, url); }
function alertBack(msg) { sfAlertBack(msg); }
function noti(msg) { sfNoti(msg); }
function notiJump(msg, url) { sfNotiJump(msg, url); }
function notiBack(msg) { sfNotiBack(msg); }
function confirm(msg, callback, noCallback) { sfConfirm(msg, callback, noCallback); }

function sfAlert(p) {
    var opt = {
        type: "alert",
        messageText: "Alert!!",
        alertType: "danger",
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

function sfAlertBack(p) {
    var opt = {
        type: "alert",
        messageText: "Alert!!",
        alertType: "danger",
        okCallback: function() { history.go(-1); },
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

function sfAlertJump(p, url) {
    var opt = {
        type: "alert",
        messageText: "Alert!!",
        alertType: "danger",
        okCallback: function() { location.href = url; },
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

function sfNoti(p) {
    var opt = {
        type: "alert",
        messageText: "Notification",
        alertType: "info",
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

function sfNotiJump(p, url) {
    var opt = {
        messageText: "Notification",
        okCallback: function() { location.href = url; },
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    sfNoti(opt);
}

function sfNotiBack(p, url) {
    var opt = {
        messageText: "Notification",
        okCallback: function() { history.go(-1); },
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    sfNoti(opt);
}

function sfConfirm(p, callback, noCallback) {
    var opt = {
        type: "confirm",
        messageText: "Confirm",
        alertType: "success",
        yesCallback: callback,
        noCallback: noCallback,
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

var __sfAlertIdx = 0;
var __sfAlertCount = 0;
function __sfAlert(options)
{
	var deferredObject = $.Deferred();
	var defaults = {
		type: "alert", //alert, prompt,confirm
		okButtonText: '확인',
		cancelButtonText: '취소',
		yesButtonText: '예',
		noButtonText: '아니오',
		messageText: 'Message',
		alertType: 'default', //default, primary, success, info, warning, danger
		inputFieldType: 'text', //could ask for number,email,etc
        fontSize: '1.2rem',
        width: null,
        icon: null,
        iconSize: null,
        isCenter: false,
        customStyle: null,
        okCallback: null,
        yesCallback: null,
        noCallback: null,
        top: '15vh',
        left: '0px',
	}
	$.extend(defaults, options);


	var _show = function(alertIdx){
        var widthTag = defaults.messageText.stringWidth() + "px";
        if (defaults.width) widthTag = defaults.width;
        if (defaults.iconSize) iconSize = defaults.iconSize;

        var $sfAlert = $("<div/>", { "id":"sfAlerts-" + alertIdx, "class":"modal fade", "tabindex":"-1", "data-bs-keyboard":"true", "data-bs-backdrop":"true" });
        $sfAlert.css("z-index", "10000");

        if (defaults.customStyle) $sfAlert.attr("style", defaults.customStyle);

        var $sfAlertDialog = $("<div/>", { "class":"modal-dialog modal-dialog-scrollable", "css": { "max-width":widthTag} });
        if (defaults.isCenter) {
            $sfAlertDialog.addClass("modal-dialog-centered");
        } else {
            var top = defaults.top, left = defaults.left;
            var parent = getTopVisibleModal();
            if (parent) {
                if (parent.css("top") != "0px") {
                    top = "calc(3rem + " + parent.css("top") + ")";
                    left = "calc(3rem + " + parent.css("left") + ")";
                    $sfAlert.css("top", top);
                    $sfAlert.css("left", left);
                } else {
                    $sfAlertDialog.addClass("modal-dialog-centered");
                }
            } else {
                $sfAlert.css("top", top);
                $sfAlert.css("left", left);
            }
        }

        var $sfAlertContent = $("<div/>", { "class":"modal-content" });
        var $sfAlertBody = $("<div/>", { "class":"modal-body" });
        $sfAlertBody.css("overflow-x", "auto");


        var iconSize = "5em";
        if (defaults.iconSize) iconSize = defaults.iconSize;
        if (defaults.icon) {
            var $iconTag = $("<div/>", { "class":"text-center mb-3", "css":{ "font-size":iconSize } }).html(defaults.icon);
            $sfAlertBody.append($iconTag);
        } else if (defaults.alertType != "default") {
            var $iconTag = $("<div/>", { "class":"text-center mb-3", "css": { "font-size":iconSize } });
            if (defaults.alertType == "warning") { $iconTag.addClass("text-warning"); $iconTag.html('<i class="bi bi-exclamation-circle"></i>'); }
            else if (defaults.alertType == "danger") { $iconTag.addClass("text-danger"); $iconTag.html('<i class="bi bi-x-circle"></i>'); }
            else { $iconTag.addClass("text-info"); $iconTag.html('<i class="bi bi-info-circle"></i>'); }
            $sfAlertBody.append($iconTag);
        }

        var $sfAlertMsg = $("<div/>", { "class":"text-center mt-3 mb-4", "css":{ "font-size":defaults.fontSize } }).html(defaults.messageText);
        $sfAlertBody.append($sfAlertMsg);

        var $sfAlertBtnArea = $("<div/>", { "class":"text-center mb-2" });

		switch (defaults.type) {
			case "alert":
                var $okBtn = $("<button/>", { "class":"btn btn-" + defaults.alertType + " ms-1", "data-bs-dismiss":"modal" }).html(defaults.okButtonText);
                if (defaults.okCallback) $okBtn.on('click', function () { defaults.okCallback(); });

                $sfAlertBtnArea.append($okBtn);
				break;
			case "confirm":
                $sfAlert.attr("data-bs-backdrop", "false");      // 모달 외부 클릭 막음
                $sfAlert.attr("data-bs-keyboard", "false");      // 키보드 esc 입력 막음

                var $yesBtn = $("<button/>", { "class":"btn btn-primary me-1", "data-bs-dismiss":"modal" }).html(defaults.yesButtonText);
                var $noBtn = $("<button/>", { "class":"btn btn-secondary ms-1", "data-bs-dismiss":"modal" }).html(defaults.noButtonText);
				if (defaults.yesCallback) $yesBtn.on('click', function () { defaults.yesCallback(); });
                if (defaults.noCallback) $noBtn.on('click', function () { defaults.noCallback(); });

                $sfAlertBtnArea.append($yesBtn);
                $sfAlertBtnArea.append($noBtn);
				break;
		}


        $sfAlertBtnArea.append($okBtn);
        $sfAlertBody.append($sfAlertBtnArea);
        $sfAlertContent.append($sfAlertBody);
        $sfAlertDialog.append($sfAlertContent);
        $sfAlert.append($sfAlertDialog);

        if(!$("#sfAlertOverlay").length) {
            $sfAlertOverlay = $("<div/>", { "id": "sfAlertOverlay", "css" : {
                "position": "fixed",
                "top": "0",
                "left": "0",
                "width": "100%",
                "height": "100%",
                "background-color": "rgba(0, 0, 0, 0.2)",
                "z-index": "9999",
                "display": "none",
            }});
            $("body").append($sfAlertOverlay);
        }

		$sfAlert.modal().on('hidden.bs.modal', function (e) {
			$(this).remove();
            if (defaults.okCallback) deferredObject.resolve(defaults.okCallback()); else deferredObject.resolve();
        }).on('hide.bs.modal', function() {
            $("#sfAlertOverlay").css("display", "none");
		}).on('shown.bs.modal', function () {
			if ($('#prompt').length > 0) $('#prompt').focus();
		}).on('show.bs.modal', function () {
            $("#sfAlertOverlay").css("display", "block");
        }).modal('show');

        $("body").append($sfAlert);
	}

    __sfAlertIdx ++;
    _show(__sfAlertIdx);
    return deferredObject.promise();
}

function getTopVisibleModal() {
    let topModal = null;
    let maxZIndex = -1;

    $('div.modal').each(function () {
        const $this = $(this);
        if ($this.css('display') === 'block') {
            const zIndex = parseInt($this.css('z-index'), 10) || 0;
            if (zIndex > maxZIndex) {
                maxZIndex = zIndex;
                topModal = $this;
            }
        }
    });

    return topModal;
}