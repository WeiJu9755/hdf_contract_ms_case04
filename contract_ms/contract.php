<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;

if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = "0";
} else {
	$isMobile = "1";
}

@include_once("/website/class/".$site_db."_info_class.php");

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("DeleteRow");
function DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();
	
	$mDB = "";
	$mDB = new MywebDB();

	//刪除主資料
	$Qry="delete from CaseManagement where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("oTable = $('#db_table').dataTable();oTable.fnDraw(false)");
	$objResponse->script("autoclose('提示', '資料已刪除！', 1500);");

	return $objResponse;
	
}

$xajax->registerFunction("confirm");
function confirm($auto_seq,$check,$memberID){

	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	$Qry = "update CaseManagement set 
			confirm4 = '$check' 
			,makeby4 = '$memberID'
			,last_modify4 = now()
			where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	$mDB->remove();
	
    $objResponse->script("oTable = $('#db_table').dataTable();oTable.fnDraw(false)");

	return $objResponse;
	
}


$xajax->registerFunction("returnValue");
function returnValue($auto_seq){
	$objResponse = new xajaxResponse();
	$earliest_entry_date = "";
	$latest_completion_date = "";

	$mDB = "";
	$mDB = new MywebDB();
	
	$Qry="SELECT 
			a.auto_seq,
			MIN(b.actual_entry_date) AS earliest_entry_date,
			MAX(b.actual_completion_date) AS latest_completion_date
		FROM CaseManagement a
		LEFT JOIN overview_building b 
			ON b.case_id = a.case_id
		WHERE a.auto_seq = '$auto_seq'
		GROUP BY a.auto_seq;";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row = $mDB->fetchRow(2)) {
		$earliest_entry_date = ($row['earliest_entry_date'] === "0000-00-00" || $row['earliest_entry_date'] === "" || is_null($row['earliest_entry_date']))
			? ""
			: $row['earliest_entry_date'];

		$latest_completion_date = ($row['latest_completion_date'] === "0000-00-00" || $row['latest_completion_date'] === "" || is_null($row['latest_completion_date']))
			? ""
			: $row['latest_completion_date'];
	}
	}
	
	$mDB->remove();
	
	$objResponse->assign("earliest_entry_date".$auto_seq,"innerHTML",$earliest_entry_date);
	$objResponse->assign("latest_completion_date".$auto_seq,"innerHTML",$latest_completion_date);	
	
	
    return $objResponse;
	
}

$xajax->processRequest();


$fm = $_GET['fm'];
//$pjt = $_GET['pjt'];
//$project_id = $_GET['project_id'];
//$auth_id = $_GET['auth_id'];

$project_id = "202412060001";
$auth_id = "CASE04";
if (isset($_GET['pjt']))
	$pjt = $_GET['pjt'];
else
	$pjt = "案件合約";



$tb = "CaseManagement";

$m_t = urlencode($_GET['pjt']);

$mess_title = $pjt;


$today = date("Y-m-d");

$dataTable_de = getDataTable_de();
$Prompt = getlang("提示訊息");
$Confirm = getlang("確認");
$Cancel = getlang("取消");

$pubweburl = "//".$domainname;



//網頁標題
$page_title = $pjt;
$page_description = trim(strip_tags($pjt));
$page_description = utf8_substr($page_description,0,1024);
$page_keywords = $pjt;

//載入上方索引列模組
@include $m_location."/sub_modal/base/project_index.php";


$m_pjt = urlencode($_GET['pjt']);

$mk = $_GET['mk'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];


$today = date("Y-m-d");


$pubweburl = "//".$domainname;

//載入功能選單模組
@include $m_location."/sub_modal/base/project_menu.php";


$fellow_count = 0;
//取得指定管理人數
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'contract'","count(*) as fellow_count");
$fellow_count =$pjmyfellow_row['fellow_count'];
if ($fellow_count == 0)
	$fellow_count = "";

/*
$warning_count = 0;
//取得指定管理人數(警訊通知對象)
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'alertlist'","count(*) as warning_count");
$warning_count =$pjmyfellow_row['warning_count'];
if ($warning_count == 0)
	$warning_count = "";
*/

$pjItemManager = false;
//檢查是否為指定管理人
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'contract' and member_no = '$memberID'","count(*) as enable_count");
$enable_count =$pjmyfellow_row['enable_count'];
if ($enable_count > 0)
	$pjItemManager = true;


//設定權限
$cando = "N";
if (($powerkey=="A") || ($super_admin=="Y") || ($pjItemManager == true)) {
	$cando = "Y";
}


//取得使用者員工身份
$member_picture = getmemberpict160($memberID);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$memberID'","member_name");
$member_name = $member_row['member_name'];

$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$memberID'","count(*) as manager_count,employee_name,employee_type,team_id");
$manager_count =$employee_row['manager_count'];
$team_id = $employee_row['team_id'];
if ($manager_count > 0) {
	$employee_name = $employee_row['employee_name'];
	$employee_type = $employee_row['employee_type'];

	$team_row = getkeyvalue2($site_db."_info","team","team_id = '$team_id'","team_name");
	$team_name = $team_row['team_name'];
} else {
	$employee_name = $member_name;
	$team_name = "未在員工名單";
}


$member_logo=<<<EOT
<div class="mytable bg-white m-auto rounded">
	<div class="myrow">
		<div class="mycell" style="text-align:center;width:73px;padding: 5px 0;">
			<img src="$member_picture" height="75" class="rounded">
		</div>
		<div class="mycell text-start p-2 vmiddle" style="width:107px;">
			<div class="size14 blue02 weight mb-1 text-nowrap">$employee_name</div>
			<div class="size12 weight text-nowrap">$team_name</div>
			<div class="size12 weight text-nowrap">$employee_type</div>
		</div>
	</div>
</div>
EOT;


$show_disabled = "";
$show_disabled_warning = "";
/*
//if ((empty($team_id)) || ((($super_admin=="Y") && ($admin_readonly == "Y")) || (($super_advanced=="Y") && ($advanced_readonly == "Y")))) {
if (((($super_admin=="Y") && ($admin_readonly == "Y")) || (($super_advanced=="Y") && ($advanced_readonly == "Y")))) {
	if ($pjItemManager <> "Y") {
		$show_disabled = "disabled";
		$show_disabled_warning = "<div class=\"size12 red weight text-center p-2\">此區為管理人專區，非經授權請勿進行任何處理</div>";
	}
}
*/

//if ($cando == "Y") {
	if (($super_admin == "Y") && ($admin_readonly == "Y")) {
		$show_disabled = "disabled";
		$show_disabled_warning = "<div class=\"size12 red weight text-center p-2\">此區為管理人專區，非經授權請勿進行任何處理</div>";
	}
//}


$show_admin_list = "";


if ($cando == "Y") {

	$show_modify_btn = "";
	//$show_ConfirmSending_btn = "";

//	if ($fm == "case") {

		if (($powerkey == "A") || (($super_admin=="Y") && ($admin_readonly <> "Y"))) {
$show_admin_list=<<<EOT
<div class="text-center">
	<div class="btn-group me-2 mb-2" role="group">
		<a role="button" class="btn btn-light" href="javascript:void(0);" onclick="openfancybox_edit('/index.php?ch=fellowlist&project_id=$project_id&auth_id=$auth_id&pro_id=contract&t=指定管理人&fm=base',850,'96%',true);" title="指定管理人"><i class="bi bi-shield-fill-check size14 red inline me-2 vmiddle"></i><div class="inline size12 me-2">指定管理人</div><div class="inline red weight vmiddle">$fellow_count</div></a>
		<!--
		<a role="button" class="btn btn-light" href="javascript:void(0);" onclick="openfancybox_edit('/index.php?ch=fellowlist&project_id=$project_id&auth_id=$auth_id&pro_id=alertlist&t=警訊通知對象&fm=base',850,'96%',true);" title="警訊通知對象"><i class="bi bi-bell-fill size14 red inline me-2 vmiddle"></i><div class="inline size12 me-2">警訊通知對象</div><div class="inline red weight vmiddle">$warning_count</div></a>
		-->
	</div>
</div>
EOT;
		}

$show_modify_btn=<<<EOT
<div class="text-center my-2">
	<div class="btn-group me-2 mb-2" role="group">
		<button type="button" class="btn btn-success text-nowrap" onclick="myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
		<button type="button" class="btn btn-warning text-nowrap" onclick="add_shortcuts('$site_db','$web_id','$templates','$project_id','$auth_id','$pjcaption','$i_caption','$fm','$memberID');"><i class="bi bi-lightning-fill red"></i>&nbsp;加入至快捷列</button>
	</div>
</div>
$show_admin_list
EOT;



$list_view=<<<EOT
<div class="w-100 m-auto p-1 mb-5 bg-white">
	<div style="width:auto;padding: 5px;">
		<div class="inline float-start me-1 mb-2">$left_menu</div>
		<a role="button" class="btn btn-light px-2 py-1 float-start inline me-3 mb-2" href="javascript:void(0);" onClick="parent.history.back();"><i class="bi bi-chevron-left"></i>&nbsp;回上頁</a>
		<a role="button" class="btn btn-light p-1" href="/">回首頁</a>$mess_title
	</div>
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-2 col-sm-12 col-md-12 p-1 d-flex flex-column justify-content-center align-items-center">
				$member_logo
			</div> 
			<div class="col-lg-8 col-sm-12 col-md-12 p-1">
				<div class="size20 pt-1 text-center">$pjt</div>
				$show_modify_btn
				$show_disabled_warning
			</div> 
			<div class="col-lg-2 col-sm-12 col-md-12">
			</div> 
		</div>
	</div>
	$show_ConfirmSending_btn
	<div class="table-wrap">
	<table class="table table-bordered border-dark w-100" id="db_table" style="min-width:1200px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 10px;background-color: #CBF3FC;">狀態(1)</th>
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 10px;background-color: #CBF3FC;">狀態(2)</th>
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 10px;background-color: #CBF3FC;">區域</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">案件編號</th>
				<th class="text-center text-nowrap vmiddle" style="width:10%;padding: 10px;background-color: #CBF3FC;">工程名稱</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">上包合約<br>簽訂日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">合約號碼<br>(ERP專案代號)</th>
				<th class="text-center text-nowrap vmiddle" style="width:10%;padding: 10px;background-color: #CBF3FC;">合約承攬建物棟數</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">合約總價(含稅)</th>
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 20px;background-color: #CBF3FC;">實際<br>進場日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 20px;background-color: #CBF3FC;">預計<br>完工日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 20px;background-color: #CBF3FC;">實際<br>完工日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">第一期預收款<br>請款方式</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">第一期預收<br>預估日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">第一期<br>請款日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">第二期預收款<br>請款方式</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">第二期預收<br>預估日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">第二期<br>請款日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">第三期預收款<br>請款方式</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">第三期預收<br>預估日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 20px;background-color: #CBF3FC;">第三期<br>請款日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:3%;padding: 10px;background-color: #CBF3FC;">確認</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">處理</th>
				<th class="text-center text-nowrap vmiddle" style="width:4%;padding: 10px;background-color: #CBF3FC;">最後修改</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
			<tr>
				<td colspan="22" class="dataTables_empty">資料載入中...</td>
			</tr>
		</tbody>
	</table>
	</div>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}
	
	
$show_view = <<<EOT
<style type="text/css">
.table-wrap {
    width: calc(100vw - 350px); /* 扣掉左側控制面板寬度 */
    overflow-x: auto;           /* 出現左右拉霸 */
    overflow-y: auto;
    border: 1px solid #ccc;
}


#db_table {
	width: 100%;
	min-width: 1200px;
	border-collapse: collapse;
}
</style>


	$list_view


<script type="text/javascript" charset="utf-8">

	var oTable;
	$(document).ready(function() {
		$('#db_table').dataTable( {
			"processing": true,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": true,
			"scrollY": 500,
			"paging": true,
			"pageLength": 50,
			"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
			"pagingType": "full_numbers",  //分页样式： simple,simple_numbers,full,full_numbers
			"searching": true,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func01/contract_ms/server_contract.php?site_db=$site_db&fm=$fm",
			"language": {
						"sUrl": "$dataTable_de"
						/*"sUrl": '//cdn.datatables.net/plug-ins/1.12.1/i18n/zh-HANT.json'*/
					},
			"fixedHeader": true,
			"fixedColumns": {
        		left: 1,
    		},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 

				//狀態(1)
				var status1 = "";
				if (aData[0] != null && aData[0] != "")
					status1 = aData[0];

				$('td:eq(0)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+status1+'</div>' );

				//狀態(2)
				var status2 = "";
				if (aData[1] != null && aData[1] != "")
					status2 = aData[1];

				$('td:eq(1)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+status2+'</div>' );

				//區域
				var region = "";
				if (aData[2] != null && aData[2] != "")
					region = aData[2];

				$('td:eq(2)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+region+'</div>' );

				//案件編號
				var case_id = "";
				if (aData[3] != null && aData[3] != "")
					case_id = aData[3];

				$('td:eq(3)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 weight text-nowrap" style="height:auto;min-height:32px;">'+case_id+'</div>' );

				//工程名稱
				var construction_id = "";
				if (aData[4] != null && aData[4] != "")
					construction_id = aData[4];

				$('td:eq(4)', nRow).html( '<div class="d-flex justify-content-center align-items-center size12 text-center" style="height:auto;min-height:32px;">'+construction_id+'</div>' );

				//上包合約簽訂日期
				var contract_date = "";
				if (aData[6] != null && aData[6] != "")
					contract_date = aData[6];

				$('td:eq(5)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+contract_date+'</div>' );

				//合約號碼(ERP專案代號)
				var ERP_no = "";
				if (aData[21] != null && aData[21] != "")
					ERP_no = aData[21];

				$('td:eq(6)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+ERP_no+'</div>' );

				//合約承攬建物棟數
				var buildings_contract = "";
				var buildings_contract2 = "";
				var std_layer_floor = "";
				var roof_protrusion_floor = "";


				if (aData[22] != null && aData[22] != "")
					buildings_contract = aData[22];
				if (aData[25] != null && aData[25] != "")
					buildings_contract2 = aData[25];
				if (aData[26] != null && aData[26] != "")
					std_layer_floor = aData[26];
				if (aData[27] != null && aData[27] != "")
					roof_protrusion_floor = aData[27];

				$('td:eq(7)', nRow).html(
				'<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">' +
					buildings_contract2 + ' / ' + std_layer_floor + '；' +
					roof_protrusion_floor + '、' + buildings_contract +
				'</div>'
				);

				//合約總價(含稅)
				var total_contract_amt = "";
				if (aData[23] != null && aData[23] != "")
					total_contract_amt = number_format(aData[23]);

				$('td:eq(8)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+total_contract_amt+'</div>' );

				// 工程人力實際進場日期和實際完工日期
				var earliest_entry_date = '<div id="earliest_entry_date'+aData[18]+'"></div>';
				var latest_completion_date = '<div id="latest_completion_date'+aData[18]+'"></div>';
				xajax_returnValue(aData[18]);

				// 實際進場日期 
				var entry_date = "";
				if (aData[18] != null && aData[18] != "") {
					entry_date = '<div id="earliest_entry_date'+aData[18]+'"></div>';
				} 
				$('td:eq(9)', nRow).html(
					'<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'
					+ entry_date +
					'</div>'
				);


				// 預計完工日期
				var completion_date = "";
				if (aData[24] != null && aData[24] != "" && aData[24] != "0000-00-00")
					completion_date = aData[24];

				$('td:eq(10)', nRow).html(
					'<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'
					+ completion_date +
					'</div>'
				);


				// 工程人力實際完工日 (最晚 or DB欄位)
				var completion_actual = "";
				if (aData[18] != null && aData[18] != "") {
					completion_actual = '<div id="latest_completion_date'+aData[18]+'"></div>';
				} 
				$('td:eq(11)', nRow).html(
					'<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'
					+ completion_actual +
					'</div>'
				);


				//第一期預收款請款方式
				var advance_payment1 = "";
				if (aData[7] != null && aData[7] != "")
					advance_payment1 = aData[7];

				$('td:eq(12)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12" style="height:auto;min-height:32px;">'+advance_payment1+'</div>' );

				//第一期預收預估日期
				var estimated_payment_date1 = "";
				if (aData[8] != null && aData[8] != "" && aData[8] != "0000-00-00")
					estimated_payment_date1 = aData[8];

				$('td:eq(13)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+estimated_payment_date1+'</div>' );

				//第一期請款日期
				var request_date1 = "";
				if (aData[9] != null && aData[9] != "" && aData[9] != "0000-00-00")
					request_date1 = aData[9];

				$('td:eq(14)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+request_date1+'</div>' );

				//第二期預收款請款方式
				var advance_payment2 = "";
				if (aData[10] != null && aData[10] != "")
					advance_payment2 = aData[10];

				$('td:eq(15)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12" style="height:auto;min-height:32px;">'+advance_payment2+'</div>' );

				//第二期預收預估日期
				var estimated_payment_date2 = "";
				if (aData[11] != null && aData[11] != "" && aData[11] != "0000-00-00")
					estimated_payment_date2 = aData[11];

				$('td:eq(16)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+estimated_payment_date2+'</div>' );

				//第二期請款日期
				var request_date2 = "";
				if (aData[12] != null && aData[12] != "" && aData[12] != "0000-00-00")
					request_date2 = aData[12];
					
				$('td:eq(17)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+request_date2+'</div>' );

				//第三期預收款請款方式
				var advance_payment3 = "";
				if (aData[13] != null && aData[13] != "")
					advance_payment3 = aData[13];

				$('td:eq(18)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12" style="height:auto;min-height:32px;">'+advance_payment3+'</div>' );

				//第三期預收預估日期
				var estimated_payment_date3 = "";
				if (aData[14] != null && aData[14] != "" && aData[14] != "0000-00-00")
					estimated_payment_date3 = aData[14];

				$('td:eq(19)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+estimated_payment_date3+'</div>' );

				//第三期請款日期
				var request_date3 = "";
				if (aData[15] != null && aData[15] != "" && aData[15] != "0000-00-00")
					request_date3 = aData[15];

				$('td:eq(20)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center size12 text-nowrap" style="height:auto;min-height:32px;">'+request_date3+'</div>' );

				//確認
				if ( aData[20] == "Y" ) {
					var mcheck = "xajax_confirm("+aData[18]+",'N','$memberID');";
					var img_check = '<a href="javascript:void(0);" onclick="'+mcheck+'"><i class="bi bi-check-circle size16 green weight"></i></a>';
				} else {
					var mcheck = "xajax_confirm("+aData[18]+",'Y','$memberID');";
					var img_check = '<a href="javascript:void(0);" onclick="'+mcheck+'"><i class="bi bi-circle size16 gray"></i></a>';
				}
				$('td:eq(21)', nRow).html( '<div class="text-center">'+img_check+'</div>' );

				var url1 = "openfancybox_edit('/index.php?ch=edit&auto_seq="+aData[18]+"&fm=$fm',800,'96%','');";
				var mdel = "myDel("+aData[18]+");";

				var show_btn = '';
				if (('$powerkey'=="A") || ('$super_admin'=="Y")) {
					show_btn = '<div class="btn-group text-nowrap">'
						+'<button type="button" class="btn btn-light" onclick="'+url1+'" title="修改"><i class="bi bi-pencil-square"></i></button>'
						+'<button type="button" class="btn btn-light" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></button>'
						+'</div>';
				} else {
					show_btn = '<div class="btn-group text-nowrap">'
						+'<button type="button" class="btn btn-light" onclick="'+url1+'" title="修改"><i class="bi bi-pencil-square"></i></button>'
						+'</div>';
				}

				$('td:eq(22)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;min-height:32px;">'+show_btn+'</div>' );

				//最後修改
				var last_modify3 = "";
				if (aData[17] != null && aData[17] != "")
					last_modify3 = '<div class="text-nowrap">'+moment(aData[17]).format('YYYY-MM-DD HH:mm')+'</div>';
				
				//編輯人員
				var member_name = "";
				if (aData[19] != null && aData[19] != "")
					member_name = '<div class="text-nowrap">'+aData[19]+'</div>';

				$('td:eq(23)', nRow).html( '<div class="text-center" style="height:auto;min-height:32px;">'+last_modify3+member_name+'</div>' );


				return nRow;
			
			}
			
		});
	
		/* Init the table */
		oTable = $('#db_table').dataTable();
		
	} );

var myDel = function(auto_seq) {

	Swal.fire({
	title: "您確定要刪除此筆資料嗎?",
	text: "此項作業會刪除所有與此筆案件記錄有關的資料",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "刪除"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_DeleteRow(auto_seq);
		}
	});

};

var myDraw = function(){
	var oTable;
	oTable = $('#db_table').dataTable();
	oTable.fnDraw(false);
}

	
</script>

EOT;

} else {

	$sid = "mbwarning";
	$show_view = mywarning("很抱歉! 目前此功能只開放給本站特定會員，或是您目前的權限無法存取此頁面。");

}

?>