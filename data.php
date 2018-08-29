<?php
/**
 * version: 0.99999 // casi casi estable :p
 * ultimas modificaciones:
 *
 * - 29.03.2011 02:46.- se agrego el metodo statico ExecuteNonQuery(sql);
 * - 30.01.2011 13.17.- se agrego el parametro $ignoreType, para no restringir el tipo de dato de la columna
 * 	 public function GetNextValue($column, $wheres = array(), $ignoreType=false) {...
 * - 13.10.2010 11:41.- se agrego la ppropiedad decode en PgDataRow
 * - 28.09.2010 11:33.- conexiones pesistentes. se creo un parametro mas en Config::$DbPersistent para indicar
 *   si se va usar persistencia, luego se verifico que este tipo de conexion toma cualquier conexion activa 
 *   creada anteriormente, con el inconveniente que no se volvian a establecer los parametros dateStyle ni encode,
 *   por lo tanto no se mostraba bien la informacion, p.e. las tildes, �, y fechas. etc..
 *   asi que se hizo un metodo PgQuery::PersistentProcess, el cual realiza estas configuraciones 
 *   de fecha, condificacion y otros, el cual es llamado en cada ejecucion de una consulta. solo asi funciono. o.O
 * - 16.08.2010 9.59.- se implemento una forma de prevenir que se inserten valores a los campos seriales 
 *   que ya existen es decir, si no se ah actualizado el current val de un campo serial, se hace esto 
 *   automanticamente al momento de crear un Datarow. 
 *   private function CheckSerialColumn(), y se usa en 
 *   public function Create($generateAutoInc = true, $checkSerialColumn = true)
 * - 29.05.2010 8.39.- se arreglo un bug en PgDataRow.SetPkey method, 
 *   no realizaba correctamente la busque del $pkname en el schema['pkey']... o.O
 * - 27.05.2010 15.42.- se agrego la funcion de salida PgQuery.ToStringArray() 
 *   para obtener la lista de filas como un array JS, p.e.: "[[1, 'campo2'],[2, 'campo3']]"
 * - 24.05.2010 9.49.- se le agrego el parametro $default en el metodo PgDataRow.GetMax($default)
 * - 12.05.2010 11:17.- se agrego el parametro PgDataRow->Create($generateAutoInc = true), 
 *   para generar o no el valor de un campo serial.
 * - 10.05.2010 11.25.- se corrigio errores con multiples conexiones en PgQuery y PgDataRow y PgProvider
 * - 25.03.2010 09:43.- se agrego el parametro 'decode' (utf8_decode) en el constructor del PgQuery
 *   function __construct($sql = "", $connection = NULL, $autoExecute = false, $decode=false) { ...
 * - 29.01.2010 13:48.- se agrego el type 'bit' en PgProvider::GetDbType, 
 *   tiene un tratameinto especial en actualizacion campo_bit = 1::bit  (= 1 no funka!)
 * - 28.01.2010 15:56.- se agrego un parametro mas al constructor del PgQuery, $autoExecute
 *   function __construct($sql = "", $connection = NULL, $autoExecute = false) { ...
 * - 28.01.2010 15:39.- se agrego un parametro a la funcion PgQuery::GetQueryValue($default=NULL), 
 *   un valor por defecto si NULL
 * - 25.01.2010 10:01.- se agrego la function PgProvider::GetDefaultValues y PgProvider:GetDefaultValue, 
 *   para obtener los valores por defecto de los campos de una tabla y un solo campo respectivamente. XD
 * - 21.01.2010 18:06.- se agrego la function PGQuery->GetRowArray() para obtener el row como un array
 *   simple, no asociativo jojo
 * - 20.01.2010 15:51: se agrego PgQuery->decode para decodificar utf8 en el GetRow() osea 'row' property
 * - 27.11.2009 13:52: tambien se agrego debugLevel al PgDataRow, los niveles son 1, 2, 3
 * 	 dichos niveles muestran el detalle del debug
 * - 27.11.2009 11:12: se corrigio un error grave! muy grave..! en PGDataRow.UpdateChildRows o.O
 *   se cambio la inicializacion de la variable $exists, dentro del bucle de los DBRows
 * 	 antes taba fuera de ese bucle, y solo funkaba si los registro taban ordenados asc.
 *   tons nunka los encontraba y consideraba que tos eran nuevos.. y procedia a inserta de hacha..Error ps.!!!
 * - 25.11.2009 14:20: se agrego mas funcionalidades que ahorita no recuerdo XD..
 * 	 ah.. ya recorde una.. uhmm.. sip.. en el metodo PgDataRow.GetNextValue()
 */
 
if (!defined("_DATA_")) {
 	define("_DATA_", "data.php");
	class PgProvider {
		public static $DbConnection = NULL;
		public static function GetConnection() {
			return self::$DbConnection;
		}
		public static function CheckDbServices() {
			return function_exists('pg_connect')?true:false;
		}
		public static function GetDbType($providerType) {
			switch ($providerType) {
				case "bpchar": return 'string'; 
				case "varchar": return 'string'; 
				case "text": return 'string'; 
				case "int2": return 'int'; 
				case "int4": return 'int'; 
				case "int8": return 'int'; 
				case "bit": return 'bit';
				case "bool": return 'boolean'; 
				case "float4": return 'float'; 
				case "float8": return 'float'; 
				case "numeric": return 'float'; 
				case "date": return 'datetime'; 
				case "time": return 'datetime'; 
				case "timestamp": return 'datetime'; 
				case "tiemstamptz": return 'datetime'; 
				case "bytea": return 'string'; 
				default: return 'string';
			}
		}
		public static function EscapeString($value) {
			if (get_magic_quotes_gpc()) {
				$value = stripslashes($value);
			}
			return pg_escape_string($value);
		}
		// devuelve el valor en el formato del proveedor. p.e.:
		// GetProviderValue(123, 'string') -> "'123'"
		public static function GetProviderValue($value, $type = NULL) {
			if (is_null($value)) return "null";
			if ($type == NULL) {
				if (is_string($value))
					return "'".self::EscapeString($value)."'"; 
				elseif (is_bool($value))
					return ($value?'true':'false');
				elseif (is_numeric($value))
					return $value;
			} else {
				switch($type) {
					case "string": return "'".self::EscapeString($value)."'";
					case "int": return $value;
					case "bit": return "$value::bit"; // o 'bit' tb funka;
					case "float": return $value;
					case "boolean": return ($value == 't' or $value == true)?"true":"false";
					case "datetime": return "'".self::EscapeString($value)."'";
					default: return "'".self::EscapeString($value)."'";
				}	
			}
		}
		// devuelve el valor typado segun $type. p.e.:
		// GetValue('123', 'int') -> 123, GetValue('t', 'boolean') -> true, 
		public static function GetValue($value, $type = 'string') {
			if (is_null($value)) {
				return NULL;
			}
			switch($type) {
				case "string": return (string) ($value);
				case "int": return (int) $value;
				case "float": return (float) $value;
				case "boolean": return ($value == 't')?true:false;
				case "datetime": return (string) $value;
				default: return (string) $value;
			}	
		}
		// schema caches! :p
		public static $schemas = array();
		public static function RegisterSchema($name, $schema) {
			self::$schemas[$name] = $schema;
		}
		public static function SchemaExists($name) {
			foreach(self::$schemas as $key=>$schema) {
				if ($key == $name) return true;
			}
			return false;
		}
		public static function GetSchema($tableName, $connection=NULL) {
			if (self::SchemaExists($tableName)) {
				return self::$schemas[$tableName];
			} 
			$sql = "SELECT
				c.relname AS table_name,
				nc.nspname AS schema_name,
				nc.nspname || '.' || c.relname AS full_table_name,
				a.attname AS column_name,
				a.attnum AS column_index,
				t.typname as column_type,
				a.atttypmod-4 as column_size,
				a.attnotnull as column_notnull,
				pg_get_expr(d.adbin, d.adrelid) as column_default,
				not(i.indkey is null or strpos(pg_catalog.array_to_string(i.indkey, ' ') || ' ', attnum::varchar || ' ') = 0)
				as column_inkey,
				not(d.adsrc is null or strpos(d.adsrc, 'nextval(') <> 1) as column_autoinc
				FROM pg_attribute a
				INNER JOIN pg_type t ON a.atttypid = t.oid
				INNER JOIN pg_class c ON a.attrelid = c.oid
				INNER JOIN pg_namespace nc ON nc.oid = c.relnamespace
				LEFT JOIN pg_attrdef d ON d.adrelid = c.oid AND d.adnum = a.attnum
				LEFT JOIN pg_index i ON i.indrelid = c.oid and i.indisprimary
				WHERE a.attnum > 0
				AND not a.attisdropped
				AND nc.nspname || '.' || c.relname = '".$tableName."'
				ORDER BY a.attnum;"; 
			$res = pg_query(is_null($connection)?self::$DbConnection:$connection, $sql);
			$schema = array("table_name"=>$tableName, "columns"=>array(), "pkey"=>array());
			while($r = pg_fetch_array($res, NULL, PGSQL_ASSOC)) {
				$schema["schema"] = $r["schema_name"];
				$schema["table_name"] = $r["table_name"];
				$schema["full_table_name"] = $r["full_table_name"];
				$schema["columns"][$r["column_name"]]["name"] = $r["column_name"];
				$schema["columns"][$r["column_name"]]["index"] = $r["column_index"];
				$schema["columns"][$r["column_name"]]["provider_type"] = $r["column_type"];
				$schema["columns"][$r["column_name"]]["type"] = self::GetDbType($r["column_type"]);
				$schema["columns"][$r["column_name"]]["size"] = $r["column_size"];
				$schema["columns"][$r["column_name"]]["notnull"] = ($r["column_notnull"]=='t')?true:false;
				$schema["columns"][$r["column_name"]]["default"] = $r["column_default"];
				$schema["columns"][$r["column_name"]]["inkey"] = ($r["column_inkey"]=='t')?true:false;
				$schema["columns"][$r["column_name"]]["autoinc"] = ($r["column_autoinc"]=='t')?true:false;
				$schema["columns"][$r["column_name"]]["fixed"] = false;
				if ($schema["columns"][$r["column_name"]]["inkey"]) {
					$schema["pkey"][] = $schema["columns"][$r["column_name"]];
				}
			}
			pg_free_result($res);
			self::RegisterSchema($tableName, $schema);
			return $schema;
		}
		public static function GetDefaultValues($tableName, $connection=NULL) {
			$schema = self::GetSchema($tableName, $connection);
			foreach($schema["columns"] as $name=>$prop) {
				$values[] = "(".(is_null($prop["default"])?"null":$prop["default"]).") as ".$name;
			}
			$q = new PgQuery("select ". implode(', ', $values), $connection);
			return $q->GetQueryFirstRow();	
		}
		public static function GetDefaultValue($tableName, $columnName, $connection=NULL) {
			$dv = self::GetDefaultValues($tableName, $connection);
			if (array_key_exists($columnName, $dv)) {
				return $dv[$columnName];	
			} else {
				exit("PGPRovider::GetDefaultValue: La columna '$columnName' no existe en la table '$tableName'");	
			}			
		}
	}
	//////////////////////////////////////
	// PgQuery class
	//////////////////////////////////////
	// PgDataQuery
	// example:
	// $q = new PgQuery();
	// $q->sql = "select * from public._test";
	// $q->Execute();
	// echo $dq->row["column_name1"];
	// echo $q->GetQueryCount();
	// echo $q->GetQueryValue();
	// $q->ToJson();
	// echo $q->GetRowAsJson();
	// $row = $q->GetRowAsHtml();
	// $q->First(); $q->Next(); $q->Prior(); $q->Last(); if ($q->Eof()) { echo 'fin'; }
	// $q->ResetReader(); while($q->Read()) { echo $q->row["column_name"]; }
	// $q->Display(); 
	// $q = new PgQuery("select * from table")->Execute();
	// $q = new PgQuery("select * from table")->Execute();
	class PgQuery {
		public static $DefaultDbConnection = NULL;
		function __construct($sql = "", $connection = NULL, $autoExecute = false, $decode=false) {
			if ($connection == NULL) {
				$this->connection = PgProvider::$DbConnection;
			} else {
				$this->connection = $connection;
			}
			$this->sql = $sql;
			$this->decode = $decode;
			if ($autoExecute == true) {
				$this->Execute();
			}
		}
		function __destruct() {
			$this->Close();
		}
		//properties
		public $connection;
		public $sql = "";
		public $resource;
		public $recNo = 0;
		public $recordCount = 0;
		public $active = false;
		public $decode = false; // for utf8decode
		public $row = array();
		private $firstRead = true;
		//methods
		// realiza operaciones de configuracion en la conexion cuando la conexion es persistente
		public function PersistentProcess() {
			if (Config::$DbPersistent) {
				@pg_set_client_encoding($this->connection, Config::$DbEncode);
				@pg_query($this->connection, "set DateStyle to 'sql, dmy'");
			}
		}
		public function Execute() {
			if ($this->active) {
				$this->Close();
			}
			self::PersistentProcess();
			$this->resource = pg_query($this->connection, $this->sql);
			if (pg_last_error($this->connection) != "") {
				echo "\n\r SQL_ERROR:\n\r".$this->sql."\n\r";
				exit;
			}
			$this->recordCount = pg_num_rows($this->resource);
			if ($this->recordCount >= 0) { // si hay resultado (filas o almenos el schema)
				$this->firstRead = true;
				$this->LoadSchema();
				$this->active = true;
				return $this->First();
			} else { // es un NonQuery
				$this->firstRead = true;
				$this->active = true;
				return $this;
			}
		}
		public function Close() {
			$this->recordCount = 0;
			$this->recNo = 0;
			$this->firstRead = true;
			$this->active = false;
			if (is_resource($this->resource)) {
				pg_free_result($this->resource); 
			}
		}
		public function First() {
			$this->MoveBy(-$this->recNo + 1); return $this;
		}
		public function Prior() {
			$this->MoveBy(-1); return $this;
		}
		public function Next() {
			$this->MoveBy(1); return $this;
		}
		public function Last() {
			$this->MoveBy($this->recordCount - $this->recNo); return $this;
		}
		// similar al DataReader.Read() // alm!
		public function Read() {
			if ($this->IsEmpty()) {
				return false;
			} elseif ($this->firstRead) {
				$this->firstRead = false;
				return ($this->MoveBy(-$this->recNo + 1)==true)?$this->row:NULL; //first
			} else {
				return ($this->MoveBy(1)==true)?$this->row:false; // next
			}
		}
		public function IsEmpty() {
			return ($this->recordCount <= 0);
		}
		public function Bof() {
			return ($this->IsEmpty() || $this->recNo <= 0);
		}
		public function Eof() {
			return ($this->IsEmpty() || $this->recNo > $this->recordCount);
		}
		public function MoveBy ($step) {
			$this->recNo += $step;
			// seek funka en base 0
			if ($this->recNo > 0 && $this->recNo <= $this->recordCount) {
				if (pg_result_seek($this->resource, $this->recNo - 1)) { 
					$this->row = $this->GetRow();
					return true;
				} else {
					die("MoveBy: ($this->recNo) fuera de rango [1 - $this->recordCount]");
				}
			}
			return false;
		}
		public function GetRow() {
			$r = pg_fetch_array($this->resource, NULL, PGSQL_ASSOC);
			foreach($this->schema["columns"] as $name=>$prop) {
				if ($this->decode == true && $prop['type']=='string') {
					if (is_null($r[$name])) {
						$r[$name] = NULL;
					} else {
						$r[$name] = utf8_decode($r[$name]);
					}
				} else {
					$r[$name] = PgProvider::GetValue($r[$name], $prop["type"]);
				}
				
			}
			return $r;
		}
/**
 * Devuelve la fila actual como un array simple (no asociativo). Para usarlo con el 'list'.
 * @return array
 */
		public function GetRowArray() {
			$r = pg_fetch_array($this->resource, $this->recNo-1, PGSQL_NUM);
			foreach($this->schema["columns"] as $name=>$prop) {
				$index = $prop['index'];
				if ($this->decode == true) {
					$r[$index] = utf8_decode(PgProvider::GetValue($r[$index], $prop["type"]));	
				} else {
					$r[$index] = PgProvider::GetValue($r[$index], $prop["type"]);
				}
			}
			return $r;
		}
		// verifica si la consulta devuelve alguna fila Exists!
		public function Exists() {
			self::PersistentProcess();
			$query = 'select exists('.$this->sql.')';
			$res = pg_query($this->connection, $query);
			if (pg_last_error($this->connection) != "") {
				echo "\n\r SQL_ERROR:\n\r".$query."\n\r";
				exit;
			}
			$value = pg_fetch_result($res, 0, 0);
			return ($value=='t')?true:false; // devolver valor y type explicitamente! 
			// OJO!: si $value = 'f', ($value==true) devuelve true! hay que utilizar ===
		}
		// obtiene solo el numero de registros
		public function GetQueryCount() {
			self::PersistentProcess();
			$query = 'select count(*) from ('.$this->sql.') as _query_count_';
			$res = pg_query($this->connection, $query);
			$value = pg_fetch_result($res, 0, 0);
			return (int) $value;
		}
		public function GetQueryFirstRow() {
			$this->Execute();
			return $this->row;
		}
		// obtiene el resultado scalar de la consulta
		public function GetQueryValue($default=NULL, $sql=NULL) {
			self::PersistentProcess();
			$_sql = is_null($sql)?$this->sql:$sql;
			$query = 'select * from ('.$_sql.') as _query_value_ limit 1';
			$res = pg_query($this->connection, $query);
			if (pg_last_error($this->connection) != "") {
				echo "\n\r SQL_ERROR:\n\r".$query."\n\r";
				exit;
			}
			$type = pg_field_type($res, 0);
			if (pg_num_rows($res) > 0) {
				$value = pg_fetch_result($res, 0, 0);// or die("GetResultValue: no result!");
			} else {
				$value = NULL;
			}
			if (is_null($value)) {
				if (!is_null($default)) return $default;
				return $value;
			}
			return PgProvider::GetValue($value, PgProvider::GetDbType($type));
		}
/**
 * Devuelve el valor de una columna del registro de una tabla
 * @param columnRef string: segun el formato schema.table.column
 * @param where string: en formato sql 'id=4 and type=2'
 * @param default mixed [optional]: valor por defecto si no hay resultado
 * @param connection resource [optional]: db connection 
 * @param decode boolean [optional]: si se desea decodificar utf8
 * @return mixed: el valor devuelto por la consulta o default
 */
		public static function GetValue($columnRef, $where, $default=NULL, $connection=NULL, $decode=false) {
			$_connection = ($connection==NULL)?PgProvider::GetConnection():$connection;
			$cr = explode('.', $columnRef);
			if (count($cr)==3) {
				$column = $cr[2];
				$tableName = $cr[0].".".$cr[1];
				$q = new PgQuery("select $column from $tableName where $where", $_connection);
				$v = $q->GetQueryValue($default);
				if (is_string($v) && $decode==true) {
					return utf8_decode($v);
				} else {
					return $v;
				}
			} else {
				exit("PgQuery.GetValue: Referencia de columna no valida ('$columnRef')");
			}
		}
/**
 * Devuelve el valor de la consulta
 * @param sql string: consulta sql que deberia devolver una sola columna y un solo registro
 * @param default mixed [optional]: valor por defecto si no hay resultado
 * @param connection resource [optional]: db connection 
 * @param decode boolean [optional]: si se desea decodificar utf8
 * @return mixed: el valor devuelto por la consulta o default
 */
		public static function GetQueryVal($sql, $default=NULL, $connection=NULL, $decode=false) {
			$_connection = ($connection==NULL)?PgProvider::GetConnection():$connection;
			$q = new PgQuery($sql, $_connection);
			$v = $q->GetQueryValue($default);
			if (is_string($v) && $decode==true) {
				return utf8_decode($v);
			} else {
				return $v;
			}
		}
		public static function GetRowValues($table, $columns, $id, $default=array(), $connection=NULL, $decode=false) {
			$_connection = ($connection==NULL)?PgProvider::GetConnection():$connection;
			$cr = explode(',', $columns);
			// clear empty column names
			$craux = $cr; foreach ($craux as $i=>$c) if (trim($c)=='') unset($cr[$i]);
			if (count($cr)>0) {
				foreach ($cr as $c) 
					$cols[] = trim($c);
				$sqlcols = implode(',', $cols);
				$schema = PgProvider::GetSchema($table, $_connection);
				if (count($schema['pkey'])==1) {
					foreach ($schema['pkey'] as $i=>$pk) {
						$wheres[] = "{$pk['name']} = ".PgProvider::GetProviderValue($id);	
					} 
					$wheres = implode(' AND ', $wheres);
					$q = new PgQuery("select $sqlcols from $table where $wheres", $_connection, true, $decode);
					if ($q->recordCount>0) {
						return $q->GetRowArray();
					} else {
						if (is_array($default)) {
							return $default;
						} else {
							foreach ($cols as $c)
								$def[] = $default;
							return $def;
						}
					} 
				} else {
					exit("PgQuery.GetRowValues: La tabla no tiene un Primary Key.");
				}
			} else {
				exit("PgQuery.GetRowValues: No se ha especificado campos en la seleccion.");
			}
		}
/*
 * Smart Selection Methods
 * 
 * example:
 * list($cod, $des) = PgQuery::Select('codigo', 'descripcion')->From('pre.nemo')->PkWhere(2)->GetRowAsList();
 */
		public $smartColumns = array();
		public $smartTable = '';
		public $smartWheres = array();
		public $smartDefaultValues = array();
		public $smartQuery = NULL;
		public $smartReady = false;
		public static function Select($c1,$c2='',$c3='',$c4='',$c5='') {
			$list = array($c1, $c2, $c3, $c4, $c5);
			foreach ($list as $c) {
				if (trim($c)!='') {
					$smartColumns[] = $c;
				}
			}
			$q = new PgQuery('--smart_select', NULL, false, true);
			$q->smartColumns = $smartColumns;
			return $q;
		}
		public function From($name) {
			$this->smartTable = $name;
			return $this;
		} 
		public function PkWhere($v) {
			$list = is_array($v)?$v:array($v);
			$schema = PgProvider::GetSchema($this->smartTable, $this->connection);
			if (count($schema['pkey'])==count($list)) {
				foreach ($schema['pkey'] as $i=>$pk) {
					$wheres[] = "{$pk['name']} = ".PgProvider::GetProviderValue($list[$i]);	
				} 
			} else
				throw new Exception('PkWhere: La Primary Key contiene un nuemro diferente de columnas.', 1);
			$this->smartWheres = $wheres;
			return $this; 
		}
		public function GetRowAsList($d1=NULL, $d2=NULL, $d3=NULL, $d4=NULL, $d5=NULL) {
			if (is_null($this->smartQuery)) {
				$this->smartQuery = "SELECT ".implode(', ', $this->smartColumns)." FROM ".$this->smartTable." WHERE ".implode(' AND ', $this->smartWheres);
			}
			if (!$this->smartReady) {
				$this->sql = $this->smartQuery;
				$this->decode = true;
				$this->Execute();
			}
			$list = array();
			if ($this->Read()) {
				return $this->GetRowArray();
			} else {
				$default = array($d1, $d2, $d3, $d4, $d5);
				foreach ($this->smartColumns as $i=>$c)
					$list[] = $default[$i];
				return $list; // :)			
			}
		}
		public static function ExecuteNonQuery($sql, $connection=NULL) {
			$_connection = ($connection==NULL)?PgProvider::GetConnection():$connection;
			@pg_query($_connection, $sql);
			if (pg_last_error($_connection) != "") {
				echo "\n\r SQL_ERROR:\n\r".$sql."\n\r";
			}
		}
		public function GetRowAsJson() {
			return json_encode($this->row);
		}
		// output functions
		public function ToJson() {
			if (!$this->active) {
				$this->Execute();
			}
			$this->ResetReader();
			$rows = array();
			while ($this->Read()) {
				$rows[] = $this->row;
			}
			return json_encode($rows);
		}
		public function ToArray() {
			if (!$this->active) {
				$this->Execute();
			}
			$this->ResetReader();
			$rows = array();
			while ($this->Read()) {
				$rows[] = $this->row;
			}
			return $rows;
		}
		// $includeDelimiter=true, osea, incluye los corchetes de los extremos de la lista o no, 
		// no de la fila o row. ([[],[],[]]) 
		public function ToStringArray($includeDelimiter=true) {
			if (!$this->active) {
				$this->Execute();
			}
			$colSep = ", "; $beginRow = "["; $endRow = "]";
			$rowSep = ", ";
			if ($includeDelimiter==true) {
				$beginList = "["; $endList = "]";	
			} else {
				$beginList = $endList = "";	
			}
			$items = array();
			$this->ResetReader();
			while ($this->Read()) {
				$row = array();
				foreach($this->schema['columns'] as $prop) {
					$row[] = PgProvider::GetProviderValue($this->row[$prop['name']], $prop['type']); 
				}
				$row = $beginRow.implode($colSep, $row).$endRow;
				$items[] = $row;
			}
			return $beginList.implode($rowSep, $items).$endList; 
		}
		public function GetColumnValues($column) {
			if ($this->active==false) $this->Execute();
			$items = array();
			$this->ResetReader();
			while ($cv = $this->Read()) {
				$items[] = $cv[$column];
			}
			return $items;
		}
		public function GetStringColumnValues($column, $separator=', ') {
			if ($this->active==false) $this->Execute();
			$items = array();
			$this->ResetReader();
			while ($cv = $this->Read()) {
				$items[] = $cv[$column];
			}
			return implode($separator, $items);
		}
		// imprime la registros del dataquery. $columns: array de nombres de columna pa mostrar
		// p.e. $dq->display(array('codigo', 'descripcion'));
		public function Display($columns = NULL, $lineSeparator = '<br>') {
			if (!$this->active) {
				$this->Execute();
			}
			if ($columns == NULL) {
				$columns = array_keys($this->schema["columns"]);
			}				
			$text = '';
			$this->ResetReader();
			while ($this->Read()) {
				foreach ($columns as $index=>$name)
					$text .= htmlentities($this->row[$name], ENT_QUOTES, "UTF-8").' ';
				$text .= $lineSeparator;
			}
			echo $text;
		}
		//utils
		public function ResetReader() {
			$this->firstRead = true;
		}
		protected function LoadSchema() {
			$this->schema["columnCount"] = pg_num_fields($this->resource);
			$this->schema["columns"] = array();
			for ($i = 0; $i < $this->schema["columnCount"]; $i++) {
				$name = pg_field_name($this->resource, $i);
				$this->schema["columns"][$name]["name"] = $name;
				$this->schema["columns"][$name]["provider_type"] = pg_field_type($this->resource, $i);
				$this->schema["columns"][$name]["type"] = PgProvider::GetDbType(pg_field_type($this->resource, $i));
				$this->schema["columns"][$name]["index"] = $i;
				$this->schema["columns"][$name]["size"] = pg_field_size($this->resource, $i);
			}
		}
		public function EscapeString($value) {
			return pg_escape_string($value);
		}
/**
 * Crea una cadena repitiendo la subconsulta por cada termino de $filter, reemplazando 
 * el parametro :filter. La subsonculta siempre entre parentesis.
 * Example: GetFilterSql('aguja pajar', "(descripcion ilike '%:filter%' OR nombre ilike '%%' OR ...)")
 */
        public static function GetFilterSql($filter, $filtersql) {
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
                return 'true';
        }
	}
	//////////////////////////////////////
	// PgDataRow class
	/////////////////////////////////////
	class PgDataRow {
		// state constants
		const UNCHANGED = 0;
		const ADDED = 1;
		const MODIFIED = 2;
		const DELETED = 3;
		// row version constants
		const CURRENT = 0;
		const ORIGINAL = 1;
		// constructor
		// no se especifica $tableName si se va usar un esquema.. Ok
		// nones eso de esquemas es pa la prox version! :p 27.11.2009 
		function __construct ($tableName=NULL, $connection = NULL) {
			$this->tableName = $tableName;
			if ($connection == NULL) {
				$this->connection = PgProvider::$DbConnection;
			} else {
				$this->connection = $connection;
			}
			if (!is_null($tableName)) {
				$this->LoadSchema();
			}
		}
		function __destruct () {
			// llamar al recolector de basura!!!...
			// pero como nada de esto es basura.. jejej no destruir na... jiji
		}
		// propiedades
		public $connection;
		public $tableName = "public.table";
		public $state = self::UNCHANGED;
		public $debug = false;
		public $decode = false; // utf8 decode
		public $debugLevel = 1; // 1, 2, 3
		private $row = array();
		private $original = array();
		private $schema = array();
		private $inlineSchema = array(); // no se usa todavia o.O
		
//		array(
//			'name'=>'ficha_ui',
//			'table'=>'public.ficha_ui',
//			'pkey'=>array('id_ficha_ui'), // for views without pkeys!
//			'relations'=>array(
//				array('name'=>'det_doc','table'=>'public.fui_det_documento'),
//				array('name'=>'det_lit','table'=>'public.fui_det_litigante')
//			)	
//		)
		private function CheckSerialColumn() {
			$serial = '';
			$default = ''; // default value for serial column i.e.: nextval('publi.table_id_seq')
			foreach($this->schema['columns'] as $cn=>$cp) {
				if ($cp['autoinc']==true) {
					$serial = $cn;
					$default = $cp['default']; 
					break;
				}
			}
			if ($serial!='') {
				$sustituir = array("nextval"=>"","::regclass"=>"","::text"=>"","\""=>"","'"=>"","("=>"",")"=>"");
				$sequence = strtr(strtolower($default), $sustituir);
				$max = $this->GetMax($serial, NULL, 0);
				$current = PgQuery::GetQueryVal("select last_value from $sequence", 0, $this->connection);
				if ($current < $max) {
					PgQuery::GetQueryVal("select setval('$sequence', $max)", 0, $this->connection);
				}
			}
		}
		// CRUD operations
		public function Create($generateAutoInc = true, $checkSerialColumn = true) {
			$values = array();
			foreach($this->schema["columns"] as $name=>$prop) {
				if ($prop['fixed']==true) continue;
				if ($prop['autoinc']==true) {
					if ($generateAutoInc==true) {
						$values[] = "(".(is_null($prop["default"])?"null":$prop["default"]).") as ".$name;
					} else {
						$values[] = "(0) as ".$name;
					}
				} else {
					$values[] = "(".(is_null($prop["default"])?"null":$prop["default"]).") as ".$name;	
				}
				
			}
			if ($checkSerialColumn) {
				$this->CheckSerialColumn();	
			}
			$q = new PgQuery("select ". implode(', ', $values), $this->connection);
			$q->Execute();
			$this->row = $q->row;
			$this->CreateRowFixedColumns();
			$this->row = $this->CalcFixedColumnValues($this->fixedColumns, $this->row);
			$this->state = self::ADDED;
			return $this;
		}
		public function Read($pkey = array()) {
			$pk = is_array($pkey)?$pkey:array($pkey);
			$q = new PgQuery($this->GetReadSql($pk), $this->connection);
			if ($this->debug) {
				echo "Read: ".$q->sql."\n\r";
			}
			$q->Execute();
			if ($q->IsEmpty()) exit("PGDataRow.Read: Record not found on table '".$this->tableName."'!");
			$this->row = $q->row;
			$this->original = $q->row;
			$this->CreateRowFixedColumns();
			$this->row = $this->CalcFixedColumnValues($this->fixedColumns, $this->row);
			$this->state = self::UNCHANGED;
			return $this;
		}
		public function Update($reload = true) {
			$init = microtime(true);
			$q = new PgQuery("", $this->connection);
			if ($this->state == self::ADDED) {
				$names = array();
				$values = array();
				foreach($this->schema["columns"] as $name=>$prop) {
					if ($prop['fixed']) continue; 
					$names[] = $name;
					$values[] = PgProvider::GetProviderValue($this->row[$name], $prop["type"]);
				}
				$q->sql = "insert into ".$this->tableName." (".implode(", ", $names).") values(".implode(", ", $values).");".$this->GetReadSql();
				if ($this->debug) {
					echo "Update: ".$q->sql."\n\r";
				}
				$q->Execute();
				if ($reload) {
					$this->row = $q->row;
					$this->original = $q->row;
					$this->CreateRowFixedColumns();
					$this->row = $this->CalcFixedColumnValues($this->fixedColumns, $this->row);
				}
				$this->UpdateChildRows();
				$this->state = self::UNCHANGED;
			} elseif ($this->state == self::MODIFIED) {
				$sets = array();
				$wheres = array();
				foreach($this->schema["columns"] as $name=>$prop) {
					if ($prop['fixed']) continue;
					$sets[] = $name." = ".PgProvider::GetProviderValue($this->row[$name], $prop["type"]);
				}
				foreach($this->schema["pkey"] as $pk) {
					$wheres[] = $pk["name"]." = ".PgProvider::GetProviderValue($this->original[$pk["name"]], $pk["type"]);
				}
				$q->sql = "update ".$this->tableName." set ".implode(", ", $sets)." where ".implode(" and ", $wheres).";".$this->GetReadSql();
				if ($this->debug) {
					echo "Update: ".$q->sql."\n\r";
				}
				
				$q->Execute();
				//echo "internal update execute time: ".(microtime(true)-$init);
				if ($reload) {
					$this->row = $q->row;
					$this->original = $q->row;
					$this->CreateRowFixedColumns();
					$this->row = $this->CalcFixedColumnValues($this->fixedColumns, $this->row);
				}
				$this->UpdateChildRows();
				$this->state = self::UNCHANGED;
				//echo "internal update reload time: ".(microtime(true)-$init);
			}
			return $this;
		}
		public function Delete($pkey = NULL, $includeRelations = false) {
			if (is_null($pkey)) {
				$values = $this->original;
			} elseif(is_array($pkey)) {
				$values = $pkey;
			} else {
				$values = array($pkey);
			}
			$pkRow = array();
			$wheres = array();
			foreach($this->schema["pkey"] as $index=>$pk) {
				$cindex = array_key_exists($pk["name"], $values)?$pk["name"]:$index;
				$wheres[] = $pk["name"]." = ".PgProvider::GetProviderValue($values[$cindex], $pk["type"]);
				$pkRow[$pk["name"]] = $values[$cindex];
			}
			if ($includeRelations == true) {
				$this->DeleteChilds($pkRow);
			}
			$q = new PgQuery("delete from ".$this->tableName." where ". implode(" and ", $wheres), $this->connection);
			if ($this->debug) {
				echo "Delete: ".$q->sql."\n\r";
			}
			$q->Execute();
			return $this;
		}
		public function Exists($pkey = array()) {
			$pk = is_array($pkey)?$pkey:array($pkey);
			$q = new PgQuery($this->GetReadSql($pk), $this->connection);
			return $q->Exists();
		}
		// Mixin operation
		// now support relation Mixin!
		public function Mixin($row, $ignoreSetAutoinc = false) {
			// set values
			foreach($this->schema["columns"] as $name=>$prop) {
				if (array_key_exists($name, $row)) {
					if ($prop["autoinc"] && $ignoreSetAutoinc) {
						continue; // no se setean campos autoinc, pasar a siguiente iteracion
						// a menos que se indique $ignoreAutoinc = false
					} 
					$this->Set($name, $row[$name]);
				}
			}
			// set relations values
			foreach($this->childRelations as $relIndex=>$relProp) {
				if (array_key_exists($relProp['name'], $row)) {
					$this->SetChildRows($relProp['name'], $row[$relProp['name']]);
				}
			}
			return $this;
		}
		public function Get($columnName, $version = self::CURRENT) {
			$value = ($version == self::CURRENT)?$this->row[$columnName]:$this->original[$columnName];
			if (is_null($value)) return NULL;
			$type = $this->schema["columns"][$columnName]["type"];
			switch($type) {
				case "string":
					$value = ($this->decode==true)?utf8_decode($value):$value; 
					return (string) $value;
				case "int": return (int) $value;
				case "float": return (float) $value;
				case "boolean": return ($value == 't' || $value==true)?true:false;
				case "datetime": return (string) $value;
				default: return (string) $value;
			}	
		}
		public function Set($columnName, $value = NULL) {
			// Set('flag', true) -> true, Set('flag', 't') -> true
			// Set('name') -> NULL
			if (!array_key_exists($columnName, $this->schema['columns'])) {
				echo "PgDataRow.Set: Column '$columnName' not exists on table '".$this->tableName."'!";
				exit;
			}
			if (is_null($value)) {
				if ($this->schema['columns'][$columnName]['notnull']) {
					echo "Set: Value for column '$columnName' can't be Null!";
					exit;
				} 
				$this->row[$columnName] = NULL;
			} else {
				$type = $this->schema["columns"][$columnName]["type"];
				switch($type) {
					case "string": 
						if ($this->schema["columns"][$columnName]["size"] > 0) {
							// eto previene los errores de longitud
							$value = substr($value, 0, $this->schema["columns"][$columnName]["size"]);
						}
						$this->row[$columnName] = (string) $value; 
					break;
					case "int": 
						if (is_numeric($value)) {
							$this->row[$columnName] = (int) $value;
						} elseif ($this->schema['columns'][$columnName]['notnull']) {
							echo "Set: Invalid value ($value) for column '$columnName' of type '$type' on table '".$this->tableName."'!";
							exit;
						} else {
							$this->row[$columnName] = NULL;
						}
						break;
					case "float": 
						if (is_numeric($value)) {
							$this->row[$columnName] = (float) $value;
						} elseif ($this->schema['columns'][$columnName]['notnull']) {
							echo "Set: Invalid value ($value) for column '$columnName' of type '$type' on table '".$this->tableName."'!";
							exit;
						} else {
							$this->row[$columnName] = NULL;
						}
						break;
					case "boolean": 
						$this->row[$columnName] = is_bool($value)?($value):(is_string($value)?(($value=='t')?true:false):false); break;
					case "datetime": $this->row[$columnName] = (string) $value; break;
					default: $this->row[$columnName] = (string) $value; break;
				}	
			}
			if ($this->state == self::UNCHANGED) {
				$this->state = self::MODIFIED;
			}
			return $this;
		}
		// fullreader! include relations!
		private function rGetRows($rel) {
			$wheres = array();
			foreach($rel["columns"] as $parentCol=>$childCol) {
				$wheres[] = $childCol." = ".PgProvider::GetProviderValue($this->row[$parentCol]);
			}
			$q = new PgQuery("select * from ".$rel["table"]." where ".implode(" and ", $wheres), $this->connection);
			$q->Execute();
			$rows = array();
			while($q->Read()) {
				$row = $q->row;
				if (array_key_exists('relations', $rel)) {
					foreach($rel['relations'] as $relIndex=>$relProp) {
						$row[$relProp['name']] = $this->rGetRows($relProp);
					}
				}
				$row = $this->CalcFixedColumnValues($rel['fixedColumns'], $row);
				$rows[] = $row;
			}
			return $rows;
		}
		public function GetRow($includeRelations = false, $onlyPkValues = false) {
			$r = array();
			foreach($this->schema["columns"] as $name=>$prop) {
				if (!$prop['inkey'] && $onlyPkValues) continue;
				$r[$name] = $this->Get($name);
				
			}
			if ($includeRelations) {
				foreach($this->childRelations as $relIndex=>$relProp) {
					$r[$relProp['name']] = $this->rGetRows($relProp);
				}
			}
			return $r;
		}
		// SetPkey!
		// para el uso de vistas con fullReader!, solo el nombre del pk
		// p.e.: array('id_ficha',...)
		public function SetPkey($pkey) {
			if (is_array($pkey)) {
				foreach($pkey as $pkname) {
					// bug fiexed for search pkey o.O
					$pkExists = false;
					foreach($this->schema['pkey'] as $pkProp) {
						if ($pkProp['name'] == $pkname) {
							$pkExists = true;
							break;
						}
					}
					if ($pkExists == false){
						if (array_key_exists($pkname, $this->schema['columns'])) {
							$this->schema['pkey'][] =  $this->schema['columns'][$pkname];
						}
					}
				}
			} else {
				exit("SetPkey: Invalid 'pkey' value!");
			}
		}
		// Child Relations!
		private $childRelations = array();
/**
 *	array(
 *		'name'=>'ficha_ui',
 *		'table'=>'public.ficha_ui',
 *		'pkey'=>array('id_ficha_ui'), // for views without pkeys!
 *		'relations'=>array(
 *			array('name'=>'det_doc','table'=>'public.fui_det_documento'),
 *			array('name'=>'det_lit','table'=>'public.fui_det_litigante')
 *		)
 *	)
 *@param array
 */
		public function AddChildRelation($relation) {
			if (is_array($relation)) {
				if (array_key_exists("name", $relation)) {
					if (array_key_exists("table", $relation)) {
						if (array_key_exists("columns", $relation)) {
							if (is_array($relation["columns"])) {
								// falta verificar que sea un array asociativo con keys string!
								$pc = $relation["columns"];
							} else {
								exit("AddChildRelation: Invalid 'columns' value for relation!");
							}				
						} else {
							$pc = array();
							foreach($this->schema["pkey"] as $pk) { 
								$pc[$pk["name"]] = $pk["name"]; 
							}
						}
					} else exit("AddChildRelation: Table name not defined!");
				} else exit("AddChildRelation: Relation name not defined!");
			} else exit("AddChildRelation: Invalid relation value!");
			
			$relation["columns"] = $pc;
			$relation["rows"] = array(); //inicializa el setted rows
			if (!array_key_exists("ignoreSetAutoinc", $relation)) {
				$relation["ignoreSetAutoinc"] = true;
			}
			if (!array_key_exists("fixedColumns", $relation)) {
				$relation["fixedColumns"] = array();
				// FIXED Column params
				// Si no se tiene una vista implentada para el READ...
				// agregue columnas que seran calculadas, segun la funcion callback o manualmente,
				// tambien se usa para la obtencion de la informacion de las relaciones, en este caso los valores 
				// pueden ser calculados solo por el callback.
				// 'name': column name
				// 'type': dbType
				// 'default: default value
				// 'function': callback function, prototype: function ($row, $columnName) { return $value; }
				// 'class': class for callback method
				// 'method': callback method, prototype: function ($row, $columnName) { return $value; }
			}
			$this->childRelations[] = $relation;
			return $this;
		}
		public function GetChildRelation($name) {
			foreach($this->childRelations as $index=>$relation) {
				if ($relation['name'] == $name) return $relation;
			}
			return NULL;
		}
		public function GetChildRelationIndex($name) {
			foreach($this->childRelations as $index=>$relation) {
				if ($relation['name'] == $name) return $index;
			}
			return NULL;
		}
		public function RelationExists($name) {
			foreach($this->childRelations as $index=>$relation) {
				if ($relation['name'] == $name) return true;
			}
			return false;
		}
		public function GetChildRows($relName) {
			$rel = $this->GetChildRelation($relName);
			if (is_array($rel)) {
				$wheres = array();
				foreach($rel["columns"] as $childCol=>$parentCol) {
					$wheres[] = $childCol." = ".PgProvider::GetProviderValue($this->row[$parentCol], 
						$this->schema["columns"][$parentCol]["type"]);
				}
				$q = new PgQuery("select * from ".$rel["table"]." where ".implode(" and ", $wheres), $this->connection);
				$q->Execute();
				if ($this->debug == true && $this->debugLevel >= 2) {
					echo "GetChildRows:".$q->sql."\n\r";
					echo $q->ToJson()."\n\rEnd GetChildRows\n\r"; 
				}
				return $q->ToArray();
			} else {
				echo "GetChildRows: Relation '$relName' not exists!";
				exit;
			}
		}
		public function SetChildRows($relName, $rows) {
			$relIndex = $this->GetChildRelationIndex($relName);
			if (!is_null($relIndex)) {
				if (is_array($rows)) {
					$this->childRelations[$relIndex]["rows"] = $rows;
				} else {
					exit("SetChildRows: 'rows' value not's a array!");
				}
			} else {
				echo "SetChildRows: Relation '$relName' not exists!";
				exit;
			}
		}
		public function UpdateChildRows() {
			foreach($this->childRelations as $relIndex=>$relprop) {
				$dbRows = $this->GetChildRows($relprop['name']);
				$settedRows = $relprop["rows"];
				$dr = new PgDataRow($relprop["table"], $this->connection);
				$dr->debug = $this->debug;
				// set sub relations!
				if (array_key_exists("relations", $relprop)) {
					if (is_array($relprop["relations"])) {
						foreach($relprop["relations"] as $r) {
							$dr->AddChildRelation($r);
						}
					} else {
						echo "UpdateChildRows: 'relations' parameter not is a Array in relation '".$relProp['name']."'!";
						exit;
					}
				}
				//var_dump($relprop); echo "\n\r";
				// conciliate rows!
				foreach($settedRows as $row) {
					// set PREVENT parent pk values
					// pks of relations 1:1, is convenient do directly on $row
					foreach($relprop["columns"] as $childCol=>$parentCol) { // for composite pk
						$row[$childCol] = $this->Get($parentCol);
					} 
					$ignoreSetAutoinc = $relprop["ignoreSetAutoinc"];
					if ($this->state == self::ADDED) {
						$dr->Create();
						$dr->Mixin($row, $ignoreSetAutoinc); // ignoramos los AutoInc!
					} elseif ($this->state == self::MODIFIED) {
						$dbRowIndex = NULL;
						$rowExists = false;
						foreach($dbRows as $i=>$r) {
							if ($this->debug == true && $this->debugLevel == 3) {
								echo "UpdateChildRows.comparation: \n\r Row:".json_encode($row)."\n\rDbRow:".json_encode($r)."\n\r";
							}
							$exists = true;
							foreach($dr->schema["pkey"] as $pk) { // for composite pk
								$exists = $exists && ($row[$pk["name"]] == $r[$pk["name"]]);
								$dbRowIndex = $i; // se garantiza el ingreso a ete bucle!
								if ($this->debug == true && $this->debugLevel == 3) {
									echo "r: ".$row[$pk["name"]]." = dbr: ".$r[$pk["name"]].", exists: ".(($exists==true)?'t':'f').", dbRowIndex: $dbRowIndex\n";
								}	
							}
							// $exists puede ser true parcialmente si el pk es compuesto.
							// $dbRowIndex no es NULL si se encontro una igualdad por lo menos...
							// se incluye a $dbRowIndex para determinar si existe, 
							// ya que $exists comienza siendo true.
							$rowExists = $exists && !is_null($dbRowIndex);
							if ($this->debug == true && $this->debugLevel == 3) {
								echo "RowExists: ".(($rowExists==true)?'t':'f').", exists: ".(($exists==true)?'t':'f').", dbRowIndex: $dbRowIndex\n\r";
							}
							if ($rowExists) break;
						}
						if ($this->debug == true) {
							// wai wai no se que poner aqui.. o.O
						}
						if ($rowExists) {
							$dr->Read($row);
							// se elimina del array para ya no volver a considerarlo en la siguiente busqueda
							unset($dbRows[$dbRowIndex]);
						} else {
							$dr->Create();
							//$ignoreSetAutoinc = true;
						}
						$dr->Mixin($row, $ignoreSetAutoinc);
					}
					// set parent pk values
					//foreach($relprop["columns"] as $childCol=>$parentCol) { // for composite pk
						//$dr->Set($childCol, $this->Get($parentCol));
					//} 
					$dr->Update();
				}
				// delete rest rows
				foreach($dbRows as $row) {
					$dr->Delete($row);
				}
			}
		}
		public function DeleteChilds($pkRow) {
			foreach($this->childRelations as $relIndex=>$relProp) {				
				$this->rDeleteChildRows($relProp, $pkRow); // las relaciones de 1er level si tienen 'columns' seteado!
			}
		}
		public function rDeleteChildRows($relation, $parentPkValues, $parentSchema = array()) {
			$schema = PgProvider::GetSchema($relation['table'], $this->connection);
			// sino esta definido el columns relation, se usa el $parentSchema y lito el pecadito!
			if (!array_key_exists('columns', $relation)) {
				$relation['columns'] = array();
				foreach($parentSchema['pkey'] as $index=>$col) {
					$relation['columns'][$col['name']] = $col['name'];
				}
			}
			$w = array();
			foreach($relation['columns'] as $pCol=>$cCol) {
				$w[] = $cCol." = ".PgProvider::GetProviderValue($parentPkValues[$pCol], $schema['columns'][$cCol]['type']);
			}
			if (array_key_exists('relations', $relation)) {
				$pkn = array();
				foreach($schema['pkey'] as $index=>$col) {
					$pkn[] = $col['name'];
				}
				$q = new PgQuery("select ".implode(', ', $pkn)." from ".$relation['table']." where ".implode(' and ', $w), $this->connection);
				$q->Execute();
				while ($q->Read()) {
					foreach($relation['relations'] as $relIndex=>$relProp) {	
						$this->rDeleteChildRows($relProp, $q->row, $schema);
					}
				}
			}
			$q = new PgQuery("delete from ".$relation['table']." where ".implode(' and ', $w), $this->connection);
			if ($this->debug) echo $q->sql."\n\r";
			$q->Execute();
		}
		// setting loginfo!
		public function UpdateLogInfo($customLog = NULL) {
			if ($customLog == NULL) {
				// syslog mode!:
				if (array_key_exists('syslog', $this->schema['columns'])) {
					$inf = "user:".Sys::GetUserName().
					", ip:".Sys::GetUserIP().
					", station:".Sys::GetUserStation().
					", date:".date("d-m-Y").
					", time:".date("H:i:s");
					if ($this->state == self::ADDED) {
						$this->Set('syslog', $inf.", proc:insert;");	
					} else {
						$this->Set('syslog', $this->Get('syslog').$inf.", proc:update;");
					}
				}
			}
		}
		// OutPut functions!
		// $includeRelations: read full data from defined relations
		public function ToJson($includeRelations = false) {
			$r = $this->GetRow($includeRelations);
			return json_encode($r);
		}
		public function ToArray($includeRelations = false) {
			return $this->GetRow($includeRelations);
		}
		public function Display($columns = NULL, $lineSeparator = '<br>') {
			if ($columns == NULL) {
				$columns = array_keys($this->schema["columns"]);
			}				
			$text = '';			
			foreach ($columns as $index=>$name) {
				$text .= $name.": ".htmlentities($this->Get($name), ENT_QUOTES, "UTF-8").', ';
			}
			echo $text . $lineSeparator;
		}
		public function GetPkNames() {
			$pkn = array();
			foreach($schema['pkey'] as $index=>$col) {
				$pkn[] = $col['name'];
			}
			return $pkn;
		}
		public function GetPkValues() {
			$pkv = array();
			foreach($schema['pkey'] as $index=>$col) {
				$pkv[] = $this->Get($col['name']);
			}
			return $pkv;
		}
		public function GetPkRow() {
			return $this->GetRow(false, true);
		}
		// utils functions
		private function GetReadSql($pkValues = NULL) {
			$values = is_null($pkValues)?$this->row:$pkValues;
			$wheres = array();
			foreach($this->schema["pkey"] as $index=>$pk) {
				$cindex = array_key_exists($pk["name"], $values)?$pk["name"]:$index;
				if (!array_key_exists($cindex, $values)) {
					echo "GetReadSql: '".$pk["name"]."' or $index index not exists on primary key values!";
					exit;
				}
				$wheres[] = $pk["name"]." = ".PgProvider::GetProviderValue($values[$cindex], $pk["type"]);
			}
			return "select * from ".$this->tableName." where ". implode(" and ", $wheres)."";
		}
		private function LoadSchema() {
			$this->schema = PgProvider::GetSchema($this->tableName, $this->connection);
		}
		// agregate 
		// $wheres = array('id_ficha_u'=>20,'piso'=>'2') to sql: "where id_ficha_u = 20 and piso = '2'"
		public function GetMax($column, $wheres = array(), $default=NULL) {
			$w = '';
			if (is_array($wheres) && count($wheres) > 0) {
				$ws = array();
				foreach($wheres as $col=>$val) {
					$ws[] = "$col = ".PgProvider::GetProviderValue($val);
				}
				$w = " where ".implode(' and ', $ws);
			} elseif (is_string($wheres)) {
				$w = " where $wheres";
			}
			$sql = "select max($column) from ".$this->tableName.$w;
			$q = new PGQuery($sql, $this->connection);
			return $q->GetQueryValue($default);		
		}
		// FIXED columns
		public function ColumnExists($name) {
			foreach($this->schema['columns'] as $cname=>$prop) {
				if ($name == $cname) return true;
			}
			return false;
		}
		private function GetNextColumnIndex() {
			$max = -1;
			foreach($this->schema['columns'] as $name=>$prop) {
				if ($prop['index'] > $max) {
					$max = $prop['index'];
				}
			}
			return ($max + 1);
		}
		public $fixedColumns = array();
		public function AddFixedColumn($name, $type='string', $default=NULL) {
			if ($this->ColumnExists($name)) {
				echo "AddFixedColumn: Column '$name' exists!";
				exit;	
			} else {
				$nextIndex = $this->GetNextColumnIndex();
				$this->schema["columns"][$name]["name"] = $name;
				$this->schema["columns"][$name]["index"] = $nextIndex; 
				$this->schema["columns"][$name]["provider_type"] = 'text'; // no se usa..creo..o.O el error avisa.. jojojo
				$this->schema["columns"][$name]["type"] = $type;
				$this->schema["columns"][$name]["size"] = 0; // todo lo que kepe!!!
				$this->schema["columns"][$name]["notnull"] = false; // si pueden ser nulos
				$this->schema["columns"][$name]["default"] = $default;
				$this->schema["columns"][$name]["inkey"] = false;
				$this->schema["columns"][$name]["autoinc"] = false;
				$this->schema["columns"][$name]["fixed"] = true;
				$this->fixedColumns[$name] = $this->schema["columns"][$name]; 
			}
		}
		private function CreateRowFixedColumns() {
			foreach($this->fixedColumns as $name=>$prop) {
				$this->row[$name] = $prop['default'];
				$this->original[$name] = $prop['default'];
			}
		}
		private function CalcFixedColumnValues($fixedColumns, $row) {
			foreach($fixedColumns as $name=>$prop) {
				$row[$name] = $prop['default'];
				if (array_key_exists('function', $prop)) {
					if (function_exists($prop['function'])) {
						$row[$name] = call_user_func($prop['function'], $row, $name);	
					} else {
						exit("PgDataRow.rGetRows: function '".$prop['function']."'not exists!");
					}
				} elseif (array_key_exists('class', $prop) && array_key_exists('method', $prop)) {
					if (method_exists($prop['class'], $prop['method'])) {
						$row[$name] = call_user_func(array($prop['class'], $prop['method']), $row, $name);
					} else {
						exit("PgDataRow.rGetRows: method '".$prop['class'].".".$prop['method']."'not exists!");
					}
				}
			}
			return $row;
		}
		// schema LOADING
		// config example:
//		public static $schema = array(
//			'name'=>'master',
//			'table'=>array('name'=>'public.master'),
//			'table_read'=>array(
//				'name'=>'public.v_master',
//				'pkey'=>array('id_master')
//			),
//			'relations'=>array(
//				array(
//					'name'=>'detail_1',
//					'table'=>array(
//						'name'=>'public.detail_1'
//					),
//					'table_read'=>array(
//						'name'=>'public.v_detail_1',
//						'pkey'=>array('id_detail_1'),
//						'columns'=>array('id_master_fk'=>'id_master'),
//						'fixedColumns'=>array(
//							'f_col'=>array('type'=>'string', 'default'=>'')
//						)
//					),
//					'ignoreSetAutoinc'=>false
//				),
//				array(
//					'name'=>'detail_2',
//					'table'=>array(
//						'name'=>'public.detail_2'
//					),
//					'ignoreSetAutoinc'=>false
//				)
//			)
//		); 
		// obtiene de manera recursiva las relaciones en un formato compatible con el PGDataRow
		private function GetCompatibleRelation($r, $read = false) {
			$nr = array(
				'name'=>$r['name']
			);
			$table = $r['table'];
			if ($read && array_key_exists('table_read', $r)) {
				$table = $r['table_read'];
			}
			$nr['table'] = $table['name'];
			if (array_key_exists('pkey', $table)) {
				$nr['pkey'] = $table['pkey'];
			}
			if (array_key_exists('columns', $table)) {
				$nr['columns'] = $table['columns'];
			}
			if (array_key_exists('ignoreSetAutoinc', $r)) {
				$nr['ignoreSetAutoinc'] = $r['ignoreSetAutoinc'];
			}
			if (array_key_exists('fixedColumns', $r)) {
				$nr['fixedColumns'] = $r['fixedColumns'];
			}
			if (array_key_exists('relations', $schema)) {
				$nr['relations'] = array();
				foreach($schema['relations'] as $i=>$re) {
					$nr[] = $this->GetCompatibleRelation($re, $read);		
				}
			}
			return $nr;
		}
		// configura el nombre y las relaciones de PGDataRow
		// schema: esquema de la tabla
		// read: indica si se usa los parametros de lectura
		// WARNING: todavia no funka esta funcionalidad
		public function Config($schema, $read = false) {
			if ($read && array_key_exists('table_read', $schema)) {
				$this->tableName = $schema['table_read']['name'];
				$this->SetPkey($schema['table_read']['pkey']);
			} else {
				$this->tableName = $schema['table']['name'];
			}
			if (array_key_exists('relations', $schema)) {
				foreach($schema['relations'] as $i=>$r) {
					$rel = $this->GetCompatibleRelation($r, $read);
					$this->AddChildRelation($rel);
				}			
			}
		}
		// GetNextValue!
		// obtiene el siguiente valor de una columna de tipo numerico de la tabla (int or float)
		// osea : select max($column) + 1 from $tablename where $wheres
		public function GetNextValue($column, $wheres = array(), $ignoreType=false) {
			if ($this->ColumnExists($column)) {
				$type = $this->schema['columns'][$column]['type'];
				if ($type == 'int' || $type == 'float'||$ignoreType) {
					$next = $this->GetMax($column, $wheres);
					return ((int)$next + 1); // XD
				} else {
					exit("PGDataRow.GetNextValue: column type '$type' non nextable o.O");
				}
			} else {
				exit("PGDataRow.GetNextValue: column '$column' not exists");
			}
		}
	}
} // EOF
?>