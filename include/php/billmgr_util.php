<?php
date_default_timezone_set("UTC");
$log_file = fopen("/usr/local/mgr5/var/". __MODULE__ .".log", "a");
$default_xml_string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<doc/>\n";

function XMLToArray($xmlObject) {
	if (is_string($xmlObject)) return $xmlObject;
	$out = array();
	foreach ((array) $xmlObject as $index => $node) $out[$index] = XMLToArray($node);
	return $out;
}

function Debug($str) {
	global $log_file;
	fwrite($log_file, date("M j H:i:s") ." [". getmypid() ."] ". __MODULE__ ." \033[1;33mDEBUG ". $str ."\033[0m\n");
}

function DebugArray($arr) {
	global $log_file;
	fwrite($log_file, print_r($arr, true));
}

function Error($str) {
	global $log_file;
	fwrite($log_file, date("M j H:i:s") ." [". getmypid() ."] ". __MODULE__ ." \033[1;31mERROR ". $str ."\033[0m\n");
}

function tmErrorHandler($errno, $errstr, $errfile, $errline) {
	global $log_file;
	Error($errno .": ". $errstr .". In file: ". $errfile .". On line: ". $errline);
	return true;
}
set_error_handler("tmErrorHandler");

function tmExceprionHandler($exception) {
	global $log_file;
	Error($exception->getMessage());
	return true;
}
set_exception_handler("tmExceprionHandler");

function LocalQuery($function, $param, $auth = NULL) {
	$cmd = "/usr/local/mgr5/sbin/mgrctl -m billmgr -o xml " . escapeshellarg($function) . " ";
	foreach ($param as $key => $value) {
		$cmd .= escapeshellarg($key) . "=" . escapeshellarg($value) . " ";
	}
	if (!is_null($auth)) {
		$cmd .= " auth=" . escapeshellarg($auth);
	}
	$out = array();
	exec($cmd, $out);
	$out_str = "";
	foreach ($out as $value) {
		$out_str .= $value . "\n";
	}
	//Debug("[LOCAL QUERY FUNCTION] mgrctl out: ". $out_str);
	return simplexml_load_string($out_str);
}

function fnQuery($url, $param, $requesttype = "POST") {
	//Debug("HttpQuery url: " . $url);
	//Debug("Request: " . http_build_query($param));

	if ($requesttype == "PUT") {

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

		if (count($param) > 0) {
			$url_params = preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($param));
			curl_setopt($curl, CURLOPT_POSTFIELDS, $url_params);
		}

	} else if ($requesttype == "DELETE") {

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

		if (count($param) > 0) {
			$url_params = preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($param));
			curl_setopt($curl, CURLOPT_POSTFIELDS, $url_params);
		}

	} else if ($requesttype == "POST") {

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($curl, CURLOPT_POST, 1);

		if (count($param) > 0) {
			$url_params = preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($param));
			curl_setopt($curl, CURLOPT_POSTFIELDS, $url_params);
		}

	} else {

		$curl = curl_init($url."?".http_build_query($param));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($curl, CURLOPT_HTTPGET, 1);
	}

	$out = curl_exec($curl) or die(curl_error($curl));
	//Debug("[HTTPQUERY FUNCTION] HttpQuery out: " . $out);
	curl_close($curl);
	return $out;
}

function HttpQuery($url, $param, $requesttype = "POST", $username = "", $password = "", $header = array("Accept: application/xml")) {
	//Debug("HttpQuery url: " . $url);
	//Debug("Request: " . http_build_query($param));
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	if ($requesttype == "DELETE" || $requesttype == "HEAD") {
		curl_setopt($curl, CURLOPT_NOBODY, 1);
	}
	if ($requesttype != "POST" && $requesttype != "GET") {
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $requesttype);
	} elseif ($requesttype == "POST") {
		curl_setopt($curl, CURLOPT_POST, 1);
	} elseif ($requesttype == "GET") {
		curl_setopt($curl, CURLOPT_HTTPGET, 1);
	}
	if (count($param) > 0) {
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
	}
	if (count($header) > 0) {
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	}
	if ($username != "" || $password != "") {
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
	}
	$out = curl_exec($curl) or die(curl_error($curl));
	//Debug("[HTTPQUERY FUNCTION] HttpQuery out: " . $out);
	curl_close($curl);
	return $out;
}

function CgiInput($skip_auth = false) {
	if (!$skip_auth) {
		$input = $_SERVER["QUERY_STRING"];
	} else {
		if ($_SERVER["REQUEST_METHOD"] == 'POST'){
			$input = file_get_contents ("php://stdin",null,null,0,$_SERVER['CONTENT_LENGTH']);
		} elseif ($_SERVER["REQUEST_METHOD"] == 'GET'){
			$input = $_SERVER["QUERY_STRING"];
		}
	}

	$param = array();
	parse_str($input, $param);
	if ($skip_auth == false && (!array_key_exists("auth", $param) || $param["auth"] == "")) {
		if (array_key_exists("billmgrses5", $_COOKIE)) {
			$cookies_bill = $_COOKIE["billmgrses5"];
			$param["auth"] = $cookies_bill;
		} elseif (array_key_exists("HTTP_COOKIE", $_SERVER)) {
			$cookies = explode("; ", $_SERVER["HTTP_COOKIE"]);
			foreach ($cookies as $cookie) {
				$param_line = explode("=", $cookie);
				if (count($param_line) > 1 && $param_line[0] == "billmgrses5") {
					$cookies_bill = explode(":", $param_line[1]);
					$param["auth"] = $cookies_bill[0];
				}
			}
		}
		//Debug("[CgiInput FUNCTION] auth: " . $param["auth"]);
	}
	if ($skip_auth == false) {
		//Debug("[CgiInput FUNCTION] auth: " . $param["auth"]);
	}
	Debug('CgiInput: '.$input.print_r($param,true));

	return $param;
}

function ClientIp() {
	$client_ip = "";
	if (array_key_exists("HTTP_X_REAL_IP", $_SERVER)) {
		$client_ip = $_SERVER["HTTP_X_REAL_IP"];
	}
	if ($client_ip == "" && array_key_exists("REMOTE_ADDR", $_SERVER)) {
		$client_ip = $_SERVER["REMOTE_ADDR"];
	}
	//Debug("client_ip: " . $client_ip);
	return $client_ip;
}

function RandomStr($size = 8) {
	$chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$chars_size = strlen($chars);
	$result = '';
	for ($i = 0; $i < $size; $i++) {
		$result .= $chars[rand(0, $chars_size - 1)];
	}
	return $result;
}

class DB extends mysqli {
	public function __construct($host, $user, $pass, $db) {
		parent::init();
		if (!parent::options(MYSQLI_INIT_COMMAND, "SET AUTOCOMMIT = 1"))
			throw new Exception("MYSQLI_INIT_COMMAND Fail");
		if (!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5))
			throw new Exception("MYSQLI_OPT_CONNECT_TIMEOUT Fail");
		if (!parent::real_connect($host, $user, $pass, $db))
			throw new Exception("Connection ERROR. ".mysqli_connect_errno().": ".mysqli_connect_error());
		//Debug("MySQL connection established");
	}
	public function __destruct() {
		parent::close();
		//Debug("MySQL connection closed");
	}
}

function dbConnection() {
	$param = LocalQuery('paramlist', array());
	$result = $param->xpath('//elem/*');
	$param_map = array();
	$param_map['DBHost'] = 'localhost';
	$param_map['DBUser'] = 'root';
	$param_map['DBPassword'] = '';
	$param_map['DBName'] = 'billmgr';
	foreach ($result as $node) {
		$param_map[$node->getName()] = (string) $node;
	}
	$db = new DB($param_map['DBHost'], $param_map['DBUser'], $param_map['DBPassword'], $param_map['DBName']);
	$db->set_charset('utf8');
	return $db;
}

?>