<?php
	require_once '../../sys.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title><?=Config::GetSystemName()?></title>
<link rel="shortcut icon" href="../../img/logo.ico"/>
<link href="../../css/base.css" rel="stylesheet" type="text/css"/>
<link href="../../css/theme.css" rel="stylesheet" type="text/css"/>
<link href="../../css/fix.css" rel="stylesheet" type="text/css"/>
<!-- JQuery -->
<script src="../../js/jquery.ui/js/jquery-1.7.1.min.js" type="text/javascript"></script>
<!-- Ext Links -->
<link rel="stylesheet" type="text/css" href="../../js/ext-4/resources/css/ext-all.css" />
<script type="text/javascript" src="../../js/ext-4/bootstrap.js"></script>
<script type="text/javascript" src="../../js/ext-4/locale/ext-lang-es.js"></script>
<script>
Ext.Loader.setConfig({enabled: true});
Ext.require([
	'Ext.util.*',
	'Ext.Action',
	'Ext.button.*',
	'Ext.form.*',
	'Ext.layout.container.Card',
	'Ext.layout.container.Border'
]);
</script>
<style>
@media print {
	.cell-head, .cell {
		border: 1px solid #ddd!important;
		padding: 3px 5px!important;
		background: none!important;
	}
	tr {
		background: none!important;
	}
	* {
		color: black!important;
	}
}
.button-change {
	padding: 5px 10px;
	color: gray;
	border: 1px solid silver;
}
.button-change:hover {
	padding: 5px 10px;
	color: black;
	border: 1px solid gray;
	text-decoration: none;
}
</style>
</head>
<body>
<?php
	$module = 'saldo';
	$prefix = "{$module}";
	
	$anipre = Sys::GetPeriodo();
	$secfun = Sys::GetP('secfun', '', true, $prefix, true);
	$depen = Sys::GetP('depen', '', true, $prefix, true);
	$tipo = Sys::GetP('tipo', '', true, $prefix, true);
	$usuario = Sys::GetUserName();
	$is_admin = Sys::GetUserIsAdmin();
	// secuencia filter
	$qsd = new SqlQuery("
	SELECT TOP 2
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
?>
<div id="<?=$prefix?>_grid_head" style="position: relative; background: white; z-index: 1;">
<table class="grid" width="100%" style="">
<tr>
	<td class="cell-head" colspan="8">
		<span class="bold fs-12">Saldo del calendario de Gastos - <?=$anipre?></span>
		<div style="position: absolute; right: 5px; top: 4px;">
			<span class="hidable">
				<a href="javascript:location.reload()" title="recargar reporte">actualizar</a>
				|
				<a href="javascript:imprimir()" title="imprimir reporte">imprimir</a>
			</span>
			<span class="printable">impreso el <?=date('d/m/Y H:i:s')?></span>
		</div>
	</td>
</tr>
<tr>
	<td class="cell" colspan="7">
		<div class="bold c-gray"><?=$secfun.' - '.$sd['des_secfun']?></div>
		<div class="bold c-gray"><?=$depen.' - '.$sd['des_depen']?></div>
	</td>
	<td class="cell" colspan="1" align="center">
<?php
	if ($qsd->recordCount>1):?>
	<a class="button-change hidable" href="javascript:void(0)" onclick="showchangesdwindow()" title="cambiar de secuencia - dependencia">cambiar</a>
<?php
	endif;?>
	</td>
</tr>
<tr>
	<td class="cell-head bold c-gray" width="3%">#</td>
	<td class="cell-head bold" width="4%">RB-R</td>
	<td class="cell-head bold" width="28%">Clasificador</td>
	<td class="cell-head c bold" width="13%">Programado</td>
	<td class="cell-head c bold" width="13%">Pre-Compro.</td>
	<td class="cell-head c bold" width="13%">Comprometido</td>
	<td class="cell-head c bold" width="13%">Saldo PreCompro.</td>
	<td class="cell-head c bold" width="13%">Saldo Compro.</td>
</tr>
<?php
	$params = array(
		$anipre, 
		$secfun,
		$depen
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
		$d = SqlProvider::LowColNames($d);
		$qa[] = $d;
	}
	function cmp($a, $b) {
		$ra = $a['c_fuefin'].$a['c_recurs'].$a['c_clapre']; 
		$rb = $b['c_fuefin'].$b['c_recurs'].$b['c_clapre'];
		return strcmp($ra, $rb);
	}
	usort($qa, 'cmp');
	foreach($qa as $i=>$d) {
		$d = Sys::UTF8Decode($d);
		$rdata = rawurlencode(json_encode($d));
?>
<tr class="<?=$prefix?>-tr" align="left" valign="top">
	<td class="cell c-gray"><?=$i+1?></td>
	<td class="cell"><?=$d['c_fuefin'].'-'.$d['c_recurs']?></td>
	<td class="cell"><?=$d['c_clapre']?> - <?=Sys::Upper($d['n_clapre_desc'])?></td>
	<td class="cell r"><?=Sys::NFormat($d['pim'])?></td>
	<td class="cell r"><?=Sys::NFormat($d['precompromiso'])?></td>
	<td class="cell r"><?=Sys::NFormat($d['compromiso'])?></td>
	<?php
	$saldo = $d['pim'] - ($d['precompromiso']+$d['compromiso']);?>
	<td class="cell r <?=$saldo>($d['pim']*0.02)?'c-green':'c-red'?>"><?=Sys::NFormat($saldo)?></td>
	<?php
	$saldo = $d['pim'] - ($d['compromiso']);?>
	<td class="cell r <?=$saldo>($d['pim']*0.02)?'c-green':'c-red'?>"><?=Sys::NFormat($saldo)?></td>
</tr>
<?php 
	}
?>
</table>
</form>
</div>
<script>
$('.<?=$prefix?>-tr')
.hover(
	function (e) { $(this).addClass('grid-row-over').addClass('grid-row-over-bg').prev().addClass('grid-row-over-before'); }, 
	function (e) { $(this).removeClass('grid-row-over').removeClass('grid-row-over-bg').prev().removeClass('grid-row-over-before'); }
);
function showchangesdwindow() {
	var w = new SecFunDepenWindow({});
	w.show();
};
function imprimir() {
	var r = confirm("Antes de imprimir el reporte, es necesario que configure la \npagina en formato horizontal. \nDesea continuar?");
	if (r) {
		window.print();
	}
};
// forms
SecFunDepenWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_secfundepen_window', title: 'Dependencia / Proyecto', width: 700, height: 500, modal: true, autoScroll: false,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('secfundepen.list.php', '', 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		SecFunDepenWindow.superclass.initComponent.call(this);
	}
});
</script>
</body>
</html>