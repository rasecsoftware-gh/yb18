<?php
$RJ256_key = 'lkirwf897+22#bbtrm8814z5qq=498j5'; // 32 * 8 = 256 bit key
$RJ256_iv = '741952hheeyy66#cs!9hjv887mxx7@8y'; // 32 * 8 = 256 bit iv

function RJ256_decrypt($string_to_decrypt) {
	global $RJ256_key, $RJ256_iv;
    $string_to_decrypt = base64_decode($string_to_decrypt);

    $rtn = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $RJ256_key, $string_to_decrypt, MCRYPT_MODE_CBC, $RJ256_iv);

    $rtn = rtrim($rtn, "\0\4");

    return ($rtn);
}


function RJ256_encrypt($string_to_encrypt) {
	global $RJ256_key, $RJ256_iv;
    $rtn = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $RJ256_key, $string_to_encrypt, MCRYPT_MODE_CBC, $RJ256_iv);

    $rtn = base64_encode($rtn);

    return ($rtn);
}
?>