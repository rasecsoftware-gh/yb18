<?php
if (!defined("_SYS_")) {
    define("_SYS_", 1);
    $path_prefix = __DIR__.DIRECTORY_SEPARATOR;
	require_once $path_prefix.'config.php';
	require_once $path_prefix.'json/json.php';
	require_once $path_prefix.'data.php';
    
    class Sys {
        // default connection
        public static $DbConnection = NULL;
        public static $InTransaction = false;
        public static function BeginTransaction($connection = NULL) {
            $res = pg_query((is_null($connection) ? self::$DbConnection : $connection), "BEGIN;");
            self::$InTransaction = true;
        }

        public static function CommitTransaction($connection = NULL) {
            $res = pg_query((is_null($connection) ? self::$DbConnection : $connection), "COMMIT;");
            self::$InTransaction = false;
        }

        public static function RollbackTransaction($connection = NULL) {
            $res = pg_query((is_null($connection) ? self::$DbConnection : $connection), "ROLLBACK;");
            self::$InTransaction = false;
        }

        public static function DbConnect() {
            $init = microtime(true);
            if (self::CheckDbServices()) {
                $cn_string = sprintf("host=%s port=%s dbname=%s user=%s password=%s", Config::$DbServer, Config::$DbPort, Config::$DbName, Config::$DbUser, Config::$DbPassword);
                self::$DbConnection = pg_connect($cn_string);
                //devuelve falso si no se pudo realizar la conexion
                if (self::$DbConnection === false) {
                    echo "Unable to connect.</br>";
                    die(print_r(pg_last_error(self::$DbConnection), true));
                }
                pg_set_client_encoding(self::$DbConnection, Config::$DbEncode);
                // configuramo la fecha del server :p
                pg_query(self::$DbConnection, "set DateStyle to 'sql, dmy'");
                $et = microtime(true) - $init;
                //echo "connection time: $et";
                return self::$DbConnection;
            } else {
                exit('No esta instalado o habilitado el cliente (.dll .so) del Servidor de Base de Datos');
            }
        }

        public static function DbDisconnect() {
            if (self::CheckDbServices()) {
                @pg_close(self::$DbConnection);
                self::$DbConnection = NULL;
            }
        }

        public static function GetConnection() {
            return self::$DbConnection;
        }

        public static function CheckDbServices() {
            return function_exists('pg_connect') ? true : false;
        }

        public static function GetArrayValue($name, $array, $default = '') {
            if (is_array($array)) {
                if (array_key_exists($name, $array))
                    return $array[$name];
                else
                    return $default;
            } else {
                return $default;
            }
        }

        public static function GetR($name, $default = '', $regInSession = false, $prefix = '', $defaultFromSession = false) {
            if (array_key_exists('load_from_session', $_REQUEST) || $defaultFromSession) {
                if (array_key_exists($name, $_REQUEST)) {// for replace recent values sended
                    $_SESSION[$prefix . $name] = $_REQUEST[$name];
                }
                return self::GetArrayValue($prefix . $name, $_SESSION, $default);
            } else {
                $value = self::GetArrayValue($name, $_REQUEST, $default);
                if ($regInSession)
                    $_SESSION[$prefix . $name] = $value;
                return $value;
            }
        }

        public static function GetP($name, $default = '', $regInSession = false, $prefix = '', $defaultFromSession = false) {
            if (array_key_exists('load_from_session', $_REQUEST) || $defaultFromSession) {
                if (array_key_exists($name, $_POST)) {// for replace recent values sended
                    $_SESSION[$prefix . $name] = $_POST[$name];
                }
                return self::GetArrayValue($prefix . $name, $_SESSION, $default);
            } else {
                $value = self::GetArrayValue($name, $_POST, $default);
                if ($regInSession)
                    $_SESSION[$prefix . $name] = $value;
                return $value;
            }
        }

        public static function GetG($name, $default = '', $regInSession = false, $prefix = '', $defaultFromSession = false) {
            if (array_key_exists('load_from_session', $_REQUEST) || $defaultFromSession) {
                if (array_key_exists($name, $_GET)) {// for replace recent values sended
                    $_SESSION[$prefix . $name] = $_GET[$name];
                }
                return self::GetArrayValue($prefix . $name, $_SESSION, $default);
            } else {
                $value = self::GetArrayValue($name, $_GET, $default);
                if ($regInSession)
                    $_SESSION[$prefix . $name] = $value;
                return $value;
            }
        }

        public static function GetS($name, $default = '') {
            return self::GetArrayValue($name, $_SESSION, $default);
        }

        public static function GetGEscaped($name, $default = '', $regInSession = false, $prefix = '', $defaultFromSession = false) {
            if (!get_magic_quotes_gpc()) {
                return addslashes(self::GetG($name, $default, $regInSession, $prefix, $defaultFromSession));
            } else {
                return self::GetG($name, $default, $regInSession, $prefix, $defaultFromSession);
            }
        }

        public static function GetPEscaped($name, $default = '', $regInSession = false, $prefix = '', $defaultFromSession = false) {
            if (!get_magic_quotes_gpc()) {
                return addslashes(self::GetP($name, $default, $regInSession, $prefix, $defaultFromSession));
            } else {
                return self::GetP($name, $default, $regInSession, $prefix, $defaultFromSession);
            }
        }

        public static function GetREscaped($name, $default = '', $regInSession = false, $prefix = '', $defaultFromSession = false) {
            if (!get_magic_quotes_gpc()) {
                return addslashes(self::GetR($name, $default, $regInSession, $prefix, $defaultFromSession));
            } else {
                return self::GetR($name, $default, $regInSession, $prefix, $defaultFromSession);
            }
        }

        // Json Request!
        public static $JsonRequest;
        public static function CallClassMethodFromRequest($className, $classMethod = NULL) {
            if ($classMethod == NULL) {
                if (array_key_exists("action", $_REQUEST)) {
                    $classMethod = self::GetR("action");
                } else {
                    // only post request!
                    // important!: use SERVICES_JSON_LOOSE_TYPE for json_decode in JSON.php,
                    // this get {a:'v'} as array associative :)
                    $request = json_decode(file_get_contents('php://input'), true);
                    if (is_array($request)) {
                        if (array_key_exists("action", $request)) {
                            $classMethod = $request["action"];
                            self::$JsonRequest = $request;
                        } else { exit("Sys: Missing action param!");
                        }
                    } else { exit("Sys: Missing action param!");
                    }
                }
            }
            if (method_exists($className, $classMethod)) {
                call_user_func(array($className, $classMethod));
                exit ;
            }
            echo "Sys: Undefined Action '$classMethod'!";
            exit ;
        }

        public static function GetJsonRequest() {
            return self::$JsonRequest;
        }

        public static function GetJsonData() {
            return self::$JsonRequest['data'];
        }

        public static function DisableClassListen() {
            define('_DISABLED_CLASS_LISTEN_', 1);
        }

        public static function CreateRequestError($msg) {
            return json_encode(array("success" => false, "message" => $msg));
        }

        public static function RequestError($msg) {
            $err = json_encode(array("success" => false, "message" => $msg));
            exit($err);
        }

        public static function RequestSuccess($msg = NULL) {
            $ok = json_encode(array("success" => true, "message" => $msg));
            exit($ok);
        }

        // decode
        public static function UTF8Decode($v) {
            $a = $v;
            if (is_array($a)) {
                foreach ($a as $i => $value) {
                    if (is_string($value)) {
                        $a[$i] = utf8_decode($value);
                    }
                }
            }
            return $a;
        }

        public static function UTF8Encode($v) {
            $a = $v;
            if (is_array($a)) {
                foreach ($a as $i => $value) {
                    if (is_string($value)) {
                        $a[$i] = utf8_encode($value);
                    }
                }
            }
            return $a;
        }

        public static function UTF8Normalize($v) {
            return self::UTF8Encode(self::UTF8Decode($v));
        }

        // Validation
        /**
         * Evalua si el valor dado cumple los patrones especificados.
         * @param value mixed: valor a evaluar
         * @param inline_pattern string: patrones de evaluacion separador por '|'. i.e.: numeric|int|float|date|!null|!empty|[>|<|<=|>=|=|<>][value]
         * @param valuename string: nombre del valor (para usar en el mensaje por defecto)
         * @param failmessage string[optional]: mensaje personalizado
         * @return string: muestra directamente el mensaje adecuado y finaliza la ejecucion del script.
         */
        public static function Check($value, $inline_pattern, $valuename, $failmessage = NULL) {
            $plist = explode('|', $inline_pattern);
            $res = true;
            $defaultmessage = '';
            foreach ($plist as $p) {
                switch ($p) {
                    case 'numeric' :
                        $res = $res && (is_numeric($value) || is_null($value));
                        $defaultmessage = "El valor del campo '$valuename' debe ser numerico";
                        break;
                    case '!numeric' :
                        $res = $res && !is_numeric($value);
                        $defaultmessage = "El valor del campo '$valuename' no debe ser numerico";
                        break;
                    case 'int' :
                        $res = $res && ((is_numeric($value) && is_int(intval($value))) || is_null($value));
                        $defaultmessage = "El valor del campo '$valuename' debe ser entero";
                        break;
                    case '!int' :
                        $res = $res && !(is_numeric($value) && is_int(intval($value)));
                        $defaultmessage = "El valor del campo '$valuename' no debe ser entero";
                        break;
                    case 'float' :
                        $res = $res && ((is_numeric($value) && is_float(floatval($value))) || is_null($value));
                        $defaultmessage = "El valor del campo '$valuename' debe ser real";
                        break;
                    case '!float' :
                        $res = $res && !(is_numeric($value) && is_float(floatval($value)));
                        $defaultmessage = "El valor del campo '$valuename' no debe ser real";
                        break;
                    case 'null' :
                        $res = $res && is_null($value);
                        $defaultmessage = "El valor del campo '$valuename' debe ser nulo";
                        break;
                    case '!null' :
                        $res = $res && !is_null($value);
                        $defaultmessage = "El valor del campo '$valuename' no debe ser nulo";
                        break;
                    case 'empty' :
                        $res = $res && trim($value) == '';
                        $defaultmessage = "El valor del campo '$valuename' debe ser una cadena vacia";
                        break;
                    case '!empty' :
                        $res = $res && trim($value) != '';
                        $defaultmessage = "El valor del campo '$valuename' no debe ser una cadena vacia";
                        break;
                    case 'date' :
                        $res = $res && (Sys::checkDate($value) || is_null($value) || trim($value) == '');
                        $defaultmessage = "El valor del campo '$valuename' debe ser una fecha valida (dd/mm/yyyy)";
                        break;
                    case '!date' :
                        $res = $res && !Sys::checkDate($value);
                        $defaultmessage = "El valor del campo '$valuename' no debe ser una fecha";
                        break;
                    default :
                        if (strpos($p, '<>') === 0) {
                            $cmpvalue = eval("return " . substr($p, 2) . ";");
                            if (is_int($cmpvalue) || is_float($cmpvalue)) {
                                $res = $res && (floatval($value) <> $cmpvalue);
                            } else {
                                $res = $res && ($value <> $cmpvalue);
                            }
                            $defaultmessage = "El valor del campo '$valuename' debe ser diferente de '$cmpvalue'";
                        } elseif (strpos($p, '<=') === 0) {
                            $cmpvalue = eval("return " . substr($p, 2) . ";");
                            if (is_int($cmpvalue) || is_float($cmpvalue)) {
                                $res = $res && (floatval($value) <= $cmpvalue);
                            } else {
                                $res = $res && ($value <= $cmpvalue);
                            }
                            $defaultmessage = "El valor del campo '$valuename' debe ser menor o igual que '$cmpvalue'";
                        } elseif (strpos($p, '>=') === 0) {
                            $cmpvalue = eval("return " . substr($p, 2) . ";");
                            if (is_int($cmpvalue) || is_float($cmpvalue)) {
                                $res = $res && (floatval($value) >= $cmpvalue);
                            } else {
                                $res = $res && ($value >= $cmpvalue);
                            }
                            $defaultmessage = "El valor del campo '$valuename' debe ser mayor o igual que '$cmpvalue'";
                        } elseif (strpos($p, '>') === 0) {
                            $cmpvalue = eval("return " . substr($p, 1) . ";");
                            if (is_int($cmpvalue) || is_float($cmpvalue)) {
                                $res = $res && (floatval($value) > $cmpvalue);
                            } else {
                                $res = $res && ($value > $cmpvalue);
                            }
                            $defaultmessage = "El valor del campo '$valuename' debe ser mayor que '$cmpvalue'";
                        } elseif (strpos($p, '<') === 0) {
                            $cmpvalue = eval("return " . substr($p, 1) . ";");
                            if (is_int($cmpvalue) || is_float($cmpvalue)) {
                                $res = $res && (floatval($value) < $cmpvalue);
                            } else {
                                $res = $res && ($value < $cmpvalue);
                            }
                            $defaultmessage = "El valor del campo '$valuename' debe ser menor que '$cmpvalue'";
                        } elseif (strpos($p, '=') === 0) {
                            $cmpvalue = eval("return " . substr($p, 1) . ";");
                            if (is_int($cmpvalue) || is_float($cmpvalue)) {
                                $res = $res && (floatval($value) == $cmpvalue);
                            } else {
                                $res = $res && ($value == $cmpvalue);
                            }
                            $defaultmessage = "El valor del campo '$valuename' debe ser igual que '$cmpvalue'";
                        } else {
                            $streval = "return ($value);";
                            $res = $res && eval($streval);
                            $to = array("<>" => " diferente ", "<=" => " menor o igual que ", ">=" => " mayor o igual que ", "<=" => " menor que ", ">" => " mayor que ", "=" => " igual que ");
                            $rule = strtr($p, $to);
                            $defaultmessage = "El valor del campo '$valuename' debe ser $rule";
                        }
                }
                if ($res == false) {
                    exit(is_null($failmessage) ? $defaultmessage : $failmessage);
                }
            }
        }

        public static function checkDate($v) {
            if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}/", $v)) {
                list($d, $m, $a) = explode('/', $v);
                return checkdate((int)$m, (int)$d, (int)$a);
            }
            return false;
        }

        public static function checkTime($v) {
            if (preg_match("/(0[1-9]|1\d|2[0-3]):([0-5]\d):([0-5]\d)/", $v)) {
                list($h, $m, $s) = explode(':', $v);
                if ($h > 0 && $h < 24 && $m >= 0 && $m < 60 && $s >= 0 && $s < 60) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Devuelva la diferencia en dias entre dos fechas
         * @param f string: fecha en formato dd/mm/yyyy
         * @param ref string [optional]: referencia de la fecha de comparacion, por defecto es la fecha actual.
         * @return integer: devuelve el numero de dias de diferencia
         */
        public static function getDateDiff($f, $ref = NULL) {
            if (!self::checkDate($f))
                exit("Sys::getDateDiff: formato de fecha no valida para el parametro 'f': '$f'");
            if ($ref == NULL) {
                $now = strtotime('now');
            } elseif (is_string($ref)) {
                if (!self::checkDate($ref))
                    exit("Sys::getDateDiff: formato de fecha no valida '$ref'");
                list($d, $m, $a) = explode('/', $ref);
                $now = strtotime("$a/$m/$d");
            } elseif (is_int($ref)) {
                $now = $ref;
            } else {
                exit("Sys::getDateDiff: tipo de dato no valido para el parametro 'ref'");
            }
            list($d, $m, $a) = explode('/', $f);
            $dif = $now - strtotime("$a/$m/$d");
            $ndias = ($dif / (24 * 60 * 60));
            return $ndias;
        }

        /**
         * Devuelva el timestamp (int) de una fecha y/o hora.
		 * 12/05/2013 14:05:34
         * @param f string: fecha en formato dd/mm/yyyy
         * @return integer: devuelve el valor de la marca de tiempo
         */
        public static function getTimeStamp($f) {
        	$f = trim($f);
        	$date = substr($f, 0, 10);
			$time = '';
			if (strlen($f)>10) {
				$time = substr($f, 11, 8);
				if (!self::checkTime($time))
                	exit("Sys::getTimeStamp: formato de hora no valida '$time'");	
			}
			//echo "$date $time";
            if (!self::checkDate($date))
                exit("Sys::getTimeStamp: formato de fecha no valida '$f'");
            list($d, $m, $a) = explode('/', $date);
            $dt = "$a/$m/$d $time";
            //echo $dt;
            return strtotime($dt);
        }

        /**
         * Verifica si una fecha (dd/mm/yyyy [hh:mm:ss]) a expirado, pasandole dias de vencimiento, tambien admite solo dias habiles
         * @param f string: fecha en formato dd/mm/yyyy
         * @param days int: dias de vencimiento
         * @param onlyWorkingDays boolean [optional]: para considerar solo dias habiles, por defecto es false
         * @return boolean: devuelve true si expiro o false en caso contrario
         */
        public static function expiredDate($f, $days, $onlyWorkingDays = false) {
            if (!Sys::checkDate($f)) {
                exit("Sys.expiredDate: Fecha no valida: '$f'");
            }
            list($d, $m, $a) = explode('/', $f);
            //$a = 'yyyy hh:mm:ss', para extraer solo el anio
            list($a) = explode(' ', $a);
            //echo "a: $a<br/>";
            $f_time = strtotime("$a/$m/$d");
            //echo "fecha: $f, f_time: $f_time<br/>";
            for ($i = 0; $i < $days; $i++) {
                $f_time += 86400;
                // (24*60*60); // segundos de un dia
                $d = date("N", $f_time);
                // devuelve numero de dia 1:lun-7:dom
                if ($d == 6 || $d == 7) {
                    $i--;
                }
            }
            $now = strtotime(date("Y/m/d"));
            //echo "f_time final: $f_time, now_time: ".$now.", now: ".date("Y/m/d")."<br/>";
            $dif = ($now - $f_time) / 86400;
            // en dias
            //echo "dif: $dif<br/>";
            return ($dif > 0);
        }

        public static function checkMail($v) {
            return (filter_var($v, FILTER_VALIDATE_EMAIL) === false) ? false : true;
        }

        public static function checkUrl($v) {
            return (filter_var($v, FILTER_VALIDATE_URL) === false) ? false : true;
        }

        public static function checkIP($v) {
            return (filter_var($v, FILTER_VALIDATE_IP) === false) ? false : true;
        }

        // utils
        public static function UrlFileExists($url) {
            if (self::checkUrl($url)) {
                $str = trim($url);
                // si la ruta termina con '/' o '\' (92) no es un archivo, pero fopen puede que si lo abra ono directori o.O
                if ($str{strlen($str) - 1} != '/' && $str{strlen($str) - 1} != chr(92)) {
                    if (@fopen($url, 'r') !== false) {
                        return true;
                    }
                }
            }
            return false;
        }

        public static function FileExists($url) {
            $str = trim($url);
            // si la ruta termina con '/' o '\' (92) no es un archivo, pero fopen puede que si lo abra ono directori o.O
            if ($str{strlen($str) - 1} != '/' && $str{strlen($str) - 1} != chr(92)) {
                if (fopen($url, 'r') !== false) {
                    return true;
                }
            }
            return false;
        }

        public static function is_utf8($string) {
            // From http://w3.org/International/questions/qa-forms-utf-8.html
            return preg_match('%^(?:
                      [\x09\x0A\x0D\x20-\x7E]            # ASCII
                    | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                    |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
                    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                    |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
                    |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
                    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                    |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
                )*$%xs', $string) == 1;
        }

        public static function upperCase($v) {
            return self::Upper($v);
        }

        public static function Upper($v) {
            //echo $v;
            $revert = false;
            if (Sys::is_utf8($v)) {
                $v = utf8_decode($v);
                $revert = true;
            }
            //echo $v;
            $to = array("ñ" => "Ñ", "á" => "Á", "é" => "É", "í" => "Í", "ó" => "Ó", "ú" => "Ú");
            $res = strtr($v, $to);
            //echo $res;
            if ($revert) {
                $res = utf8_encode($res);
            }
            //echo $res;
            return strtoupper($res);
        }

        public static function Lower($v) {
            $revert = false;
            if (Sys::is_utf8($v)) {
                $v = utf8_decode($v);
                $revert = true;
            }
            $to = array("Ñ" => "ñ", "Á" => "á", "É" => "é", "Í" => "í", "Ó" => "ó", "Ú" => "ú");
            $res = strtr($v, $to);
            if ($revert) {
                $res = utf8_encode($res);
            }
            return strtolower($res);
        }

        public static function ToHtml($v) {
            $to = array("\n" => "<br/>", " " => "&nbsp;", "\t" => str_repeat('&nbsp;', 7), "Á" => "&Aacute;", "É" => "&Eacute;", "Í" => "&Iacute;", "Ó" => "&Oacute;", "Ú" => "&Uacute;", "á" => "&aacute;", "é" => "&eacute;", "í" => "&iacute;", "ó" => "&uacute;", "ú" => "&uacute;");
            return strtr($v, $to);
        }

        public static function ZeroPad($v, $l = 6) {
            return str_pad($v, $l, '0', STR_PAD_LEFT);
        }

        public static function SerializeToJS($v) {
            return rawurlencode(json_encode($v));
        }

        public static function EscapeJSString($value) {
            $translator = array("\n" => "", "\r" => "", "'" => "\'");
            return strtr($value, $translator);
        }

        public static function IfIsNull($v, $default) {
            return is_null($v) ? $default : $v;
        }

        public static function IfIsEmpty($v, $default) {
            return (trim($v) == '') ? $default : $v;
        }

        public static function GetMonthName($i) {
            $m = array('', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
            return array_key_exists(intval($i), $m) ? $m[intval($i)] : '- undefined -';
        }

        /**
         * Devuelve el nombre del dia, sunday(dom) 1 to saturday(sab) 7
         * @param $i int: valor de 1 a 7, domingo 1
         */
        public static function GetDayName($i) {
            $d = array('', 'domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado');
            // array_key_exists(key), key solo admite valores de tipo int y string
            return array_key_exists(intval($i), $d) ? $d[$i] : '- undefined -';
        }

        // image utils
        public static function imageCreateFrom($filename, $type = NULL) {
            $imgtype = is_null($type) ? exif_imagetype($filename) : $type;
            switch ($imgtype) {
                case IMAGETYPE_GIF :
                    return imagecreatefromgif($filename);
                case IMAGETYPE_JPEG :
                    return imagecreatefromjpeg($filename);
                case IMAGETYPE_PNG :
                    $im = imagecreatefrompng($filename);
                    imagealphablending($im, false);
                    imagesavealpha($im, true);
                    return $im;
                default :
                    return false;
            }
        }

        public static function imageResize($filenameOrResource, $w, $h, $new_w, $new_h, $imgtype = NULL) {
            $type = $imgtype;
            if (is_resource($filenameOrResource)) {
                $res = $filenameOrResource;
                if (is_null($imgtype)) {
                    exit('Sys.imageResize: recurso de imagen requiere el parametro \'imgtype\'');
                }
            } elseif (is_string($filenameOrResource)) {
                $res = self::imageCreateFrom($filenameOrResource);
                if (is_null($imgtype)) {
                    $type = exif_imagetype($filenameOrResource);
                }
            } else {
                return FALSE;
            }

            $res2 = imagecreatetruecolor($new_w, $new_h);
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($res2, false);
                imagesavealpha($res2, true);
            }
            imagecopyresampled($res2, $res, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
            return $res2;
        }

        public static function imageOutput($resource, $type, $dest_filename = NULL, $cache = 0) {
            $mime = image_type_to_mime_type($type);
            header("Content-type: $mime");
            if ($cache == 0) {
                header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
            }
            switch ($type) {
                case IMAGETYPE_GIF :
                // no funka con NULL asi q se tiene que controlar la variable
                    return is_null($dest_filename) ? imagegif($resource) : imagegif($resource, $dest_filename);
                case IMAGETYPE_JPEG :
                    return imagejpeg($resource, $dest_filename, 100);
                case IMAGETYPE_PNG :
                    return imagepng($resource, $dest_filename, 9);
                default :
                    return false;
            }
        }

        /**
         * Ajusta las dimensiones de una imagen y escribe su contenido(output) o lo guardar en un archivo.
         * @param filename string: nombre del archivo
         * @param $max_w int: ancho maximo
         * @param $max_h int: alto maximo
         * @param dest_filename string: nombre del archivo destino (en este caso no esribe, solo guarda en archivo)
         * @param zoom_if_min bit: realizar zoom o agrandar si el tamano es menor que el maximo (0: false,1:true)
         * @param cache bit: si se se va esbribir en el encabezado los parametros del uso o no de cache
         * @return array: devuelve un array con la informacion array(new_w, new_h, changed, w_orig, h_orig, type, mime)
         */
        public static function imageAdjustAndOutput($filename, $max_w, $max_h, $dest_filename = NULL, $zoom_if_min = 1, $cache = 0) {
            list($newx, $newy, $changed, $x, $y, $type, $mime) = Sys::imageGetAdjustSize($filename, $max_w, $max_h, $zoom_if_min);
            $res_aux = Sys::imageCreateFrom($filename, $type);
            if ($changed == false) {
                $res = $res_aux;
            } else {
                $res = imagecreatetruecolor($newx, $newy);
                if ($type == IMAGETYPE_PNG) {
                    imagealphablending($res, false);
                    imagesavealpha($res, true);
                }
                imagecopyresampled($res, $res_aux, 0, 0, 0, 0, $newx, $newy, $x, $y);
            }
            Sys::imageOutput($res, $type, $dest_filename, $cache);
            //error_log("$filename, $dest_filename");
            imagedestroy($res);
            imagedestroy($res_aux);
        }

        /**
         * Retorna las dimensiones de la imagen, ademas del tipo y mime
         * @param filename string: nombre del archivo
         * @param $xmax int: ancho maximo
         * @param $ymax int: alto maximo
         * @param zoom_if_min bit: realizar zoom o agrandar si el tamano es menor que el maximo (0: false,1:true)
         * @return array: devuelve un array con la informacion array(new_w, new_h, changed, w_orig, h_orig, type, mime)
         */
        public static function imageGetAdjustSize($filename, $xmax, $ymax, $zoomIfMin = 0) {
            list($x, $y, $type, $mime) = self::imageGetSize($filename);
            // x = width, y = height
            $changed = true;
            if ($x <= $xmax && $y <= $ymax && $zoomIfMin == 0) {
                $newx = $x;
                $newy = $y;
                $changed = false;
            } else {
                if ($x >= $y) {
                    // horizontal
                    $newx = $xmax;
                    $newy = $newx * $y / $x;
                    if ($newy > $ymax) {
                        $newy_a = $newy;
                        $newy = $ymax;
                        $newx = $newy * $newx / $newy_a;
                    }
                } else {
                    // ajuste vertical a horizontal
                    $newy = $ymax;
                    $newx = $x / $y * $newy;
                    if ($newx > $xmax) {
                        $newx_a = $newx;
                        $newx = $xmax;
                        $newy = $newx * $newy / $newx_a;
                    }
                }
                $newx = floor($newx);
                $newy = floor($newy);
            }
            return array($newx, $newy, $changed, $x, $y, $type, $mime);
        }

        /**
         * Retorna las dimensiones de la imagen, ademas del tipo y mime
         * @param filename string: nombre del archivo
         * @return array: devuelve un array con la informacion array(w, h, type, mime)
         */
        public static function imageGetSize($filename) {
            $info = @getimagesize($filename);
            if ($info === false) {
                exit("Sys:imageGetSize: No se puede abrir el archivo '$filename'.");
            }
            return array(0 => $info[0], 1 => $info[1], 2 => $info[2], 3 => $info['mime']);
        }

        public static function IsImageFromExt($ext) {
            $filter = "jpg|jpeg|gif|png";
            $filterlist = explode("|", $filter);
            if (array_search(strtolower($ext), $filterlist) === false) {
                return false;
            }
            return true;
        }

        public static function GetFileExtension($filename) {
            $ext = strtolower(array_pop(explode('.', $filename)));
            $ext = strlen($ext) > 4 ? '' : $ext;
            return $ext;
        }

        // format
        public static function NFormat($v, $d = 2, $m=',') {
            return number_format($v, $d, '.', $m);
        }
        public static function NUnformat($v) {
            return str_replace(',', '', $v);
        }

        public static function DisplayInfReg($v) {
            $ir = explode(";", $v);
            foreach ($ir as $i => $str) {
                if (trim($str) == "")
                    continue;
                echo "<div class=\"fs-7\" style=\"border: 1px solid lightblue; margin-top: 1px; padding: 3px; 5px; background-color: #FFFBAE;\">";
                echo $str;
                echo "</div>";
            }
        }

        // session info
        public static function GetUserId() {
            return strtoupper(self::GetS('sys_user_id', 0));
        }

        public static function GetUserName() {
            return Sys::Upper(self::GetS('sys_user_name', ''));
        }

        public static function GetUserNickName() {
            return self::GetS('sys_user_nickname', '');
        }

        public static function GetUserType() {
            return self::GetS('sys_user_type', 0);
        }

        public static function GetUserIsAdmin() {
            return intval(self::GetS('sys_user_is_admin', 0), 10);
        }

        public static function GetUserTrabajadorId() {
            return self::GetS('sys_user_id_trabajador', 0);
        }

        public static function GetUserUniOrgId() {
            return self::GetS('sys_user_id_u_o', 0);
        }

        public static function GetUserEstablecimientoId() {
            return self::GetS('sys_user_establecimiento_id', '');
        }

        public static function GetUserPVentaId() {
            return self::GetS('sys_user_pventa_id', '');
        }

        public static function GetUserPrintServerActive() {
            return self::GetS('sys_user_psa', '0');
        }

        public static function CheckUserAction($key, $exitonfail = true) {
            if (self::GetUserIsAdmin() == true) {
                return true;
            } else {
                // si el key no existe, el acceso es libre!
                $exists = new PgQuery("SELECT nombre FROM sys.accion WHERE nombre='$key'", NULL, true);
                if ($exists -> recordCount == 0) {
                    return true;
                }
                if (is_null(self::$UserActions)) {
                    $userid = Sys::GetUserId();
                    $qlist = array("
                        SELECT m.nombre 
                        FROM sys.accion a
                        JOIN sys.usuario_accion ua ON ua.id_accion=a.id_accion AND ua.id_usuario=$userid
                        WHERE ua.estado = 1 AND a.estado = 1");
                    $qp = new PgQuery("
                        SELECT p.id_usuario FROM sys.usuario p
                        JOIN sys.usuario_perfil up ON up.id_perfil=p.id_usuario AND up.id_usuario=$userid AND up.estado = 1
                        WHERE p.estado = 1 AND p.is_profile = 1 AND up.estado                    
                        ", NULL, true, true);
                    while ($rp = $qp -> Read()) {
                        $qlist[] = "
                            SELECT a.nombre 
                            FROM sys.accion a
                            JOIN sys.usuario_accion ua ON ua.id_accion=a.id_accion AND ua.id_usuario={$rp['id_usuario']}
                            WHERE ua.estado = 1 
                            ";
                    }
                    $sql = "((" . implode(') UNION ALL (', $qlist) . ")) as _query";
                    $sql = "SELECT * FROM $sql GROUP BY nombre ORDER BY nombre";
                    //echo "$sql"; //return true;
                    $q = new PgQuery($sql, NULL, true, true);
                    self::$UserActions = $q -> GetColumnValues('nombre');
                    //print_r(self::$UserActions);
                }
                foreach (self::$UserActions as $a) {
                    if ("$a" == "$key") {
                        return true;
                    }
                }
                if ($exitonfail == true) {
                    exit('Usted no esta autorizado para realizar esta accion.');
                } else {
                    return false;
                }
            }
        }

        public static function GetPeriodo() {
            return self::GetS('sys_periodo', date('Y'));
        }

        public static function GetClientIP() {
            if (@$_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
                $client_ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : ((!empty($_ENV['REMOTE_ADDR'])) ? $_ENV['REMOTE_ADDR'] : "unknown");
                $entries = explode('[, ]', $_SERVER['HTTP_X_FORWARDED_FOR']);
                reset($entries);
                while (list(, $entry) = each($entries)) {
                    $entry = trim($entry);
                    if (preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $entry, $ip_list)) {
                        $private_ip = array('/^0\./', '/^127\.0\.0\.1/', '/^192\.168\..*/', '/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/', '/^10\..*/');
                        $found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);
                        if ($client_ip != $found_ip) {
                            $client_ip = $found_ip;
                            break;
                        }
                    }
                }
            } else {
                $client_ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : ((!empty($_ENV['REMOTE_ADDR'])) ? $_ENV['REMOTE_ADDR'] : "unknown");
            }
            return $client_ip;
        }

        public static function GetUserIP() {
            $ip = trim(self::GetS('sys_user_ip', self::GetClientIP()));
            return ($ip == 'localhost') ? '127.0.0.1' : $ip;
        }

        public static function GetUserStation() {
            $s = self::GetS('sys_user_station', '');
            return $s;
        }

        public static function GetUserMAC() {
            return trim(self::GetS('sys_user_mac', ''));
        }

        public static function CheckIPIntranet($ip = NULL) {
            if (is_null($ip))
                $ip = self::GetClientIP();
            if (strpos($ip, "192.168.") === 0 || strpos($ip, "127.0.0.1") === 0 || strpos($ip, "145.130.") === 0) {
                return true;
            }
            return false;
        }

        public static function LogRequest() {
            $dr = new PgDataRow('public.log');
            $dr -> Create();
            $dr -> Set('user', Sys::GetUserName());
            $dr -> Set('ip', Sys::GetClientIP());
        }

        public static function RegisterInSession($values) {
            foreach ($values as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }

        public static function IsAjaxRequest() {
            return trim(strtolower(@$_SERVER['HTTP_X_REQUESTED_WITH'])) == strtolower('XMLHttpRequest');
        }

        public static function CheckSessionEnabled() {
            if (defined('sys_checksession')) {
                if (sys_checksession === false) {
                    return false;
                }
            }
            return true;
        }

        public static function CheckSession() {
            $_SESSION['time'] = time();
            //var_dump($_SESSION);
            if (Sys::GetS('active', 0) == 0) {
                if (Sys::IsAjaxRequest()) {
                    exit('La sesion ha expirado, vuelva a ingresar. :)');
                } else {
                    $path = 'login.php';
                    if (defined('sys_login_path')) {
                        $path = sys_login_path;
                    }
                    header("Location: $path");
                    exit ;
                }
            }
        }

        public static function GetFileUrl($t, $p, $f, $params, $reldir = '') {
            return $reldir . "getfile.php?t=$t&p=$p&f=$f&$params";
        }

        public static function GenerateFileName($ext = '') {
            $time = time();
            // generate unique file name
            $alist = range('A', 'Z');
            $nlist = range(1, 9);
            $mlist = array_merge($alist, $nlist);
            shuffle($mlist);
            $clist = implode('', $mlist);
            $code = substr($clist, 0, (12 - strlen($time))) . $time;
            $filename = $code . '.' . $ext;
            return $filename;
        }

    }

    if (Config::$UseDB) {
        if (!Sys::DbConnect()) {
            exit("Sys: Error en la coneccion con la base de datos");
        }
        PgProvider::$DbConnection = Sys::GetConnection();
    }
    if (Sys::CheckSessionEnabled()) {
        Sys::CheckSession();
    }

    /*if (Sys::IsAjaxRequest()) {
		// si es ajax, cambiar al directorio principal, asi todo se referencia como si estuviera en el directorio principal
		chdir(dirname(__FILE__));
	}*/
}
?>