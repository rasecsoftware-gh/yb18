<?php
	require_once '../../sys.php';
	$module = "sys_sql";
	$prefix = "{$module}";
    
	// params
	$action = Sys::GetP('action', '');
	$sql = Sys::GetP('sql', '');
    if (get_magic_quotes_gpc()) {
        $sql = stripslashes($sql);
    }
?>
<form id="<?=$prefix?>_frm" onsubmit="return false;">
    <input type="hidden" name="action" value="execute"/>
    <div style="padding: 10px; height: 30px;">
    	<span class="bold c-gray fs-12" style="display: block; float: left; margin-right: 20px; line-height: 16px;">SQL Editor</span>
    	<span style="float: left;">
    		<button type="button" onclick="<?=$prefix?>_execute();">Execute</a>
    	</span>
    </div>
    <hr/>
    <div class="pd-5">
        <textarea id="<?=$prefix?>_sql" name="sql" style="width: 100%; height: 300px;"><?=$sql?></textarea>
    </div>
</form>
<script>
function <?=$prefix?>_execute(id) {
	var params = $('#<?=$prefix?>_frm').serialize();
	$.post('modules/<?=$module?>/list.php', params, function (data) {
	    $('#<?=$prefix?>_container').html(data); 
	});
};
</script>
<?php
if ($action == 'execute'):
        $cn = Sys::GetConnection();
        $q = @pg_query($cn, $sql);
        if ($q!==false): 
?>
<div class="pd-5 c-black bold">Query OK. 
<?php       if (pg_affected_rows($q)>0):?>
<?=pg_affected_rows($q)." rows affected."?>    
<?php       endif;?>   
<?php       if (pg_num_rows($q)!=-1 && pg_num_fields($q)>0):?>
<?=pg_num_rows($q)." rows returned."?>    
<?php       endif;?>
</div>
<div class="pd-5">
<?php       if (pg_num_rows($q)!=-1 && pg_num_fields($q)>0):?>
<?php           $qs = new PgQuery($sql, $cn, true, true); 
?>
    <table class="grid">
    <tr>
<?php           foreach($qs->schema['columns'] as $name=>$c):?>
        <td class="cell-head bold"><?=$name?></td>
<?php           endforeach;?>
    </tr>                
<?php           while ($r = $qs->Read()): 
?>
    <tr>
<?php               foreach($qs->schema['columns'] as $name=>$c):?>
        <td class="cell"><?=$r[$name]?></td>
<?php               endforeach;?>                    
    </tr>
<?php           endwhile;?>
    </table>
<?php       else: // non rows?>
    <div class="c-gray pd-5"><?=pg_last_notice($cn)?></div>
<?php       endif; // rows exists?>
<?php   else: // error query?>
    <div class="c-red pd-5"><?=pg_last_error($cn)?></div> 
<?php   endif; // query error?>
<?php
endif;?>
</div>
