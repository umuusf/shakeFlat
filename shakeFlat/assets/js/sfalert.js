function alert(msg) { sfAlert(msg); }
function alertJump(msg, url) { sfAlertJump(msg, url); }
function alertBack(msg) { sfAlertBack(msg); }
function noti(msg) { sfNoti(msg); }
function notiJump(msg, url) { sfNotiJump(msg, url); }
function notiBack(msg) { sfNotiBack(msg); }
function confirm(msg, callback, noCallback) { sfConfirm(msg, callback, noCallback); }
function inputConfirm(msg, callback) { sfInputConfirm(msg, callback); }

function sfAlert(p) {
    let opt = {
        type: "alert",
        messageText: "Alert!!",
        alertType: "danger",
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

function sfAlertBack(p) {
    let opt = {
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
    let opt = {
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
    let opt = {
        type: "alert",
        messageText: "Notification",
        alertType: "info",
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

function sfNotiJump(p, url) {
    let opt = {
        messageText: "Notification",
        okCallback: function() { location.href = url; },
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    sfNoti(opt);
}

function sfNotiBack(p, url) {
    let opt = {
        messageText: "Notification",
        okCallback: function() { history.go(-1); },
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    sfNoti(opt);
}

function sfConfirm(p, callback, noCallback) {
    let opt = {
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

function sfInputConfirm(p, callback) {
    let opt = {
        type: "input",
        inputRequired: true,
        messageText: "Input",
        alertType: "success",
        yesCallback: callback,
    };
    if (typeof p == "string") opt.messageText = p;
    else if (typeof p == "object") $.extend(opt, p);
    __sfAlert(opt);
}

let __sfAlertLanguage = {
    "ko": {
        "alert": "알림",
        "confirm": "확인",
        "cancel": "취소",
        "ok": "확인",
        "yes": "예",
        "no": "아니오",
        "required": "필수 입력값입니다.",
    },
    "en": {
        "alert": "Alert",
        "confirm": "Confirm",
        "cancel": "Cancel",
        "ok": "OK",
        "yes": "Yes",
        "no": "No",
        "required": "This is required input.",
    },
};
let __sfAlertIdx = 0;
function __sfAlert(options)
{
	let deferredObject = $.Deferred();
	let defaults = {
		type: "alert", //alert, prompt,confirm
        language: "ko",
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
        inputRequired: true,
        inputWidth: '100%',
        inputPlaceholder: '',
	}
	$.extend(defaults, options);

	let _show = function(alertIdx){
        let widthTag = defaults.messageText.stringWidth() + "px";
        if (defaults.width) widthTag = defaults.width;
        if (defaults.iconSize) iconSize = defaults.iconSize;

        let $sfAlert = $("<div/>", { "id":"sfAlerts-" + alertIdx, "class":"modal fade", "tabindex":"-1", "data-bs-keyboard":"true", "data-bs-backdrop":"true", "aria-modal":"true", "aria-labelledby":"sfAlerts-" + alertIdx, "aria-describedby":"sfAlerts-" + alertIdx, "aria-hidden":"true" });
        $sfAlert.css("z-index", "10000");

        if (defaults.customStyle) $sfAlert.attr("style", defaults.customStyle);

        let $sfAlertDialog = $("<div/>", { "class":"modal-dialog modal-dialog-scrollable", "css": { "max-width":widthTag} });
        if (defaults.isCenter) {
            $sfAlertDialog.addClass("modal-dialog-centered");
        } else {
            let top = defaults.top, left = defaults.left;
            let parent = _getTopVisibleModal();
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

        let $sfAlertContent = $("<div/>", { "class":"modal-content" });
        let $sfAlertBody = $("<div/>", { "class":"modal-body" });
        $sfAlertBody.css("overflow-x", "auto");

        if (defaults.type != "input") {
            let iconSize = "5em";
            if (defaults.iconSize) iconSize = defaults.iconSize;
            if (defaults.icon) {
                let $iconTag = $("<div/>", { "class":"text-center mb-3", "css":{ "font-size":iconSize } }).html(defaults.icon);
                $sfAlertBody.append($iconTag);
            } else if (defaults.alertType != "default") {
                let $iconTag = $("<div/>", { "class":"text-center mb-3", "css": { "font-size":iconSize } });
                if (defaults.alertType == "warning") { $iconTag.addClass("text-warning"); $iconTag.html('<i class="bi bi-exclamation-circle"></i>'); }
                else if (defaults.alertType == "danger") { $iconTag.addClass("text-danger"); $iconTag.html('<i class="bi bi-x-circle"></i>'); }
                else { $iconTag.addClass("text-info"); $iconTag.html('<i class="bi bi-info-circle"></i>'); }
                $sfAlertBody.append($iconTag);
            }
        }

        let $sfAlertMsg = $("<div/>", { "class":"text-center mt-3 mb-4", "css":{ "font-size":defaults.fontSize } }).html(defaults.messageText);
        $sfAlertBody.append($sfAlertMsg);

        let $sfAlertBtnArea = $("<div/>", { "class":"text-center mb-2" });

		switch (defaults.type) {
			case "alert":
                let $okBtn = $("<button/>", { "class":"btn btn-" + defaults.alertType + " ms-1", "data-bs-dismiss":"modal", "aria-label":"Close" }).html(__sfAlertLanguage[defaults.language].ok);
                if (defaults.okCallback) $okBtn.on('click', function () { defaults.okCallback(); });
                $sfAlertBtnArea.append($okBtn);
				break;
			case "confirm":
                $sfAlert.attr("data-bs-backdrop", "false");      // Prevent clicking outside the modal
                $sfAlert.attr("data-bs-keyboard", "false");      // Prevent keyboard esc input

                let $yesBtn = $("<button/>", { "class":"btn btn-primary me-1", "data-bs-dismiss":"modal" }).html(__sfAlertLanguage[defaults.language].yes);
                let $noBtn = $("<button/>", { "class":"btn btn-secondary ms-1", "data-bs-dismiss":"modal", "aria-label":"Close" }).html(__sfAlertLanguage[defaults.language].no);
				if (defaults.yesCallback) $yesBtn.on('click', function () { defaults.yesCallback(); });
                if (defaults.noCallback) $noBtn.on('click', function () { defaults.noCallback(); });
                $sfAlertBtnArea.append($yesBtn);
                $sfAlertBtnArea.append($noBtn);
				break;
            case "input":
                let $inputDiv = $("<div/>", { "class":"mb-3 d-flex justify-content-center"});
                let $input = $("<input/>", { "class":"form-control", "type":"text", "id":"prompt", "name":"prompt", "style":"width:" + defaults.inputWidth, "placeholder":defaults.inputPlaceholder });
                $input.attr("autocomplete", "off");

                let $validationMessage = $("<div/>", { "class":"text-danger mb-3", "css":{ "font-size":"0.8rem" } });
                let $okBtn2 = $("<button/>", { "class":"btn btn-" + defaults.alertType + " ms-1" }).html(__sfAlertLanguage[defaults.language].ok);
                let $cancelBtn = $("<button/>", { "class":"btn btn-secondary ms-1", "data-bs-dismiss":"modal", "aria-label":"Close" }).html(__sfAlertLanguage[defaults.language].cancel);

                if (defaults.yesCallback) $okBtn2.click(function () {
                    if (defaults.inputRequired && $input.val() == "") {
                        $input.focus();
                        $validationMessage.html(__sfAlertLanguage[defaults.language].required);
                        return;
                    } else {
                        defaults.yesCallback($input.val());
                        $sfAlert.modal('hide');
                    }
                });

                $inputDiv.append($input);
                $sfAlertBtnArea.append($inputDiv);
                $sfAlertBtnArea.append($validationMessage);
                $sfAlertBtnArea.append($okBtn2);
                $sfAlertBtnArea.append($cancelBtn);
                break;
		}

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
                "background-color": "rgba(0, 0, 0, 0.3)",
                "z-index": "9999",
                "display": "none",
            }});
            $("body").append($sfAlertOverlay);
        }

		$sfAlert.modal().on('hidden.bs.modal', function () {
			$(this).remove();
            deferredObject.resolve();
        }).on('hide.bs.modal', function() {
            if($("#sfAlertOverlay").length) $("#sfAlertOverlay").remove();
            if (document.activeElement) document.activeElement.blur();
		}).on('hidePrevented.bs.modal', function() {
        }).on('shown.bs.modal', function () {
			if ($('#prompt').length) $('#prompt').focus();
		}).on('show.bs.modal', function () {
            $("#sfAlertOverlay").css("display", "block");
        });

        $("body").append($sfAlert);

        $sfAlert.modal('show');
	}

    __sfAlertIdx ++;
    _show(__sfAlertIdx);
    return deferredObject.promise();
}

function _getTopVisibleModal() {
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