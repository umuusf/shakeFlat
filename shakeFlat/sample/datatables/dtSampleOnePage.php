<?php
namespace shakeFlat\datatables;
use shakeFlat\DataTables;
use shakeFlat\DB;

class dtSampleOnePage extends DataTables
{
    public function __construct($tableId)
    {
        parent::__construct($tableId);

        parent::containerOption("style='max-width:100%;'")
            ->lengthMenu([10, 20, 50, 100, 200, 500])
            ->pageLength(20)
            //->disableOrdering()
            ->orderBy("email2", "desc")
            ->exportTitle("직원 목록")
            ->exportFilename("직원목록(" . date("Y-m-d") . ")")
            ->onePage();

        parent::extraButton("btn-extra-add")    ->title("신규추가") ->class("btn-add")      ->option("data-table-id='{$tableId}'")  -> tooltip("회원을 신규 추가합니다.");
        parent::extraButton("btn-extra-order")  ->title("주문하기") ->class("btn-order")    ->tooltip("주문서를 작성합니다.");
        parent::extraButton("btn-extra-refund") ->title("환불요청") ->class("btn-refund")   ->tooltip("환불요청을 합니다.");

        parent::column("member_id")     ->title("ID")           ->noWrap()->textCenter();
        parent::column("name")          ->title("이름")         ->noWrap()->textCenter();
        parent::column("phone")         ->title("전화")         ->noWrap()->textCenter();
        parent::column("status")        ->title("상태")         ->noWrap()->textCenter();
        parent::column("city")          ->title("도시")         ->noWrap()->textCenter();
        parent::column("postal_code")   ->title("우편번호")     ->noWrap()->textCenter();
        parent::column("country")       ->title("국가")         ->noWrap()->textCenter();
        parent::column("email")         ->title("이메일")       ->noWrap();
        parent::column("address")       ->title("주소")         ->noWrap();
        parent::column("notes")         ->title("메모")         ->noWrap();
        parent::column("birth_date")    ->title("생일")         ->noWrap()->date();
        parent::column("join_date")     ->title("입사일")       ->noWrap()->date();
        parent::column("salary")        ->title("연봉")         ->noWrap()->number();
        parent::column("last_login")    ->title("마지막 로그인")->noWrap()->datetime();
        parent::column("btn")->disableInvisible()->noExport()->textCenter()->noData()
            ->renderDetailButton([ 'member_id', 'name' ])
            ->renderButton("수정", "btn-modify", "data-member-id='\${row.member_id}'")
            ->renderButton("삭제", "btn-delete", "data-member-id='\${row.member_id}'");

        parent::customSearch("name")        ->widthRem(12)  ->string();
        parent::customSearch("email")       ->widthRem(12)  ->string();
        parent::customSearch("phone")       ->widthRem(12)  ->string();
        parent::customSearch("status")      ->widthRem(6)   ->select2()         ->option(["active"=>"active", "inactive"=>"inactive", "banned"=>"banned"]);
        parent::customSearch("join_date")   ->widthRem(13)  ->dateRange();
        parent::customSearch("salary")      ->widthRem(15)  ->numberRange(0, 999999999);

        parent::customSearch("join_date2")  ->widthRem(19)  ->ex("join_date")   ->datetimeRange()->title("입사일ex");
        parent::customSearch("salary2")     ->widthRem(15)  ->ex("salary")      ->numberRange(0, 999999999)->title("연봉ex");

        parent::layoutCustomSearch([
            [ "name", "phone", "status" ],
            [ "join_date", "salary", "email" ],
            [ "join_date2", "salary2" ]
        ])->layoutList([
            "member_id", "status", "name", "phone", "city",
            "postal_code", "country", "email", "address", "notes",
            "birth_date", "join_date", "salary", "last_login", "btn"
        ])->layoutDetail([
            [ "member_id", "status" ],
            [ "name", "phone" ],
            '---',
            [ "salary", "country", "city" ],
            [ "postal_code", "address" ],
            "email",
            [ "birth_date", "join_date" ],
            "last_login",
            "notes"
        ]);
    }

    public function opRecordsTotal()
    {
        $db = DB::getInstance();
        $rs = $db->query("select count(*) as cnt from members");
        return $db->fetch($rs)['cnt'];
    }

    public function opRecordsFiltered()
    {
        $db = DB::getInstance();
        $searchQueryInfo = $this->opQuerySearch();
        $where = $searchQueryInfo['sql']; if ($where) $where = " where {$where}";
        $rs = $db->query("select count(*) as cnt from members{$where}", $searchQueryInfo['bind']);
        return $db->fetch($rs)['cnt'];
    }

    public function opListData()
    {
        $searchQueryInfo = $this->opQuerySearch();
        $orderBy = $this->opQueryOrderBy();
        $limitStart = $this->opQueryLimitStart();
        $limitLength = $this->opQueryLimitLength();

        $where = $searchQueryInfo['sql']; if ($where) $where = "where {$where}";

        $db = DB::getInstance();
        $rs = $db->query("
            select
                members.*
            from members
            {$where}
            {$orderBy}
            limit {$limitStart}, {$limitLength}
        ", $searchQueryInfo['bind']);
        $data = [];
        while($row = $db->fetch($rs)) {
            $data[] = $row;
        }
        return $data;
    }

    public function opDetailData()
    {
        $params = parent::opDetailParams();
        $memberId = $params['member_id'];
        $name = $params['name'];
        $db = DB::getInstance();
        $rs = $db->query("
            select
                members.*
            from members
            where member_id = :member_id
            and name = :name
        ", [ 'member_id' => $memberId, 'name' => $name ]);
        return $db->fetch($rs);
    }
}