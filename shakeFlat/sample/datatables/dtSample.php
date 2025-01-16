<?php
namespace shakeFlat\datatables;
use shakeFlat\DataTables;
use shakeFlat\DB;

class dtSample extends DataTables
{
    public function __construct($tableId)
    {
        parent::__construct($tableId);
        parent::containerOption("style='max-width:100%;'");
        parent::pageLength(20);
        parent::ajaxUrl("/datatables/custom-data");
        parent::orderBy("member_id", "desc");

        parent::exportTitle("직원 목록");
        parent::exportFilename("직원목록(" . date("Y-m-d") . ")");

        parent::extraButton("btn-extra-add")    ->title("신규추가") ->class("btn-add")      ->option("data-table-id='{$tableId}'")  -> tooltip("회원을 신규 추가합니다.");
        parent::extraButton("btn-extra-order")  ->title("주문하기") ->class("btn-order")    ->tooltip("주문서를 작성합니다.");
        parent::extraButton("btn-extra-refund") ->title("환불요청") ->class("btn-refund")   ->tooltip("환불요청을 합니다.");

        parent::column("member_id")     ->title("ID");
        parent::column("name")          ->title("이름");
        parent::column("phone")         ->title("전화");
        parent::column("status")        ->title("상태");
        parent::column("city")          ->title("도시");
        parent::column("postal_code")   ->title("우편번호");
        parent::column("country")       ->title("국가");
        parent::column("email")         ->title("이메일");
        parent::column("address")       ->title("주소");
        parent::column("notes")         ->title("메모");
        parent::column("birth_date")    ->title("생일")         ->date();
        parent::column("join_date")     ->title("입사일")       ->date();
        parent::column("salary")        ->title("연봉")         ->number();
        parent::column("last_login")    ->title("마지막 로그인")->datetime();
        parent::column("btn")->disableInvisible()->noExport();
        parent::column("btn")->button("detail")->title("상세보기")->class("btn-detail")->dataset("member-id", "\${row.member_id}");
        parent::column("btn")->button("edit")->title("수정")->class("btn-modify")->dataset("member-id", "\${row.member_id}");
        parent::column("btn")->button("delete")->title("삭제")->class("btn-delete")->dataset("member-id", "\${row.member_id}");


        parent::customSearch("name")        ->widthRem(12)  ->string();
        parent::customSearch("phone")       ->widthRem(12)  ->string();
        parent::customSearch("status")      ->widthRem(6)   ->select2()         ->options(["active"=>"active", "inactive"=>"inactive", "banned"=>"banned"]);
        parent::customSearch("join_date")   ->widthRem(13)  ->dateRange();
        parent::customSearch("salary")      ->widthRem(15)  ->numberRange(0, 999999999);

        parent::customSearch("join_date2")  ->widthRem(19)  ->query("join_date")    ->datetimeRange()->title("입사일ex");
        parent::customSearch("salary2")     ->widthRem(15)  ->query("salary")       ->numberRange(0, 999999999)->title("연봉ex");

        parent::layoutCustomSearch([
            [ "name", "phone", "status" ],
            [ "join_date", "salary" ],
            [ "join_date2", "salary2" ]
        ]);

        parent::layoutList([
            "member_id", "status", "name", "phone", "city",
            "postal_code", "country", "email", "address", "notes",
            "birth_date", "join_date", "salary", "last_login", "btn"
        ]);
    }
}