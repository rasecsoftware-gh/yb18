// JavaScript Document
if (typeof gds == 'undefined') gds = {};
if (typeof gds.ext == 'undefined') gds.ext = {};

gds.ext.SearchWindow = function (config) {
	gds.ext.SearchWindow.superclass.constructor.call(this, config);
};

Ext.extend(gds.ext.SearchWindow, Ext.Window, {
	title:'Buscar', modal: true, layout: 'border', height: 400, width: 390,
	addConfirm: true,
	initComponent: function() {
		// call superclass initComponent
		var sw = this;
		this.searchTextField = new Ext.form.TextField({
			x: 10, y: 35, width: 300, enableKeyEvents: true,
			listeners: {
				'keyup': { 
					fn: function (s, e) { // key press en IE no trapa el back <-
						if (e.getKey() == e.ENTER) {
							if (s._old_value == s.getValue() || this.mode == 'local') {
								var rows = this.grid.getSelectionModel().getSelections();
								if (rows.length > 0) {							
									if (this.onSelected) {
										var row = rows[0];
										this.onSelected(this, row);
										this.close();
										return;
									}
								}
							}
							if (this.mode == 'remote') {
								this.remoteQuery();
							}
							s._old_value = s.getValue();
						} else if (e.getKey() == e.UP) {
							this.grid.getSelectionModel().selectPrevious();
						} else if (e.getKey() == e.DOWN) {
							this.grid.getSelectionModel().selectNext();
						} else if (this.mode == 'local') {
							this.localQuery.defer(100, this);
						}
						s.focus(false, 100);
					}, scope: this
				}
			}
		});
		this.searchAddButton = new Ext.Button({
			x: 315, y: 35, text: 'Añadir',
			listeners: {
				'click': function (s, e) {
					if(sw.onAddClick) {
						var success = true;
						if (sw.addConfirm==true) {
							success = confirm("Realmente desea guardar?");
						}
						if(success) {
							sw.onAddClick(sw, sw.searchTextField.getValue());
						}
					}
				}
			}
		});
		this.resultLabel = new Ext.form.Label({
			text:'0 registro(s) encontrado(s)', style: {color: '#416AA3', fontFamily:'tahoma,arial,helvetica,sans-serif'},
			setCount: function (v) {
				this.el.dom.innerHTML = this.text.replace('0', '<b>'+v+'</b>');
			}
		});
		this.optionsPanel = new Ext.Panel({
			layout: 'absolute', region: 'north', height: 65, border: true, 
			items: [{
				xtype: 'label', x:10, y:10, text: 'Criterio'
			}]
		});
		this.contentPanel = new Ext.Panel({region: 'center', border: false});
		this.items = [this.optionsPanel, this.contentPanel];
		
		gds.ext.SearchWindow.superclass.initComponent.call(this);
		this.loadConfig(this.searchConfig);
		this.on('show', function () {
			this.searchTextField.focus.defer(500, this.searchTextField);
			this.loadLocalData();
		});
		this.on('show', function () {
			this.searchTextField.focus.defer(500, this.searchTextField);
			this.loadLocalData();
		});
		this.on('close', function () {
			if (this.store) {
				this.store.clearFilter(true);
			}
		});
	},
	loadConfig: function (cfg) {
		if (typeof cfg == 'undefined') return;
//		{	url: '', action:'Search', root: 'rows', mode: 'remote/local', registerStoreId: 'store_name',
//			columns: [
//			{name: 'id'}, // for store only
//			{name: 'codigo', header: 'Codigo', width: 50, search: true, searchLabel:'Por Codigo'}, // for store and grid
//			{name: 'descripcion', header: 'Descripcion', width: 50} // for store and grid (depend of header)
//		]}
		this.requestUrl = cfg.url;
		this.requestAction = cfg.action;
		this.params = cfg.params;
		this.mode = cfg.mode||'remote';
		// config store
		var fields = []; 
		var columns = []; 
		var searchArray = [];
		var defaultSearchIndex = 0;
		for (var i in cfg.columns) {
			var cc = cfg.columns[i];
			if (typeof cc == 'object') {
				fields.push(cc.name);
				cc.search = (typeof cc.search == 'boolean')?cc.search:true;
				if (typeof cc.header == 'string') {
					var c = {};
					c.dataIndex = cc.name;
					c.header = cc.header;
					c.width = cc.width||100;
					c.sortable = cc.sortable||true;
					columns.push(c);
				}
				if (cc.search === true) {
					var sitem = new Array(cc.searchBy||cc.name, cc.searchLabel||cc.header);
					if (cc.defaultItem === true) {
						defaultSearchIndex = searchArray.length;
					}
					searchArray.push(sitem);
				}
			}
		}
		this.searchByCombo = new Ext.form.ComboBox({
			x: 87, y: 10, width: 223, 
			typeAhead: true, triggerAction: 'all', editable: true, emptyText:'Seleccione...', selectOnFocus:true,
			forceSelection: true, mode: 'local', disabled: (searchArray.length == 0),
			store: searchArray
		});
		if (searchArray.length > 0) {
			this.searchByCombo.setValue(searchArray[defaultSearchIndex][0]);
		}
		this.optionsPanel.add(this.searchByCombo);
		this.optionsPanel.add(this.searchTextField);
		if (this.onAddClick) {
			this.optionsPanel.add(this.searchAddButton);
		}
		if (this.optionsPanel.rendered) {
			this.optionsPanel.doLayout();
		}
		var storeCfg = {
			url: this.requestUrl,
			fields: fields,
			listeners: {
				'beforeload': function(s, opt) {
					opt.params = Ext.apply({
						'action': this.requestAction,
						'by': this.searchByCombo.getValue(),
						'filter': this.searchTextField.getValue()
					}, this.params);
				}.createDelegate(this),
				'load': function(s, records, opt) {
					this.resultLabel.setCount(records.length);
					if (records.length > 0) {
						s.loaded = true;
						this.grid.getSelectionModel().selectFirstRow();
						//this.grid.focus(false, 200);
					}
				}.createDelegate(this)
			}
		};
		var storeId = 'no_existe_este_store_:)';
		if (typeof(cfg.registerStoreId) == 'string') {
			storeCfg.id = cfg.registerStoreId;
			storeId = cfg.registerStoreId;
		}
		if (cfg.root) {
			storeCfg.root = cfg.root;
		}
		if (Ext.StoreMgr.containsKey(storeId)) {
			this.store = Ext.StoreMgr.lookup(cfg.registerStoreId);
		} else {
			this.store = new Ext.data.JsonStore(storeCfg);
		}
		this.grid = new Ext.grid.GridPanel({ // grid
			region: 'center', border: false, enableHdMenu: false, autoScroll: true,
			columns: columns,
			store: this.store,
			stripeRows: true, 
			loadMask: true, sm: new Ext.grid.RowSelectionModel({singleSelect: true}),
			tbar:[{
				text:'Buscar', tooltip:'Ejecutar la busqueda', iconCls:'search-icon16', tabIndex: -1,
				handler: function (b) {	
					if (this.mode == 'local') {
						this.localQuery();
					} else {
						this.remoteQuery();
					}
				}.createDelegate(this)
			},'-',this.resultLabel],
			listeners: {
				'rowdblclick': {
					fn: function(g, rindex, ev) {
						if (this.onSelected) {
							var row = this.grid.store.getAt(rindex);
							this.onSelected(this, row);
							this.close();
						}
					}, scope: this
				},
				'keypress': {
					fn: function(ev) {
						if (ev.getKey() != ev.ENTER) return;
						var rows = this.grid.getSelectionModel().getSelections();
						if (rows.length > 0) {							
							if (this.onSelected) {
								var row = rows[0];
								this.onSelected(this, row);
								this.close();
							}
						}
					}, scope: this
				}
			}
		});
		this.contentPanel.add(this.grid);
		if (this.contentPanel.rendered) {
			this.contentPanel.setLayout('border');
			this.contentPanel.doLayout();
		} else {
			this.contentPanel.layout = 'border';
		}
		//this.loadLocalData();
	},
	remoteQuery: function () {
		this.store.reload();
	},
	loadLocalData: function () {
		if (this.mode != 'local') return;
		if (this.store.loaded !== true) {
			this.store.load();
		}
	},
	reload: function () {
		this.store.reload();
	},
	localQuery: function (focus) {
		if (this.grid) {
			var by = this.searchByCombo.getValue();
			var v = this.searchTextField.getValue();
			this.grid.store.filter(by, v, true, false);
			this.resultLabel.setCount(this.grid.store.getCount());
			if (this.grid.store.getCount() > 0) {
				if (focus===true) {
					//this.grid.focus();
				}
				this.grid.getSelectionModel().selectFirstRow();
			}
		}
	},
	// overridable method
	onSelected: function (sender, record) { alert(record);},
	listeners: {
		
	}
});