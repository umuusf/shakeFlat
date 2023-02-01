function sfSetClock()
{
    var monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
    var dayNames= [ "Sun","Mon","Tue","Wed","Thu","Fri","Sat" ]

    var newDate = new Date();
    newDate.setDate(newDate.getDate());

    var seconds = newDate.getSeconds();
    var minutes = newDate.getMinutes();
    var hours = newDate.getHours();

    $('#shakeflat-clock-date').html(dayNames[newDate.getDay()] + " " + newDate.getDate() + ' ' + monthNames[newDate.getMonth()] + ' ' + newDate.getFullYear());
    $("#shakeflat-clock-sec").html(( seconds < 10 ? "0" : "" ) + seconds);
    $("#shakeflat-clock-min").html(( minutes < 10 ? "0" : "" ) + minutes);
    $("#shakeflat-clock-hours").html(( hours < 10 ? "0" : "" ) + hours);

    setTimeout(sfSetClock, 1000);
}

function sfLeftMenuFullSize()
{
    $(".shakeflat-left").css("width", "240px");
    $(".shakeflat-left").css("min-width", "240px");
    $(".shakeflat-left").css("display", "inline-block");
    $(".shakeflat-left .list-group > .list-group-item > span").css("display", "inline-block");
    $(".shakeflat-left .list-group > [data-toggle='collapse']").removeClass("hide");
    $(".shakeflat-left").data("status", "full");
}

function sfLeftMenuMini()
{
    $(".shakeflat-left").css("width", "60px");
    $(".shakeflat-left").css("min-width", "60px");
    $(".shakeflat-left").css("display", "inline-block");
    $(".shakeflat-left .list-group > .list-group-item > span").css("display", "none");
    $(".shakeflat-left .list-group > [data-toggle='collapse']").addClass("hide");
    $(".shakeflat-left .list-group > a.in").next(".list-group.collapse").collapse('hide');
    $(".shakeflat-left .list-group > a.in").removeClass("in");
    $(".shakeflat-left").data("status", "mini");
}

function sfLeftMenuHide()
{
    $(".shakeflat-left").css("display", "none");
    $(".shakeflat-left").data("status", "hide");
}

function sfBlind()
{
    $(".shakeflat-sidebar-blind").css("display", "block");
}

function sfBlindOff()
{
    $(".shakeflat-sidebar-blind").css("display", "none");
}

function sfLayoutFullsize()
{
    $(".shakeflat-content-wrapper").css("min-height", "calc(100% - 57px)");
    $("#shakeflat-clock-date").css("display", "block");
    sfLeftMenuFullSize();
}

function sfLayoutMini()
{
    $(".shakeflat-content-wrapper").css("min-height", "calc(100% - 77px)");
    $("#shakeflat-clock-date").css("display", "block");
    sfLeftMenuMini();
}

function sfLayoutTiny()
{
    $(".shakeflat-content-wrapper").css("min-height", "calc(100% - 77px)");
    $("#shakeflat-clock-date").css("display", "none");
    sfLeftMenuHide();
}

function sfWindowResize()
{
    if ($(window).width() < 600) {
        sfLayoutTiny();
    } else if ($(window).width() < 1000 || localStorage.getItem('sfLeftMenu') == "mini") {
        sfLayoutMini();
    } else {
        sfLayoutFullsize();
    }
    sfBlindOff();
}

$(document).ready(function() {
    sfSetClock();
    sfWindowResize();

    $("#shakeflat-btn-left-menu").on("click", function() {
        var status = $(".shakeflat-left").data("status");
        if (status == "full") {
            sfLeftMenuMini();
            sfBlindOff();
            localStorage.setItem('sfLeftMenu', 'mini');
        } else if (status == "mini") {
            sfLayoutFullsize();
            if ($(window).width() < 1000) sfBlind();
            localStorage.setItem('sfLeftMenu', '');
        } else {
            sfLayoutFullsize();
            sfBlind();
            localStorage.setItem('sfLeftMenu', '');
        }
    });

    $(".shakeflat-sidebar-blind").on("click", function() {
        if ($(window).width() < 600) sfLeftMenuHide(); else sfLeftMenuMini();
        sfBlindOff();
    });

    $(".shakeflat-left").on("mouseleave", function() {
        if (localStorage.getItem('sfLeftMenu') != "mini") return;
        if ($(".shakeflat-left").data("status") != "full") return;
        sfLeftMenuMini();
    });

    $(".shakeflat-left").on('mouseover', function() {
        sfLayoutFullsize();
        return false;
    });

    $(".list-group-tree").on('click', "[data-toggle=collapse]", function() {
        if ($(this).children("span").css("display") != "inline-block") return;
        $(this).toggleClass('in');
        $(this).next(".list-group.collapse").collapse('toggle');
        return false;
    })

    $(window).on("resize", function(e) { sfWindowResize(); });
});