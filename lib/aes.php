<?
// Encrypt Function
function aes_encode($encrypt, $mc_key) {
	$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
	$passcrypt = trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mc_key, trim($encrypt), MCRYPT_MODE_ECB, $iv));
	$encode = base64_encode($passcrypt);
	return $encode;
}

// Decrypt Function
function aes_decode($decrypt, $mc_key) {
	$decoded = base64_decode($decrypt);
	$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
	//$iv = substr($decoded, 0, strlen($iv));
	$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, ($mc_key), $decoded, MCRYPT_MODE_ECB, $iv));
	return $decrypted;
}
function aes_decode2($data, $key) {
	# Inicializa el modulo de cifrado
	$td = mcrypt_module_open('rijndael-256', '', 'ecb', '');
	
	# Crea el vector de inicializacion y establece el tamaño de la clave (aleatorio)
	$vi = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	
	# Inicializa el cifrado
	mcrypt_generic_init($td, base64_decode($key), ($vi));
	
	# Descifra los datos
	$ddata = mdecrypt_generic($td, utf8_encode($data));
	
	# Termina el manejador de descifrado y cierra el modulo
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return $ddata;
}
?>