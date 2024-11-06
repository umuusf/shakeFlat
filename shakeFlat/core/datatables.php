<?php
namespace shakeFlat;
use shakeFlat\L;
use shakeFlat\Translation;
use shakeFlat\Param;

class DataTablesColumn
{
    private $alias;
    private $data;
    private $query;         // for database field name (if different from alias)
                            // select {$columnQuery} as {$alias} from table...
    private $title;
    private $class;
    private $render;
    private $type;          // for datatables column type
    private $displayType;   // for detail view display type
    private $searchable;
    private $orderable;
    private $buttons;

    private $detailButtonInfo;

    public function __construct($alias)
    {
        $this->alias = $alias;
        $this->data = $alias;
        $this->query = $alias;

        $this->title = "";
        $this->class = [];
        $this->render = "";
        $this->type = "";
        $this->displayType = "string";
        $this->searchable = true;
        $this->orderable = true;
        $this->buttons = [];

        $this->detailButtonInfo = [];
    }

    public function alias()
    {
        return $this->alias;
    }

    public function data($data = null)
    {
        if ($data === null) return $this->data;
        $this->data = $data;
        return $this;
    }

    public function query($query = null)
    {
        if ($query === null) return $this->query;
        $this->query = $query;
        return $this;
    }

    public function title($title = null)
    {
        if ($title === null) return $this->title;
        $this->title = $title;
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

    public function displayType($displayType = null)
    {
        if ($displayType === null) return $this->displayType;
        $this->displayType = $displayType;
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
        $this->title = "";
        $this->type = "html";
        $this->render = $render;
        $this->searchable = false;
        return $this;
    }

    public function date($format = "YYYY-MM-DD")
    {
        $this->type = "string";
        $this->displayType = "datetime:{$format}";
        $this->class("text-center");
        $this->render = "DataTable.render.date('" . $format . "')";
        return $this;
    }

    public function datetime($format = "YYYY-MM-DD HH:mm:ss")
    {
        $this->type = "string";
        $this->displayType = "datetime:{$format}";
        $this->class("text-center");
        $this->render = "DataTable.render.datetime('" . $format . "')";
        return $this;
    }

    // $thousands: thousands separator, $decimals: decimal point, $precision: decimal point separator, $prefix: prefix, $postfix: postfix
    public function number($thousands = ',', $decimals = null, $precision = null, $prefix = null, $postfix = null)
    {
        $this->type = "num";
        $this->displayType = "number";
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
    public function noWrap() { return $this->class("text-nowrap"); }

    public function disableInvisible() { return $this->class("sfdt-disable-invisible"); }
    public function noExport() { return $this->class("sfdt-no-export"); }
    public function noData() { $this->searchable = false; $this->orderable = false; return $this; }

    public function detailButtonInfo() { return $this->detailButtonInfo; }

    public function renderDetailButton($paramAliaes, $id = 'detail', $url = null, $text = "[:dtdetail:View:]", $class = "btn-detail") {
        if (!is_array($paramAliaes)) $paramAliaes = [ $paramAliaes ];
        $this->detailButtonInfo[$id] = [ 'param' => $paramAliaes, 'url' => $url ];
        $options = [];
        $options[] = "data-detail-id='{$id}'";
        foreach($paramAliaes as $alias) {
            $options[] = "data-{$alias}='\${row.{$alias}}'";
        }
        return $this->renderButton($text, $class, implode(" ", $options));
    }

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

                                    <button type="button" class="btn btn-xs{$class}"{$option}>{$btn['text']}</button>
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
    private $options;
    private $numberRangeOption;
    private $select2;
    private $exColumnQuery;     // for database field name for search(where statement)

    public function __construct($alias)
    {
        $this->alias = $alias;
        $this->title = "";
        $this->type = self::TYPE_STRING;
        $this->controlOption = "";
        $this->numberRangeOption = [ "min" => 0, "max" => 100 ];
        $this->options = [];
        $this->select2 = false;
        $this->exColumnQuery = "";
    }

    public function alias() { return $this->alias; }
    public function ex($exColumnQuery = null)
    {
        if ($exColumnQuery === null) $this->exColumnQuery = $this->alias;
        else $this->exColumnQuery = $exColumnQuery;
        return $this;
    }
    public function exColumnQuery() { return $this->exColumnQuery; }

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

    public function widthPx($px) { $this->controlOption .= " style='width:{$px}px;'"; return $this; }
    public function widthRem($rem) { $this->controlOption .= " style='width:{$rem}rem;'"; return $this; }
    public function widthEm($em) { $this->controlOption .= " style='width:{$em}em;'"; return $this; }

    public function option($key = null, $value = null)
    {
        if ($key === null && $value === null) return $this->options;

        if (is_array($key) && $value === null) {
            $this->options = array_merge($this->options, $key);
        } else {
            $this->options[$key] = $value;
        }
        return $this;
    }

    public function options($key = null, $value = null)
    {
        if ($key === null && $value === null) return $this->options;

        if (is_array($key) && $value === null) {
            $this->options = array_merge($this->options, $key);
        } else {
            $this->options[$key] = $value;
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
            "option"            => $this->options,
            "numberRangeOption" => $this->numberRangeOption,
            "select2"           => $this->select2
        ];
    }
}

class DataTablesExtraButton
{
    private $btnId;
    private $title;
    private $class;
    private $option;
    private $action;
    private $tooltip;

    public function __construct($btnId)
    {
        $this->btnId = $btnId;
        $this->title = $btnId;
        $this->class = [];
        $this->action = "";
        $this->option = [];
        $this->tooltip = "";
    }

    public function btnId() { return $this->btnId; }

    public function title($title = null)
    {
        if ($title === null) return $this->title;
        $this->title = $title;
        return $this;
    }

    public function class($class = null)
    {
        if ($class === null) return $this->class;
        if (!in_array($class, $this->class)) $this->class[] = $class;
        return $this;
    }

    public function action($action = null)
    {
        if ($action === null) return $this->action;
        $this->action = $action;
        return $this;
    }

    public function option($option = null)
    {
        if ($option === null) return $this->option;
        if (!in_array($option, $this->option)) $this->option[] = $option;
        return $this;
    }

    public function tooltip($tooltip = null)
    {
        if ($tooltip === null) return $this->tooltip;
        $this->tooltip = $tooltip;
        return $this;
    }

    public function html()
    {
        $class = implode(" ", $this->class);
        $option = implode(" ", $this->option);
        return "<button type=\"button\" id=\"{$this->btnId}\" class=\"btn btn-sm {$class}\"{$option}>{$this->title}</button>";
    }

    public function tooltipScript()
    {
        if (!$this->tooltip()) return "";
        $tp = str_replace('"', '\"', $this->tooltip());
        return <<<EOD

                $('#{$this->btnId}').data("bs-toggle", "tooltip").attr("title", "{$tp}").tooltip();
        EOD;
    }
}

class DataTables
{
    private static $checkOnceScript = false;
    private static $checkOnceHtmlColReorder = false;

    private $tableId;
    private $containerOption;
    private $disableTooltip;

    private $options;
    private $language;
    private $ajaxUrl;

    private $exportActionPrint;
    private $exportActionPDF;
    private $exportActionExcel;
    private $exportTitlePrint;
    private $exportTitlePDF;
    private $exportTitleExcel;
    private $exportFilenamePDF;
    private $exportFilenameExcel;

    private $extraButtons;

    private $columns;
    private $customSearch;
    private $defaultOrder;

    private $layoutCustomSearch;
    private $layoutList;
    private $layoutDetail;

    private $onePage;
    private $querySearchSQL;
    private $querySearchBind;

    // output for template
    private $html;

    public function __construct($tableId)
    {
        $chk = preg_match("/^[a-z][a-z0-9]*$/", $tableId);
        if (!$chk) L::system("[:dt:It must be made only of alphabet(lower case) and numbers (the first letter is an alphabet).:]");

        $this->tableId = $tableId;
        $this->containerOption = "";
        $this->disableTooltip = false;

        $this->options = [
            "stateSave"  => true,
            "pageLength" => 20,
            "lengthMenu" => [10, 20, 25, 30, 50, 75, 100],
            "paging"     => true,
            "ordering"   => true,
            "colReorder" => true,
            "responsive" => false,
            "scrollX"    => true,
            "retrieve"   => true,
            "serverSide" => true,
        ];

        $this->language = 'kr';
        $this->ajaxUrl = $_SERVER['REQUEST_URI'];
        $this->columns = [];
        $this->customSearch = [];
        $this->defaultOrder = [];

        $this->layoutCustomSearch = [];
        $this->layoutList = [];
        $this->layoutDetail = [];

        $this->exportActionPrint    = "sfdtExportAction";
        $this->exportActionPDF      = "sfdtExportAction";
        $this->exportActionExcel    = "sfdtExportAction";

        $this->exportTitlePrint     = null;
        $this->exportTitlePDF       = null;
        $this->exportTitleExcel     = null;

        $this->exportFilenamePDF    = "export-data.pdf";
        $this->exportFilenameExcel  = "export-data.xlsx";

        $this->extraButtons = [];

        $this->onePage = false;
        $this->querySearchSQL = "";
        $this->querySearchBind = null;
    }

    public function containerOption($option) { $this->containerOption = $option; return $this; }

    /*
     * table options
     */
    public function options($options) { $this->options = array_merge($this->options, $options); return $this; }
    public function pageLength($length) { $this->options["pageLength"] = intval($length); return $this; }
    public function lengthMenu($menu) { $this->options["lengthMenu"] = $menu; return $this; }
    public function disableStateSave() { $this->options["stateSave"] = false; return $this; }
    public function disableColReorder() { $this->options["colReorder"] = false; return $this; }
    public function english() { $this->language = 'en'; return $this; }
    public function ajaxUrl($url) { $this->ajaxUrl = $url; return $this; }
    public function disableTooltip() { $this->disableTooltip = true; return $this; }
    public function orderBy($alias, $dir) { $this->defaultOrder[] = [ "alias" => $alias, "dir" => $dir ]; return $this; }
    public function disableOrdering() { $this->options["ordering"] = false; return $this; }

    /*
     * export option
     */
    public function exportTitle($title) { $this->exportTitlePrint = $this->exportTitlePDF = $title; return $this; }
    public function exportFilename($filename) { $this->exportFilenamePDF = $this->exportFilenameExcel = $filename; return $this; }
    public function exportAction($action) { $this->exportActionPrint = $this->exportActionPDF = $this->exportActionExcel = $action; return $this; }

    public function exportPrintAction($action) { $this->exportActionPrint = $action; return $this; }
    public function exportPrintTitle($title) { $this->exportTitlePrint = $title; return $this; }

    public function exportPDFAction($action) { $this->exportActionPDF = $action; return $this; }
    public function exportPDFTitle($title) { $this->exportTitlePDF = $title; return $this; }
    public function exportPDFFilename($filename) { $this->exportFilenamePDF = $filename; return $this; }

    public function exportExcelAction($action) { $this->exportActionExcel = $action; return $this; }
    public function exportExcelTitle($title) { $this->exportTitleExcel = $title; return $this; }
    public function exportExcelFilename($filename) { $this->exportFilenameExcel = $filename; return $this; }

    /*
     * extra buttons
     */
    public function extraButton($btnId)
    {
        $this->extraButtons[$btnId] = new DataTablesExtraButton($btnId);
        return $this->extraButtons[$btnId];
    }

    /*
     * one page
     */
    public function onePage() { $this->onePage = true; return $this; }

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
    public function layoutCustomSearch($layout) { $this->layoutCustomSearch = $layout; return $this; }

    // columns order for list, $layout : array of column alias
    public function layoutList($layout) { $this->layoutList = $layout; return $this; }

    // columns order for detail, $layout : array of column alias
    public function layoutDetail($layout, $id = 'detail') { $this->layoutDetail[$id] = $layout; return $this; }


    // Create a script/html that should be output only once even if the instance is created multiple times
    private function onceOutput()
    {
        $script = "";
        $html = "";

        if (!self::$checkOnceScript) {
            self::$checkOnceScript = true;

            $script .= <<<EOD
                $.fn.dataTable.ext.errMode = 'throw';
                let sfdt = {};  // DataTables Object

                EOD;
        }

        if (!self::$checkOnceHtmlColReorder && $this->options["colReorder"]) {
            self::$checkOnceHtmlColReorder = true;
            if (!$this->disableTooltip) $tooltip = ` data-bs-toggle="tooltip" title="[:dt:Restore the initial state of column order and visibility.:]"`; else $tooltip = "";

            $html .= <<<EOD

                <!-- DataTables Column Config Modal -->
                <div class="modal fade" tabindex="-1" id="sfdt-modal-column-config">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-body" id="sfdt-modal-column-config-body"></div>
                            <div class="modal-footer d-flex justify-content-between">
                                <div><button type="button" class="btn btn-sm btn-reset sfdt-btn-column-config-reset"{$tooltip}>[:dt:Reset:]</button></div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">[:dt:Cancel:]</button>
                                    <button type="button" class="btn btn-sm btn-primary" id="sfdt-btn-column-config-apply" disabled>[:dt:Apply:]</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                EOD;
        }

        return [ "script" => $script, "html" => $html ];
    }

    private function makeCodeAjax()
    {
        $scriptCustomExSearch = "";
        if ($this->customSearch) {
            $scriptEx = [];
            foreach($this->customSearch as $alias => $cs) {
                if ($cs->exColumnQuery()) {
                    $scriptEx[] = <<<EOD
                        csEx['{$alias}'] = $('#sfdt-{$this->tableId}-custom-search-{$alias}').val();
                        EOD;
                }
            }
            if ($scriptEx) {
                $exstr = implode("\n            ", $scriptEx);
                $scriptCustomExSearch =<<<EOD

                                let csEx = {};
                                {$exstr}
                                data.customSearchEx = csEx;
                                localStorage.setItem('sfdt-{$this->tableId}-custom-search-ex', JSON.stringify(csEx));

                    EOD;
            }
        }

        $url = $this->ajaxUrl;
        if ($this->onePage) {
            $url = $_SERVER['REQUEST_URI'];
            $scriptCustomExSearch .= <<<EOD

                                data.sfdtPageMode = 'data';

                    EOD;
        }

        return <<<EOD

                    function(data, callback, settings) {
                        {$scriptCustomExSearch}
                        $.ajax({
                            url: '{$url}',
                            type: 'POST',
                            data: data,
                        }).done(function (json, textStatus, jqXHR) {
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
                                alert("[:dt:The server responded incorrectly. Please try again later.:]");
                                console.log("ajax page returns data in wrong:", e);
                                console.log("json:", json);
                                console.log("textStatus:", textStatus)
                                console.log("jqXHR:", jqXHR);
                                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            alert("[:dt:Communication with the server is not smooth. Please try again later.:]");
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
            $c = [ "name" => $alias ];
            if ($column->query())   $c["data"] = $column->data();
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
        $colReorder = "";
        $ebArr = [];
        $extraButtons = "";

        if ($this->options["colReorder"]) {
            $colReorder = <<<EOD
                    ,{
                                            text: '[:dt:Columns:]',
                                            className:'sfdt-btn-open-column-config',
                                            attr: { 'data-table-id': '{$this->tableId}' }
                                        }
                    EOD;
        }

        if ($this->extraButtons) {
            foreach($this->extraButtons as $btnId => $eb) $ebArr[] = $eb->html();
        }

        if ($ebArr) $extraButtons = "div: { html:`" . implode(" ", $ebArr) . "` },";

        return <<<EOD

                    {
                        topStart: [ 'search', function() { return '<button type="button" class="btn btn-sfdt-search-reset" data-table-id="{$this->tableId}"><i class="bi bi-arrow-clockwise"></i></button>'; } ],
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
                                }{$colReorder}
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
        if (!array_key_exists($alias, $this->customSearch)) L::system("[:dt:Custom search item({$alias}) not found.:]");
        $cs = $this->customSearch[$alias];
        $controlOption = $cs->controlOption();  if ($controlOption) $controlOption = " {$controlOption}";

        $forEx = "";
        $title = $cs->title();
        $sfdtDataAlias = "";
        if ($cs->exColumnQuery()) {
            $forEx = " data-sfdt-custom-search-ex='true'";
        } else {
            if (!array_key_exists($alias, $this->columns)) L::system("[:dt:Column({$alias}) not found.:]");
            if (!$title) $title = $this->columns[$alias]->title();
            $sfdtDataAlias = " data-sfdt-alias=\"{$alias}\"";
        }

        switch($cs->type()) {
            case DataTablesCustomSearch::TYPE_STRING :
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="string"{$sfdtDataAlias}{$forEx} autocomplete="off"{$controlOption}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_SELECT :
                $option = $cs->option();
                if ($cs->isSelect2() || count($option) > 10) $controlOption .= " data-sfselect2='true'";
                $optionStr = "";
                foreach($option as $key => $value) {
                    $optionStr .= "<option value=\"{$key}\">{$value}</option>";
                }
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <select class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}"{$sfdtDataAlias}{$forEx}{$controlOption}>
                                        <option value="">[:dtcustomsearch:All:]</option>
                                        {$optionStr}
                                    </select>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_DATERANGE :
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="daterange"{$sfdtDataAlias} autocomplete="off"{$forEx}{$controlOption}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_DATETIMERANGE :
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="datetimerange"{$sfdtDataAlias} autocomplete="off"{$forEx}{$controlOption}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_NUMBERRANGE :
                $numberRangeOption = $cs->numberRangeOption();
                return <<<EOD

                                <div class="sfdt-custom-search-item">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <div class="input-group input-group-sm">
                                        <input type="search" class="form-control sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="numberrange"{$sfdtDataAlias} data-sfdt-numberrange-min="{$numberRangeOption['min']}" data-sfdt-numberrange-max="{$numberRangeOption['max']}"{$forEx} autocomplete="off"{$controlOption}>
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

    private function makeCodeDetailsView()
    {
        // detail view button
        $detailScript = "";
        $detailHtml = "";
        $formatScript = "";
        foreach($this->columns as $alias => $column) {
            if ($column->displayType() == "number") {
                $formatScript .= "                else if (alias == '{$alias}') txt = txt.numberFormat();\n";
            } elseif (substr($column->displayType(), 0, 5) == "date:") {
                $format = substr($column->displayType(), 5);
                $formatScript .= "                else if (alias == '{$alias}') txt = txt.formatDateTime('{$format}');\n";
            } elseif (substr($column->displayType(), 0, 9) == "datetime:") {
                $format = substr($column->displayType(), 9);
                $formatScript .= "                else if (alias == '{$alias}') txt = txt.formatDateTime('{$format}');\n";
            }
        }
        $formatScript = trim($formatScript, "else ");
        foreach($this->columns as $alias => $column) {
            $detailButtons = $column->detailButtonInfo();
            if (!$detailButtons) continue;
            foreach($detailButtons as $id => $dbInfo) {
                if (!($this->layoutDetail[$id] ?? false)) L::system("[:dt:Layout for detail view({$id}) is not defined.:]");
                if (!($dbInfo['param'] ?? false)) L::system("[:dt:Parameter for detail view({$id}) is not defined.:]");

                $param = "";
                foreach($dbInfo['param'] as $alias) {
                    $param .= "param.{$alias} = $(this).data('{$alias}');\n    ";
                }
                $url = $dbInfo['url'];
                if ($url === null) {
                    $url = $this->ajaxUrl;
                    $param .= "param.sfdtPageMode = 'detail';\n";
                }
                $detailScript .= <<<EOD

                    $(document).on("click", ".btn-detail[data-detail-id='{$id}']", function() {
                        $("#sfdt-modal-{$this->tableId}-detail-view").find("input").val("");
                        let param = {};
                        {$param}
                        callAjax(
                            '{$url}',
                            param,
                            function(result) {
                                if (!result.data.detailData || typeof result.data.detailData !== 'object') {
                                    alert("[:dt:detailData does not exist in the result from {$url}:]");
                                    return;
                                }
                                for(let alias in result.data.detailData) {
                                    let txt = result.data.detailData[alias];
                                    {$formatScript}
                                    $("#sfdt-modal-{$this->tableId}-{$id}-detail-column-" + alias).html(txt);
                                }
                                $("#sfdt-modal-{$this->tableId}-detail-view").modal("show");
                            }
                        );
                    });

                    EOD;

                $layoutHtml = "";
                $htmlItem = function($alias) use ($id) {
                    return <<<EOD
                                            <div class="col-auto">
                                                <div class="form-floating">
                                                    <div class="form-control-plaintext text-nowrap" id="sfdt-modal-{$this->tableId}-{$id}-detail-column-{$alias}"></div>
                                                    <label for="sfdt-detail-column-{$this->tableId}-{$alias}" class="text-nowrap">{$this->columns[$alias]->title()}</label>
                                                </div>
                                            </div>

                        EOD;
                };

                $layout = $this->layoutDetail[$id];
                foreach($layout as $item) {
                    if ($item === '---') {
                        $layoutHtml .= <<<EOD

                                        <hr>
                        EOD;
                        continue;
                    }
                    $layoutHtml .= <<<EOD

                                        <div class="row">

                        EOD;
                    if (is_array($item)) foreach($item as $alias) $layoutHtml .= $htmlItem($alias);
                    else $layoutHtml .= $htmlItem($item);

                    $layoutHtml .= <<<EOD
                                        </div>
                        EOD;
                }

                if (!$detailHtml) {
                    $detailHtml = <<<EOD
                        <!-- DataTable - Detail view modal for Table Id {$this->tableId} -->
                        <div class="modal fade" tabindex="-1" id="sfdt-modal-{$this->tableId}-detail-view">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header bg-detail">
                                        <h5 class="modal-title">[:dtdetail:Detail View:]</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        {$layoutHtml}

                                    </div>
                                    <div class="modal-footer d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">[:dt:Close:]</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        EOD;
                }
            }
        }

        return [ "html" => $detailHtml, "script" => $detailScript ];
    }

    // build html, js
    public function build()
    {
        if ($this->onePage) {
            $param = Param::getInstance();
            if ($param->sfdtPageMode === "data") return $this->opAjax();
            if ($param->sfdtPageMode === "detail") return $this->opDetail();
        }

        if (!array_key_exists("keys", $this->options)) {
            foreach($this->columns as $alias => $column) $column->class("no-keys-cursor");
            $this->options["keys"] = [ "blurable" => false, "columns" => ':not(.no-keys-cursor)' ];
        }
        $options = $this->options;

        $options["ajax"] = "ajax-function";
        $options["layout"] = "layout-code";
        $codeColumns = $this->makeCodeColumns();
        $options["columns"] = $codeColumns["list"];
        if ($this->defaultOrder) {
            foreach($this->defaultOrder as $orderInfo) {
                $idx = array_search($orderInfo["alias"], $this->layoutList);
                if ($idx === false) continue;
                $options["order"][] = [ $idx, $orderInfo["dir"] ];
            }
        }
        if ($this->language == "kr") $options["language"] = [ "url" => "/assets/libs/datatables-2.1.8/i18n/ko.json" ];
        $options["drawCallback"] = "drawCallback-function";

        $optionsStr = json_encode($options, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

        $replaceFrom = $replaceTo = [];

        $replaceFrom[] = "\"ajax\": \"ajax-function\"";     $replaceTo[] = "\"ajax\": {$this->makeCodeAjax()}";
        $replaceFrom[] = "\"layout\": \"layout-code\"";     $replaceTo[] = "\"layout\": {$this->makeCodeLayout()}";
        if ($codeColumns["render"]) {
            foreach($codeColumns["render"] as $alias => $render) {
                $replaceFrom[] = "\"rendering-{$alias}\"";
                $replaceTo[] = $render;
            }
        }

        if ($this->options["stateSave"]) {
            $callBackStateSave = <<<EOD

                        let state = sfdt['{$this->tableId}'].state();
                        state.columns.forEach(function(col, index) {
                            if (col.search.search) sfdtSetSearchDefaultValue('{$this->tableId}', sfdt['{$this->tableId}'].column(index).dataSrc(), col.search.search);
                        });
                EOD;
        } else $callBackStateSave = "";

        $callBackTooltip = "";
        if (!$this->disableTooltip) {
            if ($this->extraButtons) {
                foreach($this->extraButtons as $btnId => $eb) {
                    $callBackTooltip .= $eb->tooltipScript();
                }
                $callBackTooltip .= "\n";
            }
            $callBackTooltip .= <<<EOD

                        $(".btn-sfdt-search-reset").data("bs-toggle", "tooltip").attr("title", "[:dt:Reset search conditions:]").tooltip();
                        $(".sfdt-btn-open-column-config").data("bs-toggle", "tooltip").attr("title", "[:dt:Change column order and visibility settings:]").tooltip();
                        $(".sfdt-btn-pagejump").data("bs-toggle", "tooltip").attr("title", "[:dt:Go directly to the page number you entered:]").tooltip();

                EOD;
        }

        $replaceFrom[] = "\"drawCallback\": \"drawCallback-function\"";
        $replaceTo[] = <<<EOD

                "drawCallback": function(settings) {
                    {$callBackStateSave}
                    {$callBackTooltip}
                }
            EOD;

        $replaceFrom[] = "\n"; $replaceTo[] = "\n    ";
        $optionsStr = str_replace($replaceFrom, $replaceTo, $optionsStr);

        $htmlCustomSearch = $this->makeCodeCustomSearch();
        $detailsView = $this->makeCodeDetailsView();

        $once = $this->onceOutput();

        $containerOption = $this->containerOption;
        if ($containerOption) $containerOption = " {$containerOption}";

        $scriptCsStorage = "";
        if ($this->options["stateSave"]) {
            $scriptCsStorage = <<<EOD

                    let csStorage = localStorage.getItem('sfdt-{$this->tableId}-custom-search-ex');
                    if (csStorage) {
                        let csEx = JSON.parse(csStorage);
                        for (let key in csEx) {
                            sfdtSetSearchDefaultValue('{$this->tableId}', key, csEx[key]);
                        }
                    }

                EOD;
        }

        $this->html = <<<EOD
            {$once["html"]}
            {$detailsView["html"]}

            <div{$containerOption}>
                {$htmlCustomSearch}
                <table id="{$this->tableId}" class="table table-hover table-striped text-nowrap"></table>
            </div>

            <script>
            {$once["script"]}
            $(document).ready(function() {
                sfdt['{$this->tableId}'] = new DataTable('#{$this->tableId}', {$optionsStr});
                {$scriptCsStorage}
            });
            {$detailsView["script"]}
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


    /*
     * for handling everything on one page
     */
    private function opAjax()
    {
        $param = Param::getInstance();
        $res = Response::getInstance();
        $template = Template::getInstance();

        $res->draw              = $param->draw;
        $res->recordsTotal      = $this->opRecordsTotal();
        $res->recordsFiltered   = $this->opRecordsFiltered();
        $res->data              = $this->opListData();

        $template->setMode(Template::MODE_AJAX_FOR_DATATABLE);
        $template->displayResult();
        exit;
    }

    private function opDetail()
    {
        $res = Response::getInstance();
        $res->detailData = $this->opDetailData();
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();
        exit;
    }

    // Get the data for the detailed view
    // Use it by overriding it in the inherited class.
    protected function opDetailData()
    {
        L::system("[:dt:The function(opDetailData) for detailed view is not set.:]");
    }

    // Get the parameters for the detailed view
    protected function opDetailParams($id = 'detail')
    {
        $param = Param::getInstance();

        $params = [];
        foreach($this->columns as $alias => $column) {
            $detailButtons = $column->detailButtonInfo();
            if (!$detailButtons) continue;
            $dbInfo = $detailButtons[$id] ?? false;
            if (!$dbInfo) L::system("[:dt:Cannot find the detail view button with id {$id} in the opDetailParams function.:]");
            if ($dbInfo['param'] ?? false) {
                foreach($dbInfo['param'] as $alias) {
                    $param->checkKeyValue($alias, Param::TYPE_STRING);
                    $params[$alias] = $param->get($alias);
                }
            }
        }

        return $params;
    }

    // Get the total number of records
    // Use it by overriding it in the inherited class.
    protected function opRecordsTotal()
    {
        L::system("[:dt:Query for total number of records is not set.:]");
    }

    // Get the total number of records after filtering
    // Use it by overriding it in the inherited class.
    protected function opRecordsFiltered()
    {
        L::system("[:dt:Query for total number of records after filtering is not set.:]");
    }

    // Get the data to be displayed
    // Use it by overriding it in the inherited class.
    protected function opListData()
    {
        L::system("[:dt:Function(opListData) for data is not set.:]");
    }

    // SQL where statement and binding for search
    protected function opQuerySearch()
    {
        if ($this->querySearchSQL) return [ "sql" => $this->querySearchSQL, "bind" => $this->querySearchBind ];

        $param = Param::getInstance();

        $whereOr = [];
        $whereAnd = [];

        $searchableColumns = [];        // for all searchable columns

        // find search keyword for custom search (each column)
        if ($param->columns) {
            foreach($param->columns as $col) {
                $alias = $col['name'];
                $value = $col['search']['value'];
                if (!array_key_exists($alias, $this->columns)) L::system("[:dt:Column({$alias}) not found.:]");
                $columnQuery = $this->columns[$alias]->query();
                if ($col['searchable'] === 'true') $searchableColumns[$alias] = $columnQuery;
                if (!$this->columns[$alias]->searchable() || $value === "") continue;

                $this->opQuerySearchBind($alias, $value, $columnQuery, $col['search']['regex'], $whereAnd, 'cse');
            }
        }

        // find search keyword for custom search ex
        if ($param->customSearchEx) {
            foreach($param->customSearchEx as $alias => $value) {
                if ($value === "") continue;
                if (!array_key_exists($alias, $this->customSearch)) continue;
                $columnQuery = $this->customSearch[$alias]->exColumnQuery();
                $this->opQuerySearchBind($alias, $value, $columnQuery, 'false', $whereAnd, 'cex');
            }
        }

        // find search keyword for all searchable columns
        if ($param->search && $param->search['value'] && count($searchableColumns) > 0) {
            foreach($searchableColumns as $alias => $columnQuery) {
                $whereOr[] = "({$columnQuery}) LIKE :asc_{$alias}";
                $this->querySearchBind[":asc_{$alias}"] = "%{$param->search['value']}%";
            }
        }

        if ($whereOr) $whereAnd[] = '(' . implode(' OR ', $whereOr) . ')';
        if ($whereAnd) $this->querySearchSQL = '(' . implode(' AND ', $whereAnd) . ')';

        return [ "sql" => $this->querySearchSQL, "bind" => $this->querySearchBind ];
    }

    private function opQuerySearchBind($alias, $value, $columnQuery, $regex, &$whereAnd, $prefix)
    {
        switch ($this->customSearch($alias)->type()) {
            case DataTablesCustomSearch::TYPE_STRING :
                $whereAnd[] = "({$columnQuery}) LIKE :{$prefix}_{$alias}";
                $this->querySearchBind[":{$prefix}_{$alias}"] = "%{$value}%";
                break;
            case DataTablesCustomSearch::TYPE_DATERANGE :
            case DataTablesCustomSearch::TYPE_DATETIMERANGE :
                $between = explode(' - ', $value);
                if (count($between) == 2 && strtotime($between[0]) && strtotime($between[1])) {
                    $whereAnd[] = "({$columnQuery}) BETWEEN :{$prefix}_{$alias}_start AND :{$prefix}_{$alias}_end";
                    $this->querySearchBind[":{$prefix}_{$alias}_start"] = date("Y-m-d H:i:s", strtotime($between[0]));
                    $endDate = $between[1];
                    if (strlen($endDate) == 10) {
                        $endDate .= ' 23:59:59';
                    } elseif (strlen($endDate) == 13) {
                        $endDate .= ':59:59';
                    } elseif (strlen($endDate) == 16) {
                        $endDate .= ':59';
                    }
                    $this->querySearchBind[":{$prefix}_{$alias}_end"] = date("Y-m-d H:i:s", strtotime($endDate));
                }
                break;
            case DataTablesCustomSearch::TYPE_NUMBERRANGE :
                $between = explode(' - ', $value);
                if (count($between) == 2) {
                    $between[0] = intval(preg_replace('/\D/', '', $between[0]));
                    $between[1] = intval(preg_replace('/\D/', '', $between[1]));
                    $whereAnd[] = "({$columnQuery}) BETWEEN :{$prefix}_{$alias}_start AND :{$prefix}_{$alias}_end";
                    $this->querySearchBind[":{$prefix}_{$alias}_start"] = $between[0];
                    $this->querySearchBind[":{$prefix}_{$alias}_end"] = $between[1];
                } else {
                    $whereAnd[] = "({$columnQuery}) = :{$prefix}_{$alias}";
                    $this->querySearchBind[":{$prefix}_{$alias}"] = intval(preg_replace('/\D/', '', $value));
                }
                break;
            default :
                if ($regex === 'true') {      // If regex is true, do not perform a like search, but check for an exact match.
                    $whereAnd[] = "({$columnQuery}) = :{$prefix}_{$alias}";
                    $this->querySearchBind[":{$prefix}_{$alias}"] = $value;
                } else {
                    $whereAnd[] = "({$columnQuery}) LIKE :{$prefix}_{$alias}";
                    $this->querySearchBind[":{$prefix}_{$alias}"] = "%{$value}%";
                }
                break;
            }
    }

    protected function opQueryOrderBy()
    {
        if ($this->options["ordering"] === false) {
            $order = "";
            if ($this->defaultOrder) {
                $orders = [];
                foreach($this->defaultOrder as $ord) {
                    $orders[] = "{$this->columns[$ord['alias']]->data()} {$ord['dir']}";
                }
                $order = "ORDER BY " . implode(', ', $orders);
            }
        } else {
            $param = Param::getInstance();
            $order = "";
            if ($param->order) {
                $orders = [];
                foreach($param->order as $ord) {
                    $orders[] = "{$param->columns[$ord['column']]['data']} {$ord['dir']}";
                }
                $order = "ORDER BY " . implode(', ', $orders);
            }
        }
        return $order;
    }

    protected function opQueryLimitStart()
    {
        $param = Param::getInstance();
        return $param->start;
    }

    protected function opQueryLimitLength()
    {
        $param = Param::getInstance();
        return $param->length;
    }
}