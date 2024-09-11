// DataTable export excel for serverside
function newexportaction(e, dt, button, config) {
    var self = this;
    var oldStart = dt.settings()[0]._iDisplayStart;
    dt.one('preXhr', function (e, s, data) {
        // Just this once, load all data from the server...
        data.start = 0;
        data.length = dt.page.info().recordsTotal;
        dt.one('preDraw', function (e, settings) {
            // Call the original action function
            if (button[0].className.indexOf('buttons-copy') >= 0) {
                $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-print') >= 0) {
                $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
            }
            dt.one('preXhr', function (e, s, data) {
                // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                // Set the property to what it was before exporting.
                settings._iDisplayStart = oldStart;
                data.start = oldStart;
            });
            // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
            setTimeout(dt.ajax.reload, 0);
            // Prevent rendering of the full data to the DOM
            return false;
        });
    });
    // Requery the server with the new one-time export settings
    dt.ajax.reload();
}

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
