<?php
	require_once '../../sys.php';
	$module = 'cn';
	$prefix = "{$module}_detail_bs";
	$cuames = Sys::GetR("cuames", '-', true, $prefix, true);
	$filter = Sys::GetR("filter", '');
	$secfun = Sys::GetS("{$module}secfun", ''); 
	$depen = Sys::GetS("{$module}depen", '');
	$tipo = Sys::GetS("{$module}tipo", '');
	// get array
	$srow = json_decode(rawurldecode(Sys::GetR('srow', '{}')), true);
	$prow = json_decode(rawurldecode(Sys::GetR('prow', '{}')), true);
	//var_dump($srow);
	// vars conditions
	$anipre = Sys::GetPeriodo();
	$tipo = $tipo=='I'?'IN':'CO'; 
	// query
	$params = array(
		$anipre,
		$srow['c_clapre'],
		$cuames,
		$secfun,
		$depen,
		$filter,
		$tipo
	);
	//var_dump($params);
	$sql = "{call dbo.Proc_BienServ_BienXClasificadorMes(?, ?, ?, ?, ?, ?, ?)}"; 
	$q = sqlsrv_query(SqlProvider::GetConnection(),
	$sql, $params);
	if($q === false && Config::$Debug) {
	     echo "SQL ERROR:\n";
	     die( print_r( sqlsrv_errors(), true));
	}
	$qa = array();
	while ($d = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) { 
		$qa[] = SqlProvider::LowColNames($d); 
	}
	
	function cmp($a, $b) {
		if ($a['n_bieser_desc']==$b['n_bieser_desc']) return 0;
		return ($a['n_bieser_desc']<$b['n_bieser_desc'])?-1:1;
	}
	usort($qa, 'cmp');
?>
<style>
</style>
<div id="<?=$prefix?>_grid_head" style="position: relative; z-index: 1;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="7">Bienes y Servicios</td>
</tr>
<tr>
	<td class="cell-head c bold c-gray" width="5%">#</td>
	<td class="cell-head c bold" width="35%">Descripcion</td>
	<td class="cell-head c bold" width="10%">Medida</td>
	<td class="cell-head c bold" width="10%">Precio</td>
	<td class="cell-head c bold" width="15%">Cla.Pre.</td>
	<td class="cell-head c bold" width="5%">Ok</td>
	<td class="cell-head c bold" width="20%">Clase</td>
</tr>
</table>
</div>
<div id="<?=$prefix?>_grid_body" style="overflow: auto; display: block; margin-top: 1px; z-index: 0;">
<script>
function <?=$prefix?>_resize() {
	try {
	var h = $('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).height();
	var h_head = $('#<?=$prefix?>_grid_head').outerHeight()+1;
	var h_foot = $('#<?=$prefix?>_foot').outerHeight();
	$('#<?=$prefix?>_grid_body').height(h - h_head - h_foot);
	} catch (ex) {};
};
$(window).resize(function() {
	Ext.defer(<?=$prefix?>_resize, 200);
});
<?=$prefix?>_resize();
</script>
<form id="<?=$prefix?>_frm_list" onsubmit="return false;">
<table class="grid" width="100%">
<?php
	foreach($qa as $i=>$d) {
		$d = Sys::UTF8Decode($d);
		$did = $d['c_bieser'];
		$rdata = rawurlencode(json_encode($d));
?>
<tr class="<?=$prefix?>-tr" align="left" valign="top" rdata="<?=$rdata?>">
	<td class="" colspan="8">
		<table class="row-saldo" width="100%">
		<tr align="left" valign="top">
			<td class="cell c c-black" valign="middle" width="5%"><?=$i+1?></td>
			<td class="cell l fs-7 c-gray" width="35%">
				<?=$d['n_bieser_desc']?>
			</td>
			<td class="cell c c-gray" width="10%">
				<?=$d['n_unibis_desc']?>
			</td>
			<td class="cell r c-gray" width="10%">
				<?=Sys::NFormat($d['q_bieser_cost'])?>
			</td>
			<td class="cell r c-gray" width="15%">
				<?=$d['c_clapre'].$d['c_bieser_agco']?>
			</td>
			<td class="cell c c-gray" width="5%"><input class="<?=$prefix?>_check" type="checkbox" name="list[]" value="<?=$did?>"/></td>
			<td class="cell l c-gray" width="20%">
				<?=$d['n_bisecl_nombre']?>
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
<style>
.cn-bs-info {
	padding: 2px 2px;
	background: #DFE8F6;
	border: 1px solid #99BCE8;
}
.cn-bs-cell-field {
	background: #99CDF9;
	padding: 2px 10px;
}
.cn-bs-cell-value {
	background: white;
	padding: 2px 3px;
}
</style>
<div id="<?=$prefix?>_foot" class="cn-bs-info" >
	<table style="border-collapse: separate; border-spacing: 1px; width: 100%;">
	<tr align="left" valign="top">
		<td class="cn-cell-field" width="80">Tipo</td><td id="<?=$prefix?>_tipo" class="cn-bs-cell-value"><?=$s_tipo?></td>
		<td class="pd c" rowspan="4" width="80">
			<button type="button" onclick="<?=$prefix?>_add()">Agregar</button><br/><br/>
			<button type="button" onclick="Ext.getCmp('<?=$prefix?>_window').close();">Cancelar</button>
		</td>
	</tr>
	<tr>
		<td class="cn-cell-field">Grupo</td><td id="<?=$prefix?>_grupo" class="cn-bs-cell-value"><?=$s_grupo?></td>
	</tr>
	<tr>
		<td class="cn-cell-field">Clase</td><td id="<?=$prefix?>_clase" class="cn-bs-cell-value"><?=$s_clase?></td>
	</tr>
	<tr>
		<td class="cn-cell-field">Categoria</td><td id="<?=$prefix?>_categoria" class="cn-bs-cell-value"><?=$s_categoria?></td>
	</tr>
	</table>
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
	eval("var row = "+unescape($(this).attr('rdata')));
	$('#<?=$prefix?>_tipo').html(row['c_bieser_tipo']);
	$('#<?=$prefix?>_grupo').html(row['n_grup_desc']);
	$('#<?=$prefix?>_clase').html(row['n_bisecl_nombre']);
	$('#<?=$prefix?>_categoria').html(row['nomcategoria']);
});
function <?=$prefix?>_add() {
	var params = $('#<?=$prefix?>_frm_list').serialize();
	params += "&"+$.param({
		'cuames': '<?=$cuames?>',
		'srow': '<?=Sys::GetR('srow', '{}')?>',
		'prow': '<?=Sys::GetR('prow', '{}')?>'
	});
	$.post('modules/<?=$module?>/core.php', 'action=AddDetails&'+params, function (data) {
		if ($.trim(data) == 'ok') {
			<?=$module?>_detail_reload_list();
			<?=$module?>_saldo_reload_list();
			Ext.getCmp('<?=$prefix?>_window').close();
		} else {
			alert(data);
		}
	});
};
</script>