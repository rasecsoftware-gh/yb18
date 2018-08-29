<?php
    require_once '../../sys.php';
    $module = "registro";
    $prefix = "{$module}_tipocambio";
    
    $now = date('d/m/Y H:i:s');
    
?>
<style>
.<?=$prefix?>-title {
    padding: 5px 10px 5px 5px;
    display: block;
    color: black;
    margin: 0 0 5px 0; 
}
.<?=$prefix?>-factor {
	color: black;
	font-weight: bold;
	font-size: 13pt;
}
.<?=$prefix?>-factor:hover {
	text-decoration: none;
}
</style>
<div class="<?=$prefix?>-title"> Al <?=$now?></div>
<form id="<?=$prefix?>_frm_list" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
    <td class="cell-head bold">Tipo Cambio</td>
    <td class="cell-head bold">Compra</td>
    <td class="cell-head bold">Venta</td>
</tr>
<?php
    $qp = new PgQuery("
    SELECT
      tc.*,
      COALESCE((
          SELECT tcf.tipocambio_factor
          FROM public.tipocambio tcf
          WHERE 
          tcf.tipope_id = '03' 
          AND tcf.moneda_id_de = moneda_id_1
          AND tcf.moneda_id_a = moneda_id_2
          LIMIT 1
      ), 0) as factor_c,
      COALESCE((
          SELECT tcf.tipocambio_factor
          FROM public.tipocambio tcf
          WHERE 
          tcf.tipope_id = '04' 
          AND tcf.moneda_id_de = moneda_id_2
          AND tcf.moneda_id_a = moneda_id_1
          LIMIT 1
      ), 0) as factor_v,
	  (
          SELECT tcf.tipocambio_id
          FROM public.tipocambio tcf
          WHERE 
          tcf.tipope_id = '03' 
          AND tcf.moneda_id_de = moneda_id_1
          AND tcf.moneda_id_a = moneda_id_2
          LIMIT 1
      ) as tipocambio_id1,
	  (
          SELECT tcf.tipocambio_id
          FROM public.tipocambio tcf
          WHERE 
          tcf.tipope_id = '04' 
          AND tcf.moneda_id_de = moneda_id_2
          AND tcf.moneda_id_a = moneda_id_1
          LIMIT 1
      ) as tipocambio_id2
    FROM (
      SELECT DISTINCT
      -- tc.establecimiento_id,
      (CASE WHEN tc.tipope_id = '03' THEN m1.moneda_id ELSE m2.moneda_id END) as moneda_id_1,
      (CASE WHEN tc.tipope_id = '04' THEN m1.moneda_id ELSE m2.moneda_id END) as moneda_id_2,
      (CASE WHEN tc.tipope_id = '03' THEN m1.moneda_simbolo ELSE m2.moneda_simbolo END) as moneda_desc_1,
      (CASE WHEN tc.tipope_id = '04' THEN m1.moneda_simbolo ELSE m2.moneda_simbolo END) as moneda_desc_2
      FROM public.tipocambio tc
      JOIN public.moneda m1 On m1.moneda_id = tc.moneda_id_de 
      JOIN public.moneda m2 On m2.moneda_id = tc.moneda_id_a
      -- WHERE tc.establecimiento_id = '".Sys::GetUserEstablecimientoId()."'
    ) tc
    ORDER BY moneda_id_1", NULL, true, true);
    while ($d = $qp->Read()) {
        $tcdesc = $d['moneda_desc_1']." &rArr; ".$d['moneda_desc_2'];
?>
<tr align="left" valign="top">
    <td class="cell l"><?=$tcdesc?></td>
    <td class="cell r"><a class="<?=$prefix?>-factor" href="javascript:void(0)" rid="<?=$d['tipocambio_id1']?>"><?=Sys::NFormat(($d['factor_c']), 3, ',')?></a></td>
    <td class="cell r"><a class="<?=$prefix?>-factor" href="javascript:void(0)" rid="<?=$d['tipocambio_id2']?>"><?=Sys::NFormat(($d['factor_v']), 3, ',')?></a></td>
</tr> 
<?php
    } 
?>
</table>
</form>
<script>
$('.<?=$prefix?>-factor').click(function(e) {
	var factor = prompt('Modificar Cotizacion', parseFloat($(this).html()));
	if (factor != null) {
		var params = $.param({
		'action': 'UpdateTipoCambio',
		'tipocambio_id': $(this).attr('rid'),
		'tipocambio_factor': factor
		});
		$.post('modules/<?=$module?>/core.php', params, function (data) {
			if ($.trim(data) == 'ok') {
				sys.message('La cotizacion se ha modificado.');
				<?=$prefix?>_reload_list();
			} else {
				sys.alert(data);
			}
		});
	}
});
// functions
// data functions
// Forms
function <?=$prefix?>_reload_list() {
    $('#<?=$prefix?>_container').load('modules/<?=$module?>/tipocambio.list.php');
};
</script>