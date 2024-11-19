function callAjax(url, frm, successCallback, errorCallback, _this)
{
    var frmObj = null;
    var frmData = new FormData();

    if (typeof frm === 'string') { frmObj = $("#" + frm); }
    else if (frm instanceof jQuery) { frmObj = frm; }
    else if (frm instanceof HTMLElement) { frmObj = $(frm); }
    else if (typeof frm == "object" && frm.constructor.name == "FormData") frmData = frm;
    else if (typeof frm == "object" && frm.constructor.name == "Object") {
        $.each(frm, function(k, v) {
            if (typeof v == "object" && v.constructor.name == "File") {
                frmData.append(k, $("#"+k)[0].files[0]);
            } else {
                frmData.append(k, v);
            }
        });
    }
    if (frmObj) {
        //console.log("frmObj");
        $(frmObj.find('input,textarea,select')).each(function() {
            //console.log($(this).attr("type"), $(this).attr("name"), $(this).val());
            if ($(this).attr("type") == "file") {
                frmData.append($(this).attr("name"), $("input[name="+$(this).attr("name")+"]")[0].files[0]);
            } else {
                //console.log("frm.append : ", $(this).attr("name"), $(this).val(), $(this).is(":checked"));
                // In the case of a checkbox, if it is not checked, the value is not passed.
                if (!($(this).attr("type") == "checkbox" && !$(this).is(":checked"))) frmData.append($(this).attr("name"), $(this).val());
            }
        });
    }

    var opt = {
        url: url,
        method: "POST",
        data: frmData,
        xhrFields: { withCredentials: true },
        statusCode: {
            404: function() {
                alert('페이지를 찾을 수 없습니다. (404)');
                return false;
            },
            500: function() {
                alert('서버가 응답이 없습니다. (500)');
                return false;
            }
        },
        processData: false,
        contentType: false,
    };

    //console.log(typeof frmData, opt);return;

    $.ajax(opt).done(function(result, textStatus, jqXHR) {
        if (!result || result.constructor != Object || !("data" in result) || !("error" in result) || !("errCode" in result.error)) {
            if (errorCallback) {
                errorCallback(result, _this);
            } else {
                console.log(result);
                //console.log(textStatus);
                //console.log(jqXHR);
                alert("서버 호출시 문제가 발생하였습니다. 잠시 후 다시 시도해주세요.");
            }
            return false;
        } else {
            switch(result.error.errCode) {
                case 0 :
                    return successCallback(result, _this);
                default :
                    if (result.error.errMsg && result.error.errUrl) {
                        alertJump(result.error.errMsg, result.error.errUrl);
                        return false;
                    }

                    if (errorCallback) {
                        errorCallback(result, _this);
                    } else {
                        msg = result.error.errMsg;
                        if (!msg) msg = "잘못된 접근입니다. 잠시 후 다시 시도해주세요.";
                        alert(msg + " (" + result.error.errCode + ")");
                    }
                    return false;
            }
        }
    }).fail(function(e) {
        if (errorCallback) {
            errorCallback(e, _this);
        } else {
            console.log(e);
            alert("서버 호출시 문제가 발생하였습니다. 잠시 후 다시 시도해주세요.");
        }
        return false;
    });
}

function valueForSelect(id, defaultValue) {
    var obj = null;
    if (typeof id == "string") obj = $("#"+id);
    if (typeof id == "object") obj = id;
    var v = obj.val();
    if (v == undefined) return defaultValue;
    if (v) return v;
    return defaultValue;
}

// like htmlspecialchars for PHP
String.prototype.escapeHtml = function() { return escapeHtml(this); }
function escapeHtml(str)
{
    if (!str) return '';
    if (typeof(str) != "string") return str;
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return str.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Read a page's GET URL variables and return them as an associative array.
function getParam(k, defaultValue)
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    if (k) {
        if (vars[k]) {
            return vars[k];
        } else if (defaultValue) {
            return defaultValue;
        } else {
            return null;
        }
    }
    return vars;
}

// retrieves the computed CSS styles of an HTML element and converts them into a JavaScript object
// example : var styles = $("#element").getStyleObject();
// styles : {width: "100px", height: "100px", ...}
jQuery.prototype.getStyleObject = function (str) {
    var dom = this.get(0);
    var style;
    var returns = {};
    if(window.getComputedStyle){
        var camelize = function(a,b){
            return b.toUpperCase();
        };
        style = window.getComputedStyle(dom, null);
        for(var i = 0, l = style.length; i < l; i++){
            var prop = style[i];
            var camel = prop.replace(/\-([a-z])/g, camelize);
            var val = style.getPropertyValue(prop);
            returns[camel] = val;
        };
        return returns;
    };
    if(style = dom.currentStyle){
        for(var prop in style){
            returns[prop] = style[prop];
        };
        return returns;
    };
    return this.css();
}

// Cut to length of string. (Korean processing)
String.prototype.cut = function(len) {
    var str = this;
    var s = 0;
    for (var i=0; i<str.length; i++) {
        s += (str.charCodeAt(i) > 128) ? 2 : 1;
        if (s > len) return str.substring(0,i) + "...";
    }
    return str;
}

// Returns the length of the string in bytes (handling Korean 2 bytes)
String.prototype.bytes = function() {
    var str = this;
    var s = 0;
    for (var i=0; i<str.length; i++) s += (str.charCodeAt(i) > 128) ? 2 : 1;
    return s;
}

// like number_format() for PHP
Number.prototype.numberFormat = function() {
    var num = this;
    return num.toLocaleString('ko-KR');
}

// zero to blank.
Number.prototype.numberFormatX = function() {
    var num = this;
    if (num == 0) return "";
    return num.toLocaleString('ko-KR');
}

// 스트링으로 된 날짜 값을 입력 받아서, 날짜 format 에 맞춘 스트링으로 스트링으로 반환해주는 함수
// ex) var dateStr = "2020-01-01";
//     var formattedDate = dateStr.formatDate("YYYY년 MM월 DD일 HH시 mm분 ss초");
String.prototype.formatDateTime = function(toFormat) {
    var dateStr = this;
    var date = new Date(dateStr);
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var hour = date.getHours();
    var min = date.getMinutes();
    var sec = date.getSeconds();

    var formattedDate = toFormat;
    formattedDate = formattedDate.replace("YYYY", year);
    formattedDate = formattedDate.replace("MM", padZero(month));
    formattedDate = formattedDate.replace("DD", padZero(day));
    formattedDate = formattedDate.replace("HH", padZero(hour));
    formattedDate = formattedDate.replace("mm", padZero(min));
    formattedDate = formattedDate.replace("ss", padZero(sec));

    return formattedDate;
}

// Sorts an dictionary object's keys in descending order and returns a new sorted object.
function sortKeysDescending(obj) {
    var items = Object.keys(obj).map(function(key) { return [key, obj[key]]; });
    items.sort(function(first, second) { return second[0].localeCompare(first[0]); });

    var sorted_obj = {};
    $.each(items, function(index, value) {
        var key = value[0];
        var val = value[1];
        sorted_obj[key] = val;
    });

    return sorted_obj;
}

// perform form validation checks, and if the end of id or named is _confirm, we check if the password matches.
function checkValidityForm(frm, passwdConfirmCustomMessage = "Password does not match") {
    var frmObj = null;
    var pwList = [];

    if (typeof frm === 'string') { frmObj = $("#" + frm); }
    else if (frm instanceof jQuery) { frmObj = frm; }
    else if (frm instanceof HTMLElement) { frmObj = $(frm); }
    else return false;

    frmObj.find("input[type=password]").each(function() {
        var elementId = $(this).attr("id") || $(this).attr("name");
        if (elementId) {
            pwList.push({
                id: elementId,
                element: $(this)
            });
        }
    });

    if (pwList.length > 0) {
        pwList.forEach(function(o) {
            if (o.id && o.id.substr(-8) == "_confirm") {
                var str = o.id.slice(0, -8);
                var originalPw = pwList.find(function(pw) { return pw.id === str; });

                if (originalPw) {
                    var originalVal = originalPw.element.val();
                    var confirmVal = o.element.val();

                    if (originalVal != confirmVal) {
                        o.element[0].setCustomValidity(passwdConfirmCustomMessage);
                        return false;
                    } else {
                        o.element[0].setCustomValidity("");
                    }
                }
            }
        });
    }

    // check validity
    if (!frmObj[0].checkValidity()) { frmObj[0].reportValidity(); return false; }

    return true;
}

// Function to add leading zeros to a number based on desired length
function padZero(number, length = 2) {
    return String(number).padStart(length, '0');
}

// rem to pixel (element is optional)
// ex) var pixel = convertRemToPixels(3, document.getElementById('myElement'));
function convertRemToPixels(rem, element) {
    if (!element) element = document.documentElement;
    return rem * parseFloat(getComputedStyle(element).fontSize);
}

// pixel to rem
function convertPixelToRem(pixel, element) {
    if (!element) element = document.documentElement;
    return pixel / parseFloat(getComputedStyle(element).fontSize);
}

// width(pixel) for string with font
// ex) var charWidth = getStringPixelWidth('text', document.getElementById('myElement'));
function getStringPixelWidth(str, element) {
    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    context.font =  window.getComputedStyle(element).getPropertyValue('font-size') + " " +  window.getComputedStyle(element).getPropertyValue('font-family');
    var metrics = context.measureText(str);
    return metrics.width;
}

// width(pixel) for string with font
String.prototype.stringWidth = function(font, fontSize) {
    var tt = this.toLowerCase().split(/<br>|<br\/>|<br \/>|<p>/);
    var max_tt = "";
    for(i=0;i<tt.length;i++) if (max_tt.length < tt[i].length) max_tt = tt[i];

    var fs = "1.2rem";
    if (fontSize) fs = fontSize;
    var f = font || fs + " 'Nanum Gothic'",
        o = $('<div></div>')
            .text(max_tt)
            .css({'position': 'absolute', 'float': 'left', 'white-space': 'nowrap', 'visibility': 'hidden', 'font': f})
            .appendTo($('body')),
        w = o.width();
    o.remove();
    w += 50;
    if (w < 400) w = 400;
    if (w > 1200) w = 1200;

    return w;
}