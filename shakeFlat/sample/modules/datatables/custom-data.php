<?php
/**
 * modules/datatables/custom-data.php
 *
 * module sample for DataTable
 * Example of handling custom data
 *
 */

use shakeFlat\Param;
use shakeFlat\Response;
use shakeFlat\DB;

function fnc_custom_data()
{
    sfModeAjaxForDatatable();

    $param = Param::getInstance();
    $param->checkKeyValue("draw", Param::TYPE_INT);
    $param->checkKeyValue("columns", Param::TYPE_ARRAY);
    $param->check("order", Param::TYPE_ARRAY);
    $param->checkKeyValue("start", Param::TYPE_INT);
    $param->checkKeyValue("length", Param::TYPE_INT);
    $param->checkKeyValue("search", Param::TYPE_ARRAY);
    $param->check("customSearchEx", Param::TYPE_ARRAY);

    $db = DB::getInstance();

    //shakeFlat\L::debug($param->columns);
    //shakeFlat\L::debug($param->search);

    // recordsTotal
    $rs = $db->query("SELECT COUNT(*) AS cnt FROM members");
    $recordsTotal = $db->fetch($rs)['cnt'];

    // 검색처리
    $where = "";
    $whereOr = [];
    $whereAnd = [];
    $bind = [];
    $searchableColumns = [];
    // 각 컬럼별 검색어가 있는지 확인
    foreach($param->columns as $col) {
        if ($col['searchable']) $searchableColumns[] = $col['data'];
        if ($col['search']['value']) {
            if ($col['data'] == 'birth_date' || $col['data'] == 'join_date') {
                $between = explode(' - ', $col['search']['value']);
                if (count($between) == 2 && strtotime($between[0]) && strtotime($between[1])) {
                    $whereAnd[] = "{$col['data']} BETWEEN :wa_{$col['data']}_start AND :wa_{$col['data']}_end";
                    $bind[":wa_{$col['data']}_start"] = date("Y-m-d H:i:s", strtotime($between[0]));
                    $endDate = $between[1];
                    if (strlen($endDate) == 10) {
                        $endDate .= ' 23:59:59';
                    } elseif (strlen($endDate) == 13) {
                        $endDate .= ':59:59';
                    } elseif (strlen($endDate) == 16) {
                        $endDate .= ':59';
                    }
                    $bind[":wa_{$col['data']}_end"] = date("Y-m-d H:i:s", strtotime($endDate));
                }
            } elseif ($col['data'] == 'salary') {
                $between = explode(' - ', $col['search']['value']);
                if (count($between) == 2) {
                    $between[0] = intval(preg_replace('/\D/', '', $between[0]));
                    $between[1] = intval(preg_replace('/\D/', '', $between[1]));
                    $whereAnd[] = "{$col['data']} BETWEEN :wa_{$col['data']}_start AND :wa_{$col['data']}_end";
                    $bind[":wa_{$col['data']}_start"] = $between[0];
                    $bind[":wa_{$col['data']}_end"] = $between[1];
                } else {
                    $whereAnd[] = "{$col['data']} = :wa_{$col['data']}";
                    $bind[":wa_{$col['data']}"] = intval(preg_replace('/\D/', '', $col['search']['value']));
                }
            } else {
                if ($col['search']['regex'] === 'true') {      // regex 가 true 면 like 검색을 하지 않고 일치하는지 검사한다.
                    $whereAnd[] = "{$col['data']} = :wa_{$col['data']}";
                    $bind[":wa_{$col['data']}"] = $col['search']['value'];
                } else {
                    $whereAnd[] = "{$col['data']} LIKE :wa_{$col['data']}";
                    $bind[":wa_{$col['data']}"] = "%{$col['search']['value']}%";
                }
            }
        }
    }

    if ($param->customSearchEx["join_date2"] ?? false) {
        $between = explode(' - ', $param->customSearchEx["join_date2"]);
        if (count($between) == 2 && strtotime($between[0]) && strtotime($between[1])) {
            $whereAnd[] = "join_date BETWEEN :waex_join_date2_start AND :waex_join_date2_end";
            $bind[":waex_join_date2_start"] = date("Y-m-d H:i:s", strtotime($between[0]));
            $endDate = $between[1];
            if (strlen($endDate) == 10) {
                $endDate .= ' 23:59:59';
            } elseif (strlen($endDate) == 13) {
                $endDate .= ':59:59';
            } elseif (strlen($endDate) == 16) {
                $endDate .= ':59';
            }
            $bind[":waex_join_date2_end"] = date("Y-m-d H:i:s", strtotime($endDate));
        }
    }

    if ($param->customSearchEx["salary2"] ?? false) {
        $between = explode(' - ', $param->customSearchEx["salary2"]);
        if (count($between) == 2) {
            $between[0] = intval(preg_replace('/\D/', '', $between[0]));
            $between[1] = intval(preg_replace('/\D/', '', $between[1]));
            $whereAnd[] = "salary BETWEEN :waex_{$col['data']}_start AND :waex_{$col['data']}_end";
            $bind[":waex_{$col['data']}_start"] = $between[0];
            $bind[":waex_{$col['data']}_end"] = $between[1];
        } else {
            $whereAnd[] = "salary = :waex_{$col['data']}";
            $bind[":waex_{$col['data']}"] = intval(preg_replace('/\D/', '', $param->customSearchEx["salary2"]));
        }
    }

    // 전체 검색어가 있는지 확인
    if ($param->search['value'] && count($searchableColumns) > 0) {
        foreach($searchableColumns as $col) {
            $whereOr[] = "{$col} LIKE :wo_{$col}";
            $bind[":wo_{$col}"] = "%{$param->search['value']}%";
        }
    }
    if ($whereOr) $whereAnd[] = '(' . implode(' OR ', $whereOr) . ')';
    if ($whereAnd) $where = 'where ' . implode(' AND ', $whereAnd);

    // recordsFiltered
    $rs = $db->query("SELECT COUNT(*) as cnt FROM members {$where}", $bind);
    $recordsFiltered = $db->fetch($rs)['cnt'];

    // order by 처리
    $order = "";
    if ($param->order) {
        $orders = [];
        foreach($param->order as $ord) {
            $orders[] = "{$param->columns[$ord['column']]['data']} {$ord['dir']}";
        }
        $order = "ORDER BY " . implode(', ', $orders);
    }

    // paging 처리
    $limit = "LIMIT {$param->start}, {$param->length}";

    // 데이터 조회
    $rs = $db->query("SELECT * FROM members {$where} {$order} {$limit}", $bind);
    $data = $db->fetchAll($rs);

    $res = Response::getInstance();
    $res->draw              = $param->draw;
    $res->recordsTotal      = $recordsTotal;
    $res->recordsFiltered   = $recordsFiltered;
    $res->data              = $data;
}

/* param example
json: Array
(
    [draw] => 9
    [columns] => Array
        (
            [0] => Array
                (
                    [data] => id
                    [name] =>
                    [searchable] => true
                    [orderable] => true
                    [search] => Array
                        (
                            [value] =>
                            [regex] => false
                        )

                )

            [1] => Array
                (
                    [data] => name
                    [name] =>
                    [searchable] => true
                    [orderable] => true
                    [search] => Array
                        (
                            [value] =>
                            [regex] => false
                        )

                )

            [2] => Array
                (
                    [data] => position
                    [name] =>
                    [searchable] => true
                    [orderable] => true
                    [search] => Array
                        (
                            [value] => 강남구
                            [regex] => false
                        )

                )

            [3] => Array
                (
                    [data] => office
                    [name] =>
                    [searchable] => true
                    [orderable] => true
                    [search] => Array
                        (
                            [value] =>
                            [regex] => false
                        )

                )

            [4] => Array
                (
                    [data] => start_date
                    [name] =>
                    [searchable] => true
                    [orderable] => true
                    [search] => Array
                        (
                            [value] =>
                            [regex] => false
                        )

                )

            [5] => Array
                (
                    [data] => salary
                    [name] =>
                    [searchable] => true
                    [orderable] => true
                    [search] => Array
                        (
                            [value] =>
                            [regex] => false
                        )

                )

        )

    [order] => Array
        (
            [0] => Array
                (
                    [column] => 0
                    [dir] => desc
                    [name] =>
                )

        )

    [start] => 0
    [length] => 20
    [search] => Array
        (
            [value] => 홍길동
            [regex] => false
            [fixed] => Array
                (
                    [0] => Array
                        (
                            [name] => range
                            [term] => function (searchStr, data, index) {
        if ($('input[name="example-custom-search-date"]').val() === '') return true;
        var dateRange = $('input[name="example-custom-search-date"]').val().split(' - ');
        var min = dateRange[0] ? new Date(dateRange[0]).getTime() : NaN;
        var max = dateRange[1] ? new Date(dateRange[1]).getTime() : NaN;
        if (isNaN(min) || isNaN(max)) {
            $('input[name="example-custom-search-date"]').addClass('is-invalid').attr('title', '날짜 형식이 잘못되었습니다.');
            return true;
        }

        $('input[name="example-custom-search-date"]').removeClass('is-invalid').removeAttr('title');
        var startDate = new Date(data[4]).getTime() || NaN;
        if (
            (isNaN(min) && isNaN(max)) ||
            (isNaN(min) && startDate <= max) ||
            (min <= startDate && isNaN(max)) ||
            (min <= startDate && startDate <= max)
        ) {
            return true;
        }

        return false;
    }
                        )

                )

        )

    [test] => test
)
*/