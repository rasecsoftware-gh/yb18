<?php
	require_once '../../sys.php';
	$module = "sys_accion";
	$prefix = $module;
	// params
	$filter = Sys::GetP('filter', '', true, $prefix); 
	// order 
	$sort_by = Sys::GetP('sort_by', 'key', true, $prefix);
	$sort_type = Sys::GetP('sort_type', 'ASC', true, $prefix);
	$orders = array(
		"id_accion"=>array("order_by"=>"id_accion $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"key"=>array("order_by"=>"key $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"descripcion"=>array("order_by"=>"descripcion $sort_type", "sort_type"=>"", "next_sort_type"=>"asc")
	);
	$order_by = $orders[$sort_by]['order_by'];
	$orders[$sort_by]['sort_type'] = strtolower($sort_type);
	$orders[$sort_by]['next_sort_type'] = (strtolower($sort_type)=='asc'?'desc':'asc');
	 
	// paginacion
	$page =  Sys::GetP('page', 1, true, $prefix); 
	$size = 50;
	// query
	$q = new PgQuery("
	SELECT *
	FROM sys.accion
	WHERE 
		(id_accion::text like '%$filter%' 
		OR lower(descripcion) like lower('%$filter%') 
		OR lower(key) like lower('%$filter%'))
	ORDER BY $order_by
	", NULL, false, true);
	$totalCount = $q->GetQueryCount();
	
	$pageCount = ceil($totalCount / $size); 
	$page = ($page > $pageCount)?1:$page; // controla que se use una pagina que no existe (fuera de rango), 1 pote aveces $pageCount puede ser cero 0
	$offset = (($page - 1) * $size);
	
	$q->sql .= " LIMIT $size OFFSET $offset";
	$q->Execute();
?>
<div style="padding: 3px; height: 18px;">
	<span class="bold c-gray fs-12" style="display: block; float: left; margin-right: 20px; line-height: 14px;">
	Administracion de Acciones del Sistema
	</span>
	<span style="float: left;">
		<a href="#" class="btn-icon add" onclick="<?=$prefix?>_new(); return false;" title="nuevo registro"></a>
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list(); return false;" title="actualizar lista"></a>
	</span>
</div>
<!-- SEARCH -->
<div class="pd bg-border" style="margin-bottom: 1px;">
	<form id="<?=$prefix?>_frm_search" name="<?=$prefix?>_frm_search" onsubmit="<?=$prefix?>_search(); return false;">
	<div class="frm-pd">
		<span>Filtrar por Id / Descripcion o Key&nbsp;</span>
		<span><input type="text" name="filter" value="<?=$filter?>"/></span>
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
</script>
<!-- PAGINATION -->
<script>
function <?=$prefix?>_go_page(i) {
	var params = 'load_from_session=1&page='+i;
	$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_contenido').html(data); });
};
</script>
<div class="pd bg-border l" style="margin-bottom: 1px;">
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
	<td class="cell-head bold">#</td>
	<td class="cell-head bold"><?=renderSort('Id', 'id_accion')?></td>
	<td class="cell-head bold"><?=renderSort('Key', 'key')?></td>
	<td class="cell-head bold"><?=renderSort('Descripcion', 'descripcion')?></td>
	<td class="cell-head bold">&nbsp;</td>
</tr>
<?php
	$list = array();
	while($r = $q->Read()) {
		$id = $r['id_accion'];
		$list[] = $id;
?>
<tr class="<?=$prefix?>-tr">
	<td class="cell l"><?=$q->recNo?></td>
	<td class="cell c c-blue"><?=$id?></td>
	<td class="cell l">
	<?=$r['key']?>
	</td>
	<td class="cell l"><?=$r['descripcion']?></td>
	<td class="cell">
		<span style="float: right;">
		<a href="#" class="btn-icon edit" onclick="<?=$prefix?>_edit(<?=$id?>); return false;" title="modificar"></a>
		<a href="#" class="btn-icon delete" onclick="<?=$prefix?>_delete(<?=$id?>); return false;" title="eliminar"></a>
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
	var w = new AccionWindow({p_task: 'new'});
	w.show();
};
function <?=$prefix?>_edit(id) {
	var w = new AccionWindow({p_task: 'edit', p_id: id});
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
AccionWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_window', title: 'Enlace', width: 700, height: 300, modal: true,  autoScroll: true,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/<?=$module?>/form.php', 'task='+this.p_task+'&id='+this.p_id, 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		AccionWindow.superclass.initComponent.call(this);
	}
});
</script>