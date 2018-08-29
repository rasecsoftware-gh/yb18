<?php
	require_once 'sys.php';
	
	PrintManager::Listen();
	
	class PrintManager {  
		public static function Listen() {
			if (!defined("_DISABLED_CLASS_LISTEN_")) {
				Sys::CallClassMethodFromRequest(get_class());
			}
		} 
		public static function DoPrint() {
			$url = Sys::GetP('url','');
            if (Sys::GetUserPrintServerActive() == 1) {
                $geturl = Config::GetReportUrl()."print?url=".$url;
                //echo $geturl;
                $result = @file_get_contents($geturl);
                if ($result === false) {
                    echo "<script>sys.message('La impresion ha fallado, posiblemente no este activo el servidor de impresion.');</script>";
                } else {
                    if ($result == 'ok') {
                        echo 'ok';
                    } else {
                        echo $result;   
                    }
                }
            } else {
               echo "<script>window.open('$url', '_blank');</script>"; 
            }
		}
	}
?>