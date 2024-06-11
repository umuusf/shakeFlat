<?php
/**
 * datatable/dtUserOnePage.php
 *
 * An example of using the DataTable class.
 * Example of handling all modules in one page (file)
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
use shakeFlat\Param;
use shakeFlat\DB;
use shakeFlat\Response;

class dtUserOnePage extends DataTable
{
    protected function __construct()
    {
        parent::__construct("userlist");

        // configurations
        parent::setTableClass("table table-sm table-hover");
        parent::setDBMainTable("user", "user_no");
        parent::setAnd("user.user_no >= :default_user_no", [":default_user_no" => 1]);
        parent::setSearchJoinDBTable("company", "company_no", "company_no");
        parent::setConfig([
            "pageLength"   => 30,
            "lengthMenu"   => array( 10, 20, 30, 50, 75, 100 ),
            "stateSave"    => true,
            "searching"    => true,
        ]);
        parent::setDefaultOrder("user_no", "desc");
        parent::setExcelFileName("유저정보");
        //parent::setExcelButtonClassName("btn btn-sm");
        //parent::setExcelButtonText("Excel Download");


        // define all columns
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
        parent::setCustomSearchSelectBox("status", "상태", [
            [ "value" => "", "text" => "전체", "selected" => true ],
            [ "value" => 0, "text" => "사용중지" ],
            [ "value" => 1, "text" => "정상" ],
            [ "value" => 2, "text" => "점검중" ],
        ]);
        parent::setCustomSearchDateRange("create_date", "생성일", "width:250px!important;");

        // for details
        parent::setDetailInfo([
            "status",
            [ "company_name", "nickname" ],
            "login_id",
            "price",
            "create_date"
        ], [
            "status" => [ "displayEnum" => [ 1 => "정상", 0 => "사용중지", 2 => "점검중" ] ],
        ]);

        // for modify
        parent::setModifyRecord([
            "status",
            "nickname",
            "login_id",
        ], [
            "status" => [ "type" => "radio", "optionList" => [ 1 => "정상", 0 => "사용중지", 2 => "점검중" ] ],
        ]);

        parent::setSubmitForModifyCallback(function() {
            sfModeAjax();
            $param = Param::getInstance();
            $param->checkKeyValue("sf-userlist-modify-pk", Param::TYPE_STRING);
            $pk = json_decode($param->get("sf-userlist-modify-pk"), true);
            if (!$pk || !is_array($pk) || !array_key_exists("user_no", $pk)) L::error("잘못된 접근입니다.");
            $param->checkKeyValue("sf-userlist-modify-status", Param::TYPE_INT, array( 0, 1, 2 ));
            $param->checkKeyValue("sf-userlist-modify-nickname", Param::TYPE_STRING);
            $param->checkKeyValue("sf-userlist-modify-login_id", Param::TYPE_STRING);

            $db = DB::getInstance();
            $db->query("
                update user
                set
                     status = :status
                    ,nickname = :nickname
                    ,login_id = :login_id
                where
                    user_no = :user_no
            ", [
                ":user_no"  => $pk["user_no"],
                ":status"   => $param->get("sf-userlist-modify-status"),
                ":nickname" => $param->get("sf-userlist-modify-nickname"),
                ":login_id" => $param->get("sf-userlist-modify-login_id"),
            ]);

            $res = Response::getInstance();
            $res->result = true;
        });

        // for new Record
        parent::setNewRecord([
            "status",
            "nickname",
            "login_id",
            "price",
        ], [
            "status" => [ "type" => "radio", "optionList" => [ 1 => "정상", 0 => "사용중지", 2 => "점검중" ], "defaultValue" => 2 ],
            "price"  => [ "type" => "number" ],
        ]);

        parent::setSubmitForNewCallback(function() {
            sfModeAjax();
            $param = Param::getInstance();
            $param->checkKeyValue("sf-userlist-new-status", Param::TYPE_INT, array( 0, 1, 2 ));
            $param->checkKeyValue("sf-userlist-new-nickname", Param::TYPE_STRING);
            $param->checkKeyValue("sf-userlist-new-login_id", Param::TYPE_STRING);
            $param->checkKeyValue("sf-userlist-new-price", Param::TYPE_INT);

            $db = DB::getInstance();
            $db->query("
                insert into user (
                     status
                    ,nickname
                    ,login_id
                    ,price
                ) values (
                     :status
                    ,:nickname
                    ,:login_id
                    ,:price
                )
            ", [
                ":status"   => $param->get("sf-userlist-new-status"),
                ":nickname" => $param->get("sf-userlist-new-nickname"),
                ":login_id" => $param->get("sf-userlist-new-login_id"),
                ":price"    => $param->get("sf-userlist-new-price"),
            ]);

            $res = Response::getInstance();
            $res->result = true;
        });
    }
}