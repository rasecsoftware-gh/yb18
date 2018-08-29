<?php
	require '../../sys.php'; 
?>
<div class="" style="position: relative; background: #C7E5EE; padding: 0; height: 30px;">
	<div class="c" style="position: absolute; width: 120px; height: 30px; left: 0; top: 0px; padding: 5px 5px; background: #00668e;">
	   <span class="c-white fs-13 bold">Yagobank</span> <span class="c-white fs-10">v2</span>
	</div>
	<?php include 'menu.php'; 
?>

	<div class="" style="width: auto; display: block; position: absolute; right: 10px; top: 0px; height: 30px;" align="right">
		<table cellpadding="0" cellspacing="0" border="0">
		<tr valign="middle">
		    <td class="l pd-2" height="30">
<?php
    $pv_desc = PgQuery::GetQueryVal("
    SELECT e.establecimiento_desc||' - '||p.pventa_desc 
    FROM public.pventa p 
    JOIN public.establecimiento e ON e.establecimiento_id = p.establecimiento_id
    WHERE p.pventa_id = '".Sys::GetUserPVentaId()."'", '', NULL, true, true);
?>  
            <span class="fs-7"><?=$pv_desc?></span>         
            </td>
			<td height="30"><img src="img/bullet_green.png" align="right"/></td>
			<td height="30"><span class="fs-10 pointer bold" style="color: black; line-height: 13px;" onclick="show_quickform_usuario();" title="Cambiar contrase�a"><?=Sys::GetUserName('user')?></span></td>
			<td height="30"><a class="fs-8 block bold" href="login.php?logout=1" style="color: #004954;margin: 1px 2px 0 5px; padding: 1px;" title="cerrar sesion">X</a></td>
		</tr>
		</table>
		
	</div>
</div>
