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
            "user_no"      => [ "label" => "번호",     "realColumn" => "user.user_no" ],
            "status"       => [ "label" => "상태",     "realColumn" => "user.status", "rendering" => "function(data, type, row) { switch(data) { case 0:return '사용중지';break; case 1:return '정상';break; case 2:return '점검중';break; } }" ],
            "company_name" => [ "label" => "회사",     "realColumn" => "company.name" ],
            "nickname"     => [ "label" => "닉네임",   "realColumn" => "user.nickname"  ],
            "login_id"     => [ "label" => "로그인ID", "realColumn" => "user.login_id"  ],
            "price"        => [ "label" => "가격",     "realColumn" => "user.price", "rendering" => "$.fn.dataTable.render.number(',')" ],
            "create_date"  => [ "label" => "생성일",   "realColumn" => "user.create_date" ],
            "btn"          => [ self::ATTR_BTN_DETAIL, self::ATTR_BTN_MODIFY ],
        ]);

        // for listing
        parent::setListing([
            "user_no", "status", "company_name", "nickname", "login_id", "price", "create_date", "btn"
        ], [
            "user_no"       => [ self::ATTR_TEXT_CENTER, self::ATTR_ORDERABLE ],
            "status"        => [ self::ATTR_TEXT_CENTER, self::ATTR_ORDERABLE ],
            "company_name"  => [ self::ATTR_ORDERABLE, self::ATTR_SEARCHABLE ],
            "nickname"      => [ self::ATTR_ORDERABLE, self::ATTR_SEARCHABLE ],
            "login_id"      => [ self::ATTR_ORDERABLE, self::ATTR_SEARCHABLE ],
            "price"         => [ self::ATTR_TEXT_AMOUNT, self::ATTR_ORDERABLE ],
            "create_date"   => [ self::ATTR_ORDERABLE ],
            "btn"           => [ self::ATTR_ORDERABLE ],
        ]);

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