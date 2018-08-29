<?php
	function generar_clave($longitud) {
		$clave = "";
        $valores = array(); // 61
        $j = 64;
        for ($i = 0; $i<=61; $i++) {
            $j++;
            $valores[$i] = chr($j);
            switch ($j) {
	        	case 90: $j = 96; break;
				case 122: $j = 47; break;
            }
        }
        for ($i = 1; $i<=$longitud; $i++) {
            $clave = $clave . $valores[rand(0, 61)];
        }
        return $clave;
	}
	echo generar_clave(20);
	exit;

	require 'lib/aes.php';
	
	$pass = 'tleEaEiT8sLvkXJxfImo8RezMe/6eBocnX2LLje3vRoA';
	//$pass = 'Vk9Wqr2ezypjKuuseyhnFLKCQ7PUvY7N6rDVk+tUldw=';
	//$pass = '12345678';
	$key = 'ASDertJTOfHDNqwPQAEbUYLn80sEyUMf';
	//$key = base64_encode('acm1pt');
	//echo "key: $key <br/>";
	$val = aes_decode($pass, $key);
	//$val = aes_encode($pass, $key);
	echo $val;
?>