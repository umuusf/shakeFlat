function callAjax(fnc, postdata, successCallback, errorCallback, _this)
{
    $.ajax({
        url: fnc,
        method: "POST",
        data: postdata,
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
        }
    }).done(function(result, textStatus, jqXHR) {
        if (!result || result.constructor != Object || !("data" in result) || !("error" in result) || !("errCode" in result.error)) {
            console.log(result);
            //console.log(textStatus);
            //console.log(jqXHR);
            alert("서버 호출시 문제가 발생하였습니다. 잠시 후 다시 시도해주세요.");
            return false;
        } else {
            switch(result.error.errCode) {
                case 0 :
                    return successCallback(result, _this);
                case -9999 :
                    alertJump('로그인이 필요합니다.', '/auth/login');
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
        console.log(e);
        alert("서버 호출시 문제가 발생하였습니다. 잠시 후 다시 시도해주세요.");
        return false;
    });
}
