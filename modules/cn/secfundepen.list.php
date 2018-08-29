<?php
	require_once '../../sys.php';
	$module = 'cn';
	$prefix = "{$module}_secfundepen";
	// vars
	$filter = Sys::GetR("filter", '');
	$anipre = Sys::GetPeriodo();
	$secfun = Sys::GetS("{$module}secfun", ''); 
	$depen = Sys::GetS("{$module}depen", '');
	$usuario = Sys::GetUserName();
	$is_admin = Sys::GetUserIsAdmin();
	// query
	$q = new SqlQuery("
	SELECT q.* FROM (
		SELECT 
		c_secfun, c_depend, F_AniDeSe_CoIn as tipo,
		dbo.fn_ObtieneDescripcionDependencia(c_anipre, c_depend) as des_depen,  
	   	dbo.fn_ObtenerDescripcionSecuencia(c_anipre, c_secfun) as des_secfun
		FROM dbo.Anios_Depend_SecFun
		WHERE  
		(
			EXISTS(
				SELECT * FROM dbo.usuario_secfun_depen 
				WHERE c_anipre = '$anipre' AND UPPER(c_usuario) = UPPER('$usuario')
				AND c_secfun = dbo.Anios_Depend_SecFun.c_secfun AND c_depend = dbo.Anios_Depend_SecFun.c_depend  
			) OR $is_admin = 1 
		)
		AND c_anipre = '$anipre' AND c_secfun <> '0000'
		GROUP BY c_anipre, c_secfun, c_depend, F_AniDeSe_CoIn
	) as q
	WHERE ".Sys::GetFilterSql($filter, "(des_secfun LIKE '%:filter%' OR des_depen LIKE '%:filter%' OR c_secfun LIKE '%:filter' OR c_depend LIKE '%:filter')").
	" ORDER BY c_secfun, c_depend", NULL, false, true);
	$q->cursorType = 'static';
	$q->Execute();
?>
<style>
</style>
<div id="<?=$prefix?>_grid_head" style="position: relative; z-index: 1;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="4">Seleccione Dependencia / Proyecto</td>
	<td class="cell-head bold r" colspan="1">
		<form id="<?=$prefix?>_frm_search" name="<?=$prefix?>_frm_search" onsubmit="<?=$prefix?>_search(); return false;" style="">
		<span class="" style="font-weight: normal;">buscar</span>
		<span><input type="text" name="filter" value="<?=$filter?>" style="width: 120px;"/></span>
<?php
	if (trim($filter)!=''): 
?>
		<button type="button" class="clear-search" onclick="<?=$prefix?>_clear_search();" title="cancelar busqueda">X</button>
<?php
	endif; 
?>
		</form>
	</td>
</tr>
<tr>
	<td class="cell-head c bold c-gray" width="3%">#</td>
	<td class="cell-head c bold" width="5%">Sec</td>
	<td class="cell-head c bold" width="7%">Depem</td>
	<td class="cell-head c bold" width="43%">Secuencia Funcional</td>
	<td class="cell-head c bold" width="42%">Dependencia</td>
</tr>
</table>
</div>
<script>
function <?=$prefix?>_search() {
	var params = $('#<?=$prefix?>_frm_search').serialize();
	$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).load('modules/<?=$module?>/secfundepen.list.php', params);
};
function <?=$prefix?>_clear_search() {
	document.<?=$prefix?>_frm_search.filter.value = '';
	<?=$prefix?>_search();
};
</script>
<div id="<?=$prefix?>_grid_body" style="overflow: auto; display: block; margin-top: 1px; z-index: 0;">
<script>
function <?=$prefix?>_resize() {
	try {
	var h = $('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).height();
	var h_head = $('#<?=$prefix?>_grid_head').outerHeight()+1;
	$('#<?=$prefix?>_grid_body').height(h - h_head);
	} catch (ex) {};
};
$(window).resize(function() {
	Ext.defer(<?=$prefix?>_resize, 100);
});
</script>
<form id="<?=$prefix?>_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<?php
	$qa = $q->ToArray();
	foreach($qa as $i=>$d) {
		$d = SqlProvider::LowColNames($d);
?>
<tr class="<?=$prefix?>-tr pointer" align="left" valign="top" secfun="<?=$d['c_secfun']?>" depen="<?=$d['c_depend']?>" tipo="<?=$d['tipo']?>">
	<td class="">
		<table class="row-saldo" width="100%">
		<tr align="left" valign="top">
			<td class="cell c c-black" valign="middle" width="3%"><?=$i+1?></td>
			<td class="cell c fs-8 c-gray" width="5%">
				<?=$d['c_secfun']?>
			</td>
			<td class="cell c c-gray" width="7%">
				<?=$d['c_depend']?>
			</td>
			<td class="cell l c-black fs-7" width="43%">
				<?=Sys::Upper($d['des_secfun'])?>
			</td>
			<td class="cell l c-black fs-7" width="42%">
				<?=Sys::Upper($d['des_depen'])?>
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

	Ext.getCmp('<?=$prefix?>_window').close();
	
	// set secfun and depen params on session
	var params = $.param({
		'action': 'SetSD', 
		'secfun': $(this).attr('secfun'), 
		'depen': $(this).attr('depen'),
		'tipo': $(this).attr('tipo')
	});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha seleccionado satisfactoriamente');
			<?=$module?>_reload_list();
		} else {
			alert(data);
		}
	});	
});
// init
<?=$prefix?>_resize();
</script>