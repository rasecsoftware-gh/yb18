<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// I blantantly stole, tweaked and happily used this code from: 
// Lord of Ports http://www.experts-exchange.com/M_1736399.html


$ky = 'lkirwf897+22#bbtrm8814z5qq=498j5'; // 32 * 8 = 256 bit key
$iv = '741952hheeyy66#cs!9hjv887mxx7@8y'; // 32 * 8 = 256 bit iv

$text = "80672815";

$from_vb = "DcnrBkjrcaUubLQQiIr2hsu9GSf3B/L6MteNNtZMqF0=";   // enter value from vb.net app here to test


$etext = encryptRJ256($ky, $iv, $text);
$dtext = decryptRJ256($ky, $iv, $etext);
$vtext = decryptRJ256($ky, $iv, $from_vb);

echo "<HR>orignal string: $text";
echo "<HR>encrypted in php: $etext";
echo "<HR>decrypted in php: $dtext";
echo "<HR>encrypted in vb: $from_vb";
echo "<HR>from vb decrypted in php: $vtext"; echo strlen($vtext);
echo "<HR>If you like it say thanks! richard dot varno at gmail dot com";


exit;



function decryptRJ256($key,$iv,$string_to_decrypt)
{

    $string_to_decrypt = base64_decode($string_to_decrypt);

    $rtn = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string_to_decrypt, MCRYPT_MODE_CBC, $iv);

    $rtn = rtrim($rtn, "\0\4");

    return($rtn);

}


function encryptRJ256($key,$iv,$string_to_encrypt)
{

    $rtn = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string_to_encrypt, MCRYPT_MODE_CBC, $iv);

    $rtn = base64_encode($rtn);

    return($rtn);

}    

?>
