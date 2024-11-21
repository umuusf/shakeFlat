<?php
namespace shakeFlat\datatables;
use shakeFlat\DataTables;
use shakeFlat\DB;

class dtSampleOnePage extends DataTables
{
    public function __construct($tableId)
    {
        parent::__construct($tableId);

        parent::deliverParameter("abc", 123);

        parent::containerOption("style='max-width:100%;'")
            ->lengthMenu([10, 20, 50, 100, 200, 500])
            ->pageLength(20)
            ->keyCursor()
            //->disableOrdering()
            ->orderBy("member_id", "desc")
            ->exportTitle("직원 목록")
            ->exportFilename("직원목록(" . date("Y-m-d") . ")")
            ->onePage()
            ->columnConfigFunction("columnConfigLoad", "columnConfigSave");

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
        parent::column("btn")->disableInvisible()->noExport()->textCenter()->noData()->noKeyCursor();

        parent::customSearch("name")        ->widthRem(12)  ->string();
        parent::customSearch("email")       ->widthRem(12)  ->string();
        parent::customSearch("phone")       ->widthRem(12)  ->string(); //->mask("999-999[9]-9999");
        parent::customSearch("status")      ->widthRem(6)   ->select2()         ->options(["active"=>"active", "inactive"=>"inactive", "banned"=>"banned"]);
        parent::customSearch("join_date")   ->widthRem(13)  ->dateRange();
        parent::customSearch("salary")      ->widthRem(15)  ->numberRange(0, 999999999);

        parent::customSearch("join_date2")  ->widthRem(19)  ->ex("join_date")   ->datetimeRange()->title("입사일ex");
        parent::customSearch("salary2")     ->widthRem(15)  ->ex("salary")      ->numberRange(0, 999999999)->title("연봉ex");

        parent::layoutCustomSearch([
            [ "name", "phone", "status" ],
            [ "join_date", "salary", "email" ],
            [ "join_date2", "salary2" ]
        ])->layoutList([
            "member_id", "name", "phone", "city", "status",
            "postal_code", "country", "email", "address", "notes",
            "birth_date", "join_date", "salary", "last_login", "btn"
        ]);

        parent::column("btn")
            ->buttonDetail("detailView")
                ->keyParam('member_id')
                ->keyParam('name')
                ->queryFunction('opColumnButtonQueryData')
                ->layout([
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

        parent::column("btn")
            ->buttonModify("modify")
                ->keyParam('member_id')
                ->keyParam('name')
                ->queryFunction('opColumnButtonQueryData')
                ->submitFunction('submitModify')
                ->layout([
                    "member_id",
                    "status",
                    [ "name", "phone" ],
                    "address"
                ]);

        parent::column("btn")->buttonModify("modify")->editColumn("member_id")->hidden();
        parent::column("btn")->buttonModify("modify")->editColumn("status")->title("상태")->radio()->options(["active"=>"Active", "inactive"=>"Inactive", "banned"=>"Banned" ]);
        parent::column("btn")->buttonModify("modify")->editColumn("name")->title("이름")->text()->required();
        parent::column("btn")->buttonModify("modify")->editColumn("phone")->title("전화번호")->tel()->required();
        parent::column("btn")->buttonModify("modify")->editColumn("address")->title("주소")->text()->widthRem(28);


        parent::extraButton("btn-extra-add")    ->title("신규등록") ->class("btn-add")      ->tooltip("회원을 신규 추가합니다.");
        parent::extraButton("btn-extra-order")  ->title("주문하기") ->class("btn-order")    ->tooltip("주문서를 작성합니다.");
        parent::extraButton("btn-extra-refund") ->title("환불요청") ->class("btn-refund")   ->tooltip("환불요청을 합니다.");

        $addRecord = parent::extraButton("btn-extra-add")->addRecord()->submitFunction("submitAddRecord");
        $addRecord->column("name")      ->text()->required();
        $addRecord->column("phone")     ->title("전화번호")->tel()->required();//->mask("999-999[9]-9999");
        $addRecord->column("address")   ->title("주소")->text()->widthRem(25);
        $addRecord->column("hobby")     ->title("취미")->checkbox()->options([ "a"=>"독서", "b"=>"영화감상", "c"=>"등산" ])->defaultValue("a");
        $addRecord->column("status")    ->title("상태")->radio()->options([ "active"=>"Active", "banned"=>"Banned", "inactive"=>"Inactive" ])->defaultValue("Active");
        $addRecord->column("city")      ->title("도시")->select()->options([ "a"=>"서울특별시", "b"=>"부산직할시", "c"=>"용인특례시" ])->widthRem(20);
        $addRecord->column("attach")    ->title("첨부파일")->file();
        $addRecord->column("jumin")     ->title("주민등록번호")->text()->mask("999999-9999999");
        $addRecord->column("bigo")      ->title("설명")->textarea()->required()->widthRem(30)->heightRem(15);

        $addRecord->layout([
            [ "name", "phone" ],
            "address",
            '---',
            "hobby", "status",
            "city",
            "attach",
            "jumin",
            "bigo"
        ]);

        parent::column("btn")->button("delete")->title("삭제")->class("btn-delete")->dataset("member-id", "\${row.member_id}");
    }

    public function submitAddRecord()
    {
        return true;
    }

    public function submitModify()
    {
        return true;
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

    public function opColumnButtonQueryData($params)
    {
        $db = DB::getInstance();
        $rs = $db->query("
            select
                members.*
            from members
            where member_id = :member_id
            and name = :name
        ", [ 'member_id' => $params["member_id"], 'name' => $params["name"] ]);
        return $db->fetch($rs);
    }

    public function columnConfigLoad()
    {
        $db = DB::getInstance();
        $rs = $db->query("select data from table_column_config where table_id = 'example'");
        return $db->fetch($rs)["data"] ?? null;
    }

    public function columnConfigSave($data)
    {
        $db = DB::getInstance();
        $db->query("replace into table_column_config (table_id, data) values ('example', :data)", [ 'data' => $data ]);
        return true;
    }
}