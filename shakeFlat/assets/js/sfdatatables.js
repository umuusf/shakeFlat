let sfdtMonthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
let sfdtDaysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
let sfdtCancelLabel = 'Cancel';
let sfdtApplyLabel = 'Apply';
let sfdtResetConfirmMsg = 'Do you want to revert to the initial state?';
let sfdtColumnConfigServerSideSaveMsg = 'Enter the name of the configuration to save.';
let sfdtColumnConfigDeleteMsg = 'Do you want to delete the saved configuration?';
let sfdtColumnConfigTxtTitleMessage = 'Set the order and visibility of the columns.';
let sfdtColumnConfigTxtColumn = 'Column';
let sfdtColumnConfigTxtVisible = 'Visible';
let sfdtColumnConfigTxtOrder = 'Order';
let sfdtColumnConfigInvalidConfig = 'The data is corrupted. Please delete the saved configuration.';

$(document).ready(function() {
    let sfdtKeypressDelayTimer = {};

    if ($(".sfdt-custom-search").data("language") == "kr") {
        sfdtMonthNames = ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'];
        sfdtDaysOfWeek = ['일', '월', '화', '수', '목', '금', '토'];
        sfdtCancelLabel = '취소';
        sfdtApplyLabel = '적용';
        sfdtResetConfirmMsg = '처음 상태로 되돌리겠습니까?';
        sfdtColumnConfigServerSideSaveMsg = '저장할 구성의 이름을 입력하세요.';
        sfdtColumnConfigDeleteMsg = '저장된 구성을 삭제하시겠습니까?';
        sfdtColumnConfigTxtTitleMessage = '열의 순서와 표시 여부를 설정합니다.';
        sfdtColumnConfigTxtColumn = '열';
        sfdtColumnConfigTxtVisible = '표시';
        sfdtColumnConfigTxtOrder = '순서';
        sfdtColumnConfigInvalidConfig = '데이터가 손상 되었습니다. 저장된 구성을 삭제하십시오.';
    }

    if (sfGetTheme() === 'dark') {
        $(".sfdt-custom-search-item>select[data-sfselect2='true']").select2({theme: 'bootstrap5-dark'});
    } else {
        $(".sfdt-custom-search-item>select[data-sfselect2='true']").select2({theme: 'bootstrap5'});
    }

    $(document).on("click", ".btn-sfdt-search-reset", function() {
        sfdtSearchReset($(this).data("table-id"));
        sfdt[$(this).data("table-id")].search("").draw();
    });

    $(document).on("click", "button.sfdt-btn-pagejump", function() {
        let tableId = $(this).parents('div').prev('input').data('table-id');
        let page = parseInt($(this).parents('div').prev('input').val(), 10);
        if (page < 1 || !page) page = 1;
        if (page > sfdt[tableId].page.info().pages) page = sfdt[tableId].page.info().pages;
        sfdt[tableId].page(page - 1).draw('page');
        $(this).parents('div').prev('input').val(page);
    });

    $(".sfdt-custom-search-item.sfdt-custom-search-auto-submit>select[data-sfdt-custom-search-ex!='true']").on("change", function() {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val(), true, false).draw();
    });

    $(".sfdt-custom-search-item.sfdt-custom-search-auto-submit>select[data-sfdt-custom-search-ex='true']").on("change", function() {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item.sfdt-custom-search-auto-submit>input[data-sfdt-custom-search-type='string'][data-sfdt-custom-search-ex!='true']").on("keyup", function() {
        clearTimeout(sfdtKeypressDelayTimer[$(this).attr("id")]);
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        let alias = $(this).data("sfdt-alias");
        let val = $(this).val();
        sfdtKeypressDelayTimer[$(this).attr("id")] = setTimeout(function() {
            let oriIdx = sfdtFindIndexForAlias(tableId, alias);
            sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search(val).draw();
        }, 800);
    });

    $(".sfdt-custom-search-item.sfdt-custom-search-auto-submit>input[data-sfdt-custom-search-type='string'][data-sfdt-custom-search-ex='true']").on("keyup", function() {
        clearTimeout(sfdtKeypressDelayTimer[$(this).attr("id")]);
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        sfdtKeypressDelayTimer[$(this).attr("id")] = setTimeout(function() {
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

    $(".sfdt-custom-search-item.sfdt-custom-search-auto-submit input[data-sfdt-custom-search-type='numberrange'][data-sfdt-custom-search-ex!='true']").on("apply.sfRangeSlide", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
    });

    $(".sfdt-custom-search-item.sfdt-custom-search-auto-submit input[data-sfdt-custom-search-type='numberrange'][data-sfdt-custom-search-ex='true']").on("apply.sfRangeSlide", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        sfdt[tableId].ajax.reload();
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange']").daterangepicker({
        timePicker: false, autoUpdateInput: false,
        locale: { format: 'YYYY-MM-DD', cancelLabel: sfdtCancelLabel, applyLabel: sfdtApplyLabel, monthNames: sfdtMonthNames, daysOfWeek: sfdtDaysOfWeek }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange']").daterangepicker({
        timePicker: true, autoUpdateInput: false,
        locale: { format: 'YYYY-MM-DD HH:mm', cancelLabel: sfdtCancelLabel, applyLabel: sfdtApplyLabel, monthNames: sfdtMonthNames, daysOfWeek: sfdtDaysOfWeek }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex!='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        if (oriIdx > -1) {
            if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
                sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
            }
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
            sfdt[tableId].ajax.reload();
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex!='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        if (oriIdx > -1) {
            if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
                sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
            }
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex='true']").on("apply.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
        if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
            sfdt[tableId].ajax.reload();
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex!='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex!='true']").on("cancel.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val("");
        let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
        if (oriIdx) {
            if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
                sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
            }
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex='true']").on("cancel.daterangepicker", function(ev, picker) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        $(this).val("");
        if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
            sfdt[tableId].ajax.reload();
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex!='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex!='true']").on("keypress", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        if ($(this).val() === '') $(this).trigger('cancel.daterangepicker');
        else if ($(this).val().length === 23) {
            let oriIdx = sfdtFindIndexForAlias(tableId, $(this).data("sfdt-alias"));
            if (oriIdx) {
                if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
                    sfdt[tableId].columns(sfdt[tableId].colReorder.transpose(oriIdx)).search($(this).val()).draw();
                }
            }
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'][data-sfdt-custom-search-ex='true'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange'][data-sfdt-custom-search-ex='true']").on("keypress", function(event) {
        let tableId = $(this).closest(".sfdt-custom-search").data("table-id");
        if ($(this).val() === '') $(this).trigger('cancel.daterangepicker');
        else if ($(this).val().length === 23) {
            if ($(this).closest(".sfdt-custom-search-item").hasClass("sfdt-custom-search-auto-submit")) {
                sfdt[tableId].ajax.reload();
            }
        }
    });

    $(".sfdt-custom-search-item>input[data-sfdt-custom-search-type='daterange'], .sfdt-custom-search-item>input[data-sfdt-custom-search-type='datetimerange']").on("search", function(event) {
        $(this).trigger('cancel.daterangepicker');
        $(".daterangepicker:visible .cancelBtn").trigger('click.daterangepicker');
    });

    $(document).on("click", "button.sfdt-btn-open-column-config", function() {
        let tableId = $(this).data("table-id");
        $("#sfdt-modal-column-config-body").data("table-id", tableId);
        $("#sfdt-modal-column-config-body").empty();

        let columnInfo = sfdtGetColumnStatus(tableId);
        let order = sfdt[tableId].colReorder.order();

        let $table = $("<table/>").addClass("table table-sm table-hover mb-0").css("min-width", "20rem");
        let $caption = $("<caption/>").addClass("caption-top text-nowrap text-center pt-0").text(sfdtColumnConfigTxtTitleMessage);
        let $thead = $("<thead/>");
        let $tr = $("<tr/>");
        let $th1 = $("<th/>").addClass("text-center text-nowrap").text(sfdtColumnConfigTxtColumn);
        let $th2 = $("<th/>").addClass("text-center text-nowrap").text(sfdtColumnConfigTxtVisible);
        let $th3 = $("<th/>").addClass("text-center text-nowrap").attr("colspan", 2).text(sfdtColumnConfigTxtOrder);
        $tr.append($th1).append($th2).append($th3);
        $thead.append($tr);
        $table.append($caption).append($thead);

        let $tbody = $("<tbody/>");
        for (let i = 0; i < order.length; i++) {
            let $tr = $("<tr/>").attr("data-index", order[i]).attr("draggable", "true");
            let $td1 = $("<td/>").addClass("text-center align-middle text-nowrap").text(columnInfo[order[i]].title || '-');
            let $td2 = $("<td/>").addClass("text-center align-middle text-nowrap");
            let $input = $("<input/>").attr("type", "checkbox").attr("name", "column-config-item-"+i).addClass("form-check-input sfdt-column-config-visible").prop("checked", columnInfo[order[i]].visible).prop("disabled", columnInfo[order[i]].disableInvisible);
            $td2.append($input);
            let $td3 = $("<td/>").addClass("text-center align-middle text-nowrap pb-2");
            let $btn1 = $("<button/>").attr("type", "button").addClass("btn btn-xs btn-primary me-1 sfdt-btn-column-config-up").attr("data-index", order[i]).html("<i class=\"fas fa-caret-up\"></i>");
            let $btn2 = $("<button/>").attr("type", "button").addClass("btn btn-xs btn-primary sfdt-btn-column-config-down").attr("data-index", order[i]).html("<i class=\"fas fa-caret-down\"></i>");
            $td3.append($btn1).append($btn2);
            $tr.append($td1).append($td2).append($td3);
            $tbody.append($tr);
        }
        $tbody.on("dragstart", function(e) {
            if (e.target.tagName === 'TR') {
                e.target.classList.add('dragging');
            }
        });
        $tbody.on("dragend", function(e) {
            if (e.target.tagName === 'TR') {
                e.target.classList.remove('dragging');
            }
        });
        $tbody.on("dragover", function(e) {
            e.preventDefault();
        });
        $tbody.on("drop", function(e) {
            e.preventDefault();
            const afterElement = sfdtGetDragAfterElement($tbody[0], e.clientY);
            const draggingElement = $tbody.find('.dragging')[0];
            if (afterElement == null) {
                $tbody[0].appendChild(draggingElement);
            } else {
                $tbody[0].insertBefore(draggingElement, afterElement);
            }
            sfdtUpdateApplyBtnStatus(tableId);
        });
        $table.append($tbody);

        $("#sfdt-modal-column-config-body").html($table);
        $("#sfdt-btn-column-config-apply").prop("disabled", true);

        sfdtUpdateColumnConfig(tableId);

        $("#sfdt-modal-column-config").modal("show");
    });

    $("#sfdt-btn-column-config-reset").on("click", function() {
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");
        confirm(sfdtResetConfirmMsg, function() {
            sfdt[tableId].colReorder.reset();
            sfdt[tableId].columns().header().columns().every(function() { this.visible(true); });
            sfdt[tableId].order(sfdt[tableId].init().order).draw();
            $("#sfdt-modal-column-config").modal("hide");
        });
    });

    $("#sfdt-btn-column-config-save").on("click", function() {
        let tableId = $("#sfdt-modal-column-config-body").data("table-id");

        inputConfirm({
            messageText: sfdtColumnConfigServerSideSaveMsg,
            inputWidth: '15rem'
        },async function(result) {
            if (result) {
                let savedConfigs = await sfdtColumnConfigLoad(tableId);
                savedConfigs[tableId] = savedConfigs[tableId] || [];

                let maxIdx = 0;
                for (let i = 0; i < savedConfigs[tableId].length; i++) {
                    if (savedConfigs[tableId][i].idx > maxIdx) maxIdx = savedConfigs[tableId][i].idx;
                }
                let idx = maxIdx + 1;
                let visible = [];
                let order = [];
                $("#sfdt-modal-column-config-body tbody tr").each(function(index) {
                    visible.push($(this).find("input[type='checkbox']").prop("checked"));
                    order.push(parseInt($(this).data("index"), 10));
                });

                savedConfigs[tableId].push({
                    idx: idx,
                    name: result,
                    date: new Date().toISOString(),
                    visible: visible,
                    order: order,
                });
                await sfdtColumnConfigSave(tableId, savedConfigs);
                sfdtUpdateColumnConfig(tableId);
                return true;
            }
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

    $(document).on("hide.bs.modal", ".modal", function() {
        if (document.activeElement) document.activeElement.blur();
    });
});

function sfdtGetDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('tr:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

async function sfdtUpdateColumnConfig(tableId)
{
    $("#sfdt-modal-column-config-save").html("");
    let savedConfigs = await sfdtColumnConfigLoad(tableId);
    if (savedConfigs[tableId] && savedConfigs[tableId].length > 0) {
        let $divList = $("<div/>").addClass("d-flex flex-column");
        for (let i = 0; i < savedConfigs[tableId].length; i++) {
            let savedConfig = savedConfigs[tableId][i];
            let $divItem = $("<div/>").addClass("sfdt-ccs-item").data("idx", savedConfig.idx);
            let $divText = $("<div/>").addClass("sfdt-css-text text-nowrap").text(savedConfig.name);
            let $btn = $("<button/>").addClass("sfdt-css-btn btn btn-sm").html("<i class=\"bi bi-trash-fill\"></i>");
            $btn.on("click", async function(e) {
                let obj = $(this).parents(".sfdt-ccs-item");
                let idx = obj.data("idx");
                let savedConfigs = await sfdtColumnConfigLoad(tableId);
                confirm(sfdtColumnConfigDeleteMsg, async function() {
                    savedConfigs[tableId] = savedConfigs[tableId] || [];
                    savedConfigs[tableId] = savedConfigs[tableId].filter(function(item) { return item.idx !== idx; });
                    await sfdtColumnConfigSave(tableId, savedConfigs);
                    obj.remove();
                    if ($(".sfdt-ccs-item").length === 0) $("#sfdt-modal-column-config-save").hide();
                });
                e.stopPropagation();
            });
            $divItem.append($divText).append($btn);
            $divItem.on("click", async function() {
                let idx = $(this).data("idx");
                let savedConfigs = await sfdtColumnConfigLoad(tableId);
                let savedConfig = savedConfigs[tableId].find(function(item) { return item.idx == idx; });
                if (savedConfig) {
                    let order = savedConfig.order;
                    let visible = savedConfig.visible;
                    if (order.length != visible.length || order.length != sfdt[tableId].settings().init().columns.length) {
                        alert(sfdtColumnConfigInvalidConfig);
                    } else {
                        $("#sfdt-modal-column-config-body tbody").empty();
                        for (let i = 0; i < order.length; i++) {
                            let index = order[i];
                            let title = sfdt[tableId].settings().init().columns[index].title || '-';
                            let disableInvisible = (sfdtGetColumnStatus(tableId)[index].disableInvisible) ? ' disabled' : '';

                            let $tr = $("<tr/>").attr("data-index", order[i]).attr("draggable", "true");
                            let $td1 = $("<td/>").addClass("text-center align-middle text-nowrap").text(title);
                            let $td2 = $("<td/>").addClass("text-center align-middle text-nowrap");
                            let $input = $("<input/>").attr("type", "checkbox").attr("name", "column-config-item-"+i).addClass("form-check-input sfdt-column-config-visible").prop("checked", visible[i]).prop("disabled", disableInvisible);
                            $td2.append($input);
                            let $td3 = $("<td/>").addClass("text-center align-middle text-nowrap pb-2");
                            let $btn1 = $("<button/>").attr("type", "button").addClass("btn btn-xs btn-primary me-1 sfdt-btn-column-config-up").attr("data-index", order[i]).html("<i class=\"fas fa-caret-up\"></i>");
                            let $btn2 = $("<button/>").attr("type", "button").addClass("btn btn-xs btn-primary sfdt-btn-column-config-down").attr("data-index", order[i]).html("<i class=\"fas fa-caret-down\"></i>");
                            $td3.append($btn1).append($btn2);
                            $tr.append($td1).append($td2).append($td3);

                            $("#sfdt-modal-column-config-body tbody").append($tr);
                        }
                        sfdtUpdateApplyBtnStatus(tableId);
                    }
                }
            });
            $divList.append($divItem);
        }
        $("#sfdt-modal-column-config-save").append($divList);
        $("#sfdt-modal-column-config-save").show();
    } else {
        $("#sfdt-modal-column-config-save").hide();
    }

}

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

async function sfdtColumnConfigLoad(tableId)
{
    let serverSide = $("button.sfdt-btn-open-column-config[data-table-id='"+tableId+"']").data("server-side");
    if (!serverSide) {
        return JSON.parse(localStorage.getItem('sfdt-'+tableId+'-column-configs')) || {};
    } else {
        let res = await ajaxResult(
            window.location.pathname,
            {
                sfdtPageMode: 'columnConfigLoad',
                tableId: tableId
            }
        );
        if (!('data' in res) || !('result' in res.data) || !res.data.result) return {};
        let ret = JSON.parse(res.data.result) || {};
        return ret;
    }
}

async function sfdtColumnConfigSave(tableId, data)
{
    let serverSide = $("button.sfdt-btn-open-column-config[data-table-id='"+tableId+"']").data("server-side");
    if (!serverSide) {
        localStorage.setItem('sfdt-'+tableId+'-column-configs', JSON.stringify(data));
    } else {
        let res = await ajaxResult(
            window.location.pathname,
            {
                sfdtPageMode: 'columnConfigSave',
                tableId: tableId,
                data: JSON.stringify(data)
            }
        );
        return res;
    }
}

function sfdtSearchReset(tableId)
{
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
}

function sfdtSearchConditionAll(tableId)
{
    let customSearch = {};
    $(".sfdt-"+tableId+"-custom-search").each(function() {
        if ($(this).data("sfdt-custom-search-ex") != true) {
            customSearch[$(this).data("sfdt-alias")] = $(this).val();
        }
    });
    let csExStr = localStorage.getItem('sfdt-'+tableId+'-custom-search-ex');
    if (csExStr) {
        csEx = JSON.parse(csExStr);
        for (let key in csEx) {
            customSearch[key] = csEx[key];
        }
    }
    return {
        search : sfdt[tableId].search(),
        customSearch : customSearch
    };
}