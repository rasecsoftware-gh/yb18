<?php
	require_once '../../sys.php';
	$module = 'cn';
	$prefix = "{$module}_saldo";
	$p_secfun = Sys::GetS("{$module}secfun", ''); 
	$p_depen = Sys::GetS("{$module}depen", '');
	// vars conditions
	$anipre = Sys::GetPeriodo();
?>
<style>
.row-saldo .cell {
	padding: 1px 1px 1px 1px!important;
}
.row-saldo tr:FIRST-CHILD .cell {
	padding: 3px 1px 1px 1px!important;
}
</style>
<div id="<?=$prefix?>_grid_head" style="position: relative; background: white; z-index: 1;">
<table class="grid" width="100%" style="">
<tr>
	<td class="cell-head bold" colspan="9">Saldo del calendario</td>
</tr>
<tr>
	<td class="cell-head bold c-gray" width="5%">#</td>
	<td class="cell-head bold" width="15%">RB-R</td>
	<td class="cell-head bold" width="20%">Clasificador</td>
	<td class="cell-head bold" width="20%">Calendario</td>
	<td class="cell-head bold" width="20%">Avance</td>
	<td class="cell-head bold" width="20%">Saldo</td>
</tr>
</table>
</div>
<div id="<?=$prefix?>_grid_body" style="position: relative; margin-top: 1px; overflow: auto; display: block; z-index: 0;">
<script>
function <?=$prefix?>_resize() {
	var h = $('#cn_saldo_container').height();
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
	$params = array(
		$anipre, 
		$p_secfun,
		$p_depen
	);
	$sql = "{call dbo.Proc_RepCtroCtoSaldoCalendario(?, ?, ?)}"; 
	$q = sqlsrv_query(SqlProvider::GetConnection(),
	$sql, $params);
	if( $q === false)
	{
	     echo "Error in query preparation/execution.\n";
	     die( print_r( sqlsrv_errors(), true));
	}
	sqlsrv_next_result($q);
	sqlsrv_next_result($q);
	$qa = array();
	while ($d = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
		$qa[] = $d;
	}
	function cmp($a, $b) {
		if ($a['c_clapre']==$b['c_clapre']) return 0;
		return ($a['c_clapre']<$b['c_clapre'])?-1:1;
	}
	usort($qa, 'cmp');
	foreach($qa as $i=>$d) {
		$d = Sys::UTF8Decode($d);
		$d = SqlProvider::LowColNames($d);
		$rdata = rawurlencode(json_encode($d));
?>
<tr class="<?=$prefix?>-tr" align="left" valign="top" rdata="<?=$rdata?>">
	<td class="" colspan="6">
		<table class="row-saldo" width="100%">
		<tr align="left" valign="top">
			<td class="cell c c-black" valign="middle" rowspan="2" width="5%"><?=$i+1?></td>
			<td class="cell l c-black fs-7" colspan="5">
				<?=Sys::Upper($d['n_clapre_desc'])?>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="cell fs-8 c-gray" width="15%">
				<?=$d['c_fuefin'].'-'.$d['c_recurs']?>
			</td>
			<td class="cell l c-gray" width="20%">
				<?=$d['c_clapre']?>
			</td>
			<td class="cell r c-gray" width="20%">
				<?=Sys::NFormat($d['pim'])?>
			</td>
			<td class="cell r c-gray" width="20%">
				<?=Sys::NFormat($d['precompromiso']+$d['compromiso'])?>
			</td>
				<?php
	$saldo = $d['pim'] - ($d['precompromiso']+$d['compromiso']);?>
			<td class="cell r <?=$saldo>($d['pim']*0.02)?'c-green':'c-red'?>" width="20%">
				<?=Sys::NFormat($saldo)?>
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
var <?=$prefix?>_selected_row = '';
$('.<?=$prefix?>-tr')
.hover(
	function (e) { $(this).addClass('grid-row-over').addClass('grid-row-over-bg').prev().addClass('grid-row-over-before'); }, 
	function (e) { $(this).removeClass('grid-row-over').removeClass('grid-row-over-bg').prev().removeClass('grid-row-over-before'); }
).click(function () {
	$(<?=$prefix?>_selected_row).removeClass('grid-row-select');
	$(this).addClass('grid-row-select');
	<?=$prefix?>_selected_row = this;	
});
</script>