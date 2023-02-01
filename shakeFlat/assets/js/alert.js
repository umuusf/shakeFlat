const _alert = Swal.mixin({
    icon: 'error',
    position: 'center',
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
})

function alert(msg) {
    _alert.fire({
        title: msg,
        width: msg.stringWidth() + 'px',
    })
}

function alertJump(msg, url) {
    _alert.fire({
        title: msg,
        width: msg.stringWidth() + 'px',
    }).then((result) => {
        if (result.isConfirmed) {
            location.href = url;
        }
    })
}

function alertNoti(msg) {
    _alert.fire({
        icon: 'info',
        title: msg,
        width: msg.stringWidth() + 'px',
    })
}

function alertNotiJump(msg, url) {
    _alert.fire({
        icon: 'info',
        title: msg,
        width: msg.stringWidth() + 'px',
    }).then((result) => {
        if (result.isConfirmed) {
            location.href = url;
        }
    })
}

function confirm(msg, actionCallback) {
    _alert.fire({
        icon: 'question',
        title: msg,
        width: msg.stringWidth() + 'px',
        showCancelButton: true,
        allowOutsideClick: false,
    }).then((result) => {
        if (result.isConfirmed) actionCallback();
    })
}

const _toast = Swal.mixin({
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    onOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})

// icon : success, error, warning, info, question
function toastNoti(msg) {
    _toast.fire({
        icon: 'info',
        title: msg,
    })
}

function toastAlert(msg) {
    _toast.fire({
        icon: 'error',
        title: msg,
    })
}



String.prototype.stringWidth = function(font) {
    var f = font || "2.4em 'Nanum Gothic'",
        o = $('<div></div>')
            .text(this)
            .css({'position': 'absolute', 'float': 'left', 'white-space': 'nowrap', 'visibility': 'hidden', 'font': f})
            .appendTo($('body')),
        w = o.width();
    o.remove();
    w += 50;
    if (w < 400) w = 400;
    if (w > 1200) w = 1200;

    return w;
}