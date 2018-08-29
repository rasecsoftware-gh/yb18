<?php
	require_once '../../sys.php';
	$module = "sys_menu";
	$prefix = "$module";
	// params
	$filter = Sys::GetP('filter', '', true, $prefix); 
	// order 
	$sort_by = Sys::GetP('sort_by', 'orden', true, $prefix);
	$sort_type = Sys::GetP('sort_type', 'DESC', true, $prefix);
	$orders = array(
		"orden"=>array("order_by"=>"orden $sort_type", "sort_type"=>"", "next_sort_type"=>"asc")
	);
	$order_by = $orders[$sort_by]['order_by'];
	$orders[$sort_by]['sort_type'] = strtolower($sort_type);
	$orders[$sort_by]['next_sort_type'] = (strtolower($sort_type)=='asc'?'desc':'asc');
	 
	// paginacion
	$page =  Sys::GetP('page', 1, true, $prefix); 
	$size = 100;
	// query
	$q = new PgQuery("
	SELECT m.* 
	FROM sys.menu m
	LEFT JOIN sys.menu p ON p.id_menu = m.id_parent 
	WHERE 
	(
		m.id_menu::text like '%$filter%' 
		OR lower(m.nombre) like lower('%$filter%') 
	)
	AND m.id_parent IS NULL
	ORDER BY m.orden
	", NULL, false, true);
	$totalCount = $q->GetQueryCount();
	
	$pageCount = ceil($totalCount / $size); 
	$page = ($page > $pageCount)?1:$page; // controla que se use una pagina que no existe (fuera de rango), 1 pote aveces $pageCount puede ser cero 0
	$offset = (($page - 1) * $size);
	
	//$q->sql .= " LIMIT $size OFFSET $offset";
	$q->Execute();
?>
<div style="padding: 3px; height: 16px;">
	<span class="bold c-gray fs-12" style="display: block; float: left; margin-right: 20px; line-height: 16px;">Menu del Sistema</span>
	<span style="float: left;">
		<a href="#" class="btn-icon add" onclick="<?=$prefix?>_new(); return false;" title="nuevo registro"></a>
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list(); return false;" title="actualizar lista"></a>
	</span>
</div>
<!-- SEARCH -->
<div class="pd bg" style="margin-bottom: 1px;">
	<form id="<?=$prefix?>_frm_search" name="<?=$prefix?>_frm_search" onsubmit="<?=$prefix?>_search(); return false;">
	<div class="frm-pd">
		<span>Filtrar por Id / Nombre&nbsp;</span>
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
	$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
};
</script>
<div class="frm-pd l" style="margin-bottom: 1px; border: none;">
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
	<td class="cell-head bold" width="30">Orden</td>
	<td class="cell-head bold">Id</td>
	<td class="cell-head bold">Descripcion</td>
	<td class="cell-head bold">BeginGroup</td>
	<td class="cell-head bold">Activo</td>
	<td class="cell-head bold">&nbsp;</td>
</tr>
<?php
	function renderRow($q, $r) {
		global $prefix, $list, $rn;
		$id = $r['id_menu'];
		$padding = is_null($r['id_parent'])?'':'padding-left: 20px;'; 
?>
<tr class="<?=$prefix?>-tr">
	<td class="cell l"><?=$rn?></td>
	<td class="cell" align="center" valign="middle">
<?php
		if ($q->recNo > 1) { 
?>
		<div class="sort-asc pointer <?=$prefix?>_btn_order" rid="<?=$id?>" parent="<?=$r['id_parent']?>" order="-1" style="float: none;" title="mover arriba"></div>
<?php
		}?>
<?php
		if ($q->recNo < $q->recordCount) { 
?>
		<div class="sort-desc pointer <?=$prefix?>_btn_order" rid="<?=$id?>" parent="<?=$r['id_parent']?>" order="1" style="float: none;" title="mover abajo"></div>
<?php
		}?>
	</td>
	<td class="cell c c-blue"><?=$id?></td>
	<td class="cell l" style="<?=$padding?>"><?=$r['nombre']?></td>
	<td class="cell l"><?=$r['begin_group']==1?'Si':'No'?></td>
	<td class="cell l"><?=$r['estado']==1?'Si':'No'?></td>
	<td class="cell">
		<span style="float: right;">
		<a href="#" class="btn-icon edit" onclick="<?=$prefix?>_edit(<?=$id?>); return false;" title="modificar"></a>
		<a href="#" class="btn-icon delete" onclick="<?=$prefix?>_delete(<?=$id?>); return false;" title="eliminar"></a>
		</span>
	</td>
</tr>
<?php
		$q = new PgQuery("
		SELECT m.* 
		FROM sys.menu m
		WHERE (m.id_menu::text like '%$filter%' OR lower(m.nombre) like lower('%$filter%'))
		AND m.id_parent = $id
		ORDER BY m.orden
		", NULL, true, true);
		while($rd = $q->Read()) {
			$id = $rd['id_menu'];
			$list[] = $id;
			$rn++;
			renderRow($q, $rd);
		}
	} // end function renderRow
	
	$list = array(); $rn = 1;
	while($r = $q->Read()) {
		$id = $r['id_menu'];
		$list[] = $id;
		renderRow($q, $r);
		$rn++;
	} 
	$_SESSION[$prefix."list"] = serialize($list);
?>
</table>
</div>
<script>
$('.<?=$prefix?>-tr').hover(function (e) { $(this).addClass('grid-row-over'); }, function (e) { $(this).removeClass('grid-row-over'); });

function <?=$prefix?>_new() {
	var w = new MenuWindow({p_task: 'new'});
	w.show();
};
function <?=$prefix?>_edit(id) {
	var w = new MenuWindow({p_task: 'edit', p_id: id});
	w.show();
};
function <?=$prefix?>_delete(id) {
	if (!confirm('Realmente desea eliminar?')) return;
	$.post('modules/<?=$module?>/core.php', 'action=Delete&id='+id, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha eliminado satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_reload_list() {
	$.post('modules/<?=$module?>/list.php', 'load_from_session=1', function (data) { $('#<?=$prefix?>_container').html(data); });
};
$('.<?=$prefix?>_btn_order').click(function() {
	var params = $.param({
		'action': 'ChangeOrden', 
		'id': $(this).attr('rid'), 
		'id_parent': $(this).attr('parent'),
		'order': $(this).attr('order')
	});
	$.post('modules/<?=$module?>/core.php', params, function (data) { 
		if ($.trim(data) == 'ok') {
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
});
// Forms
MenuWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_window', title: 'Menu', width: 900, height: 400, modal: true, autoScroll: true,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/<?=$module?>/form.php', 'task='+this.p_task+'&id='+this.p_id, 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		MenuWindow.superclass.initComponent.call(this);
	}
});
</script>