#!/usr/bin/php
<?php

set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/local/mgr5/include/php");
define('__MODULE__', "pmcrystalpay");

require_once 'billmgr_util.php';

$longopts  = array(
	"command:",
	"payment:",
	"amount:",
);

$options = getopt("", $longopts);

try {
	$command = $options['command'];
	Debug("command " . $options['command']);

	if ($command == "config") {
		$config_xml = simplexml_load_string($default_xml_string);
		$feature_node = $config_xml->addChild("feature");

		$feature_node->addChild("redirect", "on"); // If redirect supported
		$feature_node->addChild("notneedprofile", "on"); // If notneedprofile supported
		//$feature_node->addChild("pmvalidate", "on");

		$param_node = $config_xml->addChild("param");

		$param_node->addChild("payment_script", "/mancgi/crystalpaypayment.php");

		echo $config_xml->asXML();
	} else {
		throw new ISPErrorException("Unknown command / Неизвестная команда");
	}
} catch (Exception $e) {
	echo $e;
}

?>