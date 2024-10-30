<?php
namespace shakeFlat\datatables;
use shakeFlat\DataTables;

class dtSample extends DataTables
{
    public function __construct($tableId)
    {
        parent::__construct($tableId);
        parent::containerOption("style='max-width:100%;'");

        //parent::english();

        parent::exportTitle("직원 목록");
        parent::exportFilename("직원목록(" . date("Y-m-d") . ")");

        parent::extraButton("btn-extra-add", "신규추가", "btn-add", "data-table-id='{$tableId}'");
        parent::extraButton("btn-extra-order", "주문하기", "btn-order");
        parent::extraButton("btn-extra-refund", "환불요청", "btn-refund");

        parent::column("member_id")     ->nowrap()->title("ID")           ->textCenter();
        parent::column("name")          ->nowrap()->title("이름")         ->textCenter();
        parent::column("phone")         ->nowrap()->title("전화")         ->textCenter();
        parent::column("status")        ->nowrap()->title("상태")         ->textCenter();
        parent::column("city")          ->nowrap()->title("도시")         ->textCenter();
        parent::column("postal_code")   ->nowrap()->title("우편번호")     ->textCenter();
        parent::column("country")       ->nowrap()->title("국가")         ->textCenter();
        parent::column("email")         ->nowrap()->title("이메일");
        parent::column("address")       ->nowrap()->title("주소");
        parent::column("notes")         ->nowrap()->title("메모");
        parent::column("birth_date")    ->nowrap()->title("생일")         ->date();
        parent::column("join_date")     ->nowrap()->title("입사일")       ->date();
        parent::column("salary")        ->nowrap()->title("연봉")         ->number();
        parent::column("last_login")    ->nowrap()->title("마지막 로그인")->datetime();

        parent::column("btn")->disableInvisible()->noExport()->textCenter()
            ->renderButton("상세보기", "btn-detail", "data-member-id='\${row.member_id}'")
            ->renderButton("수정", "btn-modify", "data-member-id='\${row.member_id}'")
            ->renderButton("삭제", "btn-delete", "data-member-id='\${row.member_id}'");

        parent::customSearch("name")->string()->controlOption("style='width:200px;'");
        parent::customSearch("phone")->string()->controlOption("style='width:200px;'");
        parent::customSearch("status")->select2()->controlOption("style='width:100px;'")->data("active", "active")->data("banned", "banned")->data("inactive", "inactive");
        parent::customSearch("join_date")->dateRange()->controlOption("style='width:210px;'");
        parent::customSearch("join_date2")->ex()->title("입사일")->datetimeRange()->controlOption("style='width:300px;'");
        parent::customSearch("salary")->numberRange(0, 999999999)->controlOption("style='width:250px;'");

        parent::layoutCustomSearch([
            [ "name", "phone", "status" ],
            [ "join_date", "join_date2", "salary" ]
        ]);

        parent::layoutList([
            "member_id", "status", "name", "phone", "city",
            "postal_code", "country", "email", "address", "notes",
            "birth_date", "join_date", "salary", "last_login", "btn"
        ]);

    }
}