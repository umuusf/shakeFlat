function callAjax(url, frm, successCallback, errorCallback, _this)
{
    var frmObj = null;
    var frmData = new FormData();
    var includeFiles = false;

    //console.log(typeof frm, frm.constructor.name);
    if (typeof frm == "string") frmObj = $("#" + frm);
    else if (typeof frm == "object" && frm.constructor.name == "ce") frmObj = frm;
    else if (typeof frm == "object" && frm.constructor.name == "FormData") frmData = frm;
    else if (typeof frm == "object" && frm.constructor.name == "Object") {
        //console.log("Object");
        $.each(frm, function(k, v) {
            if (typeof v == "object" && v.constructor.name == "File") {
                frmData.append(k, $("#"+k)[0].files[0]);
                includeFiles = true;
            } else {
                frmData.append(k, v);
            }
        });
        //for (var pair of frmData.entries()) { console.log(pair[0]+ ', ' + pair[1]); }
    }
    if (frmObj) {
        //console.log("frmObj");
        $(frmObj.find('input,textarea,select')).each(function() {
            //console.log($(this).attr("type"), $(this).attr("name"), $(this).val());
            if ($(this).attr("type") == "file") {
                frmData.append($(this).attr("name"), $("input[name="+$(this).attr("name")+"]")[0].files[0]);
                includeFiles = true;
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
                case -9999 :
                    msg = result.error.errMsg;
                    if (!msg) msg = "로그인이 필요합니다.";
                    alertJump(msg, '/auth/login');
                    return false;
                default :
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
