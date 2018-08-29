<?php
	require_once '../../sys.php';
	Sys::DisableClassListen();
	require_once 'core.php';
	$module = 'cn';
	$prefix = "{$module}";
	// params
	$filter = Sys::GetP('filter', '', true, $prefix);
	$p_estado = Sys::GetP('estado', '', true, $prefix);
	// vars conditions
	$anipre = Sys::GetPeriodo();
	$secfun = Sys::GetP('secfun', '', true, $prefix, true);
	$depen = Sys::GetP('depen', '', true, $prefix, true);
	$tipo = Sys::GetP('tipo', '', true, $prefix, true);
	$usuario = Sys::GetUserName();
	$is_admin = Sys::GetUserIsAdmin();
	// secuencia filter
	$qsd = new SqlQuery("
	SELECT TOP 1
	c_secfun, c_depend, f_anidese_coin as tipo
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
	GROUP BY c_secfun, c_depend, F_AniDeSe_CoIn
	ORDER BY c_secfun, c_depend", NULL, false, true);
	$qsd->cursorType = 'static';
	$qsd->Execute();
	//echo $q->sql;
	// set default filter on empty
	if ($secfun == '' && $qsd->recordCount > 0) {
		list($secfun, $depen, $tipo) = $qsd->GetRowArray();
		$_SESSION["{$prefix}secfun"] = $secfun;
		$_SESSION["{$prefix}depen"] = $depen;
		$_SESSION["{$prefix}tipo"] = $tipo;	// tipo: I, C
	}
	$sd['des_secfun'] = SqlQuery::GetQueryVal("SELECT dbo.fn_ObtenerDescripcionSecuencia('$anipre', '$secfun')", '', NULL, true);
	$sd['des_depen'] = SqlQuery::GetQueryVal("SELECT dbo.fn_ObtieneDescripcionDependencia('$anipre', '$depen')", '', NULL, true);
	// query
	function get_filter_sql($filter, $filtersql) {
		$terms = explode(' ', $filter);
		$tlist = $terms;
		foreach ($tlist as $i=>$t) if (trim($t)=='') unset($terms[$i]);
		$sqllist = array();
		foreach ($terms as $t) {
			$sqllist[] = str_replace(':filter', $t, $filtersql);	
		}
		if (count($terms)>0)
			return implode(' AND ', $sqllist);
		else
			return '1=1';
	}
	$dtable = $tipo=='I'?'cuadro_inversion':'cuadro_necesi';
	$dprefix = $tipo=='I'?'cuainv':'cuanec';
	
	$q = new SqlQuery("
	SELECT TOP 500   
	C_Cuames, C_AniPre, C_Depen, C_SecFun, C_MesPre, N_CuaMes_Desc,dbo.DateTimeVarchar(D_CuaMes_Fecha)D_CuaMes_Fecha, Usuario,  
	dbo.fn_ObtieneDescripcionDependencia(C_AniPre,C_Depen) as N_Depen_Desc,  
	dbo.fn_ObtenerDescripcionSecuencia(C_AniPre,C_SecFun) as N_SecFun_Desc, F_CuaMes_Esta,
	(
		SELECT ISNULL(SUM(d1.q_{$dprefix}_prec*d2.q_{$dprefix}_cant), 0) 
		FROM {$dtable} d1
		JOIN {$dprefix}_detall d2 ON d2.c_{$dprefix} = d1.c_{$dprefix} AND d2.c_anipre = d1.c_anipre
		WHERE d1.c_cuames = Cuadro_Mensual.c_cuames AND d1.c_anipre = Cuadro_Mensual.c_anipre 
	) as total
	FROM Cuadro_Mensual
	WHERE C_AniPre = '$anipre' AND C_SecFun LIKE '%$secfun%'  
	AND C_Depen LIKE '%$depen%' 
	AND ('$p_estado'='' OR F_CuaMes_Esta = '$p_estado')
	AND	".get_filter_sql($filter, "(N_CuaMes_Desc LIKE '%:filter%' OR c_cuames LIKE '%:filter%')").
	" ORDER BY C_CuaMes DESC", NULL, false, true);
	//echo $q->sql;
	$q->Execute();
?>
<style>
.cn-estado {
	padding: 1px 1px;
	border: 1px solid black;
	font-size: 7pt;
	text-transform: uppercase;
	display: block;
	width: 30px;
	text-align: center;
}
.cn-sdinfo {
	padding: 2px 2px;
	background: #DFE8F6;
	border: 1px solid #99BCE8;
}
.cn-cell-field {
	background: #99CDF9;
	padding: 2px 10px;
}
.cn-cell-value {
	background: white;
	padding: 2px 3px;
}
</style>
<div id="<?=$prefix?>_hpanel" style="height: auto; position: relative;">
	<div class="cn-sdinfo">
<?php
	if ($qsd->recordCount>0):?>
		<table style="border-collapse: separate; border-spacing: 1px; width: 100%;">
		<tr align="left" valign="top">
			<td class="cn-cell-field" width="100">Secuencia</td><td class="cn-cell-value"><?="$secfun - {$sd['des_secfun']}"?></td>
			<td class="pd" rowspan="2" width="60" valign="middle" align="center">
			<button type="button" onclick="<?=$prefix?>_change_sd()">cambiar</button>
			</td>
		</tr>
		<tr>
			<td class="cn-cell-field">Dependencia</td><td class="cn-cell-value"><?="$depen - {$sd['des_depen']}"?></td>
		</tr>
		</table>
<?php
	else:?>
		<span>Usted no tiene Secuencia o Dependencia Asignada.</span>
<?php
	endif;?>
	</div>
</div>
<table width="100%" style="padding: 0; margin: 0; border: none;">
<tr>
	<td width="60%">
	<div id="<?=$prefix?>_cpanel" style="margin: 2px 0 0 0;">
		<!-- SEARCH -->
		<div id="<?=$prefix?>_toolbar"  class="pd bg" style="margin: 0 0 1px 0; height: 22px; position: relative;">
			<span class="float-l" style="">
				<a href="#" class="btn-icon add" onclick="<?=$prefix?>_new(); return false;" title="nuevo registro"></a>
				<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list(); return false;" title="actualizar lista"></a>
			</span>
			<span class="c-silver float-l">&nbsp;|&nbsp;</span>
			<span>
		
			<span class="underline-hover pointer fs-7 <?=$p_estado==''?'bold':'c-gray'?>" onclick="<?=$prefix?>_show('')">Todos</span>
			<span class="c-silver fs-7">|</span>
			<span class="underline-hover pointer fs-7 <?=$p_estado=='00'?'bold':'c-gray'?>" onclick="<?=$prefix?>_show('00')">Generados</span>
			<span class="c-silver fs-7">|</span>
			<span class="underline-hover pointer fs-7 <?=$p_estado=='01'?'bold':'c-gray'?>" onclick="<?=$prefix?>_show('01')">Cotizandose</span>
			<span class="c-silver fs-7">|</span>
			<span class="underline-hover pointer fs-7 <?=$p_estado=='02'?'bold':'c-gray'?>" onclick="<?=$prefix?>_show('02')">Adjudicados</span>
			<span class="c-silver fs-7">|</span>
			<span class="underline-hover pointer fs-7 <?=$p_estado=='03'?'bold':'c-gray'?>" onclick="<?=$prefix?>_show('03')">Comprometidos</span>
			<span class="c-silver fs-7">|</span>
			<span class="underline-hover pointer fs-7 <?=$p_estado=='99'?'bold':'c-gray'?>" onclick="<?=$prefix?>_show('99')">Anulados</span>
			</span>
			<div class="frm-pd" style="position: absolute; top: 0; right: 0;">
				<form id="<?=$prefix?>_frm_search" name="<?=$prefix?>_frm_search" onsubmit="<?=$prefix?>_search(); return false;" style="">
				<input type="hidden" name="estado" value="<?=$p_estado?>"/> 
				<span class="btn-icon search"></span>
				<span><input type="text" name="filter" value="<?=$filter?>" style="width: 80px;"/></span>
		<?php
	if (trim($filter)!=''): 
?>
				<button type="button" class="clear-search" onclick="<?=$prefix?>_clear_search();" title="cancelar busqueda">X</button>
		<?php
	endif; 
?>
				</form>
			</div>
		</div>
		<script>
		function <?=$prefix?>_search() {
			var params = $('#<?=$prefix?>_frm_search').serialize();
			$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
		};
		function <?=$prefix?>_show(v) {
			document.<?=$prefix?>_frm_search.estado.value = v;
			<?=$prefix?>_search();
		};
		function <?=$prefix?>_clear_search() {
			document.<?=$prefix?>_frm_search.filter.value = '';
			<?=$prefix?>_search();
		};
		</script>
		<div style="border: 1px solid #D5D5D5;">
		<div id="<?=$prefix?>_grid_head" style="position: relative; background: white; z-index: 1;">
		<table class="grid" width="100%">
		<tr align="left" valign="top">
			<td class="cell-head fs-8 bold" width="10%">
			&nbsp;
			</td>
			<td class="cell-head fs-8 bold" width="10%">
			A&ntilde;o
			</td>
			<td class="cell-head fs-8 bold" width=15%">
			Numero
			</td>
			<td class="cell-head fs-8 bold" width="15%">
			Fecha
			</td>
			<td class="cell-head fs-8 bold" width="35%">
			Descripcion
			</td>
			<td class="cell-head fs-8 bold c" width="15%">
			Total
			</td>
		</tr>
		</table>
		</div>
		<div id="<?=$prefix?>_grid_body" style="position: relative; margin-top: 1px; overflow: auto; display: block; z-index: 0;">
		<form id="<?=$prefix?>_list_form" onsubmit="return false;">
		<table class="grid" width="100%">
		<?php
			$list = array();
			while($r = $q->Read()) {
				$id = $r['c_cuames'];
				$list[] = $id;
		?>
		<tr class="<?=$prefix?>-tr" valign="top" rid="<?=$id?>">
			<td class="cell fs-8" width="10%">
				<?php
					$estado = $r['f_cuames_esta'];
					$einfo = Core::GetEstadoInfo($estado);
				?>
				<span class="cn-estado pointer" rid="<?=$id?>" style="background: <?=$einfo[1]?>!important; color: <?=$einfo[2]?>!important;"><?=substr($einfo[0], 0, 3)?></span>
			</td>
			<td class="cell fs-8" width="10%">
				<?=$r['c_anipre']?>
			</td>
			<td class="cell fs-8" width="15%">
				<?=$r['c_cuames']?>
			</td>
			<td class="cell fs-8" width="15%">
				<?=$r['d_cuames_fecha']?>
			</td>
			<td class="cell fs-8" width="35%">
				<?=$r['n_cuames_desc']?>
			</td>
			<td class="cell fs-8 r" width="15%">
				<?=Sys::NFormat($r['total'])?>
			</td>
		</tr>
		<?php 
			} 
			$_SESSION[$prefix."list"] = serialize($list);
		?>
		</table>
		</form>
		</div> <!-- end grid-body -->
		</div>
	</div> <!-- end cn -->
	</td>
	<td width="40%">
	<div id="<?=$prefix?>_rpanel" style="position: relative;">
		<div id="<?=$prefix?>_saldo_container" style="position: relative; margin: 3px 1px 0 2px; display: block; border: 1px solid #D5D5D5;">
		</div>
		<div id="<?=$prefix?>_poi_container" style="position: relative; margin: 3px 1px 0 2px; display: block; border: 1px solid #D5D5D5;">
		</div>
	</div>
	</td>
</tr>
</table>
<div id="<?=$prefix?>-stateinfo-dialog" title="Informacion de estado"></div>
<script>
function <?=$prefix?>_resize() {
	var h = $('#<?=$prefix?>-body').height();
	var h_hpanel = $('#<?=$prefix?>_hpanel').outerHeight();
	
	var dif = 7; 
	$('#<?=$prefix?>_cpanel').height((h - h_hpanel)-dif);
	
	$('#<?=$prefix?>_rpanel').height((h - h_hpanel)-dif);

	var h_rpanel = $('#<?=$prefix?>_rpanel').height();
	var rh = <?=$tipo=='I'?0:0.4?>;
	$('#<?=$prefix?>_saldo_container').height(h_rpanel*(1-rh));
	$('#<?=$prefix?>_poi_container').height(h_rpanel*rh);

	var h = $('#<?=$prefix?>_cpanel').height();
	var h_rest = $('#<?=$prefix?>_toolbar').outerHeight();
	h_rest += $('#<?=$prefix?>_grid_head').outerHeight()+1;
	$('#<?=$prefix?>_grid_body').height(h - h_rest);
};
$(window).resize(function() {
	Ext.defer(<?=$prefix?>_resize, 100);
});

function <?=$prefix?>_load_saldo() {
	$('#<?=$prefix?>_saldo_container').load('modules/<?=$module?>/saldo.list.php');
};
function <?=$prefix?>_load_poi() {
	$('#<?=$prefix?>_poi_container').load('modules/<?=$module?>/poi.list.php');
};

var <?=$prefix?>_cm = Ext.create('Ext.menu.Menu', {
    floating: true,  // 
    renderTo: Ext.getBody(),  // usually rendered by it's containing component
    items: [{
        text: 'Modificar',
        listeners: {
        	click: function () {
    			<?=$prefix?>_edit($(cn_selected_row).attr('rid'));
    		} 
    	}
    },{
        text: '<span class="c-red">Anular</span>',
        listeners: {
        	click: function () {
    			<?=$prefix?>_anular($(cn_selected_row).attr('rid'));
    		} 
    	} 
    },{
        text: 'Imprimir',
        listeners: {
	    	click: function () {
				<?=$prefix?>_print($(cn_selected_row).attr('rid'));
    		}
    	}
    }]
});
var cn_selected_row = '';
$('.<?=$prefix?>-tr')
.hover(
	function (e) { $(this).addClass('grid-row-over').addClass('grid-row-over-bg').prev().addClass('grid-row-over-before'); }, 
	function (e) { $(this).removeClass('grid-row-over').removeClass('grid-row-over-bg').prev().removeClass('grid-row-over-before'); }
).click(function () {
	$(cn_selected_row).removeClass('grid-row-select');
	$(this).addClass('grid-row-select');
	cn_selected_row = this;	
	//sys.alert($(this).attr('rid'));
}).bind('contextmenu', function (e) {
	$(cn_selected_row).removeClass('grid-row-select');
	$(this).addClass('grid-row-select');
	cn_selected_row = this;	
	<?=$prefix?>_cm.showAt(e.pageX, e.pageY);
	return false;
});

function <?=$prefix?>_new() {
	Ext.Msg.prompt('Nuevo Cuadro Mensual', 'Por favor ingrese la descripcion:', function(btn, text){
	    if (btn == 'ok'){
	    	var params = $.param({'action': 'Create', 'descripcion': text});
	    	$.post('modules/<?=$module?>/core.php', params, function (data) {
		    	var id = parseInt($.trim(data), 10); 
	    		if (id > 0) {
	    			sys.message('Se ha creado satisfactoriamente el Cuadro Mensual Nro. '+id);
	    			var params = $.param({'task': 'edit', 'id': $.trim(data)});
	    			$('#<?=$prefix?>_cpanel').load('modules/<?=$module?>/form.php?'+params);
	    		} else {
	    			sys.alert(data);
	    		}
	    	}); 	
	    }
	});
};
function <?=$prefix?>_edit(id) {
	var params = $.param({'task': 'edit', 'id': id});
	$('#<?=$prefix?>_cpanel').load('modules/cn/form.php?'+params);
};
function <?=$prefix?>_anular(id) {
	if (!confirm('Realmente desea ANULAR?')) return;
	var params = $.param({action: 'Anular', id: id});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha ANULADO satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			sys.alert(data);
		}
	});
};
function <?=$prefix?>_print(id) {
	var params = $.param({'n': 'cnm', 'y': '<?=Sys::GetPeriodo()?>', 'i':id});
	window.open('<?=Config::GetReportUrl()?>/report.pdf.jsp?'+params, '_blank');
};
function <?=$prefix?>_reload_list() {
	$.post('modules/<?=$module?>/list.php', 'load_from_session=1', function (data) { $('#<?=$prefix?>_container').html(data); });
};

function <?=$prefix?>_change_sd() {
	var w = new SecFunDepenWindow({});
	w.show();
};
function <?=$prefix?>_show_stateinfo(id) {
	$('#<?=$prefix?>-stateinfo-dialog').dialog({
		width: 600, height: 400, modal: true,
		open: function (e, ui) {
			$(this).load('modules/cn/stateinfo.php', 'id='+id);
		}
	});
};
$('.cn-estado').click(function () {
	var id = $(this).attr('rid');
	<?=$prefix?>_show_stateinfo(id);
});
// Forms
SecFunDepenWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_secfundepen_window', title: 'Dependencia / Proyecto', width: 800, height: 600, modal: true, autoScroll: false,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/<?=$module?>/secfundepen.list.php', '', 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		SecFunDepenWindow.superclass.initComponent.call(this);
	}
});
// init
<?=$prefix?>_load_saldo();
<?=$prefix?>_resize();
<?php
	if ($tipo=='C'):?>
<?=$prefix?>_load_poi();
<?php
	endif;?>
</script>