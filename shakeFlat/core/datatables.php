<?php
namespace shakeFlat;
use shakeFlat\L;
use shakeFlat\Translation;
use shakeFlat\Param;
use shakeFlat\TransactionDBList;

class DataTablesRenderButton
{
    private $tableId;
    private $columnAlias;
    private $btnId;
    private $type;
    private $keyParams;
    private $queryUrl;
    private $submitUrl;
    private $queryFunction;
    private $submitFunction;
    private $title;
    private $class;
    private $dataset;
    private $style;
    private $editColumn;
    private $layout;
    private $confirmMsg;        // for delete
    private $customScript;
    private $readCallbackScript;

    public function __construct($tableId, $columnAlias, $btnId)
    {
        $this->tableId = $tableId;
        $this->columnAlias = $columnAlias;
        $this->btnId = $btnId;
        $this->type = "button";
        $this->keyParams = [];
        $this->queryUrl = "";
        $this->submitUrl = "";
        $this->queryFunction = "";
        $this->submitFunction = "";
        $this->title = "";
        $this->class = [];
        $this->dataset = [];
        $this->style = [];
        $this->editColumn = [];
        $this->layout = [];
        $this->confirmMsg = "";
        $this->customScript = "";
        $this->readCallbackScript = "";

        $this->dataset("button-id", $btnId);
    }

    public function typeDetailVlew() { $this->type = "detailView"; return $this; }
    public function typeModify() { $this->type = "modify"; return $this; }
    public function typeDelete() { $this->type = "delete"; return $this; }

    public function keyParams($keyParams = null)
    {
        if ($keyParams === null) return $this->keyParams;
        if (is_array($keyParams)) $this->keyParams = array_merge($this->keyParams, $keyParams);
        else $this->keyParams[] = $keyParams;
        return $this;
    }

    public function keyParam($keyParam) { return $this->keyParams($keyParam); }

    public function queryUrl($queryUrl = null)
    {
        if ($queryUrl === null) return $this->queryUrl;
        $this->queryUrl = $queryUrl;
        return $this;
    }

    public function queryFunction($queryFunction = null)
    {
        if ($queryFunction === null) return $this->queryFunction;
        $this->queryFunction = $queryFunction;
        return $this;
    }

    public function submitFunction($submitFunction = null)
    {
        if ($submitFunction === null) return $this->submitFunction;
        $this->submitFunction = $submitFunction;
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
        else $this->class[] = $class;
        return $this;
    }

    // data-key='value'
    public function dataset($key = null, $value = null)
    {
        if ($key === null && $value === null) return $this->dataset;
        else if ($value === null) return $this->dataset[$key];
        else $this->dataset[$key] = $value;
        return $this;
    }

    public function style($style = null)
    {
        if ($style === null) return $this->style;
        else $this->style[] = $style;
        return $this;
    }

    public function editColumn($alias)
    {
        if (!isset($this->editColumn[$alias])) $this->editColumn[$alias] = new DataTablesEditColumn($this->tableId, $this->btnId, $alias);
        return $this->editColumn[$alias];
    }

    public function confirmMsg($msg = null)
    {
        if ($msg === null) return $this->confirmMsg;
        $this->confirmMsg = $msg;
        return $this;
    }

    public function customScript($script = null)
    {
        if ($script === null) return $this->customScript;
        $this->customScript = $script;
        return $this;
    }

    public function readCallbackScript($script = null)
    {
        if ($script === null) return $this->readCallbackScript;
        $this->readCallbackScript = $script;
        return $this;
    }

    public function layout($layout) { $this->layout = $layout; return $this; }




    public function render()
    {
        if ($this->keyParams) {
            foreach($this->keyParams as $alias) {
                if (!array_key_exists($alias, $this->dataset)) $this->dataset[$alias] = "\${row.{$alias}}";
            }
        }
        $style = "";   if ($this->style) $style = " style=\"" . implode(" ", $this->style) . "\"";
        $class = "";   if ($this->class) $class = " " . implode(" ", $this->class);
        $dataset = ""; foreach($this->dataset as $key => $value) $dataset .= " data-{$key}=\"{$value}\"";
        return "<button type=\"button\" class=\"btn btn-xs{$class}\"{$style}{$dataset}>{$this->title}</button>";
    }

    public function code($tableColumns, $ajaxUrl, $deliverParameters)
    {
        if ($this->type === "detailView") return $this->_codeDetailView($tableColumns, $ajaxUrl, $deliverParameters);
        else if ($this->type === "modify") return $this->_codeModify($tableColumns, $ajaxUrl, $deliverParameters);
        else if ($this->type === "delete") return $this->_codeDelete($ajaxUrl, $deliverParameters);

        return [ "html" => "", "drawCallbackScript" => "", "script" => "" ];
    }

    private function _codeDetailView($tableColumns, $ajaxUrl, $deliverParameters)
    {
        // detail view button
        $detailScript = "";
        $detailHtml = "";
        $formatScript = "";
        $isZoom = false;
        $layoutHtml = "";

        $htmlItem = function($alias) use ($tableColumns) {
            if (!array_key_exists($alias, $tableColumns)) L::system("[:dt:Column alias({$alias}) does not exist in tableColumns.:]");
            return <<<EOD
                                    <div class="col-auto">
                                        <div class="sfdt-floating">
                                            <div class="sfdt-label text-nowrap">{$tableColumns[$alias]->title()}</div>
                                            <div class="sfdt-plaintext text-nowrap" id="sfdt-modal-{$this->tableId}-{$this->btnId}-column-{$alias}"></div>
                                        </div>
                                    </div>

                EOD;
        };

        $columnAliases = [];
        foreach($this->layout as $item) {
            if ($item === '---') {
                $layoutHtml .= <<<EOD

                                <hr>
                EOD;
                continue;
            }

            if (is_string($item) && substr($item, 0, 6) === 'label:') {
                $item = substr($item, 6);
                $layoutHtml .= <<<EOD

                                <div class="row mb-2">
                                    <div class="text-nowrap">{$item}</div>
                                </div>
                EOD;
                continue;
            }

            $mb4 = "";
            if (is_array($this->layout) && $item !== end($this->layout)) $mb4 = " mb-4";
            $layoutHtml .= <<<EOD

                                <div class="row{$mb4}">

                EOD;
            if (is_array($item)) {
                foreach($item as $alias) {
                    $layoutHtml .= $htmlItem($alias);
                    $columnAliases[] = $alias;
                }
            } else {
                $layoutHtml .= $htmlItem($item);
                $columnAliases[] = $item;
            }

            $layoutHtml .= <<<EOD
                                </div>
                EOD;
        }

        foreach($columnAliases as $alias) {
            $column = $tableColumns[$alias];
            if ($column->displayType() == "number") {
                $formatScript .= "                    else if (alias == '{$alias}') txt = txt.numberFormat();\n";
            } elseif (substr($column->displayType(), 0, 5) == "date:") {
                $format = substr($column->displayType(), 5);
                $formatScript .= "                    else if (alias == '{$alias}') txt = txt.formatDateTime('{$format}');\n";
            } elseif (substr($column->displayType(), 0, 9) == "datetime:") {
                $format = substr($column->displayType(), 9);
                $formatScript .= "                    else if (alias == '{$alias}') txt = txt.formatDateTime('{$format}');\n";
            } elseif ($column->displayType() == "image") {
                $formatScript .= "                    else if (alias == '{$alias}') txt = '<img src=\"' + txt + '\" style=\"max-width:100px; max-height:100px;\">';\n";
            } elseif ($column->displayType() == "zoom") {
                $formatScript .= "                    else if (alias == '{$alias}') txt = '<img src=\"' + txt + '\" data-sflightbox=\"sfdt-image-zoom-detail\" data-big=\"'+ result.data.queryData['{$column->zoomColumn()}'] +'\" style=\"max-width:100px; max-height:100px;\">';\n";
                $isZoom = true;
            } elseif ($column->displayType() == "html") {
                $formatScript .= "                    else if (alias == '{$alias}') txt = txt;\n";
            }
        }
        if ($formatScript) {
            $formatScript = ltrim($formatScript, "else ");
            $formatScript .= "                    else txt = escapeHtml(txt);\n";
        } else {
            $formatScript = "txt = escapeHtml(txt);\n";
        }

        if (!$this->keyParams) L::system("[:dt:Parameter for detail view({$this->btnId}) is not defined.:]");
        $paramScript = "";
        foreach($this->keyParams as $alias) {
            $paramScript .= "param['{$alias}'] = $(this).data('{$alias}');\n        ";
        }
        $queryUrl = $this->queryUrl | $ajaxUrl;
        if ($queryUrl === $ajaxUrl) {
            $paramScript .= "param['sfdtPageMode'] = 'detailView';\n        ";
        }
        $paramScript .= "param['sfdtBtnId'] = '{$this->btnId}';\n        ";

        if ($deliverParameters) {
            foreach($deliverParameters as $k => $v) {
                $paramScript .= "param['{$k}'] = '{$v}';\n        ";
            }
        }

        $zoomScript = "";
        if ($isZoom) $zoomScript = "$('img[data-sflightbox]').sfLightBox();";
        $detailScript .= <<<EOD

                $(document).on("click", "button[data-button-id='{$this->btnId}']", function() {
                    let param = {};
                    {$paramScript}
                    callAjax(
                        '{$queryUrl}',
                        param,
                        function(result) {
                            if (!result.data.queryData || typeof result.data.queryData !== 'object') {
                                alert("[:dt:queryData does not exist in the result from {$queryUrl}:]");
                                return;
                            }
                            for(let alias in result.data.queryData) {
                                let txt = result.data.queryData[alias];
                                {$formatScript}
                                $("#sfdt-modal-{$this->tableId}-{$this->btnId}-column-" + alias).html(txt);
                            }
                            {$this->readCallbackScript}
                            $("#sfdt-modal-{$this->tableId}-{$this->btnId}").modal("show");
                            {$zoomScript}
                        }
                    );
                });

                {$this->customScript}
            EOD;

        if (!$this->layout) L::system("[:dt:Layout for detail view({$this->btnId}) is not defined.:]");
        $detailHtml = <<<EOD
            <!-- DataTable - Detail view modal for Table Id {$this->tableId} -->
            <div class="modal fade" tabindex="-1" id="sfdt-modal-{$this->tableId}-{$this->btnId}" aria-labelledby="Detail View" aria-describedby="Detail View" aria-hidden="true" aria-modal="true">
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

        return [ "html" => $detailHtml, "drawCallbackScript" => "", "script" => $detailScript ];
    }

    private function _codeModify($tableColumns, $ajaxUrl, $deliverParameters)
    {
        // modify button
        $modifyScript = "";
        $modifyHtml = "";
        if (!$this->keyParams) L::system("[:dt:Parameter for modify({$this->btnId}) is not defined.:]");
        $paramScript = "";
        $submitScript = "";
        foreach($this->keyParams as $alias) {
            $paramScript .= "param['{$alias}'] = $(this).data('{$alias}');\n        ";
        }
        $queryUrl = $this->queryUrl | $ajaxUrl;
        $submitUrl = $this->submitUrl | $ajaxUrl;
        if ($queryUrl === $ajaxUrl) $paramScript .= "param['sfdtPageMode'] = 'modify';\n        ";
        if ($submitUrl === $ajaxUrl) $submitScript .= "formData.append('sfdtPageMode', 'submitForModify');\n    ";
        $paramScript .= "param['sfdtBtnId'] = '{$this->btnId}';\n            ";
        $submitScript .= "formData.append('sfdtBtnId', '{$this->btnId}');\n    ";

        if ($deliverParameters) {
            foreach($deliverParameters as $k => $v) {
                $paramScript .= "param['{$k}'] = '{$v}';\n            ";
                $submitScript .= "formData.append('{$k}', '{$v}');\n    ";
            }
        }

        $columnAliases = [];
        $layoutHtml = "";
        if (!$this->layout) L::system("[:dt:Layout for modify({$this->btnId}) is not defined.:]");

        $hiddenHtml = "";
        foreach($this->editColumn as $alias => $editColumn) {
            if ($editColumn->type() == "hidden") {
                $hiddenHtml .= "<input type=\"hidden\" name=\"{$alias}\" id=\"sfdt-edit-{$this->tableId}-{$this->btnId}-{$alias}\" value=\"\">";
                $columnAliases[] = $alias;
            }
        }

        $newCommentForFile = function($alias) use ($tableColumns) {
            if (!array_key_exists($alias, $tableColumns) || !array_key_exists($alias, $this->editColumn)) L::system("[:dt:Column alias({$alias}) does not exist in tableColumns.:]");
            if ($this->editColumn[$alias]->type() != "file") return false;
            $comment = $this->editColumn[$alias]->comment();
            $this->editColumn[$alias]->comment($comment . " <span class='sfdt-edit-{$this->tableId}-modify-{$alias}-oldfile'></span>");
            return true;
        };

        foreach($this->layout as $item) {
            if ($item === '---') {
                $layoutHtml .= <<<EOD

                                <hr class="mb-3">
                EOD;
                continue;
            }
            $layoutHtml .= <<<EOD

                                <div class="row mb-4">

                EOD;
            if (is_array($item)) {
                foreach($item as $alias) {
                    $newCommentForFile($alias);
                    $layoutHtml .= $this->editColumn[$alias]->html($tableColumns);
                    $columnAliases[] = $alias;
                }
            } else {
                $newCommentForFile($item);
                $layoutHtml .= $this->editColumn[$item]->html($tableColumns);
                $columnAliases[] = $item;
            }

            $layoutHtml .= <<<EOD

                                </div>
                EOD;
        }

        $setTypesScript = "";
        foreach($columnAliases as $alias) {
            $editColumn = $this->editColumn[$alias];
            $setTypesScript .= "types['{$alias}'] = '{$editColumn->type()}';\n                ";
        }

        $modifyScript .= <<<EOD

                $(document).on("click", "button[data-button-id='{$this->btnId}']", function() {
                    let param = {};
                    {$paramScript}
                    callAjax(
                        '{$queryUrl}',
                        param,
                        function(result) {
                            if (!result.data.queryData || typeof result.data.queryData !== 'object') {
                                alert("[:dt:queryData does not exist in the result from {$queryUrl}:]");
                                return;
                            }
                            //console.log(result.data.queryData);
                            $("#sfdt-form-{$this->tableId}-{$this->btnId}")[0].reset();
                            let types = {};
                            {$setTypesScript}
                            for(let alias in types) {
                                let txt = result.data.queryData[alias];
                                if (types[alias] == 'radio') {
                                    sfdtSetDefaultValue($("#sfdt-edit-{$this->tableId}-{$this->btnId}-"+alias+"-1[name='"+alias+"']"), txt);
                                } else if (types[alias] == 'checkbox') {
                                    sfdtSetDefaultValue($("#sfdt-edit-{$this->tableId}-{$this->btnId}-"+alias+"-1[name='"+alias+"[]']"), txt);
                                } else if (types[alias] == 'file') {
                                    if (txt) {
                                        $("span.sfdt-edit-{$this->tableId}-modify-"+alias+"-oldfile").html('<a href="'+txt+'" target="_blank"><div class="ms-2 badge bg-load" style="--bs-badge-font-size:0.75rem;">[:dt:Download Current File:]</div></a>');
                                    }
                                } else if (types[alias] != 'password') {
                                    sfdtSetDefaultValue($("#sfdt-edit-{$this->tableId}-{$this->btnId}-"+alias), txt);
                                }
                            }
                            {$this->readCallbackScript}
                            $("#sfdt-btn-{$this->tableId}-{$this->btnId}-modify-submit").prop("disabled", false);
                            $("#sfdt-btn-{$this->tableId}-{$this->btnId}-modify-submit").html("[:dtmodify:Modify:]");
                            $("#sfdt-modal-{$this->tableId}-{$this->btnId}").modal("show");
                        }
                    );
                });

                // modify submit
                $("#sfdt-btn-{$this->tableId}-{$this->btnId}-modify-submit").click(function() {
                    let \$this = $(this);
                    \$this.prop("disabled", true);
                    \$this.html('<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span> <span class="align-middle">[:dtmodify:Modify:]</span>');

                    if (!$("#sfdt-form-{$this->tableId}-{$this->btnId}")[0].checkValidity()) {
                        $("#sfdt-form-{$this->tableId}-{$this->btnId}")[0].reportValidity()
                        \$this.prop("disabled", false);
                        \$this.html("[:dtmodify:Modify:]");
                        return false;
                    }
                    let formData = new FormData($("#sfdt-form-{$this->tableId}-{$this->btnId}")[0]);

                    // If there are any disabled radio buttons that are checked, pass their values.
                    $("#sfdt-form-{$this->tableId}-{$this->btnId} input[type='radio']:disabled").each(function() {
                        if ($(this).prop("checked")) formData.append($(this).attr("name"), $(this).val());
                    });
                    $("#sfdt-form-{$this->tableId}-{$this->btnId} input[type='checkbox']:disabled").each(function() {
                        if ($(this).prop("checked")) formData.append($(this).attr("name"), $(this).val());
                    });

                    {$submitScript}
                    callAjax(
                        '{$submitUrl}',
                        formData,
                        function(result) {
                            \$this.prop("disabled", false);
                            \$this.html("[:dtmodify:Modify:]");
                            if (result.data.result != true) {
                                alert("[:dtmodify:Failed to modify. Please try again.:]");
                                return;
                            }
                            noti("[:dtmodify:Successfully modified.:]", "success");
                            sfdt['{$this->tableId}'].ajax.reload(null, false);
                            $("#sfdt-modal-{$this->tableId}-{$this->btnId}").modal("hide");
                        },
                        function(e) {
                            console.log(e);
                            \$this.prop("disabled", false);
                            \$this.html("[:dtmodify:Modify:]");
                            alert("[:An error occurred while calling the server. Please try again later.:]");
                        }
                    )
                });

                $("#sfdt-modal-{$this->tableId}-{$this->btnId} .form-floating select").filter(function() {
                    return $(this).find("option").length > 12;
                }).each(function() {
                    $(this).closest(".form-floating").removeClass("form-floating").addClass("sfdt-floating-select2");
                    if (sfGetTheme() === 'dark') {
                        $(this).select2({theme:'bootstrap5-dark', dropdownParent:$("#sfdt-modal-{$this->tableId}-{$this->btnId}"), dropdownAutoWidth:true});
                    } else {
                        $(this).select2({theme:'bootstrap5', dropdownParent:$("#sfdt-modal-{$this->tableId}-{$this->btnId}"), dropdownAutoWidth:true});
                    }
                });

                {$this->customScript}
            EOD;

        $modifyHtml = <<<EOD

            <!-- DataTable - modify modal for Table Id {$this->tableId} -->
            <div class="modal fade" tabindex="-1" id="sfdt-modal-{$this->tableId}-{$this->btnId}" aria-labelledby="Modify" aria-describedby="Modify" aria-hidden="true" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-modify">
                            <h5 class="modal-title">[:dtmodaltitle:Modify:]</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="sfdt-form-{$this->tableId}-{$this->btnId}">
                            {$hiddenHtml}
                            {$layoutHtml}
                            </form>
                        </div>
                        <div class="modal-footer d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">[:dt:Cancel:]</button>
                            <button type="button" class="btn btn-sm btn-primary" id="sfdt-btn-{$this->tableId}-{$this->btnId}-modify-submit">[:dtmodify:Modify:]</button>
                        </div>
                    </div>
                </div>
            </div>
            EOD;

        return [ "html" => $modifyHtml, "drawCallbackScript" => "", "script" => $modifyScript ];
    }

    private function _codeDelete($ajaxUrl, $deliverParameters)
    {
        if (!$this->keyParams) L::system("[:dt:Parameter for delete({$this->btnId}) is not defined.:]");
        $paramScript = "";
        foreach($this->keyParams as $alias) {
            $paramScript .= "param['{$alias}'] = $(this).data('{$alias}');\n        ";
        }
        $submitUrl = $this->submitUrl | $ajaxUrl;
        if ($submitUrl === $ajaxUrl) $paramScript .= "param['sfdtPageMode'] = 'submitForDelete';\n        ";
        $paramScript .= "param['sfdtBtnId'] = '{$this->btnId}';\n        ";

        if ($deliverParameters) {
            foreach($deliverParameters as $k => $v) {
                $paramScript .= "param['{$k}'] = '{$v}';\n        ";
            }
        }

        $confirmMsg = "[:dtdelete:Are you sure you want to delete? Deleted data cannot be recovered.:]";
        if ($this->confirmMsg) $confirmMsg = $this->confirmMsg;

        $script = <<<EOD

                $(document).on("click", "button[data-button-id='{$this->btnId}']", function() {
                    let param = {};
                    {$paramScript}
                    confirm("{$confirmMsg}", function() {
                        callAjax(
                            '{$submitUrl}',
                            param,
                            function(result) {
                                if (result.data.result != true) {
                                    alert("[:dtdelete:Failed to delete. Please try again.:]");
                                    return;
                                }
                                {$this->readCallbackScript}
                                noti("[:dtdelete:Successfully deleted.:]", "success");
                                sfdt['{$this->tableId}'].ajax.reload(null, false);
                            }
                        )
                    });
                });

                {$this->customScript}
            EOD;

        return [ "html" => "", "drawCallbackScript" => "", "script" => $script ];
    }
}

class DataTablesColumn
{
    private $tableId;
    private $alias;
    private $data;
    private $searchColumn;  // for database field name (if different from alias)
                            // select {$columnQuery} as {$alias} from table...
    private $title;
    private $class;
    private $render;
    private $type;          // for datatables column type
    private $displayType;   // for detail view display type
    private $zoomColumn;
    private $searchable;
    private $orderable;
    private $invisible;
    private $renderButtons;

    //private $buttons;
    //private $detailButtonInfo;
    //private $modifyButtonInfo;

    public function __construct($tableId, $alias)
    {
        $this->tableId = $tableId;
        $this->alias = $alias;
        $this->data = $alias;
        $this->searchColumn = $alias;
        $this->title = "";
        $this->class = [];
        $this->render = "";
        $this->type = "";
        $this->displayType = "string";
        $this->zoomColumn = "";
        $this->searchable = true;
        $this->orderable = true;
        $this->invisible = false;
        $this->renderButtons = [];

        //$this->buttons = [];
        //$this->detailButtonInfo = [];
        //$this->modifyButtonInfo = [];
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

    public function searchColumn($searchColumn = null)
    {
        if ($searchColumn === null) return $this->searchColumn;
        $this->searchColumn = $searchColumn;
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

    public function zoom($zoomColumnAlias)
    {
        $this->displayType = "zoom";
        $this->zoomColumn = $zoomColumnAlias;
        return $this;
    }

    public function zoomColumn() { return $this->zoomColumn; }

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

    public function invisible($invisible = null)
    {
        if ($invisible === null) return $this->invisible;
        $this->invisible = $invisible;
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

    public function dateX($format = "YYYY-MM-DD")
    {
        $this->type = "string";
        $this->displayType = "datetime:{$format}";
        $this->class("text-center");
        $this->render = "function(data, type, row, meta) { return data.formatDateTime('{$format}'); }";
        return $this;
    }

    public function time($format = "HH:mm:ss")
    {
        $this->type = "string";
        $this->displayType = "datetime:{$format}";
        $this->class("text-center");
        $this->render = "DataTable.render.date('" . $format . "')";
        return $this;
    }

    public function timeX($format = "HH:mm:ss")
    {
        $this->type = "string";
        $this->displayType = "datetime:{$format}";
        $this->class("text-center");
        $this->render = "function(data, type, row, meta) { return data.formatDateTime('{$format}'); }";
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

    public function datetimeX($format = "YYYY-MM-DD HH:mm:ss")
    {
        $this->type = "string";
        $this->displayType = "datetime:{$format}";
        $this->class("text-center");
        $this->render = "function(data, type, row, meta) { return data.formatDateTime('{$format}'); }";
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
    public function noKeyCursor() { return $this->class("sfdt-no-keys-cursor"); }

    public function noSearchable() { $this->searchable = false; return $this; }
    public function noOrderable() { $this->orderable = false; return $this; }

    public function renderButtons() { return $this->renderButtons; }

    public function button($btnId) {
        if (!array_key_exists($btnId, $this->renderButtons)) $this->renderButtons[$btnId] = new DataTablesRenderButton($this->tableId, $this->alias, $btnId);
        return $this->renderButtons[$btnId];
    }

    public function buttonDetail($btnId)
    {
        $this->button($btnId)->typeDetailVlew()->title("[:dtdetail:View:]")->class("btn-detail");
        return $this->button($btnId);
    }

    public function buttonModify($btnId)
    {
        $this->button($btnId)->typeModify()->title("[:dtmodify:Modify:]")->class("btn-modify");
        return $this->button($btnId);
    }

    public function buttonDelete($btnId)
    {
        $this->button($btnId)->typeDelete()->title("[:dtdelete:Delete:]")->class("btn-delete");
        return $this->button($btnId);
    }
}

class DataTablesCustomSearch
{
    const TYPE_STRING           = 1001;
    const TYPE_SELECT           = 1002;
    const TYPE_DATERANGE        = 1003;
    const TYPE_DATETIMERANGE    = 1004;
    const TYPE_NUMBERRANGE      = 1005;

    private $tableId;
    private $alias;
    private $title;
    private $type;
    private $controlOption;
    private $controlStyle;
    private $enableInputMask;
    private $options;
    private $numberRangeOption;
    private $select2;
    private $exColumnQuery;     // for database field name for search(where statement)
    private $likeSearch;
    private $autoSubmit;

    public function __construct($tableId, $alias)
    {
        $this->tableId = $tableId;
        $this->alias = $alias;
        $this->title = "";
        $this->type = self::TYPE_STRING;
        $this->controlOption = [];
        $this->controlStyle = [];
        $this->enableInputMask = false;
        $this->numberRangeOption = [ "min" => 0, "max" => 100 ];
        $this->options = [];
        $this->select2 = false;
        $this->exColumnQuery = "";
        $this->likeSearch = true;
        $this->autoSubmit = true;
    }

    public function alias() { return $this->alias; }
    public function query($exColumnQuery = null)
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
        $this->controlOption[] = $option;
        return $this;
    }

    public function mask($maskOption)
    {
        $this->enableInputMask = true;
        return $this->controlOption("data-inputmask=\"'mask':'{$maskOption}'\"");
    }

    public function isEnableInputMask() { return $this->enableInputMask; }

    public function controlStyle($style = null)
    {
        if ($style === null) return $this->controlStyle;
        $this->controlStyle[] = $style;
        return $this;
    }

    public function widthPx($px) { $this->controlStyle[] = "width:{$px}px;"; return $this; }
    public function widthRem($rem) { $this->controlStyle[] = "width:{$rem}rem;"; return $this; }
    public function widthEm($em) { $this->controlStyle[] = "width:{$em}em;"; return $this; }
    public function widthPercent($p) { $this->controlStyle[] = "width:{$p}%;"; return $this; }

    public function option($key, $value = null)
    {
        if (!is_string($key) || !array_key_exists($key, $this->options)) L::system("[:dt:DataTablesCustomSearch option() parameter error.:]");
        if ($value === null) return $this->options[$key];
        $this->options[$key] = $value;
        return $this;
    }

    public function options($key = null, $value = null)
    {
        if ($key === null && $value === null) return $this->options;

        if (is_array($key) && $value === null) {
            $this->options = $this->options + $key;
        } else {
            $this->options[$key] = $value;
        }
        return $this;
    }

    public function equalSearch()
    {
        $this->likeSearch = false;
        return $this;
    }

    public function noAutoSubmit()
    {
        $this->autoSubmit = false;
        return $this;
    }

    public function numberRange($min, $max)
    {
        $this->type(self::TYPE_NUMBERRANGE);
        $this->numberRangeOption = [ "min" => $min, "max" => $max ];
        return $this;
    }

    public function string() { return $this->type(self::TYPE_STRING); }
    public function select() { $this->equalSearch(); return $this->type(self::TYPE_SELECT); }
    public function select2() { $this->equalSearch(); $this->select2 = true; return $this->type(self::TYPE_SELECT); }
    public function dateRange() { return $this->type(self::TYPE_DATERANGE); }
    public function datetimeRange() { return $this->type(self::TYPE_DATETIMERANGE); }

    public function isString() { return $this->type == self::TYPE_STRING; }
    public function isSelect() { return $this->type == self::TYPE_SELECT; }
    public function isSelect2() { return $this->type == self::TYPE_SELECT && $this->select2; }
    public function isDateRange() { return $this->type == self::TYPE_DATERANGE; }
    public function isDatetimeRange() { return $this->type == self::TYPE_DATETIMERANGE; }
    public function isNumberRange() { return $this->type == self::TYPE_NUMBERRANGE; }
    public function isEqualSearch() { return !$this->likeSearch; }
    public function isAutoSubmit() { return $this->autoSubmit; }

    public function numberRangeOption() { return $this->numberRangeOption; }

    public function getAll()
    {
        return [
            "alias"             => $this->alias,
            "type"              => $this->type,
            "controlOption"     => $this->controlOption,
            "controlStyle"      => $this->controlStyle,
            "option"            => $this->options,
            "numberRangeOption" => $this->numberRangeOption,
            "select2"           => $this->select2
        ];
    }
}

class DataTablesExtraButton
{
    private $tableId;
    private $btnId;
    private $title;
    private $class;
    private $option;
    private $action;
    private $tooltip;
    private $addRecord;       // add new record

    public function __construct($tableId, $btnId)
    {
        $this->tableId = $tableId;
        $this->btnId = $btnId;
        $this->title = $btnId;
        $this->class = [];
        $this->action = "";
        $this->option = [];
        $this->tooltip = "";
        $this->addRecord = null;
    }

    public static function getInstance($tableId, $btnId)
    {
        static $instance;
        if (!isset($instance[$tableId][$btnId])) $instance[$tableId][$btnId] = new DataTablesExtraButton($tableId, $btnId);
        return $instance[$tableId][$btnId];
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

    public function addRecord()
    {
        if (!$this->addRecord) $this->addRecord = new DataTablesAddRecord($this->tableId, $this->btnId);
        return $this->addRecord;
    }

    public function isAddRecord() { return $this->addRecord !== null; }

    public function htmlCode()
    {
        $class = implode(" ", $this->class);
        $option = implode(" ", $this->option);
        return "<button type=\"button\" id=\"{$this->btnId}\" class=\"btn btn-sm {$class}\"{$option}>{$this->title}</button>";
    }

    public function drawCallbackScript()
    {
        if (!$this->tooltip()) return "";
        $tp = str_replace('"', '\"', $this->tooltip());
        return <<<EOD

                $('#{$this->btnId}').data("bs-toggle", "tooltip").attr("title", "{$tp}").tooltip();
        EOD;
    }
}

class DataTablesEditColumn
{
    private $tableId;
    private $editId;
    private $alias;
    private $title;
    private $class;
    private $required;
    private $type;
    private $options;
    private $controlOption;
    private $controlStyle;
    private $enableInputMask;
    private $defaultValue;
    private $comment;
    private $readonly;

    public function __construct($tableId, $editId, $alias)
    {
        $this->tableId = $tableId;
        $this->editId = $editId;
        $this->alias = $alias;
        $this->title = "";
        $this->class = [];
        $this->required = false;
        $this->type = "";
        $this->defaultValue = "";
        $this->controlOption = [];
        $this->controlStyle = [];
        $this->enableInputMask = false;
        $this->options = [];
        $this->comment = "";
        $this->readonly = false;
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

    public function required() { $this->required = true; return $this; }

    public function type($type = null)
    {
        if ($type === null) return $this->type;
        $this->type = $type;
        return $this;
    }

    public function controlOption($option = null)
    {
        if ($option === null) return $this->controlOption;
        $this->controlOption[] = $option;
        return $this;
    }

    public function mask($maskOption)
    {
        $this->enableInputMask = true;
        return $this->controlOption("data-inputmask=\"'mask':'{$maskOption}'\"");
    }

    public function isEnableInputMask() { return $this->enableInputMask; }

    public function controlStyle($style = null)
    {
        if ($style === null) return $this->controlStyle;
        $this->controlStyle[] = $style;
        return $this;
    }

    public function readonly() { $this->readonly = true; return $this; }

    public function widthPx($px) { $this->controlStyle[] = "width:{$px}px;"; return $this; }
    public function widthRem($rem) { $this->controlStyle[] = "width:{$rem}rem;"; return $this; }
    public function widthEm($em) { $this->controlStyle[] = "width:{$em}em;"; return $this; }
    public function widthPercent($p) { $this->controlStyle[] = "width:{$p}%;"; return $this; }

    public function heightPx($px) { $this->controlStyle[] = "height:{$px}px;"; return $this; }
    public function heightRem($rem) { $this->controlStyle[] = "height:{$rem}rem;"; return $this; }
    public function heightEm($em) { $this->controlStyle[] = "height:{$em}em;"; return $this; }
    public function heightPercent($p) { $this->controlStyle[] = "height:{$p}%;"; return $this; }

    public function hidden($defaultValue = null)    { $this->type("hidden");   if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function textarea($defaultValue = null)  { $this->type("textarea"); if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function text($defaultValue = null)      { $this->type("text");     if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function number($defaultValue = null)    { $this->type("number");   if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function email($defaultValue = null)     { $this->type("email");    if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function url($defaultValue = null)       { $this->type("url");      if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function date($defaultValue = null)      { $this->type("date");     if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function time($defaultValue = null)      { $this->type("time");     if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function month($defaultValue = null)     { $this->type("month");    if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function password($defaultValue = null)  { $this->type("password"); if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function tel($defaultValue = null)       { $this->type("tel");      if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function file($defaultValue = null)      { $this->type("file");     if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function checkbox($defaultValue = null)  { $this->type("checkbox"); if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function radio($defaultValue = null)     { $this->type("radio");    if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }
    public function select($defaultValue = null)    { $this->type("select");   if ($defaultValue !== null) $this->defaultValue($defaultValue); return $this; }

    public function defaultValue($value = null)
    {
        if ($value === null) return $this->defaultValue;
        $this->defaultValue = $value;
        return $this;
    }

    public function option($value, $text = null)
    {
        if (!array_key_exists($value, $this->options)) L::system("[:dtedit:DataTablesEditColumn {$this->editId} option parameter error.:]");
        if ($text === null) return $this->options[$value];
        $this->options[$value] = $text;
        return $this;
    }

    public function options($options = null)
    {
        if ($options === null) return $this->options;
        if (!is_array($options)) L::system("[:dtedit:DataTablesEditColumn {$this->editId} options parameter error.:]");

        foreach($options as $value => $text) {
            $this->options[$value] = $text;
        }
        return $this;
    }

    public function comment($comment = null)
    {
        if ($comment === null) return $this->comment;
        $this->comment = $comment;
        return $this;
    }

    public function html($tableColumns)
    {
        $class = implode(" ", $this->class);    if ($class) $class = " " . $class;
        $controlOption = "";    if ($this->controlOption) $controlOption = " " . implode(" ", $this->controlOption);
        $controlStyle = "";     if ($this->controlStyle) $controlStyle = " style=\"" . implode(" ", $this->controlStyle) . "\"";
        $required = "";         if ($this->required) $required = " required";
        $title = $this->title;  if (!$title) $title = $tableColumns[$this->alias]->title();
        $readonly = "";         if ($this->readonly) { $readonly = " readonly"; }

        $commentHtml = "";
        if ($this->readonly) {
            $commentHtml = <<<EOD

                                    <small class="text-body-secondary ms-2">[:dtedit:Read only.:]</small>
                EOD;
        } elseif ($this->comment) {
                $commentHtml = <<<EOD

                                        <small class="text-body-secondary ms-2">{$this->comment}</small>
                    EOD;
        }

        $html = "";
        switch($this->type) {
            case "hidden" :
                $html = <<<EOD

                                    <input type="hidden" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}" name="{$this->alias}" value="{$this->defaultValue}"{$controlOption}>
                EOD;
                break;
            case "text" :
            case "number" :
            case "email" :
            case "url" :
            case "date" :
            case "time" :
            case "month" :
            case "password" :
            case "tel" :
                $html = <<<EOD

                                    <div class="col-auto">
                                        <div class="form-floating{$readonly}">
                                            <input type="{$this->type}" class="form-control{$class}" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}" name="{$this->alias}" placeholder="" value="{$this->defaultValue}"{$required}{$readonly}{$controlOption}{$controlStyle} autocomplete="off">
                                            <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}">{$title}</label>
                                        </div>
                                        {$commentHtml}
                                    </div>
                    EOD;
                break;
            case "textarea" :
                $html = <<<EOD

                                    <div class="col-auto">
                                        <div class="form-floating{$readonly}">
                                            <textarea type="{$this->type}" class="form-control{$class}" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}" name="{$this->alias}" placeholder="" {$required}{$readonly}{$controlOption}{$controlStyle} autocomplete="off">{$this->defaultValue}</textarea>
                                            <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}">{$title}</label>
                                        </div>
                                        {$commentHtml}
                                    </div>
                    EOD;
                break;
            case "file" :
                $html = <<<EOD

                                    <div class="col-auto">
                                        <div class="sfdt-floating-file{$required}{$readonly}">
                                            <input type="file" class="form-control{$class}" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}" name="{$this->alias}" placeholder="" value="{$this->defaultValue}"{$required}{$readonly}{$controlOption}{$controlStyle} autocomplete="off">
                                            <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}">{$title}</label>
                                        </div>
                                        {$commentHtml}
                                    </div>
                    EOD;
                break;
            case "checkbox" :
                $htmlSub = "";
                $disabled = ""; if ($this->readonly) $disabled = " disabled";
                $idx = 1;
                if (!is_array($this->options)) L::system("[:dtedit:DataTablesEditColumn {$this->editId} {$this->alias} checkbox options not defined.:]");
                if (count($this->options) > 1) $name = "{$this->alias}[]"; else $name = "{$this->alias}";
                foreach($this->options as $value => $text) {
                    if ($value === $this->defaultValue) $checked = " checked"; else $checked = "";
                    $htmlSub .= <<<EOD

                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-{$idx}" name="{$name}" value="{$value}"{$checked}{$disabled}{$controlOption}{$controlStyle}>
                                                    <label class="form-check-label" for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-{$idx}">{$text}</label>
                                                </div>
                        EOD;
                    $idx++;
                }
                $html = <<<EOD

                                    <div class="col-auto me-3">
                                        <div class="sfdt-floating-checkbox{$required}{$readonly}">
                                            <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-1">{$title}</label>
                                            {$htmlSub}
                                        </div>
                                        {$commentHtml}
                                    </div>
                    EOD;
                break;
            case "radio" :
                $htmlSub = "";
                $disabled = ""; if ($this->readonly) $disabled = " disabled";
                $idx = 1;
                if (!is_array($this->options)) L::system("[:dtedit:DataTablesEditColumn {$this->editId} {$this->alias} radio options not defined.:]");
                foreach($this->options as $value => $text) {
                    if ($value === $this->defaultValue) $checked = " checked"; else $checked = "";
                    $htmlSub .= <<<EOD

                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-{$idx}" name="{$this->alias}" value="{$value}"{$required}{$disabled}{$checked}{$controlOption}{$controlStyle}>
                                                    <label class="form-check-label" for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-{$idx}">{$text}</label>
                                                </div>
                        EOD;
                    $idx ++;
                }
                $html = <<<EOD

                                    <div class="col-auto me-3">
                                        <div class="sfdt-floating-radio{$required}{$readonly}">
                                            <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-1">{$title}</label>
                                            {$htmlSub}
                                        </div>
                                        {$commentHtml}
                                    </div>
                    EOD;
                break;
            case "select" :
                if (!is_array($this->options)) L::system("[:dtedit:DataTablesEditColumn {$this->editId} {$this->alias} select options not defined.:]");
                $optionArr = [];
                if ($this->defaultValue) {
                    $optionArr[] = "<option value=''>[:dtedit:Select:]</option>";
                } else {
                    $optionArr[] = "<option value='' selected>[:dtedit:Select:]</option>";
                }
                foreach($this->options as $value => $text) {
                    if ($this->defaultValue != "" && $value === $this->defaultValue) $selected = " selected"; else $selected = "";
                    $optionArr[] = "<option value='{$value}'{$selected}>{$text}</option>";
                }
                $optionHtml = implode("\n                            ", $optionArr);
                if ($this->readonly) {
                    $html = <<<EOD

                                            <input type="hidden" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}" name="{$this->alias}" value="{$this->defaultValue}" data-readonly="true">
                                            <div class="d-none">
                                                <select id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-readonly-select">
                                                {$optionHtml}
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <div class="form-floating{$readonly}">
                                                    <input type="text" class="form-control{$class}" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}-readonly-text" placeholder="" value=""{$required}{$readonly}{$controlOption}{$controlStyle} autocomplete="off">
                                                    <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}">{$title}</label>
                                                </div>
                                                {$commentHtml}
                                            </div>
                        EOD;
                } else {

                    $html = <<<EOD

                                            <div class="col-auto">
                                                <div class="form-floating{$readonly}">
                                                    <select class="form-select" id="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}" name="{$this->alias}"{$required}{$controlOption}{$controlStyle}>
                                                    {$optionHtml}
                                                    </select>
                                                    <label for="sfdt-edit-{$this->tableId}-{$this->editId}-{$this->alias}">{$title}</label>
                                                </div>
                                                {$commentHtml}
                                            </div>
                        EOD;
                }
                break;
        }

        return $html;
    }
}

class DataTablesAddRecord
{
    private $tableId;
    private $btnId;

    private $layout;
    private $columns;
    private $submitFunction;

    private $customScript;

    public function __construct($tableId, $btnId)
    {
        $this->tableId = $tableId;
        $this->btnId = $btnId;
        $this->layout = [];
        $this->columns = [];
        $this->submitFunction = "";
        $this->customScript = "";
    }

    public function layout($layout = null)
    {
        if ($layout === null) return $this->layout;
        $this->layout = $layout;
        return $this;
    }

    public function isEnableInputMask()
    {
        foreach($this->columns as $alias => $column) {
            if ($column->isEnableInputMask()) return true;
        }
        return false;
    }

    public function submitFunction($submitFunction = null)
    {
        if ($submitFunction === null) return $this->submitFunction;
        $this->submitFunction = $submitFunction;
        return $this;
    }

    public function column($alias)
    {
        if (!isset($this->columns[$alias])) $this->columns[$alias] = new DataTablesEditColumn($this->tableId, $this->btnId, $alias);
        return $this->columns[$alias];
    }

    public function customScript($customScript = null)
    {
        if ($customScript === null) return $this->customScript;
        $this->customScript = $customScript;
        return $this;
    }

    public function html($tableColumns)
    {
        $controlHtml = "";
        if (!$this->columns) L::system("[:dtaddrecord:No columns defined for DataTablesAddRecord.:]");
        if (!$this->layout) L::system("[:dtaddrecord:No layout defined for DataTablesAddRecord.:]");
        $existFile = false;
        foreach($this->layout as $item) {
            if ($item === '---') {
                $controlHtml .= <<<EOD

                                <hr class="mb-3">
                EOD;
                continue;
            }
            $controlHtml .= <<<EOD

                                <div class="row mb-4">
                EOD;
            if (is_array($item)) {
                foreach($item as $alias) {
                    $controlHtml .= $this->column($alias)->html($tableColumns);
                    if ($this->column($alias)->type() === "file") $existFile = true;
                }
            } else {
                $controlHtml .= $this->column($item)->html($tableColumns);
                if ($this->column($item)->type() === "file") $existFile = true;
            }

            $controlHtml .= <<<EOD

                                </div>
                EOD;
        }

        $multipart = "";
        if ($existFile) $multipart = " enctype=\"multipart/form-data\"";
        $html = <<<EOD
            <!-- DataTable - add record modal for Table Id {$this->tableId} -->
            <div class="modal fade" tabindex="-1" id="sfdt-modal-{$this->tableId}-{$this->btnId}" aria-labelledby="add new record" aria-describedby="add new record" aria-hidden="true" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-add">
                            <h5 class="modal-title">[:dtaddrecord:Add New:]</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="sfdt-form-{$this->tableId}-{$this->btnId}"{$multipart}>
                            {$controlHtml}
                            </form>
                        </div>
                        <div class="modal-footer d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">[:dt:Cancel:]</button>
                            <button type="button" class="btn btn-sm btn-add" id="sfdt-btn-{$this->tableId}-{$this->btnId}-add-submit">[:dtaddrecord:Submit:]</button>
                        </div>
                    </div>
                </div>
            </div>
            EOD;

        return $html;
    }

    public function drawCallbackScript()
    {
        return <<<EOD

                    $("#{$this->btnId}").click(function() {
                        $("#sfdt-form-{$this->tableId}-{$this->btnId}")[0].reset();
                        $("sfdt-btn-{$this->tableId}-{$this->btnId}-add-submit").prop("disabled", false);
                        $("sfdt-btn-{$this->tableId}-{$this->btnId}-add-submit").html("[:dtaddrecord:Submit:]");
                        $("#sfdt-modal-{$this->tableId}-{$this->btnId}").modal("show");
                    });
            EOD;
    }

    public function script($queryUrl, $deliverParameters)
    {
        $deliverParametersScript = "";
        if ($deliverParameters) {
            foreach($deliverParameters as $k => $v) {
                $deliverParametersScript .= "formData.append(\"{$k}\", \"{$v}\");\n            ";
            }
        }

        return <<<EOD

                $("#sfdt-modal-{$this->tableId}-{$this->btnId} .form-floating select").filter(function() {
                    return $(this).find("option").length > 12;
                }).each(function() {
                    $(this).closest(".form-floating").removeClass("form-floating").addClass("sfdt-floating-select2");
                    if (sfGetTheme() === 'dark') {
                        $(this).select2({theme:'bootstrap5-dark', dropdownParent:$("#sfdt-modal-{$this->tableId}-{$this->btnId}"), dropdownAutoWidth:true});
                    } else {
                        $(this).select2({theme:'bootstrap5', dropdownParent:$("#sfdt-modal-{$this->tableId}-{$this->btnId}"), dropdownAutoWidth:true});
                    }
                });

                // add record submit
                $("#sfdt-btn-{$this->tableId}-{$this->btnId}-add-submit").click(function() {
                    let \$this = $(this);
                    \$this.prop("disabled", true);
                    \$this.html('<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span> <span class="align-middle">[:dtaddrecord:Submit:]</span>');

                    if (!$("#sfdt-form-{$this->tableId}-{$this->btnId}")[0].checkValidity()) {
                        $("#sfdt-form-{$this->tableId}-{$this->btnId}")[0].reportValidity()
                        \$this.prop("disabled", false);
                        \$this.html("[:dtaddrecord:Submit:]");
                        return false;
                    }
                    let formData = new FormData($("#sfdt-form-{$this->tableId}-{$this->btnId}")[0]);
                    formData.append("sfdtPageMode", "submitForAddRecord");
                    formData.append("sfdtBtnId", "{$this->btnId}");
                    {$deliverParametersScript}
                    callAjax(
                        '{$queryUrl}',
                        formData,
                        function(result) {
                            \$this.prop("disabled", false);
                            \$this.html("[:dtaddrecord:Submit:]");
                            if (result.data.result != true) {
                                alert("[:dtaddrecord:Failed to add record. Please try again.:]");
                                return;
                            }
                            noti("[:dtaddrecord:Record added successfully.:]");
                            sfdt['{$this->tableId}'].ajax.reload();
                            $("#sfdt-modal-{$this->tableId}-{$this->btnId}").modal("hide");
                        },
                        function(e) {
                            console.log(e);
                            \$this.prop("disabled", false);
                            \$this.html("[:dtaddrecord:Submit:]");
                            alert("[:An error occurred while calling the server. Please try again later.:]");
                        }
                    )
                });

                {$this->customScript}
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
    private $deliverParameters;
    private $drawCallbackScript;

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
    private $columnDefaultClass;

    private $layoutCustomSearch;
    private $layoutList;
    private $layoutDetail;
    private $layoutModify;
    private $modifyColumns;

    private $columnConfigSaveFunction;
    private $columnConfigLoadFunction;

    private $onePage;
    private $querySearchSQL;
    private $querySearchBind;

    private $jsScript;
    private $jsScriptOnReady;

    private $onLoadInitSearch;

    // output for template
    private $html;

    public function __construct($tableId)
    {
        $chk = preg_match("/^[a-z][a-z0-9]*$/", $tableId);
        if (!$chk) L::system("[:dt:It must be made only of alphabet(lower case) and numbers (the first letter is an alphabet).:]");

        $this->tableId = $tableId;
        $this->containerOption = "";
        $this->disableTooltip = false;
        $this->deliverParameters = [];

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

        $translation = Translation::getInstance();
        $this->language = $translation->getTranslationLang();
        $this->ajaxUrl = $_SERVER['REQUEST_URI'];
        $this->columns = [];
        $this->customSearch = [];
        $this->defaultOrder = [];
        $this->columnDefaultClass = [];
        $this->drawCallbackScript = [];

        $this->layoutCustomSearch = [];
        $this->layoutList = [];
        $this->layoutDetail = [];
        $this->layoutModify = [];
        $this->modifyColumns = [];

        $this->columnConfigSaveFunction = "";
        $this->columnConfigLoadFunction = "";

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

        $this->jsScript = [];
        $this->jsScriptOnReady = [];

        $this->onLoadInitSearch = true;
    }

    public function containerOption($option) { $this->containerOption = $option; return $this; }


    // Defines parameters that must be passed(deliver) when calling the ajax module. (for onePage)
    public function deliverParameter($k, $v) { $this->deliverParameters[$k] = $v; }

    /*
     * table options
     */
    public function options($options) { $this->options = array_merge($this->options, $options); return $this; }
    public function pageLength($length) { $this->options["pageLength"] = intval($length); return $this; }
    public function lengthMenu($menu) { $this->options["lengthMenu"] = $menu; return $this; }
    public function disableStateSave() { $this->options["stateSave"] = false; return $this; }
    public function disableColReorder() { $this->options["colReorder"] = false; return $this; }
    public function ajaxUrl($url) { $this->ajaxUrl = $url; return $this; }
    public function disableTooltip() { $this->disableTooltip = true; return $this; }
    public function orderBy($alias, $dir) { $this->defaultOrder[] = [ "alias" => $alias, "dir" => $dir ]; return $this; }
    public function disableOrdering() { $this->options["ordering"] = false; return $this; }
    public function keyCursor() { $this->options["keys"] = [ "blurable" => true, "columns" => ':not(.sfdt-no-keys-cursor)' ]; return $this; }

    /*
     * callback script
     */
    public function drawCallbackScript($script) { $this->drawCallbackScript[] = $script; return $this; }

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

    public function searchKeep() { $this->onLoadInitSearch = false; return $this; }

    /*
     * extra buttons
     */
    public function extraButton($btnId)
    {
        if (!isset($this->extraButtons[$btnId])) $this->extraButtons[$btnId] = new DataTablesExtraButton($this->tableId, $btnId);
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
        if (!isset($this->columns[$alias])) $this->columns[$alias] = new DataTablesColumn($this->tableId, $alias);
        return $this->columns[$alias];
    }

    public function columns() { return $this->columns; }

    public function columnDefaultClass($class, ...$classes)
    {
        $this->columnDefaultClass[] = $class;
        if ($classes) $this->columnDefaultClass = array_merge($this->columnDefaultClass, $classes);
        return $this;
    }

    // custom search, $aliases : array of column alias
    public function customSearch($alias) {
        if (!isset($this->customSearch[$alias])) $this->customSearch[$alias] = new DataTablesCustomSearch($this->tableId, $alias);
        return $this->customSearch[$alias];
    }

    // layout for custom search, $layout : array of column alias
    public function layoutCustomSearch($layout) { $this->layoutCustomSearch = $layout; return $this; }

    // columns order for list, $layout : array of column alias
    public function layoutList($layout = null) {
        if ($layout === null) return $this->layoutList;
        $this->layoutList = $layout;
        return $this;
    }

    // columns order for detail, $layout : array of column alias
    public function layoutDetail($layout, $id = 'detail') { $this->layoutDetail[$id] = $layout; return $this; }

    // columns order for modify, $layout : array of column alias
    public function layoutModify($layout, $editId = 'modify') { $this->layoutModify[$editId] = $layout; return $this; }
    public function modifyColumn($alias, $editId = 'modify')
    {
        if (!isset($this->modifyColumns[$editId][$alias])) $this->modifyColumns[$editId][$alias] = new DataTablesEditColumn($this->tableId, $editId, $alias);
        return $this->modifyColumns[$editId][$alias];
    }

    // column config load/save function
    public function columnConfigFunction($loadFunction, $saveFunction) { $this->columnConfigLoadFunction = $loadFunction; $this->columnConfigSaveFunction = $saveFunction; return $this; }

    // Additional JavaScript code to be executed after DataTables initialization
    public function jsScript($script) { $this->jsScript[] = $script; return $this; }
    public function jsScriptOnReady($script) { $this->jsScriptOnReady[] = $script; return $this; }

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
            $html .= <<<EOD

                <!-- DataTables Column Config Modal -->
                <div class="modal fade" tabindex="-1" id="sfdt-modal-column-config" aria-labelledby="Column Config" aria-describedby="Column Config" aria-hidden="true" aria-modal="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-body" id="sfdt-modal-column-config-body"></div>
                            <div class="modal-footer d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-sm btn-reset" id="sfdt-btn-column-config-reset">[:dt:Reset:]</button>
                                    <button type="button" class="btn btn-sm btn-save" id="sfdt-btn-column-config-save"><i class="bi bi-floppy"></i></button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" aria-label="Close">[:dt:Cancel:]</button>
                                    <button type="button" class="btn btn-sm btn-primary" id="sfdt-btn-column-config-apply" disabled>[:dt:Apply:]</button>
                                </div>
                            </div>
                        </div>
                        <div id="sfdt-modal-column-config-save">
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

        $deliverParameters = "";
        if ($this->deliverParameters) {
            foreach($this->deliverParameters as $k => $v) {
                $deliverParameters .= "data.{$k} = '{$v}';\n            ";
            }
        }

        return <<<EOD

                    function(data, callback, settings) {
                        {$scriptCustomExSearch}
                        {$deliverParameters}
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
        $html = "";
        $drawCallbackScript = "";
        $script = "";
        foreach($this->columns as $alias => $column) {
            $c = [ "name" => $alias ];
            if ($column->data())    $c["data"] = $column->data();
            if ($column->title())   $c["title"] = $column->title();

            $sumClass = [];
            if ($this->columnDefaultClass) $sumClass = $this->columnDefaultClass;
            if ($column->class()) $sumClass = array_merge($sumClass, $column->class());

            if ($sumClass) {
                $class = [];
                $onlyClass = [ "align" => "", "wrap" => "", "case" => "", "fs" => "", "fw" => "", "fst" => "", "deco" => "" ];
                foreach($sumClass as $cls) {
                    if (in_array($cls, [ "text-start", "text-end", "text-center" ])) $onlyClass["align"] = $cls;
                    else if (in_array($cls, [ "text-wrap", "text-nowrap" ])) $onlyClass["wrap"] = $cls;
                    else if (in_array($cls, [ "text-lowercase", "text-uppercase", "text-capitalize" ])) $onlyClass["case"] = $cls;
                    else if (in_array($cls, [ "fs-1", "fs-2", "fs-3", "fs-4", "fs-5", "fs-6" ])) $onlyClass["fs"] = $cls;
                    else if (in_array($cls, [ "fw-bold", "fw-bolder", "fw-semibold", "fw-medium", "fw-normal", "fw-light", "fw-lighter" ])) $onlyClass["fw"] = $cls;
                    else if (in_array($cls, [ "fst-italic", "fst-normal" ])) $onlyClass["fst"] = $cls;
                    else if (in_array($cls, [ "text-decoration-underline", "text-decoration-line-through", "text-decoration-none" ])) $onlyClass["deco"] = $cls;
                    else $class[] = $cls;
                }
                foreach($onlyClass as $k => $v) if ($v) $class[] = $v;
                if ($class) $c["className"] = implode(" ", $class);
            }

            if ($column->type())    $c["type"] = $column->type();
            $c["searchable"] = $column->searchable();
            $c["orderable"] = $column->orderable();
            $c["visible"] = !$column->invisible();

            if ($column->renderButtons()) {
                $c["render"] = "rendering-{$alias}";
                $render[$alias] = "\n                function(data, type, row, meta) {\n                    return `\n                        ";
                foreach($column->renderButtons() as $btnId => $rb) {
                    if ($rb->render()) $render[$alias] .= trim($rb->render()) . "\n                        ";
                    $rbCode = $rb->code($this->columns, $this->ajaxUrl, $this->deliverParameters);
                    $html .= $rbCode["html"];
                    $drawCallbackScript .= $rbCode["drawCallbackScript"];
                    $script .= $rbCode["script"];
                }
                $render[$alias] .= "`;\n                }";
            } else if ($column->render()) {
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
        return [ "list" => $list, "render" => $render, "html" => $html, "drawCallbackScript" => $drawCallbackScript, "script" => $script ];
    }

    private function makeCodeLayout()
    {
        $colReorder = "";
        $ebArr = [];
        $extraButtons = "";

        if ($this->options["colReorder"]) {
            $serverSide = "false";
            if ($this->columnConfigLoadFunction && $this->columnConfigSaveFunction) {
                if (!is_callable([ $this, $this->columnConfigLoadFunction ]) || !is_callable([ $this, $this->columnConfigSaveFunction ])) L::system("[:dt:Column config load/save function is not callable.:]");
                $serverSide = "true";
            }

            $colReorder = <<<EOD
                    ,{
                                            text: '[:dt:Columns:]',
                                            className:'sfdt-btn-open-column-config',
                                            attr: {
                                                'data-table-id': '{$this->tableId}',
                                                'data-server-side': '{$serverSide}',
                                            }
                                        }
                    EOD;
        }

        if ($this->extraButtons) {
            foreach($this->extraButtons as $btnId => $eb) {
                $ebArr[] = $eb->htmlCode();
            }
        }

        if ($ebArr) $extraButtons = "div: { html:`" . implode(" ", $ebArr) . "` },";
        $customSearchOpen = "";
        if ($this->customSearch) $customSearchOpen = ' <button type="button" class="btn btn-sm btn-secondary" id="btn-sfdt-custom-search-detail-collaps" data-bs-toggle="collapse" data-bs-target=".sfdt-custom-search">[:dt:Detail Search:] <i class="fa-regular fa-square-caret-up"></i></button>';

        return <<<EOD

                    {
                        topStart: [
                            'search',
                            function() { return '<button type="button" class="btn btn-sfdt-search-reset" data-table-id="{$this->tableId}"><i class="bi bi-arrow-clockwise"></i></button>{$customSearchOpen}'; }
                        ],
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
                        ]
                    }
            EOD;
    }

    private function makeCodeCustomSearchItem($alias)
    {
        if (!array_key_exists($alias, $this->customSearch)) L::system("[:dt:Custom search item({$alias}) not found.:]");
        $cs = $this->customSearch[$alias];
        $controlOption = ""; if ($cs->controlOption()) $controlOption = " " . implode(" ", $cs->controlOption());
        $controlStyle = ""; if ($cs->controlStyle()) $controlStyle = " style=\"" . implode("; ", $cs->controlStyle()) . "\"";

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

        $autoSumit = $cs->isAutoSubmit() ? " sfdt-custom-search-auto-submit" : "";
        switch($cs->type()) {
            case DataTablesCustomSearch::TYPE_STRING :
                return <<<EOD

                                <div class="sfdt-custom-search-item{$autoSumit}">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="string"{$sfdtDataAlias}{$forEx} autocomplete="off"{$controlOption}{$controlStyle}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_SELECT :
                $option = $cs->options();
                if ($cs->isSelect2() || count($option) > 12) $controlOption .= " data-sfselect2='true'";
                $optionStr = "";
                foreach($option as $key => $value) {
                    $optionStr .= "<option value=\"{$key}\">{$value}</option>";
                }
                return <<<EOD

                                <div class="sfdt-custom-search-item{$autoSumit}">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <select class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}"{$sfdtDataAlias}{$forEx}{$controlOption}{$controlStyle}>
                                        <option value="">[:dtcustomsearch:All:]</option>
                                        {$optionStr}
                                    </select>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_DATERANGE :
                return <<<EOD

                                <div class="sfdt-custom-search-item{$autoSumit}">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="daterange"{$sfdtDataAlias} autocomplete="off"{$forEx}{$controlOption}{$controlStyle}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_DATETIMERANGE :
                return <<<EOD

                                <div class="sfdt-custom-search-item{$autoSumit}">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <input type="search" class="form-control form-control-sm sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="datetimerange"{$sfdtDataAlias} autocomplete="off"{$forEx}{$controlOption}{$controlStyle}>
                                </div>

                    EOD;

            case DataTablesCustomSearch::TYPE_NUMBERRANGE :
                $numberRangeOption = $cs->numberRangeOption();
                return <<<EOD

                                <div class="sfdt-custom-search-item{$autoSumit}">
                                    <label for="sfdt-{$this->tableId}-custom-search-{$alias}">{$title} :</label>
                                    <div class="input-group input-group-sm">
                                        <input type="search" class="form-control sfdt-{$this->tableId}-custom-search" name="sfdt-{$this->tableId}-custom-search-{$alias}" id="sfdt-{$this->tableId}-custom-search-{$alias}" data-sfdt-custom-search-type="numberrange"{$sfdtDataAlias} data-sfdt-numberrange-min="{$numberRangeOption['min']}" data-sfdt-numberrange-max="{$numberRangeOption['max']}"{$forEx} autocomplete="off"{$controlOption}{$controlStyle}>
                                    </div>
                                </div>

                    EOD;
        }
    }

    private function makeCodeCustomSearch()
    {
        if (!$this->customSearch) return [ "html" => "", "isEnableInputMask" => false ];

        if (!$this->layoutCustomSearch) $this->layoutCustomSearch = array_keys($this->customSearch);

        $html = "";
        $noGroup = "";
        $isEnableInputMask = false;
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
                    if ($this->customSearch[$alias]->isEnableInputMask()) $isEnableInputMask = true;
                }
                $html .= <<<EOD

                            <div class="sfdt-custom-search-group">
                                {$item}
                            </div>

                    EOD;
            } else {
                $noGroup .= $this->makeCodeCustomSearchItem($arr);
                if ($this->customSearch[$arr]->isEnableInputMask()) $isEnableInputMask = true;
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
                <div class="sfdt-custom-search mb-3 collapse" data-table-id="{$this->tableId}" data-language="{$this->language}">
                    {$html}
                </div>

            EOD;
        return [ "html" => $html, "isEnableInputMask" => $isEnableInputMask ];
    }

    // build html, js
    public function build()
    {
        // Setting up a branch for one page
        if ($this->onePage) {
            $param = Param::getInstance();
            switch($param->sfdtPageMode) {
                case "data"                 : return $this->opAjax();
                case "submitForAddRecord"   : return $this->opSubmitForAddRecord();
                case "modify"               : return $this->opModify();
                case "submitForModify"      : return $this->opSubmitForModify();
                case "submitForDelete"      : return $this->opSubmitForDelete();
                case "detailView"           : return $this->opDetailView();
                case "columnConfigSave"     : return $this->opColumnConfigSave();
                case "columnConfigLoad"     : return $this->opColumnConfigLoad();
            }
        }

        // Organize options
        $options = $this->options;

        // Replace the parts of PHP's array that cannot be converted directly to JSON.
        $replaceFrom = $replaceTo = [];

        $options["ajax"] = "ajax-function";
        $replaceFrom[] = "\"ajax\": \"ajax-function\"";
        $replaceTo[] = "\"ajax\": {$this->makeCodeAjax()}";

        $options["layout"] = "layout-code";
        $replaceFrom[] = "\"layout\": \"layout-code\"";
        $replaceTo[] = "\"layout\": {$this->makeCodeLayout()}";

        $codeColumns = $this->makeCodeColumns();
        $options["columns"] = $codeColumns["list"];
        if ($this->defaultOrder) {
            foreach($this->defaultOrder as $orderInfo) {
                $idx = array_search($orderInfo["alias"], $this->layoutList);
                if ($idx === false) continue;
                $options["order"][] = [ $idx, $orderInfo["dir"] ];
            }
        }

        if ($this->onLoadInitSearch) {
            $options["stateSaveParams"] = "stateSaveParams-function";
            $replaceFrom[] = "\"stateSaveParams\": \"stateSaveParams-function\"";

            $orderInit = "";
            if ($this->defaultOrder && isset($options["order"]) && $options["order"]) $orderInit = "delete data.order; data.order = " . json_encode($options["order"], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . ";";
            $replaceTo[] = "\"stateSaveParams\": function(settings, data) { data.search.search = ''; for(i=0;i<data.columns.length;i++) { data.columns[i].search.search = ''; } {$orderInit} }";
        }

        if ($this->language == "kr") $options["language"] = [ "url" => "/assets/libs/datatables-2.1.8/i18n/ko.json" ];
        $options["drawCallback"] = "drawCallback-function";

        // Convert the options to a JSON string.
        $optionsStr = json_encode($options, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

        if ($codeColumns["render"]) {
            foreach($codeColumns["render"] as $alias => $to) {
                $replaceFrom[] = "\"rendering-{$alias}\"";
                $replaceTo[] = $to;
            }
        }

        $localStorageScript = "";
        $drawCallbackScript = "";
        $drawCallbackInputMask = false;
        $addRecordHtml = "";        // Action part for add new button
        $addRecordScript = "";      // Script part for add new button
        $customSearchHtml = "";

        if ($this->drawCallbackScript) $drawCallbackScript .= implode("\n", $this->drawCallbackScript);
        if ($codeColumns["drawCallbackScript"]) $drawCallbackScript .= $codeColumns["drawCallbackScript"];

        if ($this->extraButtons) {
            foreach($this->extraButtons as $btnId => $eb) {
                if (!$this->disableTooltip) $drawCallbackScript .= $eb->drawCallbackScript();
                if ($eb->isAddRecord()) {
                    $addRecordHtml = $eb->AddRecord()->html($this->columns);
                    $drawCallbackScript .= $eb->AddRecord()->drawCallbackScript();
                    $addRecordScript .= $eb->AddRecord()->script($this->ajaxUrl, $this->deliverParameters);
                    if (!$drawCallbackInputMask && $eb->AddRecord()->isEnableInputMask()) $drawCallbackInputMask = true;
                }
            }
        }

        if (!$this->onLoadInitSearch) {
            $drawCallbackScript .= <<<EOD

                        let state = sfdt['{$this->tableId}'].state();
                        state.columns.forEach(function(col, index) {
                            if (col.search.search) sfdtSetDefaultValue($("#sfdt-{$this->tableId}-custom-search-" + sfdt['{$this->tableId}'].column(index).dataSrc()), col.search.search);
                        });
                EOD;
            $localStorageScript .= <<<EOD

                    let storage = localStorage.getItem('sfdt-{$this->tableId}-custom-search-ex');
                    if (storage) {
                        let csEx = JSON.parse(storage);
                        for (let key in csEx) {
                            sfdtSetDefaultValue($("#sfdt-{$this->tableId}-custom-search-" + key), csEx[key]);
                        }
                    }
                EOD;
        }

        if (!$this->disableTooltip) {
            $drawCallbackScript .= <<<EOD

                        $(".btn-sfdt-search-reset").data("bs-toggle", "tooltip").attr("title", "[:dt:Reset search conditions:]").tooltip();
                        $(".sfdt-btn-open-column-config").data("bs-toggle", "tooltip").attr("title", "[:dt:Change column order and visibility settings:]").tooltip();
                        $("#sfdt-btn-column-config-reset").data("bs-toggle", "tooltip").attr("title", "[:dt:Restore the initial state of column order and visibility.:]").tooltip();
                        $("#sfdt-btn-column-config-save").data("bs-toggle", "tooltip").attr("title", "[:dt:Save or load the order and visibility of columns.:]").tooltip();
                        $(".sfdt-btn-pagejump").data("bs-toggle", "tooltip").attr("title", "[:dt:Go directly to the page number you entered:]").tooltip();
                EOD;
        }

        // for custom search
        if ($this->customSearch) {
            $localStorageScript .= <<<EOD

                    $(document).on("hide.bs.collapse", "div.sfdt-custom-search.collapse", function () {
                        localStorage.setItem('sfdt-{$this->tableId}-custom-search-onoff', 0);
                        $("#btn-sfdt-custom-search-detail-collaps").html("[:dt:Detail Search:] <i class='fa-regular fa-square-caret-down'></i></i>");
                    });

                    $(document).on("show.bs.collapse", "div.sfdt-custom-search.collapse", function () {
                        localStorage.setItem('sfdt-{$this->tableId}-custom-search-onoff', 1);
                        $("#btn-sfdt-custom-search-detail-collaps").html("[:dt:Detail Search:] <i class='fa-regular fa-square-caret-up'></i></i>");
                    });
                EOD;

            $codeCustomSearch = $this->makeCodeCustomSearch();
            $customSearchHtml = $codeCustomSearch["html"];

            // input mask initialization
            $drawCallbackInputMask = $drawCallbackInputMask | $codeCustomSearch["isEnableInputMask"]; //  | $modifyView["isEnableInputMask"]
            if ($drawCallbackInputMask) $drawCallbackScript .= "\n        $(\":input[data-inputmask]\").inputmask();";

            // If there is detailed search content, highlight the detailed search toggle button.
            $drawCallbackScript .= <<<EOD
                        if (Object.values(sfdtSearchConditionAll('{$this->tableId}').customSearch).some(condition => condition)) {
                            $("#btn-sfdt-custom-search-detail-collaps").addClass("btn-primary").removeClass("btn-secondary");
                        } else {
                            $("#btn-sfdt-custom-search-detail-collaps").removeClass("btn-primary").addClass("btn-secondary");
                        }
                EOD;
        }

        $replaceFrom[] = "\"drawCallback\": \"drawCallback-function\"";
        $replaceTo[] = <<<EOD

                "drawCallback": function(settings) {
                    {$drawCallbackScript}
                    let onoff = localStorage.getItem('sfdt-{$this->tableId}-custom-search-onoff');
                    if (onoff == '1') {
                        $(".sfdt-custom-search").addClass("show");
                        $("#btn-sfdt-custom-search-detail-collaps").html("[:dt:Detail Search:] <i class='fa-regular fa-square-caret-up'></i></i>");
                    } else {
                        $("#btn-sfdt-custom-search-detail-collaps").html("[:dt:Detail Search:] <i class='fa-regular fa-square-caret-down'></i></i>");
                    }
                }
            EOD;

        // After cleaning up the indentation, run replace.
        $replaceFrom[] = "\n"; $replaceTo[] = "\n    ";
        $optionsStr = str_replace($replaceFrom, $replaceTo, $optionsStr);


        $containerOption = $this->containerOption;
        if ($containerOption) $containerOption = " {$containerOption}";

        // A script that should be output only once on the current web page.
        $once = $this->onceOutput();

        // addtional js code
        $jsScript = "";
        $jsScriptOnReady = "";
        if ($this->jsScript) $jsScript = implode("\n", $this->jsScript);
        if ($this->jsScriptOnReady) $jsScriptOnReady = implode("\n", $this->jsScriptOnReady);

        // Finallay, create the HTML to be output.
        $this->html = <<<EOD
            {$once["html"]}
            {$codeColumns["html"]}
            {$addRecordHtml}

            <div{$containerOption}>
                {$customSearchHtml}
                <table id="{$this->tableId}" class="table table-hover table-striped text-nowrap"></table>
            </div>

            <script>
            {$once["script"]}
            $(document).ready(function() {
                sfdt['{$this->tableId}'] = new DataTable('#{$this->tableId}', {$optionsStr});
                {$localStorageScript}
                {$addRecordScript}
                {$codeColumns["script"]}
                {$jsScriptOnReady}
            });
            {$jsScript}
            </script>

            EOD;
    }

    public function echoHtml() {
        echo $this->translationOutput($this->html);
    }

    private function translationOutput($output)
    {
        $translation = Translation::getInstance();
        if ($this->language) {
            if (is_array($output)) {
                $output = json_decode($translation->convert(json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $this->language), true);
            } else {
                $output = $translation->convert($output, $this->language);
            }
            $translation->updateCache($this->language);
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
        $res->recordsFiltered   = $res->recordsTotal;
        if ($this->opQuerySearch()['sql'] ?? false) {
            $res->recordsFiltered   = $this->opRecordsFiltered();
        }
        $res->data              = $this->opListData();

        $template->setMode(Template::MODE_AJAX_FOR_DATATABLE);
        $template->displayResult();
        exit;
    }

    private function opRenderButton($btnId)
    {
        $crb = null;
        foreach($this->columns as $ars => $column) {
            $crbs = $column->renderButtons();
            if (!$crbs || !array_key_exists($btnId, $crbs)) continue;
            if (!$crbs[$btnId]) L::system("[:dt:Button {$btnId} is not defined.:]");
            $crb = $crbs[$btnId];
        }
        if (!$crb) L::system("[:dt:Button {$btnId} is not defined.:]");
        return $crb;
    }

    // Find the render button of the column and get the query function related information for the render button.
    private function opQueryParams()
    {
        $param = Param::getInstance();
        $param->checkKeyValue('sfdtBtnId', Param::TYPE_STRING);

        $crb = $this->opRenderButton($param->sfdtBtnId);
        if (!$crb->queryFunction()) L::system("[:dt:Query function(queryFunction) for button {$param->sfdtBtnId} is not defined.:]");
        if (!is_callable([ $this, $crb->queryFunction() ])) L::system("[:dt:Query function {$crb->queryFunction()} for button {$param->sfdtBtnId} is not callable.:]");

        $params = [];
        $kp = $crb->keyParams();
        if (!$kp) L::system("[:dt:Key parameters for button {$param->sfdtBtnId} is not defined.:]");
        foreach($kp as $alias) {
            $param->checkKeyValue($alias, Param::TYPE_STRING);
            $params[$alias] = $param->get($alias);
        }
        if (!$params) L::system("[:dt:Button {$param->sfdtBtnId} is not defined.:]");

        return [ $param->sfdtBtnId, $crb->queryFunction(), $params ];
    }

    private function opDetailView()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        list($btnId, $queryFunction, $params) = $this->opQueryParams();
        $res = Response::getInstance();
        $res->queryData = call_user_func_array([ $this, $queryFunction ], [ $params, $btnId ]);
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
    }

    private function opModify()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        list($btnId, $queryFunction, $params) = $this->opQueryParams();
        $res = Response::getInstance();
        $res->queryData = call_user_func_array([ $this, $queryFunction ], [ $params, $btnId ]);
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
    }

    private function opSubmitForModify()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        $param = Param::getInstance();
        $param->checkKeyValue('sfdtBtnId', Param::TYPE_STRING);

        $crb = $this->opRenderButton($param->sfdtBtnId);
        if (!$crb->submitFunction()) L::system("[:dt:Submit function(submitFunction) for button {$param->sfdtBtnId} is not defined.:]");
        if (!is_callable([ $this, $crb->submitFunction() ])) L::system("[:dt:Submit function {$crb->submitFunction()} for button {$param->sfdtBtnId} is not callable.:]");

        $result = call_user_func([ $this, $crb->submitFunction() ]);

        $res = Response::getInstance();
        $res->result = $result;
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
    }

    private function opSubmitForDelete()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        $param = Param::getInstance();
        $param->checkKeyValue('sfdtBtnId', Param::TYPE_STRING);

        $crb = $this->opRenderButton($param->sfdtBtnId);
        if (!$crb->submitFunction()) L::system("[:dt:Submit function(submitFunction) for button {$param->sfdtBtnId} is not defined.:]");
        if (!is_callable([ $this, $crb->submitFunction() ])) L::system("[:dt:Submit function {$crb->submitFunction()} for button {$param->sfdtBtnId} is not callable.:]");

        $result = call_user_func([ $this, $crb->submitFunction() ]);

        $res = Response::getInstance();
        $res->result = $result;
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
    }

    // for handling everything on one page
    // Receives an ajax call from the submit action of the add record form and returns a response to it.
    private function opSubmitForAddRecord()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        $param = Param::getInstance();
        $btnId = $param->sfdtBtnId;
        if (!isset($this->extraButtons[$btnId])) L::system("[:dtaddrecord:Extra button {$btnId} not found.:]");
        if (!$this->extraButtons[$btnId]->isAddRecord()) L::system("[:dtaddrecord:Extra button {$btnId} is not AddRecord.:]");
        if (!$this->extraButtons[$btnId]->AddRecord()->submitFunction() || !is_callable([ $this, $this->extraButtons[$btnId]->AddRecord()->submitFunction() ])) L::system("[:dtaddrecord:Extra button {$btnId} submitFunction is not callable.:]");
        $result = call_user_func([ $this, $this->extraButtons[$btnId]->AddRecord()->submitFunction() ]);

        $res = Response::getInstance();
        $res->result = $result;
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
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
                $searchColumn = $this->columns[$alias]->searchColumn();
                if ($col['searchable'] === 'true') $searchableColumns[$alias] = $searchColumn;
                if (!$this->columns[$alias]->searchable() || $value === "") continue;

                $this->opQuerySearchBind($alias, $value, $searchColumn, $whereAnd, $this->querySearchBind, 'cse');
            }
        }

        // find search keyword for custom search ex
        if ($param->customSearchEx) {
            foreach($param->customSearchEx as $alias => $value) {
                if ($value === "") continue;
                if (!array_key_exists($alias, $this->customSearch)) continue;
                $columnQuery = $this->customSearch[$alias]->exColumnQuery();
                $this->opQuerySearchBind($alias, $value, $columnQuery, $whereAnd, $this->querySearchBind, 'cex');
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

    public function opQuerySearchBind($alias, $value, $columnQuery, &$whereAnd, &$bind, $prefix)
    {
        switch ($this->customSearch($alias)->type()) {
            case DataTablesCustomSearch::TYPE_STRING :
                if ($this->customSearch($alias)->isEqualSearch()) {
                    $whereAnd[] = "({$columnQuery}) = :{$prefix}_{$alias}";
                    $bind[":{$prefix}_{$alias}"] = $value;
                } else {
                    $whereAnd[] = "({$columnQuery}) LIKE :{$prefix}_{$alias}";
                    $bind[":{$prefix}_{$alias}"] = "%{$value}%";
                }
                break;
            case DataTablesCustomSearch::TYPE_DATERANGE :
            case DataTablesCustomSearch::TYPE_DATETIMERANGE :
                $between = explode(' - ', $value);
                if (count($between) == 2 && strtotime($between[0]) && strtotime($between[1])) {
                    $whereAnd[] = "({$columnQuery}) BETWEEN :{$prefix}_{$alias}_start AND :{$prefix}_{$alias}_end";
                    $bind[":{$prefix}_{$alias}_start"] = date("Y-m-d H:i:s", strtotime($between[0]));
                    $endDate = $between[1];
                    if (strlen($endDate) == 10) {
                        $endDate .= ' 23:59:59';
                    } elseif (strlen($endDate) == 13) {
                        $endDate .= ':59:59';
                    } elseif (strlen($endDate) == 16) {
                        $endDate .= ':59';
                    }
                    $bind[":{$prefix}_{$alias}_end"] = date("Y-m-d H:i:s", strtotime($endDate));
                }
                break;
            case DataTablesCustomSearch::TYPE_NUMBERRANGE :
                $between = explode(' - ', $value);
                if (count($between) == 2) {
                    $between[0] = intval(preg_replace('/\D/', '', $between[0]));
                    $between[1] = intval(preg_replace('/\D/', '', $between[1]));
                    $whereAnd[] = "({$columnQuery}) BETWEEN :{$prefix}_{$alias}_start AND :{$prefix}_{$alias}_end";
                    $bind[":{$prefix}_{$alias}_start"] = $between[0];
                    $bind[":{$prefix}_{$alias}_end"] = $between[1];
                } else {
                    $whereAnd[] = "({$columnQuery}) = :{$prefix}_{$alias}";
                    $bind[":{$prefix}_{$alias}"] = intval(preg_replace('/\D/', '', $value));
                }
                break;
            default :
                if ($this->customSearch($alias)->isEqualSearch()) {
                    $whereAnd[] = "({$columnQuery}) = :{$prefix}_{$alias}";
                    $bind[":{$prefix}_{$alias}"] = $value;
                } else {
                    $whereAnd[] = "({$columnQuery}) LIKE :{$prefix}_{$alias}";
                    $bind[":{$prefix}_{$alias}"] = "%{$value}%";
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

    private function opColumnConfigSave()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        $param = Param::getInstance();
        $param->checkKeyValue('data', Param::TYPE_STRING);

        if (!$this->columnConfigSaveFunction || !is_callable([ $this, $this->columnConfigSaveFunction ])) L::system("[:dt:Column config load/save function is not callable.:]");
        $result = call_user_func([ $this, $this->columnConfigSaveFunction ], $param->data);

        $res = Response::getInstance();
        $res->result = $result;
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
    }

    private function opColumnConfigLoad()
    {
        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        if (!$this->columnConfigLoadFunction || !is_callable([ $this, $this->columnConfigLoadFunction ])) L::system("[:dt:Column config load/save function is not callable.:]");
        $result = call_user_func([ $this, $this->columnConfigLoadFunction ]);
        if (!$result || $result == "undefined") $result = "";

        $res = Response::getInstance();
        $res->result = $result;
        $template = Template::getInstance();
        $template->setMode(Template::MODE_AJAX);
        $template->displayResult();

        $modtList = Modt::instanceList();
        if ($modtList) foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        if ($dbList) foreach($dbList as $db) $db->commit();

        exit;
    }
}