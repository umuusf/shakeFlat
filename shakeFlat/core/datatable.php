<?php
/**
 * core/datatable.php
 *
 * When using DataTable.js, a standardized code set is provided.
 * You must include the /asset/js/shakeflat.js file.
 *
 */

namespace shakeFlat;
use shakeFlat\L;
use shakeFlat\Param;

class DataTable extends L
{
    // for list
    const ATTR_SEARCHABLE       = 9001;
    const ATTR_ORDERABLE        = 9002;
    const ATTR_BTN_DETAIL       = 9003;
    const ATTR_BTN_MODIFY       = 9004;
    const ATTR_BTN_DELETE       = 9005;
    const ATTR_INVISIBLE        = 9006;

    // for new, modify
    const ATTR_REQUIRED         = 9101;
    const ATTR_READONLY         = 9102;
    const ATTR_TYPE_TEXT        = 9201;
    const ATTR_TYPE_TEXTAREA    = 9202;
    const ATTR_TYPE_HIDDEN      = 9203;
    const ATTR_TYPE_SELECT      = 9204;
    const ATTR_TYPE_RADIO       = 9205;
    const ATTR_TYPE_CHECKBOX    = 9206;
    const ATTR_TYPE_PASSWORD    = 9207;
    const ATTR_TYPE_NUMBER      = 9208;
    const ATTR_TYPE_EMAIL       = 9209;
    const ATTR_TYPE_URL         = 9210;
    const ATTR_TYPE_DATETIME    = 9211;
    const ATTR_TYPE_DATE        = 9212;
    const ATTR_TYPE_TIME        = 9213;
    const ATTR_TYPE_MONTH       = 9214;
    const ATTR_TYPE_TEL         = 9215;

    // common
    const ATTR_TEXT_AMOUNT      = 9901;
    const ATTR_TEXT_LEFT        = 9911;
    const ATTR_TEXT_CENTER      = 9912;
    const ATTR_TEXT_RIGHT       = 9913;


    private static $instance            = array();

    private $htmlTableId                = "";
    private $jsTableName                = "";
    private $setName                    = "";
    private $ajaxUrl                    = "";
    private $tableClass                 = "table table-sm table-hover";
    private $connectionName             = "default";
    private $mainDBTable                = "";
    private $mainDBTablePK              = [];                   // Primary key column name
    private $joinDBTable                = array();
    private $andConditions              = array();
    private $orConditions               = array();
    private $searchJoinDBTable          = array();
    private $searchAndConditions        = array();
    private $searchOrConditions         = array();
    private $groupBy                    = array();

    private $defaultOrder               = array();
    private $defaultOrderDirection      = array();
    private $customSearch               = array();
    private $paging                     = true;
    private $searching                  = true;
    private $pageLength                 = 30;
    private $lengthMenu                 = array( 10, 20, 30, 50, 75, 100 );
    private $stateSave                  = true;
    private $createdRow                 = "";
    private $drawCallBack               = "";
    private $dom                        = "<'row justify-content-between'<'col-auto'B><'col-auto'<'row'<'col-auto'<'sf-custom-search'>><'col-auto'f>>>><'row'<'col-12'tr>><'row justify-content-between'<'col-auto'i><'col-auto'<'row sf-page-row'<'col-auto'l><'col-auto'<'sf-page-input'>><'col-auto'p>>>>";

    private $excelEnable                = true;
    private $excelFileName              = "";
    private $excelButtonText            = "Excel";
    private $excelButtonClassName       = "btn btn-sm btn-secondary";

    private $extraButtons               = array();

    private $columns                    = array();
    private $listing                    = array();      // Column list of list screen

    private $detailInfo                 = array();      // View details of 1 row
    private $detailInfoLayout           = array();
    private $detailInfoReadAjaxUrl      = "";
    private $detailInfoModalTitle       = "상세보기";

    private $newRecord                  = array();
    private $newRecordLayout            = array();
    private $newRecordAjaxUrl           = "";
    private $newRecordModalTitle        = "신규추가";
    private $newRecordCallback          = null;

    private $modifyRecord               = array();
    private $modifyRecordLayout         = array();
    private $modifyRecordReadAjaxUrl    = "";
    private $modifyRecordSubmitAjaxUrl  = "";
    private $modifyRecordModalTitle     = "수정하기";
    private $modifyRecordCallback       = null;

    private $deleteRecordCallback       = null;
    private $deleteRecordSubmitAjaxUrl  = "";

    private $javaScript                 = array();
    private $javaScriptReady            = array();         // on document ready
    private $javaScript2                = array();         // custom
    private $javaScriptReady2           = array();
    private $htmlModal                  = array();
    private $bind                       = array();

    private $deliverParameters          = array();         // Defines parameters that must be passed when calling the ajax module. (for onePage)

    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (isset(self::$instance[$calledClass])) return self::$instance[$calledClass];
        self::$instance[$calledClass] = new $calledClass();
        return self::$instance[$calledClass];
    }

    protected function __construct($setName, $config = array())
    {
        $chk = preg_match("/^[a-z][a-z0-9]*$/", $setName);
        if (!$chk) self::system("It must be made only of alphabet(lower case) and numbers (the first letter is an alphabet).");
        $this->setName = $setName;

        // Set the url of each ajax page to the current page.
        // When not in one page mode, use each setting method to change.
        $router = Router::getInstance();
        $this->ajaxUrl                   = "/{$router->module()}/{$router->fnc()}/";
        $this->detailInfoReadAjaxUrl     = "/{$router->module()}/{$router->fnc()}/";
        $this->newRecordAjaxUrl          = "/{$router->module()}/{$router->fnc()}/";
        $this->modifyRecordReadAjaxUrl   = "/{$router->module()}/{$router->fnc()}/";
        $this->modifyRecordSubmitAjaxUrl = "/{$router->module()}/{$router->fnc()}/";
        $this->deleteRecordSubmitAjaxUrl = "/{$router->module()}/{$router->fnc()}/";

        if ($config) $this->setConfig($config);
    }

    // Required when not in one page mode.
    public function setListAjax($url)
    {
        $this->ajaxUrl = $url;
    }

    protected function setTableClass($tableClass)
    {
        $this->tableClass = $tableClass;
    }

    protected function setDBMainTable($mainDBTable, $pkColumn, $connectionName = "default")
    {
        $this->mainDBTable = $mainDBTable;
        if (is_array($pkColumn)) $this->mainDBTablePK = $pkColumn;
        elseif (is_string($pkColumn)) $this->mainDBTablePK = [ $pkColumn ];
        $this->connectionName = $connectionName;
    }

    protected function setConfig($config)
    {
        if (isset($config["ajaxUrl"]))                  $this->ajaxUrl                  = $config["ajaxUrl"];
        if (isset($config["tableClass"]))               $this->tableClass               = $config["tableClass"];
        if (isset($config["connectionName"]))           $this->connectionName           = $config["connectionName"];
        if (isset($config["mainDBTable"]))              $this->mainDBTable              = $config["mainDBTable"];
        if (isset($config["columns"]))                  $this->columns                  = $config["columns"];
        if (isset($config["defaultOrder"]))             $this->defaultOrder[]           = $config["defaultOrder"];
        if (isset($config["defaultOrderDirection"]))    $this->defaultOrderDirection[]  = $config["defaultOrderDirection"];
        if (isset($config["paging"]))                   $this->paging                   = $config["paging"];
        if (isset($config["pageLength"]))               $this->pageLength               = $config["pageLength"];
        if (isset($config["lengthMenu"]))               $this->lengthMenu               = $config["lengthMenu"];
        if (isset($config["stateSave"]))                $this->stateSave                = $config["stateSave"];
        if (isset($config["searching"]))                $this->searching                = $config["searching"];
        if (isset($config["excel"]))                    $this->excelEnable              = $config["excel"];             // true/false
    }

    // Defines the columns(fields) to be listed.
    // alias : unique name (To distinguish when two or more tables have the same field name when used in a query statement.)
    // rendering : The javascript code of the "render" item described in columnDefs
    protected function setAllColumns($columns)
    {
        $this->columns = array();
        foreach($columns as $alias => $attr) {
            $this->setColumn($alias, $attr);
        }
    }

    // Update/add one column(field) defined in the list.
    public function setColumn($alias, $attr)
    {
        if (!isset($this->columns[$alias])) {
            $this->columns[$alias] = array(
                "label"             => $attr["label"] ?? "",
                "divClassName"      => $attr["divClassName"] ?? "",
                "className"         => $attr["className"] ?? "",

                "labelStyle"        => $attr["labelStyle"] ?? "",
                "divStyle"          => $attr["divStyle"] ?? "",
                "style"             => $attr["style"] ?? "",

                "displayEnum"       => $attr["displayEnum"] ?? [],
                "rendering"         => $attr["rendering"] ?? "",

                "realColumn"        => null,

                "isDetailInfoBtn"   => $attr["isDetailInfoBtn"] ?? false,
                "isModifyBtn"       => $attr["isModifyBtn"] ?? false,
                "isDeleteBtn"       => $attr["isDeleteBtn"] ?? false,

                "searchable"        => $attr["searchable"] ?? false,
                "orderable"         => $attr["orderable"] ?? false,
                "invisible"         => $attr["invisible"] ?? false,

                "required"          => $attr["required"] ?? false,
                "readonly"          => $attr["readonly"] ?? false,
                "notfill"           => $attr["notfill"] ?? false,             // Do not fill in the value. Example: password

                "type"              => $attr["type"] ?? "text",
                "optionList"        => $attr["optionList"] ?? [],
                "placeholder"       => $attr["placeholder"] ?? "",
                "defaultValue"      => $attr["defaultValue"] ?? "",
                "comment"           => $attr["comment"] ?? "",

                "textCenter"        => $attr["textCenter"] ?? false,
                "textLeft"          => $attr["textLeft"] ?? false,
                "textRight"         => $attr["textRight"] ?? false,
                "textAmount"        => $attr["textAmount"] ?? false,

                "custom"            => $attr["custom"] ?? "",
            );
        }

        $attr = $this->constToAttr($attr);

        foreach($attr as $key => $val) {
            switch($key) {
                case "label" :
                case "className" :
                case "rendering" :
                    $this->columns[$alias][$key] = $val;
                    break;

                case "isDetailInfoBtn" :
                case "isModifyBtn" :
                case "isDeleteBtn" :

                case "searchable" :
                case "orderable" :
                    $this->columns[$alias][$key] = $val;
                    break;

                case "realColumn" :
                    // realColumn :
                    //   If omitted, it is replaced with the alias value.
                    //   If you want to output only the contents of render, set it to blank("") or null.
                    $realColumn = null;
                    if (!($attr["isModifyBtn"] ?? false) && !($attr["isDetailInfoBtn"] ?? false) && !($attr["isDeleteBtn"] ?? false)) {
                        if (!array_key_exists("realColumn", $attr)) $realColumn = $alias;
                        elseif ($attr["realColumn"] !== null) $realColumn = $attr["realColumn"];
                    }
                    $this->columns[$alias]["realColumn"] = $realColumn;
                    break;
            }
        }
    }

    private function constToAttr($attr)
    {
        $attr2 = $attr;
        foreach($attr2 as $idx => $val) {
            switch($val) {
                case self::ATTR_BTN_DETAIL      : $attr["isDetailInfoBtn"] = true;  unset($attr[$idx]); break;
                case self::ATTR_BTN_MODIFY      : $attr["isModifyBtn"] = true;      unset($attr[$idx]); break;
                case self::ATTR_BTN_DELETE      : $attr["isDeleteBtn"] = true;      unset($attr[$idx]); break;

                case self::ATTR_SEARCHABLE      : $attr["searchable"] = true;       unset($attr[$idx]); break;
                case self::ATTR_ORDERABLE       : $attr["orderable"] = true;        unset($attr[$idx]); break;
                case self::ATTR_INVISIBLE       : $attr["invisible"] = true;        unset($attr[$idx]); break;

                case self::ATTR_REQUIRED        : $attr["required"] = true;         unset($attr[$idx]); break;
                case self::ATTR_READONLY        : $attr["readonly"] = true;         unset($attr[$idx]); break;

                case self::ATTR_TYPE_TEXT       : $attr["type"] = "text";           unset($attr[$idx]); break;
                case self::ATTR_TYPE_TEXTAREA   : $attr["type"] = "textarea";       unset($attr[$idx]); break;
                case self::ATTR_TYPE_HIDDEN     : $attr["type"] = "hidden";         unset($attr[$idx]); break;
                case self::ATTR_TYPE_SELECT     : $attr["type"] = "select";         unset($attr[$idx]); break;
                case self::ATTR_TYPE_RADIO      : $attr["type"] = "radio";          unset($attr[$idx]); break;
                case self::ATTR_TYPE_CHECKBOX   : $attr["type"] = "checkbox";       unset($attr[$idx]); break;
                case self::ATTR_TYPE_NUMBER     : $attr["type"] = "number";         unset($attr[$idx]); break;
                case self::ATTR_TYPE_EMAIL      : $attr["type"] = "email";          unset($attr[$idx]); break;
                case self::ATTR_TYPE_URL        : $attr["type"] = "url";            unset($attr[$idx]); break;
                case self::ATTR_TYPE_DATETIME   : $attr["type"] = "datetime";       unset($attr[$idx]); break;
                case self::ATTR_TYPE_DATE       : $attr["type"] = "date";           unset($attr[$idx]); break;
                case self::ATTR_TYPE_TIME       : $attr["type"] = "time";           unset($attr[$idx]); break;
                case self::ATTR_TYPE_MONTH      : $attr["type"] = "month";          unset($attr[$idx]); break;
                case self::ATTR_TYPE_TEL        : $attr["type"] = "tel";            unset($attr[$idx]); break;
                case self::ATTR_TYPE_PASSWORD   : $attr["type"] = "password"; $attr["notfill"] = true; unset($attr[$idx]); break;

                case self::ATTR_TEXT_CENTER     : $attr["textCenter"] = true;       unset($attr[$idx]); break;
                case self::ATTR_TEXT_LEFT       : $attr["textLeft"] = true;         unset($attr[$idx]); break;
                case self::ATTR_TEXT_RIGHT      : $attr["textRight"] = true;        unset($attr[$idx]); break;

                case self::ATTR_TEXT_AMOUNT     : $attr["textAmount"] = true;       unset($attr[$idx]); break;
            }
        }
        return $attr;
    }

    // Defines the column list and order of the list screen
    public function setListing($layout, $aliasList = null)
    {
        if (!is_array($layout)) self::system("layout is not defined.");
        $this->listing = array();
        foreach($layout as $alias) {
            if (!isset($this->columns[$alias])) self::system("This is an undefined alias: " . $alias);
            $this->listing[$alias] = $this->columns[$alias];
        }

        if ($aliasList && is_array($aliasList)) {
            foreach($aliasList as $alias => $attrList) {
                $attrList = $this->constToAttr($attrList);
                foreach($attrList as $key => $data) {
                    if (!isset($this->columns[$alias][$key])) self::system("This is an unknown attribute: " . $key);
                    $this->listing[$alias][$key] = $data;
                }
            }
        }
    }

    public function setAttribute($aliasList, $attrName, $attrVal)
    {
        if (!is_array($aliasList)) $aliasList = [ $aliasList ];
        foreach($aliasList as $alias) {
            $this->columns[$alias][$attrName] = $attrVal;
            if (isset($this->listing[$alias])) $this->listing[$alias][$attrName] = $attrVal;
        }
    }

    public function setAttrSearchable($aliasList)
    {
        $this->setAttribute($aliasList, "searchable", true);
    }

    public function setAttrOrderable($aliasList)
    {
        $this->setAttribute($aliasList, "orderable", true);
    }

    public function setAttrInvisible($aliasList)
    {
        $this->setAttribute($aliasList, "invisible", true);
    }

    public function setAttrRequired($aliasList)
    {
        $this->setAttribute($aliasList, "required", true);
    }

    public function setAttrReadonly($aliasList)
    {
        $this->setAttribute($aliasList, "readonly", true);
    }

    public function setAttrTextCenter($aliasList)
    {
        $this->setAttribute($aliasList, "textCenter", true);
    }

    public function setAttrTextLeft($aliasList)
    {
        $this->setAttribute($aliasList, "textLeft", true);
    }

    public function setAttrTextRight($aliasList)
    {
        $this->setAttribute($aliasList, "textRight", true);
    }

    public function setAttrTextAmount($aliasList)
    {
        $this->setAttribute($aliasList, "textAmount", true);
    }

    // Do not use the Excel download function.
    public function setExcelDisable()
    {
        $this->excelEnable = false;
    }

    public function setSearchDisable()
    {
        $this->searching = false;
    }

    // Defines parameters that must be passed(deliver) when calling the ajax module. (for onePage)
    public function deliverParameter($k, $v)
    {
        $this->deliverParameters[$k] = $v;
    }

    // Returns a code that adds parameters to the ajax code.
    private function deliverParamCode()
    {
        if (!$this->deliverParameters) return "";
        $paramStr = "";
        foreach($this->deliverParameters as $k => $v) {
            $paramStr .= "{$k}:'{$v}',";
        }
        return $paramStr;
    }

    // Returns a code that adds parameters to formData of the ajax code.
    private function deliverAppendCode($frm = "formData")
    {
        if (!$this->deliverParameters) return "";
        $paramStr = "";
        foreach($this->deliverParameters as $k => $v) {
            $paramStr .= "{$frm}.append(\"{$k}\", \"{$v}\");\n\t\t\t\t\t";
        }
        return $paramStr;
    }

    // Required when not in one page mode.
    public function setNewRecordAjax($submitUrl)
    {
        $this->newRecordAjaxUrl = $submitUrl;
    }

    public function setSubmitForNewCallback($callback)
    {
        $this->newRecordCallback = $callback;
    }

    public function setNewRecord($layout, $attrList = null, $modalTitle="신규추가")
    {
        if (!is_array($layout)) self::system("layout is not defined.");
        $this->newRecordModalTitle = $modalTitle;
        foreach($layout as $arr) {
            if (!is_array($arr)) {
                if (!isset($this->columns[$arr])) self::system("This is an undefined alias: " . $arr);
                $this->newRecord[$arr] = $this->columns[$arr];
            } else {
                foreach($arr as $arr2) {
                    if (!isset($this->columns[$arr2])) self::system("This is an undefined alias: " . $arr2);
                    $this->newRecord[$arr2] = $this->columns[$arr2];
                }
            }
        }
        $this->newRecordLayout = $layout;

        if ($attrList && is_array($attrList)) {
            foreach($attrList as $alias => $attr) {
                $attr = $this->constToAttr($attr);
                foreach($attr as $key => $data) {
                    if (!isset($this->columns[$alias][$key])) self::system("This is an unknown attribute: " . $key);
                    $this->newRecord[$alias][$key] = $data;
                }
            }
        }
    }

    public function setNewRecordAttr($alias, $attr)
    {
        if (!isset($this->newRecord[$alias])) self::system("This is an undefined alias: " . $alias);

        $attr = $this->constToAttr($attr);
        foreach($attr as $key => $data) {
            if (!isset($this->columns[$alias][$key])) self::system("This is an unknown attribute: " . $key);
            $this->newRecord[$alias][$key] = $data;
        }
    }

    // Required when not in one page mode.
    public function setModifyAjax($readUrl, $submitUrl)
    {
        $this->modifyRecordReadAjaxUrl = $readUrl;
        $this->modifyRecordSubmitAjaxUrl = $submitUrl;
    }

    public function setDeleteAjax($url)
    {
        $this->deleteRecordSubmitAjaxUrl = $url;
    }

    public function setSubmitForModifyCallback($callback)
    {
        $this->modifyRecordCallback = $callback;
    }

    public function setSubmitForDeleteCallback($callback)
    {
        $this->deleteRecordCallback = $callback;
    }

    public function setModifyRecord($layout, $attrList = null, $modalTitle = "수정하기")
    {
        if (!is_array($layout)) self::system("layout is not defined.");
        $this->modifyRecordModalTitle = $modalTitle;
        foreach($layout as $arr) {
            if (!is_array($arr)) {
                if (!isset($this->columns[$arr])) self::system("This is an undefined alias: " . $arr);
                $this->modifyRecord[$arr] = $this->columns[$arr];
            } else {
                foreach($arr as $arr2) {
                    if (!isset($this->columns[$arr2])) self::system("This is an undefined alias: " . $arr2);
                    $this->modifyRecord[$arr2] = $this->columns[$arr2];
                }
            }
        }
        $this->modifyRecordLayout = $layout;

        if ($attrList && is_array($attrList)) {
            foreach($attrList as $alias => $attr) {
                $attr = $this->constToAttr($attr);
                foreach($attr as $key => $data) {
                    if (!isset($this->columns[$alias][$key])) self::system("This is an unknown attribute: " . $key);
                    $this->modifyRecord[$alias][$key] = $data;
                }
            }
        }
    }

    public function setModifyRecordAttr($alias, $attr)
    {
        if (!isset($this->modifyRecord[$alias])) self::system("This is an undefined alias: " . $alias);

        $attr = $this->constToAttr($attr);
        foreach($attr as $key => $data) {
            if (!isset($this->columns[$alias][$key])) self::system("This is an unknown attribute: " . $key);
            $this->modifyRecord[$alias][$key] = $data;
        }
    }

    // Required when not in one page mode.
    public function setDetailInfoAjax($url)
    {
        $this->detailInfoReadAjaxUrl = $url;
    }

    public function setDetailInfo($layout, $attrList = null, $modalTitle = "상세보기")
    {
        if (!is_array($layout)) self::system("layout is not defined.");
        $this->detailInfoModalTitle = $modalTitle;
        foreach($layout as $arr) {
            if (!is_array($arr)) {
                if (!isset($this->columns[$arr])) self::system("This is an undefined alias: " . $arr);
                $this->detailInfo[$arr] = $this->columns[$arr];

            } else {
                foreach($arr as $arr2) {
                    if (!isset($this->columns[$arr2])) self::system("This is an undefined alias: " . $arr2);
                    $this->detailInfo[$arr2] = $this->columns[$arr2];
                }
            }
        }
        $this->detailInfoLayout = $layout;

        if ($attrList && is_array($attrList)) {
            foreach($attrList as $alias => $attr) {
                $attr = $this->constToAttr($attr);
                foreach($attr as $key => $data) {
                    $this->detailInfo[$alias][$key] = $data;
                }
            }
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
    protected function setJoinDBTable($tableName, $joinColumn, $matchColumn, $tableAlias = null, $joinCondition = null, $joinConditionBind = null)
    {
        $this->joinDBTable[] = array (
            "tableName"         => $tableName,
            "tableAlias"        => $tableAlias,
            "joinColumn"        => $joinColumn,
            "matchColumn"       => $matchColumn,
            "joinCondition"     => $joinCondition,
            "joinConditionBind" => $joinConditionBind,
        );
    }

    // set default condition for AND
    public function setAnd($condition, $bind = null)
    {
        $w = trim($condition, " \n\r\t");
        if (strtolower(substr($w, 0, 4)) == "and ") $w = substr($w, 4);
        $this->andConditions[] = $condition;

        if ($bind) $this->bind = array_merge($this->bind, $bind);
    }

    // set default condition for OR
    public function setOr($condition, $bind = null)
    {
        $w = trim($condition, " \n\r\t");
        if (strtolower(substr($w, 0, 3)) == "or ") $w = substr($w, 4);
        $this->orConditions[] = $condition;

        if ($bind) $this->bind = array_merge($this->bind, $bind);
    }

    public function setGroupBy($queryGroupBy)
    {
        $this->groupBy[] = $queryGroupBy;
    }

    public function setSearchJoinDBTable($tableName, $joinColumn, $matchColumn, $joinCondition = null, $joinConditionBind = null)
    {
        $this->searchJoinDBTable[] = array (
            "tableName"         => $tableName,
            "joinColumn"        => $joinColumn,
            "matchColumn"       => $matchColumn,
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
        $this->defaultOrder[] = $alias;
        $this->defaultOrderDirection[] = $direction;
    }

    public function setCustomSearchHidden($alias, $callbackFnc)
    {
        $this->customSearch[$alias] = array(
            "type"      => "hidden",
            "callback"  => $callbackFnc,
        );
    }

    public function setCustomSearchSelectBox($alias, $label, $list, $isSelect2 = false, $style = null, $callbackFnc = null)
    {
        $this->customSearch[$alias] = array(
            "type"      => "select",
            "label"     => $label,
            "list"      => $list,
            "isSelect2" => $isSelect2,
            "style"     => $style,
            "callback"  => $callbackFnc,
        );
    }

    public function setCustomSearchDateRange($alias, $label, $style = null, $compareDateFormat = "Y-m-d H:i:s", $callbackFnc = null)
    {
        $this->customSearch[$alias] = array(
            "type"      => "dateRange",
            "label"     => $label,
            "style"     => $style,
            "compareDateFormat" => $compareDateFormat,
            "callback"  => $callbackFnc,
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

    public function setExtraButton($text, $actionJS, $class = "btn btn-sm")
    {
        $this->extraButtons[] = array(
            "text"      => $text,
            "action"    => $actionJS,
            "class"     => $class,
        );
    }

    /*
     * for listing (html, js)
     */

    private function check()
    {
        if (!$this->ajaxUrl) self::system("DataTable setting value is missing: ajaxURL");
        if (!$this->columns) self::system("DataTable setting value is missing: listing");
        if (!$this->mainDBTable) self::system("DataTable setting value is missing: mainDBTable");
    }

    private function build()
    {
        $this->check();
        if ($this->htmlTableId != "") return;

        $this->htmlTableId  = "sf-tbl-" . $this->setName;
        $this->jsTableName = "sf_tbl_" . $this->setName;

        $stateSave  = ($this->stateSave) ? "true" : "false";
        $searching  = ($this->searching) ? "true" : "false";
        $paging     = ($this->paging) ? "true" : "false";
        $lengthMenu = json_encode($this->lengthMenu);

        $order = "[]";
        $orderArr = array();
        foreach($this->defaultOrder as $ii => $defaultOrder) {
            $idx = array_search($defaultOrder, array_keys($this->listing));
            if ($idx !== false) $orderArr[] = [ $idx, "{$this->defaultOrderDirection[$ii]}" ];
        }
        if ($orderArr) $order = json_encode($orderArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $columnsList = array();
        $excelColumnsList = array();
        $idx = 0;
        $invisibleColumnsList = array();
        foreach($this->listing as $alias => $attr) {
            $searchable = "searchable: false, ";
            $orderable  = "orderable: false, ";
            $label      = "";
            $data       = "";
            $className  = "";
            $render     = "";
            $visible    = "";
            //if ($attr["invisible"]) $visible = "visible: false, ";
            if ($attr["invisible"]) $invisibleColumnsList[] = $idx;


            if (!$attr["isModifyBtn"] && !$attr["isDetailInfoBtn"] && !$attr["isDeleteBtn"]) {
                if ($attr["realColumn"]) $data = "data: \"{$alias}\", ";
                if ($attr["searchable"] && $data) $searchable = "searchable: true, ";
                if ($attr["orderable"] && $data)  $orderable = "orderable: true, ";
                if ($attr["label"]) $label = "title: \"{$attr["label"]}\", ";
                if ($attr["rendering"]) $render = "render: {$attr["rendering"]}, ";
            } else {
                $data = "data: \"\", ";
                if ($attr["rendering"]) {
                    $render = "render: {$attr["rendering"]}, ";
                } else {
                    // $this->mainDBTablePK 으로 pk json string 값을 만든다.
                    $pkscript = "var pks = {}; ";
                    foreach($this->mainDBTablePK as $pk) {
                        $pkscript .= "pks['{$pk}'] = row['{$pk}']; ";
                    }
                    $pkscript .= "var pk = JSON.stringify(pks); ";

                    $rfnc = array();
                    if($attr["isDetailInfoBtn"]) {
                        $rfnc[] = "<button data-pk='\"+pk+\"' class=\'btn btn-xs btn-detail btn-{$this->setName}-detailinfo\'>상세보기</button>";
                        $this->setScriptDetailInfo();
                    }
                    if ($attr["isModifyBtn"]) {
                        $rfnc[] = "<button data-pk='\"+pk+\"' class=\'btn btn-xs btn-modify btn-{$this->setName}-modify\'>수정</button>";
                        $this->setScriptModify();
                    }
                    if ($attr["isDeleteBtn"]) {
                        $rfnc[] = "<button data-pk='\"+pk+\"' class=\'btn btn-xs btn-delete btn-{$this->setName}-delete\'>삭제</button>";
                        $this->setScriptDelete();
                    }
                    $render = "render: function(data, type, row) { {$pkscript} return \"" . implode(" &nbsp;", $rfnc) . "\" }";
                }
            }
            if ($render == "") {
                if ($attr["displayEnum"]) {
                    $context = "";
                    foreach($attr["displayEnum"] as $k => $v) {
                        $vq = str_replace("'", "\'", $v);
                        $vq = preg_replace("/\{([a-zA-Z\d._]+)\}/", "'+$1+'", $vq);
                        $context .= "if (data == '{$k}') return '{$vq}';";
                    }
                    $render = "render: function(data, type, row) { {$context} }";
                } else {
                    if ($attr["textAmount"]) {
                        $render = "render:$.fn.dataTable.render.number(',')";       // If you want to handle numbers below the decimal point, you must define it separately. Only integer types are handled here.
                    } else {
                        $render = "render:$.fn.dataTable.render.text()";
                    }
                }
            }

            $attr["className"] = join(" ", array_filter([ $attr["className"], ($attr["textCenter"] ? "text-center" : ""), ($attr["textLeft"] ? "text-start" : ""), ($attr["textRight"] ? "text-end" : ""), ($attr["textAmount"] ? "text-amount" : "") ]));
            if ($attr["className"]) $className = "className: \"{$attr["className"]}\", ";

            $columnsList[] = "{ name: \"{$alias}\", type: \"html\", {$data}{$label}{$searchable}{$orderable}{$className}{$visible}{$render}}";
            if ($attr["realColumn"] && !$attr["invisible"]) $excelColumnsList[] = $idx;

            $idx++;
        }

        $columns = "[\n\t\t\t\t\t".implode(",\n\t\t\t\t\t", $columnsList)."\n\t\t\t\t]";
        $excelColumns = json_encode($excelColumnsList, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $drawCallBack = "";         if ($this->drawCallBack) $drawCallBack = "drawCallback : function(settings) { {$this->drawCallBack} },\n";
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
                if (isset($info["label"]) && $info["label"]) $html .= "\t\t\t\t<label class='ms-3 me-1' for='sf_search_{$alias}'>{$info["label"]}: </label>\\\n";
                if (isset($info["style"]) && $info["style"]) $style = "style='{$info["style"]}' ";
                switch($info["type"]) {
                    case "select" :
                        $html .= "\t\t\t\t<select class='form-select form-select-sm w-auto sf-custom-search-{$this->jsTableName}' name='sf_search_{$alias}' id='sf_search_{$alias}' {$style}>\\\n";
                        foreach($info["list"] as $attr) {
                            if ($default === null) $default = $attr["value"];
                            $selected = "";
                            if (isset($attr["selected"]) && $attr["selected"]) { $selected = " selected"; $default = $attr["value"]; }
                            $html .= "\t\t\t\t\t<option value='{$attr["value"]}'{$selected}>{$attr["text"]}\\\n";
                        }
                        $html .= "\t\t\t\t</select>\\\n";
                        if ($info["isSelect2"]) $customSearchSelect2 .= "$(\"#sf_search_{$alias}\").select2({theme: 'bootstrap-5'});\n";
                        break;
                    case "dateRange" :
                        $html .= "\t\t\t\t<input type='search' class='form-control form-control-sm w-auto sf-custom-search-{$this->jsTableName}' {$style}name='sf_search_{$alias}' id='sf_search_{$alias}' autocomplete='off'>\\\n";
                        $customSearchDateRange .= "$(\"#sf_search_{$alias}\").daterangepicker({ timePicker:true, autoUpdateInput: false, locale: { format: 'YYYY-MM-DD HH:mm', cancelLabel: 'Clear' }});\n\t\t";
                        $customSearchDateRange .= "$(\"#sf_search_{$alias}\").on(\"apply.daterangepicker\", function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm')); {$this->jsTableName}.ajax.reload(null, false); });\n\t\t";
                        break;
                    case "hidden" :
                        $html .= "\t\t\t\t<input type='hidden' name='sf_search_{$alias}' id='sf_search_{$alias}'>\\\n";
                        break;
                }
                $customSearchList[] = $html;
                $customSearchAjaxData .= "data.sf_search_{$alias} = valueForSelect($(\"#sf_search_{$alias}\"), \"{$default}\");\n\t\t\t\t";
            }
        }
        if ($customSearchList) {
            $customSearch = "$(\"div.sf-custom-search\").html(\"\\\n\t\t\t<div class='d-flex flex-row align-items-center flex-wrap'>\\\n" . implode("", $customSearchList) . "\t\t\t</div>\\\n\t\t\");";
            $customSearchReload = "$(document).on(\"change\", \".sf-custom-search-{$this->jsTableName}\", function() { {$this->jsTableName}.ajax.reload(null, false); });";
        }

        $buttons = array();
        if ($this->excelEnable) {
            $buttons[] = "{ extend: 'excelHtml5', titleAttr: 'Excel', {$excelFileName}{$excelButtonText}{$excelButtonClassName}action: newexportaction, exportOptions: { columns: {$excelColumns} } }";
        }
        if ($this->newRecord) {
            $buttons[] = "{ title: 'New', text: '{$this->newRecordModalTitle}', action: sf_open_add_form_{$this->setName}, {$excelButtonClassName} },";
            $this->setScriptNewRecord();
        }
        if ($this->extraButtons) {
            foreach($this->extraButtons as $exBtn) {
                $class = "";
                $action = "";
                if ($exBtn["class"]) $class = "className: '{$exBtn["class"]}', ";
                if ($exBtn["action"]) $action = "action: {$exBtn["action"]}, ";
                $buttons[] = "{ text: '{$exBtn["text"]}', {$class}{$action} }";
            }
        }
        $buttonsStr = implode(",", $buttons);

        $deliverParameters = "";
        if ($this->deliverParameters) {
            foreach($this->deliverParameters as $k => $v) {
                $deliverParameters .= "data.{$k} = '{$v}';\n\t\t\t\t";
            }
        }

        $customInvisible = "";
        if ($invisibleColumnsList) $customInvisible = $this->jsTableName.".columns([".implode(",", $invisibleColumnsList)."]).visible(false);";

        $this->javaScript[] = <<<EOD
            // This is the code generated through the DataTable class.
            var {$this->jsTableName} = null;
            function {$this->jsTableName}_init() {
                if ({$this->jsTableName}) {
                    {$this->jsTableName}.ajax.reload(null, false);
                } else {
                    {$this->jsTableName} = $("#{$this->htmlTableId}").DataTable({
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
                            {$deliverParameters}
                            data.sfdtmode = 'listAjax';
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
                                console.log("ajax fail:", jqXHR, textStatus);
                            });
                        },
                        columns     : {$columns},
                        order       : {$order},
                        aaSorting   : [  ],
                        dom         : "{$this->dom}",
                        buttons     : [ {$buttonsStr} ],
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
                            "search" : "검색: ",
                            "zeroRecords" : "검색된 데이터가 없습니다.",
                            "paginate" : { "first" : "처음", "last" : "마지막", "next" : "다음","previous" : "이전" },
                            "aria" : { "sortAscending" : " :  오름차순 정렬", "sortDescending" : " :  내림차순 정렬" }
                        },
                        {$drawCallBack}{$createdRow}
                    });
                    $("div.sf-page-input").html("<input type='number' id='sf-paginate-number' class='form-control form-control-sm' min='1'><button type='button' id='sf-btn-paginate-direct' class='btn btn-sm btn-dark'>이동</button>");
                    {$customSearch}
                    {$customSearchSelect2}
                    {$customSearchDateRange}
                    {$customInvisible}
                }
            }

            EOD;

        $this->javaScriptReady[] = <<<EOD
                {$this->jsTableName}_init();
                $(document).on('click', '#sf-btn-paginate-direct', function() { {$this->jsTableName}.page(parseInt($('#sf-paginate-number').val())-1).draw(false); });
                $(document).on('page.dt draw.dt', '#{$this->htmlTableId}', function() { $('#sf-paginate-number').val({$this->jsTableName}.page.info().page+1); });

                $("#{$this->htmlTableId}_filter > label > input[type='search']").attr("id", "{$this->htmlTableId}-searchbox");
                {$customSearchReload}
            EOD;
    }

    private function setScriptNewRecord()
    {
        $openScript = "";
        $formHtml = "\n";
        $modalId = "sf-modal-add-{$this->setName}";
        foreach($this->newRecordLayout as $arr) {
            if (is_array($arr)) {
                $formHtml .= "\t\t\t\t<div class='row row-cols-lg-auto'>\n";
                foreach($arr as $alias => $arr2) {
                    $formHtml .= $this->_modalRecordUnit($arr2, $this->newRecord[$arr2], "new", $openScript, $modalId, "col-auto");
                }
                $formHtml .= "\t\t\t\t</div>\n";
            } else {
                $formHtml .= $this->_modalRecordUnit($arr, $this->newRecord[$arr], "new", $openScript, $modalId);
            }
        }

        $this->htmlModal[] = <<<EOD
            <div class="modal fade" id="{$modalId}">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-add">
                            <h5 class="modal-title">{$this->newRecordModalTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="sf-add-form-{$this->setName}">
                            <form id="sf-frm-add-{$this->setName}" autocomplete="off">
                            {$formHtml}
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">취소</button>
                            <button id="sf-btn-add-{$this->setName}" type="button" class="btn btn-sm btn-primary">추가</button>
                        </div>
                    </div>
                </div>
            </div>
            EOD;

        $this->javaScript[] = <<<EOD
            var sf_add_inner_{$this->setName} = "";
            function sf_open_add_form_{$this->setName}() {
                $("#sf-add-form-{$this->setName}").html(sf_add_inner_{$this->setName});
                {$openScript}
                $("#sf-modal-add-{$this->setName}").modal("show");
            }
            EOD;
        $this->javaScriptReady[] = <<<EOD
                sf_add_inner_{$this->setName} = $("#sf-add-form-{$this->setName}").html();
                $(document).on("click", "#sf-btn-add-{$this->setName}", function() {
                    if (!$("#sf-frm-add-{$this->setName}")[0].checkValidity()) {
                        $("#sf-frm-add-{$this->setName}")[0].reportValidity()
                        return false;
                    }
                    var formData = new FormData($("#sf-frm-add-{$this->setName}")[0]);
                    formData.append("sfdtmode", "submitForNewAjax");
                    {$this->deliverAppendCode()}
                    callAjax(
                        "{$this->newRecordAjaxUrl}",
                        Object.fromEntries(formData),
                        function(result) {
                            if (result.data.result != true) {
                                alert("오류가 발생하였습니다. 잠시 후 다시 시도해주세요.");
                                return;
                            }
                            noti("등록 되었습니다.");
                            {$this->jsTableName}.ajax.reload(null, false);
                            $("#sf-modal-add-{$this->setName}").modal("hide");
                        }
                    )
                });
            EOD;
    }

    private function setScriptModify()
    {
        $openScript = "";
        $fillScript = "";
        $formHtml = "\n";
        $modalId = "sf-modal-modify-{$this->setName}";
        foreach($this->modifyRecordLayout as $arr) {
            if (is_array($arr)) {
                $formHtml .= "\t\t\t\t<div class='row row-cols-lg-auto'>\n";
                foreach($arr as $alias => $arr2) {
                    $formHtml .= $this->_modalRecordUnit($arr2, $this->modifyRecord[$arr2], "modify", $openScript, $modalId, "col-auto");
                    if ($fillScript) $fillScript .= "\n\t\t\t\t";
                    $this->_modalRecordFill($arr2, $this->modifyRecord[$arr2], "result.data.info.{$arr2}", $fillScript);
                }
                $formHtml .= "\t\t\t\t</div>\n";
            } else {
                $formHtml .= $this->_modalRecordUnit($arr, $this->modifyRecord[$arr], "modify", $openScript, $modalId);
                if ($fillScript) $fillScript .= "\n\t\t\t\t";
                $this->_modalRecordFill($arr, $this->modifyRecord[$arr], "result.data.info.{$arr}", $fillScript);
            }
        }

        $this->htmlModal[] = <<<EOD
            <div class="modal fade" id="{$modalId}">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-modify">
                            <h5 class="modal-title">{$this->modifyRecordModalTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="sf-modify-form-{$this->setName}">
                            <form id="sf-frm-modify-{$this->setName}" autocomplete="off">
                            <input type="hidden" id="sf-{$this->setName}-modify-pk" name="sf-{$this->setName}-modify-pk" value="">
                            {$formHtml}
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">취소</button>
                            <button id="sf-btn-modify-{$this->setName}" type="button" class="btn btn-sm btn-primary">수정</button>
                        </div>
                    </div>
                </div>
            </div>
            EOD;

        $this->javaScript[] = <<<EOD
            var sf_modify_inner_{$this->setName} = "";
            EOD;
        $this->javaScriptReady[] = <<<EOD
            sf_modify_inner_{$this->setName} = $("#sf-modify-form-{$this->setName}").html();
            $(document).on("click", ".btn-{$this->setName}-modify", function() {
                var pk = JSON.stringify($(this).data("pk"));
                callAjax(
                    "{$this->modifyRecordReadAjaxUrl}",
                    { pk : pk, sfdtmode : 'detailForModifyAjax', {$this->deliverParamCode()} },
                    function(result) {
                        if (!result.data.info) {
                            alert("오류가 발생하였습니다. 잠시 후 다시 시도해주세요.");
                            return;
                        }
                        //console.log(result.data);
                        $("#sf-modify-form-{$this->setName}").html(sf_modify_inner_{$this->setName});
                        {$openScript}
                        {$fillScript}
                        $("#sf-{$this->setName}-modify-pk").val(pk);
                        $("#sf-modal-modify-{$this->setName}").modal("show");
                    }
                );
            });
            $(document).on("click", "#sf-btn-modify-{$this->setName}", function() {
                if (!$("#sf-frm-modify-{$this->setName}")[0].checkValidity()) {
                    $("#sf-frm-modify-{$this->setName}")[0].reportValidity()
                    return false;
                }
                var formData = new FormData($("#sf-frm-modify-{$this->setName}")[0]);
                formData.append("sfdtmode", "submitForModifyAjax");
                {$this->deliverAppendCode()}
                callAjax(
                    "{$this->modifyRecordSubmitAjaxUrl}",
                    Object.fromEntries(formData),
                    function(result) {
                        if (result.data.result != true) {
                            alert("오류가 발생하였습니다. 잠시 후 다시 시도해주세요.");
                            return;
                        }
                        noti("수정 되었습니다.");
                        {$this->jsTableName}.ajax.reload(null, false);
                        $("#sf-modal-modify-{$this->setName}").modal("hide");
                    }
                )
            });
        EOD;

    }

    private function setScriptDelete()
    {
        $this->javaScriptReady[] = <<<EOD
            $(document).on("click", ".btn-{$this->setName}-delete", function() {
                var pk = JSON.stringify($(this).data("pk"));
                confirm("정말 삭제하시겠습니까? 삭제된 자료는 복구 되지 않습니다.", function() {
                    callAjax(
                        "{$this->deleteRecordSubmitAjaxUrl}",
                        {
                            "sfdtmode": "submitForDeleteAjax",
                            "sf-{$this->setName}-delete-pk": pk
                        },
                        function(result) {
                            if (result.data.result != true) {
                                alert("오류가 발생하였습니다. 잠시 후 다시 시도해주세요.");
                                return;
                            }
                            noti("삭제 되었습니다.");
                            {$this->jsTableName}.ajax.reload(null, false);
                        }
                    )
                });
            });
        EOD;
    }

    private function setScriptDetailInfo()
    {
        $openScript = "\n";
        $formHtml = "\n";
        foreach($this->detailInfoLayout as $arr) {
            if (is_array($arr)) {
                $formHtml .= "\t\t\t\t<div class='row row-cols-lg-auto'>\n";
                foreach($arr as $alias => $arr2) {
                    $formHtml .= $this->_modalDetailInfoUnit($arr2, $this->detailInfo[$arr2], $openScript, "col-auto");
                }
                $formHtml .= "\t\t\t\t</div>\n";
            } else {
                $formHtml .= $this->_modalDetailInfoUnit($arr, $this->detailInfo[$arr], $openScript);
            }
        }
        $this->htmlModal[] = <<<EOD
            <div class="modal fade" id="sf-modal-detailinfo-{$this->setName}">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-detail">
                            <h5 class="modal-title">{$this->detailInfoModalTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="sf-detailinfo-{$this->setName}">
                            {$formHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
            EOD;

        $this->javaScriptReady[] = <<<EOD
            $(document).on("click", ".btn-{$this->setName}-detailinfo", function() {
                var pk = JSON.stringify($(this).data("pk"));
                callAjax(
                    "{$this->detailInfoReadAjaxUrl}",
                    { pk : pk, sfdtmode : 'detailAjax', {$this->deliverParamCode()} },
                    function(result) {
                        if (!result.data.info) {
                            alert("오류가 발생하였습니다. 잠시 후 다시 시도해주세요.");
                            return;
                        }
                        {$openScript}
                        $("#sf-modal-detailinfo-{$this->setName}").modal("show");
                    }
                );
            });
        EOD;
    }

    private function _modalDetailInfoUnit($alias, $recordInfo, &$openScript, $divClass = "")
    {
        $label = $recordInfo["label"] ?? $alias;

        $divClass = join(" ", array_filter([ "mb-2", $divClass, $recordInfo["divClassName"] ]));
        $class = join(" ", array_filter([ "data-modal-detailinfo", $recordInfo["className"], (($recordInfo["textCenter"]) ? "text-center" : ""), (($recordInfo["textLeft"]) ? "text-start" : ""), (($recordInfo["textRight"]) ? "text-end" : "") ]));

        $style = "";      if ($recordInfo["style"])       $style = " style=\"{$recordInfo["style"]}\"";
        $divStyle = "";   if ($recordInfo["divStyle"])    $divStyle = " style=\"{$recordInfo["divStyle"]}\"";
        $labelStyle = ""; if ($recordInfo["labelStyle"])  $labelStyle = " style=\"{$recordInfo["labelStyle"]}\"";


        if (isset($recordInfo["sectionTitle"]) && $recordInfo["sectionTitle"]) {
            return <<<EOD
                <div class="data-modal-detailinfo-section-title">
                    <div class="fw-bold fs-5">{$recordInfo["sectionTitle"]}</div>
                </div>
            EOD;
        }

        if (isset($recordInfo["customHTML"]) && $recordInfo["customHTML"]) {
            return <<<EOD
                <div class="{$divClass}"{$divStyle}>
                    <div>{$recordInfo["customHTML"]}</div>
                </div>
            EOD;
        }


        $customJS = "escapeHtml(result.data.info.{$alias})";
        if (isset($recordInfo["customJS"])) $customJS = $recordInfo["customJS"];

        if ($recordInfo["displayEnum"]) {
            $scp = "";
            foreach($recordInfo["displayEnum"] as $value => $displayText) {
                if ($scp) $scp .= "else ";
                $scp .= "if (result.data.info.{$alias} == '{$value}') $(\"#sf-{$this->setName}-detailinfo-{$alias}\").html(\"{$displayText}\");\n";
            }
            if ($scp) $scp .= "else ";
            $scp .= "$(\"#sf-{$this->setName}-detailinfo-{$alias}\").html({$customJS});\n";
            $openScript .= "\t\t\t\t" . str_replace("\n", "\n\t\t\t\t", $scp) . "\n";
        } else {
            $openScript .= <<<EOD
                            $("#sf-{$this->setName}-detailinfo-{$alias}").html({$customJS});

            EOD;
        }

        if ($recordInfo["textAmount"]) {
            $openScript .= <<<EOD
                            var v = parseFloat($("#sf-{$this->setName}-detailinfo-{$alias}").html());
                            if (isNaN(v)) $("#sf-{$this->setName}-detailinfo-{$alias}").html(0);
                            else if (typeof v == "number") $("#sf-{$this->setName}-detailinfo-{$alias}").html(v.numberFormat());

            EOD;
        }

        return <<<EOD
                        <div class="{$divClass}"{$divStyle}>
                            <div class="d-inline-flex flex-column">
                                <div class="label-modal-detailinfo"{$labelStyle}>{$label}</div>
                                <div class="{$class}" id="sf-{$this->setName}-detailinfo-{$alias}"{$style}></div>
                            </div>
                        </div>\n
        EOD;
    }

    private function _modalRecordFill($alias, $recordInfo, $defaultValue, &$fillScript)
    {
        if ($recordInfo["notfill"]) return;
        switch($recordInfo["type"]) {
            case "select" :
                if ($recordInfo["readonly"]) {
                    foreach($recordInfo["optionList"] as $k => $v) {
                        $fillScript .= "if ({$defaultValue} == '{$k}') $(\"#sf-{$this->setName}-modify-{$alias}-text\").val('{$v}');";
                    }
                    $fillScript .= "$(\"#sf-{$this->setName}-modify-{$alias}\").val({$defaultValue});";
                } else {
                    if (count($recordInfo["optionList"]) >= 10) {
                        $fillScript .= "$(\"#sf-{$this->setName}-modify-{$alias}\").val({$defaultValue}).trigger('change');";
                    } else {
                        $fillScript .= "$(\"#sf-{$this->setName}-modify-{$alias}\").val({$defaultValue}).prop('selected',true);";
                    }
                }
                break;
            case "radio" :
                $fillScript .= "$(\"input:radio[name=sf-{$this->setName}-modify-{$alias}][value=\"+{$defaultValue}+\"]\").prop('checked', true);";
                break;
            case "checkbox" :
                $v = array_keys($recordInfo["optionList"])[0];
                $fillScript .= "if ({$defaultValue} == '{$v}') $(\"input:checkbox[name=sf-{$this->setName}-modify-{$alias}]\").prop('checked', true);";
                break;
            case "password" :
                $fillScript .= "$(\"#sf-{$this->setName}-modify-{$alias}\").val(\"\");";
                break;
            default :
                $fillScript .= "$(\"#sf-{$this->setName}-modify-{$alias}\").val({$defaultValue});";
                break;
        }

    }

    private function _modalRecordUnit($alias, $recordInfo, $prefix, &$openScript, $modalId, $divClass = "")
    {
        $label = $recordInfo["label"] ?? $alias;
        $type = $recordInfo["type"] ?? "text";

        $divClass = join(" ", array_filter([ "mb-2", $divClass, $recordInfo["divClassName"] ]));
        $class = join(" ", array_filter([ $recordInfo["className"], (($recordInfo["textCenter"]) ? "text-center" : ""), (($recordInfo["textLeft"]) ? "text-start" : ""), (($recordInfo["textRight"]) ? "text-end" : "") ]));

        $style = "";      if ($recordInfo["style"])       $style = " style=\"{$recordInfo["style"]}\"";
        $divStyle = "";   if ($recordInfo["divStyle"])    $divStyle = " style=\"{$recordInfo["divStyle"]}\"";
        $labelStyle = ""; if ($recordInfo["labelStyle"])  $labelStyle = " style=\"{$recordInfo["labelStyle"]}\"";
        $readonly = "";   if ($recordInfo["readonly"])    $readonly = " readonly";
        $comment = "";    if ($recordInfo["comment"])     $comment = "<small class=\"mute\">{$recordInfo["comment"]}</small>";

        $requiredStar = "";
        $required = "";
        if ($recordInfo["required"]) {
            $requiredStar = "(<i class=\"bi bi-asterisk text-danger\" style=\"font-size:0.5rem;\"></i>)";
            $required = " required";
        }
        $defaultValue = "";

        $custom = ""; if($recordInfo["custom"]) $custom = " {$recordInfo["custom"]}";

        switch($type) {
            case "hidden" :
                return <<<EOD
                                <input type="hidden" id="sf-{$this->setName}-{$prefix}-{$alias}" name="sf-{$this->setName}-{$prefix}-{$alias}"{$custom}>\n
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
                if ($recordInfo["defaultValue"]) $defaultValue = " value=\"" . htmlspecialchars($recordInfo["defaultValue"]) . "\"";
                return <<<EOD
                                <div class="{$divClass}"{$divStyle}>
                                    <label for="sf-{$this->setName}-{$prefix}-{$alias}" class="col-form-label"{$labelStyle}>{$label}{$requiredStar}:</label>
                                    <input type="{$type}" class="form-control form-control-sm{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}" name="sf-{$this->setName}-{$prefix}-{$alias}"{$style}{$required}{$defaultValue}{$readonly}{$custom}>
                                    {$comment}
                                </div>\n
                EOD;
                break;
            case "datetime" :
                if ($recordInfo["defaultValue"]) $defaultValue = " value=\"" . htmlspecialchars($recordInfo["defaultValue"]) . "\"";
                return <<<EOD
                                <div class="{$divClass}"{$divStyle}>
                                    <label for="sf-{$this->setName}-{$prefix}-{$alias}" class="col-form-label"{$labelStyle}>{$label}{$requiredStar}:</label>
                                    <input type="datetime-local" class="form-control form-control-sm{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}" name="sf-{$this->setName}-{$prefix}-{$alias}"{$style}{$required}{$defaultValue}{$readonly}{$custom}>
                                    {$comment}
                                </div>\n
                EOD;
                break;
            case "textarea" :
                if ($recordInfo["defaultValue"]) $defaultValue = htmlspecialchars($recordInfo["defaultValue"]);
                return <<<EOD
                                <div class="{$divClass}"{$divStyle}>
                                    <label for="sf-{$this->setName}-{$prefix}-{$alias}" class="col-form-label"{$labelStyle}>{$label}{$requiredStar}:</label>
                                    <textarea class="form-control form-control-sm{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}" name="sf-{$this->setName}-{$prefix}-{$alias}"{$style}{$required}{$readonly}{$custom}>{$defaultValue}</textarea>
                                    {$comment}
                                </div>\n
                EOD;
                break;
            case "select" :
                //if (!$recordInfo["optionList"]) self::system("A definition is required for the attribute: optionList");

                if ($readonly == " readonly") {
                    $defaultValue = $recordInfo["defaultValue"] ?? "";
                    $defaultText = $recordInfo["defaultValue"] ?? "";
                    foreach($recordInfo["optionList"] as $k => $v) {
                        if (isset($recordInfo["defaultValue"]) && $recordInfo["defaultValue"] == $k) { $defaultValue = $k; $defaultText = $v; break; }
                    }
                    return <<<EOD
                                    <div class="{$divClass}"{$divStyle}>
                                        <label for="sf-{$this->setName}-{$prefix}-{$alias}-text" class="col-form-label"{$labelStyle}>{$label}{$requiredStar}:</label>
                                        <input type="hidden" class="{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}" name="sf-{$this->setName}-{$prefix}-{$alias}" value="{$defaultValue}">
                                        <input type="text" class="form-control form-control-sm{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}-text" name="sf-{$this->setName}-{$prefix}-{$alias}-text"{$style} readonly value="{$defaultText}"{$custom}>
                                        {$comment}
                                    </div>\n
                    EOD;
                } else {
                    $options = "";
                    if ($recordInfo["optionList"]) {
                        foreach($recordInfo["optionList"] as $k => $v) {
                            if ($options) $options .= "\n\t\t\t\t\t";
                            if (isset($recordInfo["defaultValue"]) && $recordInfo["defaultValue"] == $k) $selected = " selected"; else $selected = "";
                            $options .= "<option value=\"{$k}\"{$selected}>{$v}</option>";
                        }
                        if (count($recordInfo["optionList"]) >= 10) {
                            if ($openScript) $openScript .= "\n\t";
                            $openScript .= "$(\"#sf-{$this->setName}-{$prefix}-{$alias}\").select2({theme: 'bootstrap-5',dropdownParent: $(\"#{$modalId}\")});";
                        }
                    }
                    return <<<EOD
                                    <div class="{$divClass}{$divStyle}">
                                        <label for="sf-{$this->setName}-{$prefix}-{$alias}" class="col-form-label"{$labelStyle}>{$label}{$requiredStar}:</label>
                                        <select class="form-select form-select-sm w-auto{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}" name="sf-{$this->setName}-{$prefix}-{$alias}"{$style}{$required}{$readonly}{$custom}>
                                        {$options}
                                        </select>
                                        {$comment}
                                    </div>\n
                    EOD;
                }
                break;
            case "checkbox" :
                if (!$recordInfo["optionList"]) self::system("A definition is required for the attribute: optionList");

                $input = "\n";
                $idx = 0;
                $defaultValueArr = explode(",", ($recordInfo["defaultValue"] ?? ""));
                foreach($recordInfo["optionList"] as $k => $v) {
                    if (in_array($k, $defaultValueArr)) $checked = " checked"; else $checked = "";
                    $idx ++;
                    $input .= <<<EOD
                                            <div class="form-check me-3">
                                                <input type="checkbox" class="form-check-input{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}-{$idx}" name="sf-{$this->setName}-{$prefix}-{$alias}" value="{$k}"{$style}{$checked}{$readonly}{$custom}>
                                                <label class="form-check-label text-nowrap" for="sf-{$this->setName}-{$prefix}-{$alias}-{$idx}"{$labelStyle}>{$v}</label>
                                            </div>\n
                    EOD;
                }
                return <<<EOD
                                <div class="{$divClass}"{$divStyle}>
                                    <label for="sf-{$this->setName}-{$prefix}-{$alias}-1" class="col-form-label"{$labelStyle}>{$label}:</label>
                                    <div class="d-flex flex-wrap">
                                    {$input}
                                    </div>
                                    {$comment}
                                </div>\n
                EOD;
                break;
            case "radio" :
                if (!$recordInfo["optionList"]) self::system("A definition is required for the attribute: optionList");

                $input = "\n";
                $idx = 0;
                $defaultValue = $recordInfo["defaultValue"] ?? null;
                foreach($recordInfo["optionList"] as $k => $v) {
                    if (($idx == 0 && !$defaultValue) || ($defaultValue == $k)) $checked = " checked"; else $checked = "";
                    $idx ++;
                    $input .= <<<EOD
                                            <div class="form-check me-3">
                                                <input type="radio" class="form-radio-input{$class}" id="sf-{$this->setName}-{$prefix}-{$alias}-{$idx}" name="sf-{$this->setName}-{$prefix}-{$alias}" value="{$k}"{$style}{$checked}{$readonly}{$custom}>
                                                <label class="form-radio-label text-nowrap" for="sf-{$this->setName}-{$prefix}-{$alias}-{$idx}"{$labelStyle}>{$v}</label>
                                            </div>\n
                    EOD;
                }
                return <<<EOD
                                <div class="{$divClass}"{$divStyle}>
                                    <label for="sf-{$this->setName}-{$prefix}-{$alias}-1" class="col-form-label"{$labelStyle}>{$label}:</label>
                                    <div class="d-flex flex-wrap">
                                    {$input}
                                    </div>
                                    {$comment}
                                </div>\n
                EOD;
                break;
        }
    }

    public function setJS($script)
    {
        $this->javaScript2[] = $script;
    }

    public function setJSReady($script)
    {
        $this->javaScriptReady2[] = $script;
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
        $javaScript = implode("\n", $this->javaScript);
        $javaScriptReady = implode("\n", $this->javaScriptReady);
        $javaScript2 = implode("\n", $this->javaScript2);
        $javaScriptReady2 = implode("\n", $this->javaScriptReady2);
        echo <<< EOD
            <script>
            {$javaScript}
            {$javaScript2}
            $(document).ready(function() {
            {$javaScriptReady}
            {$javaScriptReady2}
            });
            </script>
            EOD;
    }

    public function echoModal()
    {
        $this->build();
        if ($this->htmlModal) {
            $htmlModal = implode("\n", $this->htmlModal);
            echo $htmlModal . "\n";
        }
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
                $mc = $ji["matchColumn"];
                if (strpos($mc, ".") === false) $mc = "{$this->mainDBTable}.{$mc}";

                if ($ji["tableAlias"]) {
                    $joinSQL .= "left join {$ji["tableName"]} as {$ji["tableAlias"]} on {$ji["tableAlias"]}.{$ji["joinColumn"]} = {$mc}";
                } else {
                    $joinSQL .= "left join {$ji["tableName"]} on {$ji["tableName"]}.{$ji["joinColumn"]} = {$mc}";
                }
                if ($ji["joinCondition"]) $joinSQL .= " " . $ji["joinCondition"];
                $joinSQL .= "\n";
            }
        }
        if ($isSearch && $this->searchJoinDBTable) {
            foreach($this->searchJoinDBTable as $ji) {
                $mc = $ji["matchColumn"];
                if (strpos($mc, ".") === false) $mc = "{$this->mainDBTable}.{$mc}";
                $joinSQL .= "left join {$ji["tableName"]} on {$ji["tableName"]}.{$ji["joinColumn"]} = {$mc}";
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

    private function groupbySQL()
    {
        if (!$this->groupBy) return "";
        return "group by " . implode(",", $this->groupBy);
    }

    public function recordCount()
    {
        static $count = -1;
        if ($count != -1) return $count;

        $db = DB::getInstance($this->connectionName);
        $rs = $db->query("
            select count(*) as CNT
            from {$this->mainDBTable}
            " . $this->joinSQL() . "
            " . $this->whereSQL() . "
        ", $this->bind);
        $count = intval($db->fetch($rs)["CNT"]);
        return $count;
    }

    private function procSearching()
    {
        static $proc = false;
        if ($proc !== false) return;
        $param = $this->ajaxParam();
        if ($param->search["value"] ?? false) {
            foreach($param->columns as $idx => $col) {
                if (($col["searchable"] ?? "false") == "true" && $this->columns[$col["name"]]["realColumn"]) {
                    $this->setSearchOr("{$this->columns[$col["name"]]["realColumn"]} like :sf_search_{$idx}", array(":sf_search_{$idx}" => "%{$param->search["value"]}%"));
                }
            }
        }

        // custom search
        if ($this->customSearch) {
            foreach($this->customSearch as $alias => $info) {
                $val = $param->get("sf_search_{$alias}", null);
                if ($info["callback"] !== null) call_user_func($info["callback"], $val);

                if (!isset($this->columns[$alias]) || !$this->columns[$alias]) continue;
                if (!isset($this->columns[$alias]["realColumn"]) || !$this->columns[$alias]["realColumn"]) continue;
                if($val !== null) {
                    switch($info["type"]) {
                        case "select" :
                            $this->setSearchAnd("{$this->columns[$alias]["realColumn"]} = :sf_custom_search_{$alias}", array(":sf_custom_search_{$alias}" => $val));
                            break;
                        case "dateRange" :
                            $val = trim($val);
                            $valStart = substr($val, 0, 16);
                            $valEnd = substr($val, 19);

                            if (sfValidateDate($valStart, "Y-m-d H:i")) {
                                $dateTime = \DateTime::createFromFormat("Y-m-d H:i:s", "{$valStart}:00");
                                $this->setSearchAnd("{$this->columns[$alias]["realColumn"]} >= :sf_custom_search_{$alias}_start", array(":sf_custom_search_{$alias}_start" => $dateTime->format($info["compareDateFormat"])));
                                $val = $valStart . " - " . substr($val, 19);
                            }
                            if (sfValidateDate($valEnd, "Y-m-d H:i")) {
                                $dateTime = \DateTime::createFromFormat("Y-m-d H:i:s", "{$valEnd}:59");
                                $this->setSearchAnd("{$this->columns[$alias]["realColumn"]} <= :sf_custom_search_{$alias}_end", array(":sf_custom_search_{$alias}_end" => $dateTime->format($info["compareDateFormat"])));
                                $val = substr($val, 0, 16) . " - " . $valEnd;
                            }
                            break;
                        //case "hidden" :
                        //    call_user_func($info["callback"], $val);
                        //    break;
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
            select count(*) as CNT
            from {$this->mainDBTable}
            " . $this->joinSQL(1) . "
            " . $this->whereSQL(1) . "
        ", $this->bind);

        return intval($db->fetch($rs)["CNT"] ?? 0);
    }

    public function listData()
    {
        $this->procSearching();
        $param = $this->ajaxParam();

        $order = "";
        if($param->order) {
            $ol = array();
            foreach($param->order as $od) {
                $alias = $param->columns[$od["column"]]["name"];
                $dir = $od["dir"];
                $ol[] = "{$this->listing[$alias]["realColumn"]} {$dir}";
            }
            $order = "order by " . implode(",", $ol);
        }

        $db = DB::getInstance($this->connectionName);

        $columnArr = array();
        foreach($this->listing as $alias => $attr) {
            if (!$attr["realColumn"]) continue;
            if ($db->dbSystem() == "mysql") $alias = "`{$alias}`";
            $columnArr[] = "{$attr["realColumn"]} as {$alias}";
        }

        $columns = implode(",", $columnArr);

        if ($db->dbSystem() == "unknown") {
            $limitStart = $param->start + 1;
            $limitEnd = $param->start + $param->length;
            if ($order == "") $order = "order by {$this->mainDBTable}.{$this->mainDBTablePK[0]} desc";
            $rs = $db->query("
                select * from (
                    select
                    {$columns},
                    ROW_NUMBER() OVER ({$order}) AS sfrnum
                    from {$this->mainDBTable}
                    " . $this->joinSQL(1) . "
                    " . $this->whereSQL(1) . "
                    " . $this->groupbySQL() . "
                ) as otbl
                where sfrnum between {$limitStart} and {$limitEnd}
            ", $this->bind);
        } else {
            if ($db->dbSystem() == "sqlsrv") {
                $limit = "offset {$param->start} rows fetch next {$param->length} rows only";
            } else {
                $limit = "offset {$param->start} limit {$param->length}";
            }
            $rs = $db->query("
                select
                {$columns}
                from {$this->mainDBTable}
                " . $this->joinSQL(1) . "
                " . $this->whereSQL(1) . "
                " . $this->groupbySQL() . "
                {$order}
                {$limit}
            ", $this->bind);
        }
        $data = array();
        while($attr = $db->fetch($rs)) {
            $data[] = $attr;
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

    public function recordData($pk)
    {
        $db = DB::getInstance($this->connectionName);

        $columnArr = array();
        foreach($this->detailInfo as $alias => $attr) {
            if (!isset($attr["realColumn"]) || !$attr["realColumn"]) continue;
            if ($db->dbSystem() == "mysql") $alias = "`{$alias}`";
            $columnArr[] = "{$attr["realColumn"]} as {$alias}";
        }
        $columns = implode(",", $columnArr);

        $where = $this->whereSQL(1);
        if ($where) $where .= " and"; else $where = " where";

        $wherePk = [];
        foreach($this->mainDBTablePK as $pkf) {
            $wherePk[] = "{$this->mainDBTable}.{$pkf} = :{$pkf}";
            $this->bind[":{$pkf}"] = $pk[$pkf];
        }
        $rs = $db->query("
            select
            {$columns}
            from {$this->mainDBTable}
            " . $this->joinSQL(1) . "
            {$where} " . implode(" and ", $wherePk) . "
        ", $this->bind);
        $data = $db->fetch($rs);
        return $data;
    }

    // for 1 record info ajax
    public function ajaxDetailInfoResponse($pk)
    {
        $res = Response::getInstance();
        $res->info = $this->recordData($pk);
    }

    public function recordDataForModify($pk)
    {
        $db = DB::getInstance($this->connectionName);

        $columnArr = array();
        foreach($this->modifyRecord as $alias => $attr) {
            if (!$attr["realColumn"] || $attr["notfill"]) continue;
            if ($db->dbSystem() == "mysql") $alias = "`{$alias}`";
            $columnArr[] = "{$attr["realColumn"]} as {$alias}";
        }
        $columns = implode(",", $columnArr);

        $where = $this->whereSQL(1);
        if ($where) $where .= " and"; else $where = " where";

        $wherePk = [];
        foreach($this->mainDBTablePK as $pkf) {
            $wherePk[] = "{$this->mainDBTable}.{$pkf} = :{$pkf}";
            $this->bind[":{$pkf}"] = $pk[$pkf];
        }
        $rs = $db->query("
            select
            {$columns}
            from {$this->mainDBTable}
            " . $this->joinSQL(1) . "
            {$where} " . implode(" and ", $wherePk) . "
        ", $this->bind);
        $data = $db->fetch($rs);
        return $data;
    }

    // for 1 record info ajax
    public function ajaxDetailForModifyResponse($pk)
    {
        $res = Response::getInstance();
        $res->info = $this->recordDataForModify($pk);
    }



    //
    // When code is automatically generated and each page is automatically implemented to operate as one page
    //
    public function onePageExec()
    {
        $param = Param::getInstance();
        $param->check("sfdtmode", Param::TYPE_STRING, array("main", "listAjax", "detailAjax", "detailForModifyAjax", "submitForNewAjax", "submitForModifyAjax", "submitForDeleteAjax"));
        $mode = $param->get("sfdtmode", "main");

        switch($mode) {
            case "main":
                sfModeWeb();
                break;
            case "listAjax":
                sfModeAjaxForDatatable();
                $this->ajaxResponse();
                break;
            case "detailAjax":
                sfModeAjax();
                $param->checkKeyValue("pk", Param::TYPE_JSON);
                $this->ajaxDetailInfoResponse($param->pk);
                break;
            case "detailForModifyAjax":
                sfModeAjax();
                $param->checkKeyValue("pk", Param::TYPE_JSON);
                $this->ajaxDetailForModifyResponse($param->pk);
                break;
            case "submitForNewAjax":
                sfModeAjax();
                if (!$this->newRecordCallback) self::system("A callback function to handle new submit must be defined(setSubmitForNewCallback)");
                call_user_func($this->newRecordCallback);
                break;
            case "submitForModifyAjax":
                sfModeAjax();
                if (!$this->modifyRecordCallback) self::system("A callback function to handle modify submit must be defined(setSubmitForModifyCallback)");
                call_user_func($this->modifyRecordCallback);
                break;
            case "submitForDeleteAjax":
                sfModeAjax();
                if (!$this->deleteRecordCallback) self::system("A callback function to handle delete submit must be defined(setSubmitForDeleteCallback)");
                call_user_func($this->deleteRecordCallback);
                break;
        }
    }

    // A function called by (main)templates.
    public function onePageEcho()
    {
        $this->echoJS();
        $this->echoTable();
        $this->echoModal();
    }
}