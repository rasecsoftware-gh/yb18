<?php
	require_once '../../sys.php';
	$module = 'cn';
	$prefix = "{$module}_poi";
	$secfun = Sys::GetS("{$module}secfun", ''); 
	$depen = Sys::GetS("{$module}depen", '');
	// vars conditions
	$anipre = Sys::GetPeriodo();
	// query
	$params = array(
		$depen,
		$anipre
	);
	$sql = "{call dbo.Proc_ActiviPOIGetbyDepen(?, ?)}"; 
	$q = sqlsrv_query(SqlProvider::GetConnection(),
	$sql, $params);
	if( $q === false)
	{
	     echo "Error in query preparation/execution.\n";
	     die( print_r( sqlsrv_errors(), true));
	}
	$qa = array();
	while ($d = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
		$qa[] = SqlProvider::LowColNames($d);
	}
	function cmp($a, $b) {
		if ($a['c_clapre']==$b['c_clapre']) return 0;
		return ($a['c_clapre']<$b['c_clapre'])?-1:1;
	}
	//usort($qa, 'cmp');
?>
<style>
.row-poi .cell {
	padding: 1px 1px 1px 1px!important;
}
.row-poi tr:FIRST-CHILD .cell {
	padding: 3px 1px 1px 1px!important;
}
</style>
<div id="<?=$prefix?>_grid_head" style="position: relative; z-index: 1;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="8">Actividades POI</td>
</tr>
<tr>
	<td class="cell-head c bold c-gray" width="5%">#</td>
	<td class="cell-head c bold" width="10%">Obj</td>
	<td class="cell-head c bold" width="10%">Met</td>
	<td class="cell-head c bold" width="10%">Act</td>
	<td class="cell-head c bold" width="17%">Tri 1</td>
	<td class="cell-head c bold" width="16%">Tri 2</td>
	<td class="cell-head c bold" width="16%">Tri 3</td>
	<td class="cell-head c bold" width="16%">Tri 4</td>
</tr>
</table>
</div>
<div id="<?=$prefix?>_grid_body" style="overflow: auto; display: block; margin-top: 1px; z-index: 0;">
<script>
function <?=$prefix?>_resize() {
	var h = $('#cn_poi_container').height();
	var h_head = $('#<?=$prefix?>_grid_head').outerHeight()+1;
	$('#<?=$prefix?>_grid_body').height(h - h_head);
};
$(window).resize(function() {
	Ext.defer(<?=$prefix?>_resize, 200);
});
<?=$prefix?>_resize();
</script>
<form id="<?=$prefix?>_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<?php
	foreach($qa as $i=>$d) {
		$d = Sys::UTF8Decode($d);
		$rdata = rawurlencode(json_encode($d));
?>
<tr class="<?=$prefix?>-tr" align="left" valign="top" rdata="<?=$rdata?>">
	<td class="" colspan="8">
		<table class="row-saldo" width="100%">
		<tr align="left" valign="top">
			<td class="cell c c-black" valign="middle" rowspan="2" width="5%"><?=$i+1?></td>
			<td class="cell l c-black fs-7" colspan="7">
				<?=Sys::Upper($d['n_actpoi_desc'])?>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="cell c fs-8 c-gray" width="10%">
				<?=$d['c_objeti']?>
			</td>
			<td class="cell c c-gray" width="10%">
				<?=$d['c_metpoi']?>
			</td>
			<td class="cell c c-gray" width="10%">
				<?=$d['c_actpoi']?>
			</td>
			<td class="cell r c-gray" width="17%">
				<?=Sys::NFormat($d['q_actpoi_tri1'])?>
			</td>
			<td class="cell r c-gray" width="16%">
				<?=Sys::NFormat($d['q_actpoi_tri2'])?>
			</td>
			<td class="cell r c-gray" width="16%">
				<?=Sys::NFormat($d['q_actpoi_tri3'])?>
			</td>
			<td class="cell r c-gray" width="16%">
				<?=Sys::NFormat($d['q_actpoi_tri4'])?>
			</td>
		</tr>
		</table>
	</td>
</tr>
<?php 
	}
?>
</table>
</form>
</div>
<script>
var cn_poi_selected_row = '';
$('.<?=$prefix?>-tr')
.hover(
	function (e) { $(this).addClass('grid-row-over').addClass('grid-row-over-bg').prev().addClass('grid-row-over-before'); }, 
	function (e) { $(this).removeClass('grid-row-over').removeClass('grid-row-over-bg').prev().removeClass('grid-row-over-before'); }
).click(function () {
	$(cn_poi_selected_row).removeClass('grid-row-select');
	$(this).addClass('grid-row-select');
	cn_poi_selected_row = this;	
});
</script>