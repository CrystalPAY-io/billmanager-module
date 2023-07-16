#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/local/mgr5/include/php");
define('__MODULE__', "pmcrystalpay");
require_once 'billmgr_util.php';

echo "Content-Type: text/html; charset=utf-8\n\n";

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    $size = $_SERVER["CONTENT_LENGTH"];
    if ($size == 0) {
        $size =    $_SERVER["HTTP_CONTENT_LENGTH"];
    }
    if (!feof(STDIN)) {
        $input = fread(STDIN, $size);
    }
} else {
    Debug("Method not allowed!");
    die('Method not allowed!');
}

if (!function_exists('hash_equals')) {
    function hash_equals($str1, $str2)
    {
        if (strlen($str1) != strlen($str2)) return false;
        $res = $str1 ^ $str2;
        $ret = 0;
        for ($i = strlen($res) - 1; $i >= 0; $i--) {
            $ret |= ord($res[$i]);
        }
        return !$ret;
    }
}

$content = json_decode($input, true);

if (!$content) {
    Error("No content in callback!");
    die("No content in callback!");
}

if ($content["state"] != "payed") {
    die();
}

$elid = $content["extra"];

if (!$elid) {
    Error("No extra(elid) in callback!");
    die("No extra(elid) in callback!");
}

$info = LocalQuery("payment.info", array("elid" => $elid));
$payment = $info->payment[0];

if (!$payment->id) {
    Error("Payment not found!");
    die("Payment not found!");
}

$signature = $content["signature"];

$id = $content["id"];
$url = $content["url"];
$salt = (string)$payment->paymethod[1]->SALT;

$hash = sha1($id . ":" . $salt);

if (!hash_equals($hash, $signature)) { //Безопасное сравнение подписи callback
    Error("Invalid signature!");
    exit("Invalid signature!");
}

LocalQuery("payment.setpaid", array("elid" => $elid, 'externalid' => $id, 'info' => $url));
?>
