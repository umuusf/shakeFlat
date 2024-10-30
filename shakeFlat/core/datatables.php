<?php
namespace shakeFlat;
use shakeFlat\L;
use shakeFlat\Translation;
use shakeFlat\Template;
use shakeFlat\Router;

class DataTablesColumn
{
    private $alias;
    private $title;
    private $data;
    private $class;
    private $render;
    private $type;
    private $searchable;
    private $orderable;
    private $buttons;

    public function __construct($alias)
    {
        $this->alias = $alias;
        $this->title = "";
        $this->data = $alias;
        $this->class = [];
        $this->render = "";
        $this->type = "";
        $this->searchable = true;
        $this->orderable = true;
        $this->buttons = [];
    }

    public function alias()
    {
        return $this->alias;
    }

    public function title($title = null)
    {
        if ($title === null) return $this->title;
        $this->title = $title;
        return $this;
    }

    public function data($data = null)
    {
        if ($data === null) return $this->data;
        $this->data = $data;
        return $this;
    }

    public function class($class = null)
    {
        if ($class === null) return $this->class;
        if (!in_array($class, $this->class)) $this->class[] = $class;
        return $this;
    }

    public function render($render = null)
    {
        if ($render === null) return $this->render;
        $this->render = $render;
        return $this;
    }

    public function type($type = null)
    {
        if ($type === null) return $this->type;
        $this->type = $type;
        return $this;
    }

    public function searchable($searchable = null)
    {
        if ($searchable === null) return $this->searchable;
        $this->searchable = $searchable;
        return $this;
    }

    public function orderable($orderable = null)
    {
        if ($orderable === null) return $this->orderable;
        $this->orderable = $orderable;
        return $this;
    }

    public function onlyRender($render)
    {
        $this->data = "";
        $this->title = "";
        $this->type = "html";
        $this->render = $render;
        return $this;
    }

    public function date($format = "YYYY-MM-DD")
    {
        $this->type = "string";
        $this->class("text-center");
        $this->render = "DataTable.render.date('" . $format . "')";
        return $this;
    }

    public function datetime($format = "YYYY-MM-DD HH:mm:ss")
    {
        $this->type = "string";
        $this->class("text-center");
        $this->render = "DataTable.render.datetime('" . $format . "')";
        return $this;
    }

    // $thousands: thousands separator, $decimals: decimal point, $precision: decimal point separator, $prefix: prefix, $postfix: postfix
    public function number($thousands = ',', $decimals = null, $precision = null, $prefix = null, $postfix = null)
    {
        $this->type = "num";
        $this->render = "DataTable.render.number(" .
                ($thousands !== null ? "'{$thousands}'" : "null") . ", " .
                ($decimals !== null ? $decimals : "null") . ", " .
                ($precision !== null ? "'{$precision}'" : "null") . ", " .
                ($prefix !== null ? $prefix : "null") . ", " .
                ($postfix !== null ? $postfix : "null") . ")";
        return $this;
    }

    public function textCenter() { return $this->class("text-center"); }
    public function textEnd() { return $this->class("text-end"); }
    public function textStart() { return $this->class("text-start"); }
    public function nowrap() { return $this->class("text-nowrap"); }

    public function disableInvisible() { return $this->class("sfdt-disable-invisible"); }
    public function noExport() { return $this->class("sfdt-no-export"); }

    public function renderButton($text, $class = "", $option = null) {
        $this->buttons[] = [
            "text" => $text,
            "class" => $class,
            "option" => $option
        ];

        $bArr = [];
        foreach($this->buttons as $btn) {
            if ($btn['class']) $class = " {$btn['class']}"; else $class = "";
            if ($btn['option']) $option = " {$btn['option']}"; else $option = "";
            $bArr[] = <<<EOD

                                    <button type=\"button\" class=\"btn btn-xs{$class}\"{$option}>{$btn['text']}</button>
                EOD;
        }
        $buttons = implode(" ", $bArr);

        $this->onlyRender(<<<EOD

                        function(data, type, row, meta) {
                            return `{$buttons}
                            `;
                        }
            EOD);
        return $this;
    }
}

class DataTablesCustomSearch
{
    const TYPE_STRING           = 1001;
    const TYPE_SELECT           = 1002;
    const TYPE_DATERANGE        = 1003;
    const TYPE_DATETIMERANGE    = 1004;
    const TYPE_NUMBERRANGE      = 1005;

    private $alias;
    private $title;
    private $type;
    private $controlOption;
    private $data;
    private $numberRangeOption;
    private $select2;

    private $ex;        // for not in columns

    public function __construct($alias)
    {
        $this->alias = $alias;
        $this->title = "";
        $this->type = "string";
        $this->controlOption = "";
        $this->numberRangeOption = [ "min" => 0, "max" => 100 ];
        $this->data = [];
        $this->select2 = false;
        $this->ex = false;
    }

    public function alias() { return $this->alias; }
    public function ex() { $this->ex = true; return $this; }
    public function isEx() { return $this->ex; }

    public function title($title = null)
    {
        if ($title === null) return $this->title;
        $this->title = $title;
        return $this;
    }

    public function type($type = null)
    {
        if ($type === null) return $this->type;
        $this->type = $type;
        return $this;
    }

    public function controlOption($option = null)
    {
        if ($option === null) return $this->controlOption;
        $this->controlOption = $option;
        return $this;
    }

    public function data($key = null, $value = null)
    {
        if ($key === null && $value === null) return $this->data;

        if (is_array($key) && $value === null) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function numberRange($min, $max)
    {
        $this->type(self::TYPE_NUMBERRANGE);
        $this->numberRangeOption = [ "min" => $min, "max" => $max ];
        return $this;
    }

    public function string() { return $this->type(self::TYPE_STRING); }
    public function select() { return $this->type(self::TYPE_SELECT); }
    public function select2() { $this->select2 = true; return $this->type(self::TYPE_SELECT); }
    public function dateRange() { return $this->type(self::TYPE_DATERANGE); }
    public function datetimeRange() { return $this->type(self::TYPE_DATETIMERANGE); }

    public function isString() { return $this->type == self::TYPE_STRING; }
    public function isSelect() { return $this->type == self::TYPE_SELECT; }
    public function isSelect2() { return $this->type == self::TYPE_SELECT && $this->select2; }
    public function isDateRange() { return $this->type == self::TYPE_DATERANGE; }
    public function isDatetimeRange() { return $this->type == self::TYPE_DATETIMERANGE; }
    public function isNumberRange() { return $this->type == self::TYPE_NUMBERRANGE; }

    public function numberRangeOption() { return $this->numberRangeOption; }

    public function getAll()
    {
        return [
            "alias"             => $this->alias,
            "type"              => $this->type,
            "controlOption"     => $this->controlOption,
            "data"              => $this->data,
            "numberRangeOption" => $this->numberRangeOption,
            "select2"           => $this->select2
        ];
    }
}

class DataTables
{
    private static $instance = array();
    private static $checkOnce = false;

    private $tableId;
    private $containerOption;

    private $options;
    private $language;

    private $exportActionPrint;
    private $exportActionPDF;
    private $exportActionExcel;
    private $exportTitlePrint;
    private $exportTitlePDF;
    private $exportTitleExcel;
    private $exportFilenamePDF;
    private $exportFilenameExcel;

    private $extraButtons;

    private $recordsTotal;
    private $recordsFiltered;

    private $columns;
    private $customSearch;

    private $layoutCustomSearch;
    private $layoutList;

    // output for template
    private $html;

    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (isset(self::$instance[$calledClass])) return self::$instance[$calledClass];
        self::$instance[$calledClass] = new $calledClass();
        return self::$instance[$calledClass];
    }

    protected function __construct($tableId)
    {
        $chk = preg_match("/^[a-z][a-z0-9]*$/", $tableId);
        if (!$chk) L::system("[:dt:It must be made only of alphabet(lower case) and numbers (the first letter is an alphabet).:]");

        $this->tableId = $tableId;
        $this->containerOption = "";

        $this->options = [
            "pageLength" => 20,
            "lengthMenu" => [10, 20, 25, 30, 50, 75, 100],
            "paging"     => true,

            "stateSave"  => true,
            "colReorder" => true,

            "responsive" => false,
            "scrollX"    => true,

            "retrieve"   => true,
            "serverSide" => true,
        ];
        $this->language = 'kr';
        $this->recordsTotal = 0;
        $this->recordsFiltered = 0;
        $this->columns = [];
        $this->customSearch = [];
        $this->layoutCustomSearch = [];
        $this->layoutList = [];

        $this->exportActionPrint    = "sfdtExportAction";
        $this->exportActionPDF      = "sfdtExportAction";
        $this->exportActionExcel    = "sfdtExportAction";

        $this->exportTitlePrint     = null;
        $this->exportTitlePDF       = null;
        $this->exportTitleExcel     = null;

        $this->exportFilenamePDF    = "export-data.pdf";
        $this->exportFilenameExcel  = "export-data.xlsx";

        $this->extraButtons = [];
    }

    public function containerOption($option) { $this->containerOption = $option; }

    /*
     * table options
     */
    public function options($options) { $this->options = array_merge($this->options, $options); }
    public function disableColReorder() { $this->options["colReorder"] = false; }

    public function english() { $this->language = 'en'; }

    /*
     * export option
     */
    public function exportTitle($title) { $this->exportTitlePrint = $this->exportTitlePDF = $title; }
    public function exportFilename($filename) { $this->exportFilenamePDF = $this->exportFilenameExcel = $filename; }
    public function exportAction($action) { $this->exportActionPrint = $this->exportActionPDF = $this->exportActionExcel = $action; }

    public function exportPrintAction($action) { $this->exportActionPrint = $action; }
    public function exportPrintTitle($title) { $this->exportTitlePrint = $title; }

    public function exportPDFAction($action) { $this->exportActionPDF = $action; }
    public function exportPDFTitle($title) { $this->exportTitlePDF = $title; }
    public function exportPDFFilename($filename) { $this->exportFilenamePDF = $filename; }

    public function exportExcelAction($action) { $this->exportActionExcel = $action; }
    public function exportExcelTitle($title) { $this->exportTitleExcel = $title; }
    public function exportExcelFilename($filename) { $this->exportFilenameExcel = $filename; }

    /*
     * extra buttons
     */
    public function extraButton($btnId, $text, $class = "", $option = null) {
        $this->extraButtons[$btnId] = [
            "text" => $text,
            "class" => $class,
            "option" => $option
        ];
    }

    /*
     * column options
     */
    public function column($alias)
    {
        if (!isset($this->columns[$alias])) $this->columns[$alias] = new DataTablesColumn($alias);
        return $this->columns[$alias];
    }

    // custom search, $aliases : array of column alias
    public function customSearch($alias) {
        if (!isset($this->customSearch[$alias])) $this->customSearch[$alias] = new DataTablesCustomSearch($alias);
        return $this->customSearch[$alias];
    }

    // layout for custom search, $layout : array of column alias
    public function layoutCustomSearch($layout) { $this->layoutCustomSearch = $layout; }

    // columns order for list, $layout : array of column alias
    public function layoutList($layout) { $this->layoutList = $layout; }



    public function recordsTotal()
    {
    }

    public function recordsFiltered()
    {
    }

    // Create a script/html that should be output only once even if the instance is created multiple times
    private function once()
    {
        $script = "";
        $html = "";

        if (self::$checkOnce) return [ "script" => $script, "html" => $html ];
        self::$checkOnce = true;

        $script .= <<<EOD
            $.fn.dataTable.ext.errMode = 'throw';
            let sfdt = {};  // DataTables Object

            EOD;

        $html .= <<<EOD

            <!-- DataTables Column Config Modal -->
            <div class="modal fade" tabindex="-1" id="sfdt-modal-column-config">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-body" id="sfdt-modal-column-config-body"></div>
                        <div class="modal-footer d-flex justify-content-between">
                            <div><button type="button" class="btn btn-reset sfdt-btn-column-config-reset">[:dt:Reset:]</button></div>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">[:dt:Cancel:]</button>
                                <button type="button" class="btn btn-primary" id="sfdt-btn-column-config-apply" disabled>[:dt:Apply:]</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            EOD;


        return [ "script" => $script, "html" => $html ];
    }

    private function makeCodeAjax()
    {
        $router = Router::getInstance();
        //$url = $router->module() . "/" . $router->fnc();

        $scriptCustomExSearch = "";
        foreach($this->customSearch as $alias => $cs) {
            if ($cs->isEx()) {
                $scriptCustomExSearch .= <<<EOD

                    data.customSearchEx_{$alias} = $("#sfdt-{$this->tableId}-custom-search-{$alias}").val();
                    EOD;
            }
        }

        return <<<EOD

                    function(data, callback, settings) {
                        {$scriptCustomExSearch}
                        $.ajax({
                            url: "/datatables/custom-data",
                            type: "POST",
                            data: data,
                        }).done(function (json, textStatus, jqXHR) {
                            console.log("call ajax");
                            try {
                                if (typeof json !== 'object') throw "json is not object";
                                if (!('error' in json) || !('errCode' in json.error) || !('errMsg' in json.error)) throw "error object is not exist";
                                if (json.error.errCode !== 0) {
                                    if ('errUrl' in json.error && json.error.errUrl) {
                                        alertJump(json.error.errMsg, json.error.errUrl);
                                    } else {
                                        alert(json.error.errMsg);
                                    }
                                    //console.log(json);
                                    callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                                    return;
                                }
                                if (!('data' in json) || !('draw' in json.data) || !('recordsTotal' in json.data) || !('recordsFiltered' in json.data) || !('data' in json.data)) throw "data object is not exist";
                                callback(json.data);
                            } catch (e) {
                                alert("[:Oops!! An error has occurred.<br>Engineers comparable to advanced AI are working hard to fix it.<br>We won't let you down!!:]");
                                console.log("ajax page returns data in wrong:", e);
                                console.log("json:", json);
                                console.log("textStatus:", textStatus)
                                console.log("jqXHR:", jqXHR);
                                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            alert("[:Oops!! An error has occurred.<br>Engineers comparable to advanced AI are working hard to fix it.<br>We won't let you down!!:]");
                            console.log("ajax fail");
                            console.log("textStatus:", textStatus);
                            console.log("jqXHR:", jqXHR);
                            console.log("errorThrown:", errorThrown);
                            callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                        });
                    }
            EOD;
    }

    private function makeCodeColumns()
    {
        $code = [];
        $render = [];
        foreach($this->columns as $alias => $column) {
            $c = [];

            if ($column->data())    $c["data"] = $column->data();
            if ($column->title())   $c["title"] = $column->title();
            if ($column->class())   $c["className"] = implode(" ", $column->class());
            if ($column->type())    $c["type"] = $column->type();
            $c["searchable"] = $column->searchable();
            $c["orderable"] = $column->orderable();
            if ($column->render()) {
                $c["render"] = "rendering-{$alias}";
                $render[$alias] = trim($column->render());
            }

            $code[$alias] = $c;
        }

        if (!$this->layoutList) $this->layoutList = array_keys($this->columns);
        $list = [];
        foreach($this->layoutList as $alias) {
            if (isset($code[$alias])) $list[] = $code[$alias];
        }
        return [ "list" => $list, "render" => $render ];
    }

    private function makeCodeLayout()
    {
        $ebArr = [];
        if ($this->extraButtons) {
            foreach($this->extraButtons as $btnId => $btn) {
                $option = "";
                if ($btn['option']) $option = " {$btn['option']}";
                $ebArr[] = "<button type=\"button\" id=\"{$btnId}\" class=\"btn btn-sm {$btn['class']}\"{$option}>{$btn['text']}</button>";
            }
        }
        if ($ebArr) $extraButtons = "div: { html:`" . implode(" ", $ebArr) . "` },";
        else $extraButtons = "";

        return <<<EOD

                    {
                        topStart: [ 'search' ],
                        topEnd: {
                            buttons: [
                                {
                                    extend: 'print',
                                    title : '{$this->exportTitlePrint}',
                                    action: {$this->exportActionPrint},
                                    exportOptions: { columns: ':visible:not(.sfdt-no-export)' },
                                    customize:
                                        function (win) {
                                            $(win.document.body).find('h1').each(function() {
                                                $(this).replaceWith('<h3 class="text-center mb-3">' + $(this).html() + '</h3>');
                                            });
                                            $(win.document.body).find('table').addClass('compact').css('font-size', '9pt').css('text-align', 'center');
                                            $(win.document.body).find('table').find('thead').find('th').css('text-align', 'center');
                                        }
                                }, {
                                    extend: 'pdf',
                                    filename: '{$this->exportFilenamePDF}',
                                    action: {$this->exportActionPDF},
                                    title: '{$this->exportTitlePDF}',
                                    exportOptions: { columns: ':visible:not(.sfdt-no-export)' },
                                    customize:
                                        function(doc) {
                                            doc.defaultStyle.font = 'hangul';
                                            doc.defaultStyle.fontSize = 9;
                                            doc.defaultStyle.alignment = 'center';
                                            doc.styles.tableHeader = { alignment: 'center', bold: true, fontSize: 9, noWrap: true };

                                            let tblIdx = 0;
                                            for(let i=0;i<doc.content.length;i++) {
                                                if (doc.content[i].table) { tblIdx = i; break; }
                                            }
                                            if ($(doc.content[tblIdx].table.body[0]).length > 7) doc.pageOrientation = 'landscape';
                                            let mr = doc.content[tblIdx].table.body.length-1; if (mr > 20) mr = 20;
                                            let colSum = {}, colCnt = {};
                                            for(let j=0;j<doc.content[tblIdx].table.body[0].length;j++) { colSum[j] = 0; colCnt[j] = 0; }
                                            for(let i=1;i<=mr;i++) {
                                                for(let j=0;j<doc.content[tblIdx].table.body[i].length;j++) {
                                                    if (doc.content[tblIdx].table.body[i][j].text.bytes() > 0) {       // String.bytes() : see sfutil.js
                                                        colCnt[j]++;
                                                        colSum[j] += doc.content[tblIdx].table.body[i][j].text.bytes();
                                                    }
                                                }
                                            }
                                            let avgSum = 0, colAvg = {};
                                            for(let j=0;j<doc.content[tblIdx].table.body[0].length;j++) { colAvg[j] = colSum[j] / colCnt[j]; avgSum += colAvg[j]; }
                                            let widths = [];
                                            for(let j=0;j<doc.content[tblIdx].table.body[0].length;j++) {
                                                widths.push(((colAvg[j] / avgSum) * 100) + "%");
                                            }
                                            doc.content[tblIdx].table.widths = widths;
                                        }
                                }, {
                                    extend: 'excel',
                                    filename: '{$this->exportFilenameExcel}',
                                    action: {$this->exportActionExcel},
                                    title: '{$this->exportTitleExcel}',
                                    exportOptions: { columns: ':visible:not(.sfdt-no-export)' }
                                }, {
                                    text: '[:dt:Columns:]',
                                    className:'sfdt-btn-open-column-config',
                                    attr: { 'data-table-id': '{$this->tableId}' }
                                }
                            ],
                            {$extraButtons}
                        },
                        bottomEnd: [
                            'pageLength',
                            function() {
                                return `
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="sfdt-{$this->tableId}-page-jump" class="form-control form-control-sm sfdt-page-jump" min="1" data-table-id="{$this->tableId}">
                                        <div class="input-group-append"><button type="button" class="btn btn-sm sfdt-btn-pagejump">이동</button></div>
                                    </div>
                                `;
                            },
                            'paging'
                        ],
                    }
            EOD;
    }

    private function makeCodeCustomSearchItem($alias)
    {
        $cs = $this->customSearch[$alias];
        $controlOption = $cs->controlOption();  if ($controlOption) $controlOption = " {$controlOption}";

        $forEx = "";
        $title = $cs->title();
        $sfdtData = "";
        if ($cs->isEx()) {
            $forEx = " data-sfdt-custom-search-ex='true'";
        } else {
            if (!$title) $title = $this->columns[$alias]->title();
            $sfdtData = " data-sfdt-data=\"{$this->columns[$alias]->data()}\"";
        }

        switch($cs->type()) {
            case DataTablesCustomSearch::TYPE_STRING :
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="string"{$sfdtData}{$forEx} autocomplete="off"{$controlOption}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_SELECT :
                $data = $cs->data();
                if ($cs->isSelect2() || count($data) > 10) $controlOption .= " data-sfselect2='true'";
                $option = "";
                foreach($data as $key => $value) {
                    $option .= "<option value=\"{$key}\">{$value}</option>";
                }
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <select class="form-control form-control-sm" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}"{$sfdtData}{$forEx}{$controlOption}>
                                        <option value="">[:dtcustomsearch:All:]</option>
                                        {$option}
                                    </select>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_DATERANGE :
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="daterange"{$sfdtData} autocomplete="off"{$forEx}{$controlOption}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_DATETIMERANGE :
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="datetimerange"{$sfdtData} autocomplete="off"{$forEx}{$controlOption}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_NUMBERRANGE :
                $numberRangeOption = $cs->numberRangeOption();
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <div class="input-group input-group-sm">
                                        <input type="search" class="form-control" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="numberrange"{$sfdtData} data-sfdt-numberrange-min="{$numberRangeOption['min']}" data-sfdt-numberrange-max="{$numberRangeOption['max']}"{$forEx} autocomplete="off"{$controlOption}>
                                    </div>
                                </div>

                    EOD;
        }
    }

    private function makeCodeCustomSearch()
    {
        if (!$this->customSearch) return "";

        if (!$this->layoutCustomSearch) $this->layoutCustomSearch = array_keys($this->customSearch);

        $html = "";
        $noGroup = "";
        foreach($this->layoutCustomSearch as $arr) {
            if (is_array($arr)) {
                if ($noGroup) {
                    $html .= <<<EOD

                                <div class="sfdt-custom-search-group">
                                    {$noGroup}
                                </div>

                        EOD;
                    $noGroup = "";
                }

                $item = "";
                foreach($arr as $alias) {
                    $item .= $this->makeCodeCustomSearchItem($alias);
                }
                $html .= <<<EOD

                            <div class="sfdt-custom-search-group">
                                {$item}
                            </div>

                    EOD;
            } else {
                $noGroup .= $this->makeCodeCustomSearchItem($arr);
            }
        }

        if ($noGroup) {
            $html .= <<<EOD

                    <div class="sfdt-custom-search-group">
                        {$noGroup}
                    </div>

                EOD;
            $noGroup = "";
        }

        $html = <<<EOD

                <!-- shakeFlat DataTables Custom Search -->
                <div class="sfdt-custom-search mb-3" data-table-id="{$this->tableId}" data-language="{$this->language}">
                    {$html}
                </div>

            EOD;
        return $html;
    }

    // build html, js
    public function build()
    {
        if (!array_key_exists("keys", $this->options)) {
            foreach($this->columns as $alias => $column) {
                if (!$column->data()) $column->class("no-keys-cursor");
            }
            $this->options["keys"] = [ "blurable" => false, "columns" => ':not(.no-keys-cursor)' ];
        }
        $options = $this->options;

        $options["ajax"] = "ajax-function";
        $options["layout"] = "layout-code";
        $codeColumns = $this->makeCodeColumns();
        $options["columns"] = $codeColumns["list"];
        if ($this->language == "kr") $options["language"] = [ "url" => "/assets/libs/datatables-2.1.8/i18n/ko.json" ];
        $options["drawCallback"] = "drawCallback-function";
        $optionsStr = json_encode($options, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

        $replaceFrom = [];
        $replaceTo = [];
        $replaceFrom[] = "\"ajax\": \"ajax-function\"";     $replaceTo[] = "\"ajax\": {$this->makeCodeAjax()}";
        $replaceFrom[] = "\"layout\": \"layout-code\"";     $replaceTo[] = "\"layout\": {$this->makeCodeLayout()}";

        $replaceFrom[] = "\"drawCallback\": \"drawCallback-function\"";
        $replaceTo[] = <<<EOD

                "drawCallback": function(settings) {
                    var state = sfdt['{$this->tableId}'].state();
                    state.columns.forEach(function(col, index) {
                        if (col.search.search) sfdtSetSearchDefaultValue('{$this->tableId}', index, col.search.search);
                    });
                }
            EOD;
        if ($codeColumns["render"]) {
            foreach($codeColumns["render"] as $alias => $render) {
                $replaceFrom[] = "\"rendering-{$alias}\"";
                $replaceTo[] = $render;
            }
        }
        $optionsStr = str_replace($replaceFrom, $replaceTo, $optionsStr);

        $htmlCustomSearch = $this->makeCodeCustomSearch();
        $once = $this->once();

        $containerOption = $this->containerOption;
        if ($containerOption) $containerOption = " {$containerOption}";

        $this->html = <<<EOD
            {$once["html"]}

            <div{$containerOption}>
                {$htmlCustomSearch}
                <table id="{$this->tableId}" class="table table-hover table-striped text-nowrap"></table>
            </div>

            <script>
            {$once["script"]}
            $(document).ready(function() {
                sfdt['{$this->tableId}'] = new DataTable('#{$this->tableId}', {$optionsStr});
            });
            </script>

            EOD;
    }

    public function echoHtml() {
        echo $this->translationOutput($this->html, $this->language);
    }

    private function translationOutput($output, $lang)
    {
        $translation = Translation::getInstance();
        if ($lang) {
            if (is_array($output)) {
                $output = json_decode($translation->convert(json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $lang), true);
            } else {
                $output = $translation->convert($output, $lang);
            }
            $translation->updateCache($lang);
            return $output;
        }
        if (is_array($output)) {
            return json_decode($translation->passing(json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), true);
        } else {
            return $translation->passing($output);
        }
    }
}