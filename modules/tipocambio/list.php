<?php
	require_once '../../sys.php';
	$module = 'tipocambio';
	$prefix = "{$module}";
	// params
	$filter = Sys::GetP('filter', '', true, $prefix); 
	// order 
	$sort_by = Sys::GetP('sort_by', 'tipocambio_id', true, $prefix);
	$sort_type = Sys::GetP('sort_type', 'DESC', true, $prefix);
	$orders = array(
		"tipocambio_id"=>array("order_by"=>"tipocambio_id $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"tipocambio_desc"=>array("order_by"=>"tipocambio_desc $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"establecimiento_desc"=>array("order_by"=>"establecimiento_desc $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"moneda_desc_de"=>array("order_by"=>"moneda_desc_de $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"moneda_desc_a"=>array("order_by"=>"moneda_desc_a $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"tipocambio_fecha"=>array("order_by"=>"tipocambio_fecha $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"tipope_desc"=>array("order_by"=>"tipope_desc $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"end"=>array("order_by"=>"end $sort_type", "sort_type"=>"", "next_sort_type"=>"asc")
	);
	$order_by = $orders[$sort_by]['order_by'];
	$orders[$sort_by]['sort_type'] = strtolower($sort_type);
	$orders[$sort_by]['next_sort_type'] = (strtolower($sort_type)=='asc'?'desc':'asc');
	 
	// paginacion
	$page =  Sys::GetP('page', 1, true, $prefix); 
	$size = 100;
	
	$hoy = date('d/m/Y');
	// query
	$q = new PgQuery("
	SELECT tc.*,
	mde.moneda_desc as moneda_desc_de,
	ma.moneda_desc as moneda_desc_a,
	tio.tipope_desc --,
	--e.establecimiento_desc
	FROM public.tipocambio tc
	JOIN public.moneda mde On mde.moneda_id = tc.moneda_id_de
	JOIN public.moneda ma On ma.moneda_id = tc.moneda_id_a
	JOIN public.tipope tio ON tio.tipope_id = tc.tipope_id
	--JOIN public.establecimiento e On e.establecimiento_id = tc.establecimiento_id
	WHERE 
	(
		tc.tipocambio_id::text ilike '%$filter%'
		OR mde.moneda_desc ilike '%$filter%'
		OR ma.moneda_desc ilike '%$filter%'
		OR tio.tipope_desc ilike '%$filter%'
	)
	ORDER BY $order_by
	", NULL, false, true);
	$totalCount = $q->GetQueryCount();
	
	$pageCount = ceil($totalCount / $size); 
	$page = ($page > $pageCount)?1:$page; // controla que se use una pagina que no existe (fuera de rango), 1 pote aveces $pageCount puede ser cero 0
	$offset = (($page - 1) * $size);
	
	$q->sql .= " LIMIT $size OFFSET $offset";
	$q->Execute();
?>
<div style="padding: 3px; height: 26px; line-height: 16px;">
	<span class="bold c-gray fs-12" style="float: left;">Tipos de Cambio</span>
	<span style="float: left; margin-left: 10px;">
		<a href="#" class="btn-icon add" onclick="<?=$prefix?>_new(); return false;" title="nuevo registro"></a>
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list(); return false;" title="actualizar lista"></a>
	</span>
</div>
<!-- SEARCH -->
<div class="pd bg-border" style="margin-bottom: 1px;">
	<form id="<?=$prefix?>_frm_search" name="<?=$prefix?>_frm_search" onsubmit="<?=$prefix?>_search(); return false;">
	<div class="frm-pd">
		<span>Buscar&nbsp;</span>
		<span><input id="<?=$prefix?>_filter" type="text" name="filter" value="<?=$filter?>"/></span>
	</div>
	<div class="frm-pd">
		<button type="submit">consultar</button>
		<button type="button" onclick="<?=$prefix?>_clear_search();">limpiar</button>
	</div> 
	</form>
</div>
<script>
function <?=$prefix?>_search() {
	var params = $('#<?=$prefix?>_frm_search').serialize();
	$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
};
function <?=$prefix?>_clear_search() {
	document.<?=$prefix?>_frm_search.filter.value = '';
	<?=$prefix?>_search();
};
$('#<?=$prefix?>_filter').focus();
</script>
<!-- PAGINATION -->
<script>
function <?=$prefix?>_go_page(i) {
	var params = 'load_from_session=1&page='+i;
	$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
};
</script>
<div class="frm-pd l" style="margin-bottom: 1px; border: 1px solid transparent;">
<?php
	$pages = array();
	for($i=1; $i<=$pageCount; $i++) {
		$selCls = ($i==$page)?"page-selected bold c":"page c";
		$pages[] = "<a href=\"#\" onclick=\"{$prefix}_go_page($i); return false;\" class=\"$selCls c-black\" title=\"pagina $i\">$i</a>"; 	
	} 
?>
	<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top"><?="mostrando del ".($offset+1)." al ".($offset+$q->recordCount)." de $totalCount&nbsp;|&nbsp;"?></td>
		<td valign="top"><?=implode("", $pages)?></td>
	</tr>
	</table>
</div>
<script>
	function <?=$prefix?>_sort(by, type) { 
		var params = 'load_from_session=1&sort_by='+by+'&sort_type='+type;
		$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
	};
</script>
<?php
	function renderSort($title, $name) { global $orders, $prefix; 
?>
	<a href="#" onclick="<?=$prefix?>_sort('<?=$name?>', '<?=$orders[$name]['next_sort_type']?>'); return false;"><?=$title?></a><span class="sort-<?=$orders[$name]['sort_type']?>"></span>
<?php
	} 
?>
<div class="grid-container">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold"><?=renderSort('Id', 'tipocambio_id')?></td>
	<td class="cell-head bold"><?=renderSort('Fecha', 'tipocambio_fecha')?></td>
	<td class="cell-head bold"><?=renderSort('Tipo Operacion', 'tipope_desc')?></td>
	<td class="cell-head bold"><?=renderSort('Recibe', 'moneda_desc_de')?></td>
	<td class="cell-head bold"><?=renderSort('Operador', 'tipocambio_operador')?></td>
	<td class="cell-head bold"><?=renderSort('Factor', 'tipocambio_factor')?></td>
	<td class="cell-head bold"><?=renderSort('Entrega', 'moneda_desc_a')?></td>
	<td class="cell-head bold">&nbsp;</td>
</tr>
<?php
	$list = array();
	while($r = $q->Read()) {
		$id = $r['tipocambio_id'];
		$list[] = $id;
		//$vigente[$r['tipooperacion_id']] = array($r['moneda_id_de']=>array($r['moneda_id_a']=>$r['tipocambio_fecha']));
?>
<tr class="<?=$prefix?>-tr">
	<td class="cell c c-blue"><?=$id?></td>
	<td class="cell l"><?=$r['tipocambio_fecha']?></td>
	<td class="cell l"><?=substr($r['tipope_desc'],0,6)?></td>
	<td class="cell l"><?=$r['moneda_desc_de']?></td>
	<td class="cell l"><?=$r['tipocambio_operador']?></td>
	<td class="cell r"><?=$r['tipocambio_factor']?></td>
	<td class="cell l"><?=$r['moneda_desc_a']?></td>
	<td class="cell">
		<span style="float: right;">
		<a href="#" class="btn-icon edit" onclick="<?=$prefix?>_edit(<?=$id?>); return false;" title="modificar"></a>
		</span>
	</td>
</tr>
<?php 
	} 
	$_SESSION[$prefix."list"] = serialize($list);
?>
</table>
</div>
<script>
$('.<?=$prefix?>-tr').hover(function (e) { $(this).addClass('grid-row-over'); }, function (e) { $(this).removeClass('grid-row-over'); });

function <?=$prefix?>_new() {
	var w = new TipoCambioWindow({p_task: 'new'});
	w.show();
};
function <?=$prefix?>_edit(id) {
	var w = new TipoCambioWindow({p_task: 'edit', p_id: id});
	w.show();
};
function <?=$prefix?>_delete(id) {
	if (!confirm('Realmente desea eliminar?')) return;
	$.post('modules/<?=$module?>/core.php', 'action=Delete&id='+id, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha eliminado satisfactoriamente');
			$.post('modules/<?=$module?>/list.php', '', function (data) { $('#<?=$prefix?>_container').html(data); });
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_reload_list() {
	$.post('modules/<?=$module?>/list.php', 'load_from_session=1', function (data) { $('#<?=$prefix?>_container').html(data); });
};
// Forms
TipoCambioWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_window', title: 'Tipo de Cambio', width: 500, height: 300, modal: true,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/<?=$prefix?>/form.php', 'task='+this.p_task+'&id='+this.p_id, 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		TipoCambioWindow.superclass.initComponent.call(this);
	}
});
</script>