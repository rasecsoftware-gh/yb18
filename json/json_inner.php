<?php

require_once 'JSON/JSON.php';

function json_encode($arg)
{
	global $services_json;
	if (!isset($services_json)) {
		$services_json = new Services_JSON();
	}
	return $services_json->encode($arg);
}

function json_decode($arg, $assoc=false)
{
	global $services_json;
	if (!isset($services_json)) {
		$services_json = new Services_JSON();
	}
	if ($assoc == true) {
		$services_json->use = SERVICES_JSON_LOOSE_TYPE;
	} else {
		$services_json->use = 0;
	}
	return $services_json->decode($arg);
}

?>
