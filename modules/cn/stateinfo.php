<?php
	require_once '../../sys.php';
	$module = 'cn';
	$prefix = "{$module}_stateinfo";
	$cuames = Sys::GetR("id", '');
	$tipo = Sys::GetS("{$module}tipo", '');
	// vars conditions
	$anipre = Sys::GetPeriodo();
	// generado
	$qgen = new SqlQuery("
	SELECT 
	cm.c_cuames, cm.c_anipre, cm.n_cuames_desc,
	CONVERT(VARCHAR, cm.d_cuames_fecha, 103) AS d_cuames_fecha, f_cuames_esta, usuario 
	FROM dbo.cuadro_mensual cm
	WHERE cm.c_anipre = '$anipre' AND cm.c_cuames = '$cuames'
	", NULL, true, true);
	// cotizandose
	$qcot = new SqlQuery("
	SELECT 
	bp.c_provee,
	p.n_provee_desc
	FROM dbo.buena_procuames bp
	JOIN dbo.provee p ON p.c_provee = bp.c_provee
	WHERE bp.c_anipre = '$anipre' AND bp.c_cuames = '$cuames'
	GROUP BY bp.c_provee, p.n_provee_desc
	", NULL, false, true);
	$qcot->cursorType = 'static';
	$qcot->Execute();
	// adjudicado
	$qadj = new SqlQuery("
	SELECT 
	bp.c_provee,
	p.n_provee_desc, 
	SUM(q_buepro_padj*q_buepro_cadj) as total
	FROM dbo.buena_procuames bp
	JOIN dbo.provee p ON p.c_provee = bp.c_provee
	WHERE bp.c_anipre = '$anipre' AND bp.c_cuames = '$cuames'
	AND bp.f_proveeganador = '1'
	GROUP BY bp.c_provee, p.n_provee_desc
	", NULL, false, true);
	$qadj->cursorType = 'static';
	$qadj->Execute();
	// comprometido
	$qcom = new SqlQuery("
	SELECT 
	ord.c_orden,
	ord.c_orden_cod,
	CONVERT(VARCHAR, ord.d_orden_fecha, 103) AS d_orden_fecha,
	ord.c_provee,
	ord.c_siamreg,
	ord.f_orden_esta,
	p.n_provee_desc,
	SUM(q_detord_cant*q_detord_cost) as total
	FROM dbo.orden ord
	JOIN dbo.orden_detall od ON od.c_anipre = ord.c_anipre AND od.c_orden_cod = ord.c_orden_cod AND od.c_orden = ord.c_orden
	JOIN dbo.provee p ON p.c_provee = ord.c_provee
	WHERE ord.c_anipre = '$anipre' AND ord.c_cuames = '$cuames'
	GROUP BY 
	ord.c_orden,
	ord.c_orden_cod,
	ord.d_orden_fecha,
	ord.c_provee,
	ord.c_siamreg,
	ord.f_orden_esta,
	p.n_provee_desc
	ORDER BY
	ord.d_orden_fecha DESC,	ord.c_orden_cod DESC, ord.c_orden
	", NULL, false, true);
	$qcom->cursorType = 'static';
	$qcom->Execute();
	// anulado
	
?>
<style>
.<?=$prefix?>-anulado td {
	color: red!important;
}
</style>
<div class="pd" style="position: relative; margin-top: 5px;">
	<span class="c-silver fs-14 bold">00</span><span class="c-black fs-14 bold"><?=intval($qgen->row['c_cuames'], 10)?></span>
	&nbsp;&nbsp;<span class="c-gray fs-10"><?=$qgen->row['n_cuames_desc']?></span>
</div>
<div style="position: relative;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold">Generado</td>
</tr>
<?php
	while ($r = $qgen->Read()) {
?>
<tr align="left" valign="top">
	<td class="pd">
	Cuadro Mensual generado el <?=$r['d_cuames_fecha']?> por <?=$r['usuario']?>.
	</td>
</tr>
<?php 
	}
?>
</table>
</div>
<?php
	if ($qcot->recordCount > 0):?>
<div style="position: relative; margin-top: 5px;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="2">Cotizandose</td>
</tr>
<?php
	while ($r = $qcot->Read()) {
?>
<tr align="left" valign="top">
	<td class="pd l" width="100">
	<?=$r['c_provee']?>
	</td>
	<td class="pd l">
	<?=$r['n_provee_desc']?>
	</td>
</tr>
<?php 
	}
?>
</table>
</div>
<?php
	endif;?>
<?php
	if ($qadj->recordCount > 0):?>
<div style="position: relative; margin-top: 5px;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="3">Adjudicado</td>
</tr>
<?php
	while ($r = $qadj->Read()) {
?>
<tr align="left" valign="top">
	<td class="pd l" width="100">
	<?=$r['c_provee']?>
	</td>
	<td class="pd l">
	<?=$r['n_provee_desc']?>
	</td>
	<td class="pd r">
	<?=Sys::NFormat($r['total'])?>
	</td>
</tr>
<?php 
	}
?>
</table>
</div>
<?php
	endif;?>
<?php
	if ($qcom->recordCount > 0):?>
<div style="position: relative; margin-top: 5px;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="7">Comprometido</td>
</tr>
<tr class="<?=$r['f_orden_esta']=='99'?"$prefix-anulado":''?>" align="left" valign="top">
	<td class="pd c fs-6 c-silver">
	TIPO
	</td>
	<td class="pd c fs-6 c-silver">
	N&ordm; ORD.
	</td>
	<td class="pd c fs-6 c-silver">
	FECHA
	</td>
	<td class="pd c fs-6 c-silver">
	REG.SIAM
	</td>
	<td class="pd c fs-6 c-silver">
	RUC
	</td>
	<td class="pd l fs-6 c-silver">
	PROVEEDOR
	</td>
	<td class="pd r fs-6 c-silver">
	TOTAL
	</td>
</tr>
<?php
	while ($r = $qcom->Read()) {
?>
<tr class="<?=$r['f_orden_esta']=='99'?"$prefix-anulado":''?>" align="left" valign="top">
	<td class="pd c">
	<?=$r['c_orden_cod']?>
	</td>
	<td class="pd c">
	<?=$r['c_orden']?>
	</td>
	<td class="pd c">
	<?=$r['d_orden_fecha']?>
	</td>
	<td class="pd c">
	<?=$r['c_siamreg']?>
	</td>
	<td class="pd c">
	<?=$r['c_provee']?>
	</td>
	<td class="pd l">
	<?=$r['n_provee_desc']?>
	</td>
	<td class="pd r">
	<?=Sys::NFormat($r['total'])?>
	</td>
</tr>
<?php 
	}
?>
</table>
</div>
<?php
	endif;?>