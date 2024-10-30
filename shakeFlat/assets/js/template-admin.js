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
    $(".shakeflat-left .list-group > [data-bs-toggle='collapse']").removeClass("hide");
    $(".shakeflat-left .list-group.collapse").removeClass("hide");
    $(".shakeflat-left .list-group.opened").removeClass("hide").addClass("show");
    $(".shakeflat-left").data("status", "full");
}

function sfLeftMenuMini()
{
    $(".shakeflat-left").css("width", "60px");
    $(".shakeflat-left").css("min-width", "60px");
    $(".shakeflat-left").css("display", "inline-block");
    $(".shakeflat-left .list-group > .list-group-item > span").css("display", "none");
    $(".shakeflat-left .list-group > [data-bs-toggle='collapse']").addClass("hide");
    $(".shakeflat-left .list-group.collapse").removeClass("show").addClass("hide");
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

function sfGetTheme()
{
    return sfGetThemeCookie().theme;
}

function sfCheckTheme(select)
{
    if (select == "dark" || select == "light") return select;
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return "dark";
    } else {
        return "light";
    }
}

// theme : light, dark   select : light, dark, auto
function sfSetThemeCookie(theme, select)
{
    var c = { theme: theme, select: select };
    Cookies.set('sfTheme', JSON.stringify(c), { expires: 3650, path: '/' });
}

function sfGetThemeCookie()
{
    var c = Cookies.get('sfTheme');
    if (c == undefined) return { theme: "light", select: "auto" };

    var j = JSON.parse(c);
    var theme = j.theme || "light";
    var select = j.select || "auto";

    if (theme != "dark") theme = "light";
    if (select != "light" && select != "dark") select = "auto";
    return { theme: theme, select: select };
}

// select : light, dark, auto
function sfSetTheme(select)
{
    var theme = sfCheckTheme(select);
    $("html").attr("data-sf-theme", theme);

    var html = "<i class=\"bi bi-circle-half\"></i> Auto";
    if (select == "light") html = "<i class=\"bi bi-sun-fill\"></i> Light";
    if (select == "dark") html = "<i class=\"bi bi-moon-stars-fill\"></i> Dark";
    $(".shakeflat-theme-dropdown > button").html(html);

    $(".shakeflat-theme-dropdown .dropdown-menu button").removeClass("active");
    $(".shakeflat-theme-dropdown .dropdown-menu button[data-sf-theme-value='" + select + "']").addClass("active");

    sfSetThemeCookie(theme, select);
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

    $(".shakeflat-left .list-group div.list-group-item").on("click", function() {
        if ($(this).data("bs-toggle") == "collapse") $(this).toggleClass("open");
        return false;
    });

    $(".shakeflat-theme-dropdown .dropdown-menu button").on("click", function() {
        sfSetTheme($(this).data("sf-theme-value"));
        //location.reload();
    });

    // Apply on next refresh
    if (sfGetThemeCookie().select == "auto" && sfCheckTheme("auto") != sfGetThemeCookie().theme) {
        sfSetThemeCookie(sfCheckTheme("auto"), "auto");
    }

    $(window).on("resize", function(e) { sfWindowResize(); });
});

// Puts the value read from DB into divElement and returns html.
// If you attach a db field name to the prefix @db-, the corresponding part is replaced.
// ex) <div>@db-title</div> => <div>Your Title String...</div>
// ex) $("#quizset-info").assignDB(result.data.info)
jQuery.prototype.assignDB = function(data) {
    var divElement = this;
    divElement.html(divElement.getAssignDB(data));
    divElement.show();
}

// Returns the changed html without immediately changing the divElement html
jQuery.prototype.getAssignDB = function(data) {
    var divElement = this;
    var html = divElement.html();
    for(var k in data) {
        html = html.replaceAll("@db-" + k, data[k]);
    };
    html = html.replaceAll("b-img", "img");         // In the case of an img tag, an error occurs because lookup is performed before the src= path is replaced. To prevent this, the img tag is described as b-img.

    return html;
}

// Check the id value of the html dom element and insert the db value.
// <div id="db-title"></div> => <div id="db-title">Your Title String...</div>
jQuery.prototype.assignDB2 = function(data) {
    var divElement = this;
    divElement.html(divElement.getAssignDB2(data));
    divElement.show();
}

jQuery.prototype.getAssignDB2 = function(data) {
    $(this).find("*").each(function() {
        for(var k in data) {
            if ($(this).attr("id") == "db-" + k) {
                if ($(this).prop("tagName") == "DB-IMG") {
                    var styles = $(this).getStyleObject();
                    $(this).replaceWith($("<img id='db-"+k+"' src='" + data[k] + "'>"));
                    $("#db-"+k).css(styles);
                } if ($(this).prop("tagName") == "A" && $(this).data("role") == "db-link") {
                    if (data[k]) {
                        $(this).attr("href", data[k]);
                        $(this).html($(this).data("html"));
                    } else {
                        $(this).attr("href", "#");
                        $(this).html("");
                    }
                } else {
                    if (data[k]) $(this).html(data[k]); else $(this).html("");
                }
            }
        }
    });
}

// Check with the input(form control) id value of the html dom element and insert the db value into the value.
// <input type="text" id="db-title" name="db-title"> => <input type="text" id="db-title" name="db-title" value="Your Title String...">
// prefix is used to compare with the id value of data by attaching prefix in front of the input id
jQuery.prototype.assignDB3 = function(data, prefix) {
    var divElement = this;
    divElement.html(divElement.getAssignDB3(data, prefix));
    divElement.show();
}

jQuery.prototype.getAssignDB3 = function(data, prefix) {
    if (!prefix) prefix = "";
    $(this).find("*").each(function() {
        for(var k in data) {
            if ($(this).attr("id") == k || $(this).attr("id") == prefix + k) {
                if ($(this).prop("tagName") == "INPUT") {
                    if ($(this).attr("type").toUpperCase() == "TEXT" && $(this).hasClass("cls-number") && typeof setAutoNumericVal == 'function') {
                        setAutoNumericVal("#"+$(this).attr("id"), data[k]);
                    } else if ($(this).attr("type").toUpperCase() == "HIDDEN" || $(this).attr("type").toUpperCase() == "TEXT" ||
                        $(this).attr("type").toUpperCase() == "DATE" || $(this).attr("type").toUpperCase() == "DATETIME" || $(this).attr("type").toUpperCase() == "NUMBER") {
                        $(this).val(data[k]);
                    }
                } else if ($(this).prop("tagName") == "TEXTAREA") {
                    $(this).val(data[k]);
                } else if ($(this).prop("tagName") == "SELECT") {
                    $(this).val(data[k]);
                }
            }
        }
    });
}
