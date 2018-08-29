<?php
	require_once("../../sys.php");
	$module = "registro";
	$prefix = "{$module}_transferencia";
	// default values
	$tipope_id = '05';
	$pv_id = Sys::GetR('pventa_id', '');
?>
<?php
$qe = new PgQuery("
    select 
        r2.registro_id,
        r2.registro_fecha::date as registro_fecha,
        r2.pventa_id,
        pv.pventa_desc,
        e.establecimiento_desc,
        (
            SELECT m.moneda_simbolo 
            FROM public.registro_det d
            JOIN public.moneda m ON m.moneda_id = d.moneda_id 
            WHERE d.registro_id = r2.registro_id
            LIMIT 1
        ) as moneda_simbolo,
        (SELECT SUM(abs(d.registro_det_importe)) FROM public.registro_det d where d.registro_id = r2.registro_id
        ) as total
    from public.registro r
    join public.registro r2 ON -- registro origen con retorno
        r2.registro_id = r.registro_id_parent -- tenga com padre un registro local 
        and r2.registro_retornar = '1' -- el padre te marcado con retorno 
        and r2.registro_estado <> 'N' -- no este ANULADO
        and r2.tipope_id = '05' -- tipo transferencia
        and r2.pventa_id = '$pv_id'
        and NOT EXISTS( -- el registro no tiene retornos registrados
            SELECT * 
            FROM public.registro r3 
            WHERE r3.registro_id_main = r2.registro_id
            AND r3.registro_estado <> 'N'
        )
    join public.pventa pv ON pv.pventa_id = r2.pventa_id
    join public.establecimiento e on e.establecimiento_id = pv.establecimiento_id
    where r.registro_estado = 'T' -- registro aceptado
    AND r.pventa_id = '".Sys::GetUserPVentaId()."'  
    order by r.registro_id", NULL, true, false);?>
    <select id="<?=$prefix?>_regitro_id_retornar" name="registro_id_retornar">
        <option value="">- nadie -</option>
<?php
while ($re = $qe->Read()):?>
        <option value="<?=$re['registro_id']?>"><?=$re['establecimiento_desc']?> - <?=$re['pventa_desc']?>: <?=$re['registro_id']?>. <?=$re['registro_fecha']?>. <?=$re['moneda_simbolo']?> <?=Sys::NFormat($re['total'])?></option>
<?php
endwhile; 
?>
    </select>
<script>	
	// init
	//$('#<?=$prefix?>_pventa_id_destino').focus();
</script>