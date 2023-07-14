#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/local/mgr5/include/php");
define('__MODULE__', "pmcrystalpay");

require_once 'billmgr_util.php';

echo "Content-Type: text/html; charset=utf-8\n\n";

$param = CgiInput();

if (empty($param['elid'])) {
	throw new Error("no elid");
}

$info = LocalQuery("payment.info", array("elid" => $param["elid"]));
$payment = $info->payment[0];

$elid = (string)$payment->id;
$description = (string)$payment->description;

$amount = intval($payment->paymethodamount);
$amount_currency = (string)$payment->currency[1]->iso;

$payer_details = (string)$payment->useremail;

$auth_login = (string)$payment->paymethod[1]->AUTH_LOGIN;
$auth_secret = (string)$payment->paymethod[1]->AUTH_SECRET;
$lifetime = intval((string)$payment->paymethod[1]->LIFETIME);

$redirect_url = (string)$payment->manager_url . "?func=payment.success&elid=" . $elid . "&module=" . __MODULE__;

$callback_url = parse_url((string)$payment->manager_url);
$callback_url = $callback_url['scheme'] . '://' . $callback_url['host'] . ($callback_url['port'] ? ':' . $callback_url['port'] : "");
$callback_url = $callback_url . "/mancgi/crystalpayresult.php";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.crystalpay.io/v2/invoice/create/");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["auth_login" => $auth_login, "auth_secret" => $auth_secret, "amount" => $amount, "amount_currency" => $amount_currency, "type" => "purchase", "description" => $description, "redirect_url" => $redirect_url, "callback_url" => $callback_url, "extra" => $elid, "payer_details" => $payer_details, "lifetime" => $lifetime]));

$result = curl_exec($ch);
curl_close($ch);

$resultArray = json_decode($result, true);

if (!$resultArray) {
	Error("No result in response!");
	die("No result in response!");
}

if ($resultArray["error"]) {
	Error("Errors in response: " . implode("; ", $resultArray["errors"]));
	die("Errors in response!");
}

echo '
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
<form name="payment_redirect" method="GET" action="' . $resultArray["url"] . '">
<input type="hidden" name="i" value="' . $resultArray["id"] . '">
</form>
<script>document.payment_redirect.submit();</script>
</body>
</html>';
?>