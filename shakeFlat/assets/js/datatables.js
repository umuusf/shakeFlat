$(document).ready(function() {
    let keypressDelayTimer = {};

    let monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let cancelLabel = 'Cancel';
    let applyLabel = 'Apply';
    let resetConfirmMsg = 'Do you want to revert to the initial state?';

    if ($(".sfdt-custom-search").data("language") == "kr") {
        monthNames = ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'];
        daysOfWeek = ['일', '월', '화', '수', '목', '금', '토'];
        cancelLabel = '취소';
        applyLabel = '적용';
        resetConfirmMsg = '처음 상태로 되돌리겠습니까?';
    }


    if (sfGetTheme() === 'dark') {
        $(".sfdt-custom-search-item>select[data-sfselect2='true']").select2({theme: 'bootstrap5-dark'});
    } else {
        $(".sfdt-custom-search-item>select[data-sfselect2='true']").select2({theme: 'bootstrap5'});
    }

    $(document).on("click", ".btn-sfdt-search-reset", function() {
        let tableId = $(this).data("table-id");
        $(".sfdt-"+tableId+"-custom-search").each(function() {
            if ($(this).data("sfdt-custom-search-ex") != true) {
                let alias = $(this).data("sfdt-alias");
                let oriIdx = sfdtFindIndexForAlias(tableId, alias);
                sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search("");
            }
            switch($(this).prop("tagName")) {
                case "INPUT" :
                    if ($(this).attr("type") === 'text' || $(this).attr("type") === 'search') $(this).val("");
                    break;
                case "SELECT" :
                    if ($(this).data("sfselect2") == true) $(this).val($(this).find("option:first").val()).trigger("change.select2");
                    else $(this).find("option:first").prop("selected", true);
                    break;
            }
        });
        sfdt[tableId].search("").draw();
    });

    $(document).on("click", "button.sfdt-btn-pagejump", function() {
        let tableId = $(this).parents('div').prev('input').data('table-id');
        let page = parseInt($(this).parents('div').prev('input').val(), 10);
        if (page < 1 || !page) page = 1;
        if (page > sfdt[tableId].page.info().pages) page = sfdt[tableId].page.info().pages;
        sfdt[tableId].page(page - 1).draw('page');
        $(this).parents('div').prev('input').val(page);
    });

    $(".sfdt-custom-search-item>select[data-sfdt-custom-search-ex!='true']").on("change", function() {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val(), true, false).draw();
    });

    $(".sfdt-custom-search-item>select[data-sfdt-custom-search-ex='true']").on("change", function() {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='string'][data-sfdt-custom-search-ex!='true']").on("keyup", function() {
        clearTimeout(keypressDelayTimer[$(this).attr("id")]);
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        let alias = $(this).data("sfdt-alias");
        let val = $(this).val();
        keypressDelayTimer[$(this).attr("id")] = setTimeout(function() {
            let oriIdx = sfdtFindIndexForAlias(tableId, alias);
            sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search(val).draw();
        }, 800);
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='string'][data-sfdt-custom-search-ex='true']").on("keyup", function() {
        clearTimeout(keypressDelayTimer[$(this).attr("id")]);
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        keypressDelayTimer[$(this).attr("id")] = setTimeout(function() {
            sfdt[tableId].ajax.reload();
        }, 800);
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='string']").on("search", function() {
        $(this).val("");
        $(this).trigger("keyup");
    });

    $(".sfdt-custom-search-item input[data-sfdt-custom-search-type='numberrange']").sfRangeSlide({
        theme: sfGetTheme(),
        min: $(".sfdt-custom-search-item input[data-sfdt-custom-search-type='numberrange']").data("sfdt-numberrange-min"),
        max: $(".sfdt-custom-search-item input[data-sfdt-custom-search-type='numberrange']").data("sfdt-numberrange-max")
    });

    $(".sfdt-custom-search-item input[data-sfdt-custom-search-type='numberrange'][data-sfdt-custom-search-ex!='true']").on("apply.sfRangeSlide", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
    });

    $(".sfdt-custom-search-item input[data-sfdt-custom-search-type='numberrange'][data-sfdt-custom-search-ex='true']").on("apply.sfRangeSlide", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange']").daterangepicker({
        timePicker: false, autoUpdateInput: false,
        locale: { format: 'YYYY-MM-DD', cancelLabel: cancelLabel, applyLabel: applyLabel, monthNames: monthNames, daysOfWeek: daysOfWeek }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange']").daterangepicker({
        timePicker: true, autoUpdateInput: false,
        locale: { format: 'YYYY-MM-DD HH:mm', cancelLabel: cancelLabel, applyLabel: applyLabel, monthNames: monthNames, daysOfWeek: daysOfWeek }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex!='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        if (oriIdx > -1) sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex!='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        if (oriIdx > -1) sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex!='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex!='true']").on("cancel.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val("");
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        if (oriIdx) sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex='true']").on("cancel.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val("");
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex!='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex!='true']").on("keypress", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        if ($(this).val() === '') $(this).trigger('cancel.daterangepicker');
        else if ($(this).val().length === 23) {
            let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
            if (oriIdx) sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex='true']").on("keypress", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        if ($(this).val() === '') $(this).trigger('cancel.daterangepicker');
        else if ($(this).val().length === 23) {
            sfdt[tableId].ajax.reload();
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange']").on("search", function(event) {
        $(this).trigger('cancel.daterangepicker');
        $(".daterangepicker:visible .cancelBtn").trigger('click.daterangepicker');
    });

    $(document).on("click", "button.sfdt-btn-open-column-config", function() {
        let txtTitleMessage = 'Set the order and visibility of the columns.';
        let txtColumn = 'Column';
        let txtVisible = 'Visible';
        let txtOrder = 'Order';
        if ($(".sfdt-custom-search").data("language") == "kr") {
            txtTitleMessage = '열의 순서와 표시 여부를 설정합니다.';
            txtColumn = '열';
            txtVisible = '표시';
            txtOrder = '순서';
        }

        let tableId = $(this).data("table-id");
        $("#sfdt-modal-column-config-body").data("table-id", tableId);
        $("#sfdt-modal-column-config-body").empty();

        let columnInfo = sfdtGetColumnStatus(tableId);
        let order = sfdt[tableId].colReorder.order();

        let tbl = `
            <table class="table table-sm table-hover mb-0">
                <caption class="caption-top text-nowrap text-center pt-0">${txtTitleMessage}</caption>
                <thead>
                    <tr>
                        <th class="text-center text-nowrap">${txtColumn}</th>
                        <th class="text-center text-nowrap">${txtVisible}</th>
                        <th colspan=2 class="text-center text-nowrap">${txtOrder}</th>
                    </tr>
                </thead>
                <tbody>
        `;
        for (let i = 0; i < order.length; i++) {
            let index = order[i];
            let title = columnInfo[index].title || '-';
            let visible = columnInfo[index].visible;
            let disableInvisible = (columnInfo[index].disableInvisible) ? ' disabled' : '';

            tbl += `
                <tr data-index="${index}">
                    <td class="text-center align-middle text-nowrap">${title}</td>
                    <td class="text-center align-middle text-nowrap">
                        <input type="checkbox" class="form-check-input sfdt-column-config-visible" ${visible ? 'checked' : ''}${disableInvisible}>
                    </td>
                    <td class="text-center align-middle text-nowrap pb-2">
                        <button type="button" class="btn btn-xs btn-primary sfdt-btn-column-config-up"   data-index="${index}"><i class="fas fa-caret-up"></i></button>
                        <button type="button" class="btn btn-xs btn-primary sfdt-btn-column-config-down" data-index="${index}"><i class="fas fa-caret-down"></i></button>
                    </td>
                </tr>
            `;
        }
        tbl += `
                </tbody>
            </table>
        `;

        $("#sfdt-modal-column-config-body").html(tbl);

        $("#sfdt-btn-column-config-apply").prop("disabled", true);
        $("#sfdt-modal-column-config").modal("show");
    });

    $(".sfdt-btn-column-config-reset").on("click", function() {
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");
        confirm(resetConfirmMsg, function() {
            sfdt[tableId].colReorder.reset();
            sfdt[tableId].columns().header().columns().every(function() { this.visible(true); });
            sfdt[tableId].order(sfdt[tableId].init().order).draw();
            $("#sfdt-modal-column-config").modal("hide");
        });
    });

    $(document).on("click", "button.sfdt-btn-column-config-up", function() {
        let prev = $(this).parents("tr").prev();
        if (prev.length === 0) return;
        $(this).parents("tr").insertBefore(prev);
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");
        sfdtUpdateApplyBtnStatus(tableId);
    });

    $(document).on("click", "button.sfdt-btn-column-config-down", function() {
        let next = $(this).parents("tr").next();
        if (next.length === 0) return;
        $(this).parents("tr").insertAfter(next);
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");
        sfdtUpdateApplyBtnStatus(tableId);
    });

    $(document).on("change", "input.sfdt-column-config-visible", function() {
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");
        sfdtUpdateApplyBtnStatus(tableId);
    });

    $("#sfdt-btn-column-config-apply").on("click", function() {
        // apply visible status
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");
        let columnInfo = sfdtGetColumnStatus(tableId);
        $("#sfdt-modal-column-config-body tbody tr").each(function(index) {
            let visible = $(this).find("input[type='checkbox']").prop("checked");
            sfdt[tableId].column(parseInt($(this).data("index"), 10)).visible(visible);
        });

        // apply order
        let order = [];
        $("#sfdt-modal-column-config-body tbody tr").each(function(index) {
            order.push(parseInt($(this).data("index"), 10));
        });
        sfdt[tableId].colReorder.order(order, true);

        $("#sfdt-modal-column-config").modal("hide");
    });

});



function sfdtFindIndexForAlias(tableId, alias)
{
    let columns = sfdt[tableId].settings().init().columns;
    for (let i = 0; i < columns.length; i++) if (columns[i].name == alias) return i;
    return -1;
}

function sfdtSetDefaultValue(input, value)
{
    switch(input.prop("tagName")) {
        case "SELECT":
            if (input.hasClass("select2-hidden-accessible")) {
                input.val(value).trigger("change.select2");
            } else {
                input.val(value);
            }
            break;
        case "INPUT":
            // checkbox and radio are only used in editColumn
            // customSearch only has select
            if (input.attr("type") === 'checkbox') {
                let checkboxGroup = $("#" + input.attr("id")).closest(".sfdt-floating-checkbox").find("input[type='checkbox'][name='"+input.attr("name")+"']");
                checkboxGroup.each(function() {
                    if (typeof value === 'string') {
                        if ($(this).val() == value) $(this).prop("checked", true);
                    } else if (typeof value === 'object') {
                        if (value.includes($(this).val())) $(this).prop("checked", true);
                    }
                });
            } else if (input.attr("type") === 'radio') {
                let radioGroup = $("#" + input.attr("id")).closest(".sfdt-floating-radio").find("input[type='radio'][name='"+input.attr("name")+"']");
                radioGroup.each(function() {
                    if ($(this).val() == value) $(this).prop("checked", true);
                });
            } else {
                input.val(value);
            }
            break;
    }
}

function sfdtGetColumnStatus(tableId)
{
    let columnInfo = {};
    sfdt[tableId].columns().header().columns().every(function() {
        columnInfo[this.init()._crOriginalIdx] = { title: this.init().sTitle, visible: this.init().bVisible, disableInvisible: this.init().className.includes("sfdt-disable-invisible") ? true : false };
    });
    return columnInfo;
}

// check enable/disable apply button
function sfdtUpdateApplyBtnStatus(tableId)
{
    let order = sfdt[tableId].colReorder.order();
    let changed = false;
    let columnInfo = sfdtGetColumnStatus(tableId);
    $("#sfdt-modal-column-config-body tbody tr").each(function(index) {
        if (order[index] !== parseInt($(this).data("index"), 10) || columnInfo[parseInt($(this).data("index"), 10)].visible !== $(this).find("input[type='checkbox']").prop("checked")) {
            changed = true;
            return false;
        }
    });
    $("#sfdt-btn-column-config-apply").prop("disabled", !changed);;
}

// export for server-side processing
function sfdtExportAction(e, dt, button, config, cb) {
    var self = this;
    var oldStart = dt.settings()[0]._iDisplayStart;
    dt.one('preXhr', function (e, s, data) {
        // Just this once, load all data from the server...
        data.start = 0;
        data.length = dt.page.info().recordsTotal;
        dt.one('preDraw', function (e, settings) {
            // Call the original action function
            if (button[0].className.indexOf('buttons-copy') >= 0) {
                $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config, cb);
            } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config, cb) :
                    $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config, cb);
            } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config, cb) :
                    $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config, cb);
            } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config, cb) :
                    $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config, cb);
            } else if (button[0].className.indexOf('buttons-print') >= 0) {
                $.fn.dataTable.ext.buttons.print.action(e, dt, button, config, cb);
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
