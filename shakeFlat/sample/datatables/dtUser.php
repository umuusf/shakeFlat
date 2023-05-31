<?php
/**
 * datatable/dtUser.php
 *
 * An example of using the DataTable class.
 * Manage the user table.
 *
 * table schema(sample) :
 *      CREATE TABLE `user` (
 *       `user_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *       `nickname` varchar(15) NOT NULL DEFAULT '',
 *       `login_id` varchar(30) NOT NULL DEFAULT '',
 *       `login_passwd` varchar(50) NOT NULL DEFAULT '',
 *       `create_date` timestamp NOT NULL DEFAULT current_timestamp(),
 *       `modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
 *       PRIMARY KEY (`user_no`)
 *      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
 */

namespace shakeFlat\datatables;
use shakeFlat\DataTable;

class dtUser extends DataTable
{
    protected function __construct()
    {
        parent::__construct("userlist");

        parent::setTableClass("table table-sm table-hover");
        parent::setListAjax("/welcome/datatable_sample_ajax/");

        parent::setDBMainTable("user", "user_no");
        parent::setAnd("user_no >= :default_user_no", [":default_user_no" => 1]);
        parent::setSearchJoinDBTable("company", "company_no", "company_no");

        parent::setConfig([
            "pageLength"   => 30,
            "lengthMenu"   => array( 10, 20, 30, 50, 75, 100 ),
            "stateSave"    => true,
            "searching"    => true,
        ]);

        parent::setAllColumns([
            "user_no"      => [ "label" => "번호",     "orderable" => true ],
            "status"       => [ "label" => "상태",     "realColumn"=> "user.status", "className" => "text-center", "rendering" => "function(data, type, row) { switch(data) { case 0:return '사용중지';break; case 1:return '정상';break; case 2:return '점검중';break; } }" ],
            "company_name" => [ "label" => "회사",     "realColumn" => "company.name", "orderable" => false, "searchable" => true ],
            "nickname"     => [ "label" => "닉네임",   "orderable" => true, "searchable" => true ],
            "login_id"     => [ "label" => "로그인ID", "orderable" => true, "searchable" => true ],
            "price"        => [ "label" => "가격",     "orderable" => true, "className" => "text-amount", "rendering" => "$.fn.dataTable.render.number(',')" ],
            "create_date"  => [ "label" => "생성일",   "realColumn" => "user.create_date", "orderable" => true ],
            "modify_btn"   => [ "realColumn"=> null,   "rendering" => "function(data, type, row) { return '<button class=\'btn btn-xs btn-primary\'>버튼</button>'; }" ],
        ]);

        parent::setListing([ "user_no", "status", "company_name", "nickname", "login_id", "price", "create_date", "modify_btn" ]);

        parent::setDefaultOrder("user_no", "desc");

        parent::setExcelFileName("유저정보");
        //parent::setExcelButtonClassName("btn btn-sm");
        //parent::setExcelButtonText("Excel Download");

        parent::setCustomSearchSelectBox("status", "상태", [
            [ "value" => "", "text" => "전체", "selected" => true ],
            [ "value" => 0, "text" => "사용중지" ],
            [ "value" => 1, "text" => "정상" ],
            [ "value" => 2, "text" => "점검중" ],
        ]);

        parent::setCustomSearchDateRange("create_date", "생성일", "width:250px!important;");
    }
}