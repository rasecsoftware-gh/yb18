<?php 
	require_once '../../sys.php';
	$module = 'registro';
	$prefix = "{$module}";
?>
<script>
	// remove and save title :p
	var title = Ext.getCmp('<?=$prefix?>_tab').title;
	Ext.getCmp('maintab').remove(Ext.getCmp('<?=$prefix?>_tab'), true);
	// reloading!
	Ext.getCmp('maintab').add({
        id: '<?=$prefix?>_tab', title: title, 
        bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', padding: 2, closable: true, autoScroll: true, 
        layout: 'border',
        items: [{
	        region: 'west', xtype: 'panel', border: false,
	        //collapsible: true,
	        split: true,
	        width: 250,
	        layout: 'border',
	        bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg',
	    	items: [{
	    	    region: 'north', xtype: 'panel',
	    	    split: true, height: 260,
	    	    title: 'Operaciones',
	    	    bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', 
	    	    listeners: {
                    'render': function (s) {
                        $('#'+s.body.dom.id).load('modules/<?=$module?>/sidemenu.php');
                    }
                }
	    	},{
                region: 'center', xtype: 'panel',
                title: 'Tipo de Cambio',
                bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', 
                html: '<div id="<?=$prefix?>_tipocambio_container" class=""></div>',
                tools: [{
                    type:'refresh',
                    qtip: 'Actualizar',
                    // hidden:true,
                    handler: function(event, toolEl, panel){
                        <?=$prefix?>_tipocambio_reload_list();
                    }
                }],
                listeners: {
                    'afterrender': function (s) {
                        $('#<?=$prefix?>_tipocambio_container').load('modules/<?=$module?>/tipocambio.list.php');
                    }
                }
            }]
	    },{
	    	region: 'center', xtype: 'panel', layout: 'border', border: false,
	    	bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg',
			items: [{
		    	region: 'center', xtype: 'panel', 
				border: true, autoScroll: true,
				bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', 
				html: '<div id="<?=$prefix?>_container" class=""></div>',
				listeners: {
		        	'afterrender': function (s) {
		        		$('#<?=$prefix?>_container').load('modules/<?=$module?>/list.php');
		    		}
		    	}
		    },{
		        region: 'east', xtype: 'panel', border: false, split: true, margins: '2px 2px 2px 0',
		        bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg',
		        layout: 'border', width: 400,
		    	items: [{
			        region: 'north', xtype: 'panel', border: true, height: 300, split: true, autoScroll: true,
			        title: 'Informacion de Operacion',
			        html: '<div id="<?=$prefix?>_info_container" class=""></div>',
			       bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', 
			        listeners: {
			        	'afterrender': function (s) {
			        		//$('#'+s.body.dom.id).load('modules/<?=$module?>/sidemenu.php');
			    		}
			    	}
			    },{
			        region: 'center', xtype: 'panel', border: true, autoScroll: true,
			        title: 'Detalles de Operacion',
			        bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', 
			        html: '<div id="<?=$prefix?>_det_container" class=""></div>',
			        tools: [{
                        type:'refresh',
                        qtip: 'Actualizar',
                        // hidden:true,
                        handler: function(event, toolEl, panel){
                            <?=$prefix?>_det_reload_list();
                        }
                    }],
			        listeners: {
			        	'afterrender': function (s) {
			        		//$('#'+s.body.dom.id).load('modules/<?=$module?>/sidemenu.php');
			    		}
			    	}
			    }]
		    }]
	    }]
    });
	Ext.getCmp('maintab').doLayout(true, true);
	Ext.getCmp('maintab').setActiveTab('<?=$prefix?>_tab');
</script>