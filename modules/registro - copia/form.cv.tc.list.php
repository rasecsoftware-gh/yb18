<?php
	require_once("../../sys.php");
	$module = "registro";
	$prefix = "{$module}_cv";
	$tipope_id = Sys::GetR('tipope_id','');
	$focus = Sys::GetR('focus','');

	$qe = new PgQuery("
	SELECT tc.tipocambio_id, tc.moneda_id_de, tc.moneda_id_a, tc.tipocambio_factor, tc.tipocambio_operador,
	mde.moneda_desc as moneda_desc_de,
	ma.moneda_desc as moneda_desc_a,
	mde.moneda_simbolo as moneda_simbolo_de,
	ma.moneda_simbolo as moneda_simbolo_a
	FROM public.tipocambio tc
	JOIN public.moneda mde on mde.moneda_id = tc.moneda_id_de
	JOIN public.moneda ma on ma.moneda_id = tc.moneda_id_a
	WHERE tc.tipope_id = '$tipope_id'  
	ORDER BY tc.tipocambio_id
	", NULL, true, false);
	$tc_selected = array();
	if ($qe->recordCount>0) {
		$tc_selected = $qe->row;
	}
?>
<style>
.<?=$prefix?>-tc {
	font-family: "Lucida Console";
	font-size: 15pt;
}
.<?=$prefix?>-tc * {
	font-family: "Lucida Console";
}
</style>
    <select class="<?=$prefix?>-tc bold" id="<?=$prefix?>_tipocambio_id" name="tipocambio_id" style="width: 500px; height: 35px;"> 
<?php
while ($re = $qe->Read()):?>
<?php
		if ($tipope_id=='03'):?>
		<option value="<?=$re['tipocambio_id']?>" rdata="<?=rawurlencode($qe->GetRowAsJson())?>"><?=Sys::ToHtml(str_pad($re['moneda_desc_de'], 17, " ", STR_PAD_RIGHT))."&rArr;&nbsp;&nbsp;".$re['moneda_desc_a']?></option>
<?php
		else: 
?>
		<option value="<?=$re['tipocambio_id']?>" rdata="<?=rawurlencode($qe->GetRowAsJson())?>"><?=Sys::ToHtml(str_pad($re['moneda_desc_a'], 17, " ", STR_PAD_RIGHT))."&rArr;&nbsp;&nbsp;".$re['moneda_desc_de']?></option>
<?php
		endif;?>		
<?php
endwhile; 
?>
	</select>
<script>	
	var tcdata = <?=json_encode($tc_selected)?>;
	// controls
	$('#<?=$prefix?>_tipocambio_id').change(function () {
		eval("tcdata = "+unescape( $($(this).children()[$(this).get(0).selectedIndex]).attr('rdata')));
		//alert(tcdata);
		<?=$prefix?>_set_tc_values(tcdata);
		//alert('set values');
		$('#<?=$prefix?>_registro_det_importe_de').focus().select();
	}).keypress(function (e) {
		//console.info(e);
		if (e.keyCode == 13) {
			<?=$prefix?>_set_tc_values(tcdata);
			$('#<?=$prefix?>_registro_det_importe_de').focus().select();
		} else if (e.keyCode == 9) {
			<?=$prefix?>_set_tc_values(tcdata);
		}
	});
	// init
	<?=$prefix?>_set_tc_values(tcdata);
<?php
	if ($focus == '1'):?>
	$('#<?=$prefix?>_tipocambio_id').focus();
<?php
	endif; 
?>
</script>