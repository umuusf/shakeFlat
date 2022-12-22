<?php
/**
 * libs/datatable.inc
 *
 * When using DataTable.js, a standardized code set is provided.
 * You must include the /asset/js/shakeflat.js file.
 *
 */

namespace shakeFlat\libs;
use shakeFlat\libs\L;
use shakeFlat\libs\Param;

class DataTable extends L
{
    private static $instance      = array();

    private $htmlTableId          = "";
    private $setName              = "";
    private $ajaxUrl              = "";
    private $ajaxMethod           = "POST";
    private $tableClass           = "table table-sm table-hover";
    private $connectionName       = "default";
    private $mainDBTable          = "";
    private $joinDBTable          = array();
    private $andConditions        = array();
    private $orConditions         = array();
    private $searchJoinDBTable    = array();
    private $searchAndConditions  = array();
    private $searchOrConditions   = array();
    private $listing              = array();
    private $defaultOrder         = "";
    private $defaultOrderDirection= "desc";
    private $customSearch         = array();
    private $paging               = true;
    private $searching            = true;
    private $pageLength           = 30;
    private $lengthMenu           = array( 10, 20, 30, 50, 75, 100 );
    private $stateSave            = true;
    private $createdRow           = "";
    private $drawCallBack         = "";
    private $dom                  = "<'row justify-content-end mb-1'<'col-auto'B>><'row justify-content-between'<'col-auto'l><'col-auto'<'row'<'col-auto'<'custom-search'>><'col-auto'f>>>><'row'<'col-12'tr>><'row justify-content-between'<'col-auto'i><'col-auto'p>>";
    private $excelFileName        = "";
    private $excelButtonText      = "Excel";
    private $excelButtonClassName = "btn btn-sm btn-secondary";

    private $jsScript             = array();
    private $jsScriptReady        = array();         // on document ready
    private $bind                 = array();

    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (isset(self::$instance[$calledClass])) return self::$instance[$calledClass];
        self::$instance[$calledClass] = new $calledClass();
        return self::$instance[$calledClass];
    }

    protected function __construct($setName, $ajaxInfo = array(), $config = array())
    {
        $this->setName = $setName;

        if (isset($ajaxInfo["url"])) $this->setAjax($ajaxInfo["url"], $ajaxInfo["method"] ?? "POST");
        if ($config) $this->setConfig($config);
    }

    protected function setAjax($url, $method = "POST")
    {
        $this->ajaxUrl = $url;
        $this->ajaxMethod = $method;
    }

    protected function setTableClass($tableClass)
    {
        $this->tableClass = $tableClass;
    }

    protected function setDBMainTable($mainDBTable, $connectionName = "default")
    {
        $this->mainDBTable = $mainDBTable;
        $this->connectionName = $connectionName;
    }

    protected function setConfig($config)
    {
        if (isset($config["ajaxUrl"]))                  $this->ajaxUrl              = $config["ajaxUrl"];
        if (isset($config["ajaxMethod"]))               $this->ajaxMethod           = $config["ajaxMethod"];

        if (isset($config["tableClass"]))               $this->tableClass           = $config["tableClass"];

        if (isset($config["connectionName"]))           $this->connectionName       = $config["connectionName"];
        if (isset($config["mainDBTable"]))              $this->mainDBTable          = $config["mainDBTable"];

        if (isset($config["listing"]))                  $this->listing              = $config["listing"];
        if (isset($config["defaultOrder"]))             $this->defaultOrder         = $config["defaultOrder"];
        if (isset($config["defaultOrderDirection"]))    $this->defaultOrderDirection= $config["defaultOrderDirection"];
        if (isset($config["paging"]))                   $this->paging               = $config["paging"];
        if (isset($config["pageLength"]))               $this->pageLength           = $config["pageLength"];
        if (isset($config["lengthMenu"]))               $this->lengthMenu           = $config["lengthMenu"];
        if (isset($config["stateSave"]))                $this->stateSave            = $config["stateSave"];
        if (isset($config["searching"]))                $this->searching            = $config["searching"];
    }

    // Defines the fields to be listed.
    // alias : unique name (To distinguish when two or more tables have the same field name when used in a query statement.)
    // rendering : The javascript code of the "render" item described in columnDefs
    protected function setListing($list)
    {
        foreach($list as $alias => $row) {
            // realColumn :
            //   If omitted, it is replaced with the alias value.
            //   If you want to output only the contents of render, set it to blank("") or null.
            $realColumn = null;
            if (!array_key_exists("realColumn", $row)) $realColumn = $alias;
            elseif ($row["realColumn"] !== null) $realColumn = $row["realColumn"];

            $this->listing[$alias] = array(
                "display"    => $row["display"] ?? true,    // If omitted, it is recognized as true.
                "title"      => $row["title"] ?? "",        // If omitted, it is recognized as blank.
                "realColumn" => $realColumn,
                "orderable"  => $row["orderable"] ?? false,
                "searchable" => $row["searchable"] ?? false,
                "className"  => $row["className"] ?? "text-center text-nowrap",
                "rendering"  => $row["rendering"] ?? "",
            );
        }
    }

    protected function setDBConnectionName($connectionName)
    {
        $this->connectionName = $connectionName;
    }

    protected function setMainDBTable($tableName)
    {
        $this->mainDBTable = $tableName;
    }

    // set left join table infomations.
    // $joinCondition : Conditions added to the ON condition
    // ex) If you need to perform a query like the one below, the statement corresponding to the [] part
    // ... left join tblSecond on tblSecond.key = tblMain.key [and tblSecond.duedate = '2022-01-01']
    protected function setJoinTable($tableName, $joinColumn, $mainDBTableColumn, $joinCondition = null, $joinConditionBind = null)
    {
        $this->joinDBTable[] = array (
            "tableName"         => $tableName,
            "joinColumn"        => $joinColumn,
            "mainDBTableColumn" => $mainDBTableColumn,
            "joinCondition"     => $joinCondition,
            "joinConditionBind" => $joinConditionBind,
        );
    }

    // set default condition for AND
    protected function setAnd($condition, $bind = null)
    {
        $w = trim($condition, " \n\r\t");
        if (strtolower(substr($w, 0, 4)) == "and ") $w = substr($w, 4);
        $this->andConditions[] = $condition;

        if ($bind) $this->bind = array_merge($this->bind, $bind);
    }

    // set default condition for OR
    protected function setOr($condition, $bind = null)
    {
        $w = trim($condition, " \n\r\t");
        if (strtolower(substr($w, 0, 3)) == "or ") $w = substr($w, 4);
        $this->orConditions[] = $condition;

        if ($bind) $this->bind = array_merge($this->bind, $bind);
    }

    public function setSearchJoinDBTable($tableName, $joinColumn, $mainDBTableColumn, $joinCondition = null, $joinConditionBind = null)
    {
        $this->searchJoinDBTable[] = array (
            "tableName"         => $tableName,
            "joinColumn"        => $joinColumn,
            "mainDBTableColumn" => $mainDBTableColumn,
            "joinCondition"     => $joinCondition,
            "joinConditionBind" => $joinConditionBind,
        );
    }

    // set default condition for AND
    public function setSearchAnd($condition, $bind = null)
    {
        $w = trim($condition, " \n\r\t");
        if (strtolower(substr($w, 0, 4)) == "and ") $w = substr($w, 4);
        $this->searchAndConditions[] = $condition;

        if ($bind) $this->bind = array_merge($this->bind, $bind);
    }

    // set default condition for OR
    public function setSearchOr($condition, $bind = null)
    {
        $w = trim($condition, " \n\r\t");
        if (strtolower(substr($w, 0, 3)) == "or ") $w = substr($w, 4);
        $this->searchOrConditions[] = $condition;

        if ($bind) $this->bind = array_merge($this->bind, $bind);
    }

    public function setDefaultOrder($alias, $direction = "desc")
    {
        $this->defaultOrder = $alias;
        $this->defaultOrderDirection = $direction;
    }

    public function setCustomSearchSelectBox($alias, $title, $list, $isSelect2 = false, $style = null)
    {
        $this->customSearch[$alias] = array(
            "type"      => "select",
            "title"     => $title,
            "list"      => $list,
            "isSelect2" => $isSelect2,
            "style"     => $style,
        );
    }

    public function setCustomSearchDateRange($alias, $title, $style = null)
    {
        $this->customSearch[$alias] = array(
            "type"      => "dateRange",
            "title"     => $title,
            "style"     => $style,
        );
    }

    public function setPageLength($length)
    {
        $this->pageLength = $length;
    }

    // $lengthMenu : array( 10, 20, 30, ... )
    public function setLengthMenu($lengthMenu)
    {
        if (!is_array($lengthMenu)) return false;
        $this->lengthMenu = $lengthMenu;
    }

    // $state : true/false
    public function setStateSave($state)
    {
        $this->stateSave = $state;
    }

    public function setCreatedRow($sentence)
    {
        $this->createdRow = $sentence;
    }

    public function setDrawCallBack($sentence)
    {
        $this->drawCallBack = $sentence;
    }

    public function setDom($dom)
    {
        $this->dom = $dom;
    }

    public function setExcelFileName($filename)
    {
        $this->excelFileName = $filename;
    }

    public function setExcelButtonText($html)
    {
        $this->excelButtonText = $html;
    }

    public function setExcelButtonClassName($class)
    {
        $this->excelButtonClassName = $class;
    }



    /*
     * for listing (html, js)
     */

    private function check()
    {
        if (!$this->ajaxUrl) self::system("DataTable setting value is missing: ajaxURL");
        if (!$this->listing) self::system("DataTable setting value is missing: listing");
        if (!$this->mainDBTable) self::system("DataTable setting value is missing: mainDBTable");
    }

    private function build()
    {
        $this->check();
        if ($this->htmlTableId != "") return;

        $this->htmlTableId  = "tbl-" . $this->setName;
        $jsTableName = "tbl_" . str_replace("-", "_", $this->setName);

        $stateSave  = ($this->stateSave) ? "true" : "false";
        $searching  = ($this->searching) ? "true" : "false";
        $paging     = ($this->paging) ? "true" : "false";
        $lengthMenu = json_encode($this->lengthMenu);

        $order = "[]";
        $idx = array_search($this->defaultOrder, array_keys($this->listing));
        if ($idx !== false) $order = json_encode([[ $idx, "$this->defaultOrderDirection" ]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $columnsList = array();
        $excelColumnsList = array();
        $idx = 0;
        foreach($this->listing as $alias => $row) {
            if (!$row["display"]) continue;

            if ($row["realColumn"]) $data = "data: \"{$alias}\", "; else $data = "";
            if ($row["searchable"] && $data) $searchable = "searchable: true, "; else $searchable = "searchable: false, ";
            if ($row["orderable"] && $data)  $orderable = "orderable: true, "; else $orderable = "orderable: false, ";

            if ($row["rendering"])  $render = "render: {$row["rendering"]}, "; else $render = "";
            if ($row["title"])      $title = "title: \"{$row["title"]}\", "; else $title = "";
            if ($row["className"])  $className = "className: \"{$row["className"]}\", "; else $className = "";

            $columnsList[] = "{ name: \"{$alias}\", {$data}{$title}{$searchable}{$orderable}{$render}{$className}}";
            $excelColumnsList[] = $idx++;
        }
        $columns = "[\n\t\t\t\t\t".implode(",\n\t\t\t\t\t", $columnsList)."\n\t\t\t\t]";
        $excelColumns = json_encode($excelColumnsList, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $drawCallBack = "";         if ($this->drawCallBack) $drawCallBack = "drawCallBack : function(settings) { {$this->drawCallBack} },\n";
        $createdRow = "";           if ($this->createdRow) $createdRow = "createdRow : function(row, data, dataIndex, cells) { {$this->createdRow} },\n";
        $excelFileName = "";        if ($this->excelFileName) $excelFileName = "title: \"" . htmlspecialchars($this->excelFileName) . "\", ";
        $excelButtonText = "";      if ($this->excelButtonText) $excelButtonText = "text: \"" . str_replace("\"", "\\\"", $this->excelButtonText) . "\", ";
        $excelButtonClassName = ""; if ($this->excelButtonClassName) $excelButtonClassName = "className: \"" . str_replace("\"", "\\\"", $this->excelButtonClassName) . "\", ";


        $customSearch = "";
        $customSearchAjaxData   = "";
        $customSearchReload     = "";
        $customSearchSelect2    = "";
        $customSearchDateRange  = "";
        $customSearchList       = array();
        if ($this->customSearch) {
            $html = "";
            foreach($this->customSearch as $alias => $info) {
                $default = null;
                $html = "";
                $style = "";
                if (isset($info["title"]) && $info["title"]) $html .= "\t\t\t\t<label class='ms-3 me-2' for='sf_search_{$alias}'>{$info["title"]}: </label>\\\n";
                if (isset($info["style"]) && $info["style"]) $style = "style='{$info["style"]}' ";
                switch($info["type"]) {
                    case "select" :
                        $html .= "\t\t\t\t<select class='form-select form-select-sm w-auto sf-custom-search-{$jsTableName}' {$style}name='sf_search_{$alias}' id='sf_search_{$alias}'>\\\n";
                        foreach($info["list"] as $row) {
                            if ($default === null) $default = $row["value"];
                            $selected = "";
                            if (isset($row["selected"]) && $row["selected"]) { $selected = " selected"; $default = $row["value"]; }
                            $html .= "\t\t\t\t\t<option value='{$row["value"]}'{$selected}>{$row["text"]}\\\n";
                        }
                        $html .= "\t\t\t\t</select>\\\n";
                        if ($info["isSelect2"]) $customSearchSelect2 .= "$(\"#sf_search_{$alias}\").select2({theme: 'bootstrap-5'});\n";
                        break;
                    case "dateRange" :
                        $html .= "\t\t\t\t<input type='search' class='form-control form-control-sm w-auto sf-custom-search-{$jsTableName}' {$style}name='sf_search_{$alias}' id='sf_search_{$alias}' autocomplete='off'>\\\n";
                        $customSearchDateRange .= "$(\"#sf_search_{$alias}\").daterangepicker({ timePicker:true, autoUpdateInput: false, locale: { format: 'YYYY-MM-DD HH:mm', cancelLabel: 'Clear' }});\n\t\t";
                        $customSearchDateRange .= "$(\"#sf_search_{$alias}\").on(\"apply.daterangepicker\", function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm')); {$jsTableName}.ajax.reload(null, false); });\n\t\t";
                        break;
                }
                $customSearchList[] = $html;
                $customSearchAjaxData .= "data.sf_search_{$alias} = valueForSelect($(\"#sf_search_{$alias}\"), \"{$default}\");\n\t\t\t\t";
            }
        }
        if ($customSearchList) {
            $customSearch = "$(\"div.custom-search\").html(\"\\\n\t\t\t<div class='d-flex flex-row align-items-center flex-wrap'>\\\n" . implode("", $customSearchList) . "\t\t\t</div>\\\n\t\t\");";
            $customSearchReload = "$(document).on(\"change\", \".sf-custom-search-{$jsTableName}\", function() { {$jsTableName}.ajax.reload(null, false); });";
        }

        $this->jsScript[] = <<<EOD
// This is the code generated through the DataTable class.
var {$jsTableName} = null;
function init_{$jsTableName}() {
    if ({$jsTableName}) {
        {$jsTableName}.ajax.reload(null, false);
    } else {
        {$jsTableName} = $("#{$this->htmlTableId}").DataTable({
            searching   : {$searching},
            stateSave   : {$stateSave},
            paging      : {$paging},
            pageLength  : {$this->pageLength},
            pagingType  : "full_numbers",
            lengthMenu  : {$lengthMenu},
            processing  : true,
            serverSide  : true,
            retrieve    : true,
            ajax        : function(data, callback, settings) {
                {$customSearchAjaxData}
                $.ajax({
                    url: "{$this->ajaxUrl}",
                    type: "POST",
                    data: data,
                }).done(function (json, textStatus, jqXHR) {
                    try {
                        if (typeof json != "object") throw "not object";
                        if ('error' in json && 'errCode' in json.error && json.error.errCode != 0) throw json.error.errMsg;
                        if (!'draw' in json || !'recordsTotal' in json || !'recordsFiltered' in json || !'data' in json) throw "invalid format";
                        callback(json);
                    } catch (e) {
                        console.log("ajax page returns data in wrong:", e);
                        console.log(json);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.log("ajax fail:", textStatus);
                });
            },
            columns     : {$columns},
            order       : {$order},
            dom         : "{$this->dom}",
            buttons     : [
                //{ title: 'New', text: 'New', {$excelButtonClassName} },
                { extend: 'excelHtml5', titleAttr: 'Excel', {$excelFileName}{$excelButtonText}{$excelButtonClassName}action: newexportaction, exportOptions: { columns: {$excelColumns} } },
            ],
            language    : {
                "decimal" : "",
                "emptyTable" : "데이터가 없습니다.",
                "info" : "_START_ - _END_ / 전체 _TOTAL_개",
                "infoEmpty" : "0개",
                "infoFiltered" : "(전체 _MAX_개 중 검색결과)",
                "infoPostFix" : "",
                "thousands" : ",",
                "lengthMenu" : "_MENU_ 개씩 보기",
                "loadingRecords" : "로딩중...",
                "processing" : "처리중...",
                "search" : "검색 : ",
                "zeroRecords" : "검색된 데이터가 없습니다.",
                "paginate" : { "first" : "처음", "last" : "마지막", "next" : "다음","previous" : "이전" },
                "aria" : { "sortAscending" : " :  오름차순 정렬", "sortDescending" : " :  내림차순 정렬" }
            },
            {$drawCallBack}{$createdRow}
        });
        {$customSearch}
        {$customSearchSelect2}
        {$customSearchDateRange}
    }
}
EOD;

        $this->jsScriptReady[] = <<<EOD
    init_{$jsTableName}();
    {$customSearchReload}
EOD;
    }

    public function echoTable()
    {
        $this->build();
        $htmlTableClass = ($this->tableClass) ? " class=\"{$this->tableClass}\"" : "";
        echo "<table id=\"{$this->htmlTableId}\"{$htmlTableClass}></table>\n";
    }

    public function echoJS()
    {
        $this->build();
        $jsScript = implode("\n", $this->jsScript);
        $jsScriptReady = implode("\n", $this->jsScriptReady);
        echo "
<script>
{$jsScript}
$(document).ready(function() {
{$jsScriptReady}
});
</script>
";
    }



    /*
     * for serverside(ajax)
     */

    private function ajaxParam()
    {
        static $param = null;
        if ($param !== null) return $param;

        $param = Param::getInstance();

        $param->checkKeyValue("draw", Param::TYPE_INT);
        $param->checkKeyValue("columns", Param::TYPE_ARRAY);
        $param->checkKeyValue("start", Param::TYPE_INT);
        $param->checkKeyValue("length", Param::TYPE_INT);
        $param->checkKeyValue("search", Param::TYPE_ARRAY);
        $param->check("order", Param::TYPE_ARRAY);

        return $param;
    }

    private function joinSQL($isSearch = 0)
    {
        $joinSQL = "";
        if ($this->joinDBTable) {
            foreach($this->joinDBTable as $ji) {
                $joinSQL .= "left join {$ji["tableName"]} on {$ji["tableName"]}.{$ji["joinColumn"]} = {$this->mainDBTable}.{$ji["mainDBTableColumn"]}";
                if ($ji["joinCondition"]) $joinSQL .= " " . $ji["joinCondition"];
                $joinSQL .= "\n";
            }
        }
        if ($isSearch && $this->searchJoinDBTable) {
            foreach($this->searchJoinDBTable as $ji) {
                $joinSQL .= "left join {$ji["tableName"]} on {$ji["tableName"]}.{$ji["joinColumn"]} = {$this->mainDBTable}.{$ji["mainDBTableColumn"]}";
                if ($ji["joinCondition"]) $joinSQL .= " " . $ji["joinCondition"];
                $joinSQL .= "\n";
            }
        }
        return $joinSQL;
    }

    private function whereSQL($isSearch = 0)
    {
        $whereSQL = "";
        $andW = $this->andConditions;
        if ($this->orConditions) $andW[] = "(" . implode(" or ", $this->orConditions) . ")";
        if ($isSearch) {
            if ($this->searchOrConditions) $andW[] = "(" . implode(" or ", $this->searchOrConditions) . ")";
            if ($this->searchAndConditions) $andW = array_merge($andW, $this->searchAndConditions);
        }
        if ($andW) $whereSQL = "where " . implode(" and ", $andW);
        return $whereSQL;
    }

    public function recordCount()
    {
        static $count = -1;
        if ($count != -1) return $count;

        $db = DB::getInstance($this->connectionName);
        $rs = $db->query("
            select count(*) as cnt
            from {$this->mainDBTable}
            " . $this->joinSQL() . "
            " . $this->whereSQL() . "
        ", $this->bind);
        $count = intval($db->fetch($rs)["cnt"]);
        return $count;
    }

    private function procSearching()
    {
        static $proc = false;
        if ($proc !== false) return;
        $param = $this->ajaxParam();
        if ($param->search["value"] ?? false) {
            foreach($param->columns as $idx => $col) {
                if (($col["searchable"] ?? "false") == "true" && $this->listing[$col["name"]]["realColumn"]) {
                    $this->setSearchOr("{$this->listing[$col["name"]]["realColumn"]} like :sf_search_{$idx}", array(":sf_search_{$idx}" => "%{$param->search["value"]}%"));
                }
            }
        }

        // custom search
        if ($this->customSearch) {
            foreach($this->customSearch as $alias => $info) {
                $val = $param->get("sf_search_{$alias}", null);
                if($val !== null) {
                    switch($info["type"]) {
                        case "select" :
                            $this->setSearchAnd("{$this->listing[$alias]["realColumn"]} = :sf_custom_search_{$alias}", array(":sf_custom_search_{$alias}" => $val));
                            break;
                        case "dateRange" :
                            $val = trim($val);

                            $valStart = substr($val, 0, 16);
                            $valEnd = substr($val, 19);
                            if (sfValidateDate($valStart, "Y-m-d H:i")) {
                                $this->setSearchAnd("{$this->listing[$alias]["realColumn"]} >= :sf_custom_search_{$alias}_start", array(":sf_custom_search_{$alias}_start" => "{$valStart}-00"));
                                $val = $valStart . " - " . substr($val, 19);
                            }
                            if (sfValidateDate($valEnd, "Y-m-d H:i")) {
                                $this->setSearchAnd("{$this->listing[$alias]["realColumn"]} <= :sf_custom_search_{$alias}_end", array(":sf_custom_search_{$alias}_end" => "{$valEnd}-59"));
                                $val = substr($val, 0, 16) . " - " . $valEnd;
                            }
                            break;
                    }
                }
            }
        }

        $proc = true;
    }

    public function searchCount()
    {
        $this->procSearching();
        if (!$this->searchJoinDBTable && !$this->searchAndConditions && !$this->searchOrConditions) return $this->recordCount();

        $db = DB::getInstance($this->connectionName);
        $rs = $db->query("
            select count(*) as cnt
            from {$this->mainDBTable}
            " . $this->joinSQL(1) . "
            " . $this->whereSQL(1) . "
        ", $this->bind);

        return intval($db->fetch($rs)["cnt"] ?? 0);
    }

    public function listData()
    {
        $this->procSearching();
        $param = $this->ajaxParam();

        $order = "";
        if($param->order && isset($param->columns[$param->order[0]["column"]]) && $param->columns[$param->order[0]["column"]]["orderable"] == true && $this->listing[$param->columns[$param->order[0]["column"]]["data"]]["realColumn"]) {
            $order = "order by {$this->listing[$param->columns[$param->order[0]["column"]]["data"]]["realColumn"]} {$param->order[0]["dir"]}";
        }

        $columnArr = array();
        foreach($this->listing as $alias => $row) {
            if (!$row["realColumn"]) continue;
            $columnArr[] = "{$row["realColumn"]} as $alias";
        }
        $columns = implode(",", $columnArr);

        $db = DB::getInstance($this->connectionName);
        $rs = $db->query("
            select
            {$columns}
            from {$this->mainDBTable}
            " . $this->joinSQL(1) . "
            " . $this->whereSQL(1) . "
            {$order}
            limit {$param->start}, {$param->length}
        ", $this->bind);
        $data = array();
        while($row = $db->fetch($rs)) {
            $data[] = $row;
        }

        return $data;
    }

    public function ajaxResponse()
    {
        $res = Response::getInstance();
        $res->draw              = $this->ajaxParam()->draw;
        $res->recordsTotal      = $this->recordCount();
        $res->recordsFiltered   = $this->searchCount();
        $res->data              = $this->listData();
    }
}