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

// Sorts an dictionary object's keys in descending order and returns a new sorted object.
Object.prototype.sortKeysDescending = function() { return sortKeysDescending(this); }
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

// now : Date()
function getFormattedDate(now) {
    const year = now.getFullYear();
    const month = padZero(now.getMonth() + 1); // Months start at 0, so add 1.
    const day = padZero(now.getDate());
    const hours = padZero(now.getHours());
    const minutes = padZero(now.getMinutes());
    const seconds = padZero(now.getSeconds());

    const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    return formattedDate;
}

// 숫자가 한 자리일 경우 앞에 0을 붙이는 함수
function padZero(number) {
    return number < 10 ? '0' + number : number;
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
