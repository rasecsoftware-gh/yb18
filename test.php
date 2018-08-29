<?
	require 'sys.php';
	$t = Sys::getTimeStamp('26/08/2013 14:31:01');
	var_dump($t);
    exit;
    $server = "http://127.0.0.1:88/print?url=";
    $url = "http://localhost/yago/modules/registro/print.php?id=0101-00000030";
    //echo urlencode("http://localhost/yago/modules/registro/print.php?id=0101-00000030");
    $geturl = $server.urlencode($url);
    echo $geturl;
    $result = @file_get_contents($geturl);
    exit;
	require 'sys.php';
	//echo Config::GetReportUrl()."printers";
	$printers = @file_get_contents(Config::GetReportUrl()."printers");
	//echo $printers;
	if ($printers!==false) {
		$list = explode('|', $printers);
	}
	var_dump($list);
	exit;
    echo strtotime('2013-08-18 23:59:59');
    echo "<br/>";
    echo strtotime('2013-08-18');
?>