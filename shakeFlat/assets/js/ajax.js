function callAjax(fnc, postdata, successCallback, errorCallback, _this)
{
    $.ajax({
        url: "/" + fnc,
        method: "POST",
        data: postdata,
        xhrFields: { withCredentials: true },
        statusCode: {
            404: function() {
                alert('Page is not found. (404)');
                return false;
            },
            500: function() {
                alert('Internal server error. (500)');
                return false;
            }
        }
    }).done(function(result, textStatus, jqXHR) {
        switch(result.error.errCode) {
            case 0 :
                return successCallback(result.data, _this);
            case -9999 :
                alertJump('Please login.', '/auth/login');
                return false;
            default :
                console.log(result);
                //console.log(textStatus);
                //console.log(jqXHR);
                if (errorCallback) {
                    errorCallback(result, _this);
                } else {
                    msg = result.error.errMsg;
                    if (!msg) msg = "The wrong approach. Please try again in a few minutes.";
                    alert(msg + " (" + result.error.errCode + ")");
                }
                return false;
        }
    }).fail(function(e) {
        console.log(e);
        alert("A problem occurred when calling the server. Please try again in a few minutes.");
        return false;
    });
}
