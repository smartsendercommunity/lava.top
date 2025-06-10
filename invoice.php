<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

include("config.php");

$result["state"] = true;
$input = json_decode(file_get_contents('php://input'), true);

if ($input["email"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'email' is required";
} else {
  if (!filter_var($input["email"], FILTER_VALIDATE_EMAIL)) {
    $result["state"] = false;
    $result["error"]["message"][] = "'email' is not valid";
  }
}
if ($input["ssToken"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'ssToken' is required";
}
if ($input["userId"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'userId' is required";
}
if ($input["action"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'action' is required";
}
if ($input["offerId"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'offerId' is required";
}
if ($input["currency"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'currency' is required";
} else if (!in_array($input["currency"], ["USD", "EUR", "RUB"])) {
  $result["state"] = false;
  $result["error"]["message"][] = "'currency' must be one of: USD, EUR, RUB";
}
if ($result["state"] == false) {
  http_response_code(422);
  echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

$invoice["email"] = $input["email"];
$invoice["offerId"] = $input["offerId"];
$invoice["currency"] = $input["currency"];
if ($input["language"] != NULL && in_array($input["language"], ["EN", "RU", "ES"])) {
  $invoice["buyerLanguage"] = $input["language"];
}
if ($input["paymentMethod"] != NULL && in_array($input["paymentMethod"], ["BANK131", "UNLIMINT", "PAYPAL", "STRIPE"])) {
  $invoice["paymentMethod"] = $input["paymentMethod"];
}
if ($input["utm_source"] != NULL) {
  $invoice["clientUtm"]["utm_source"] = $input["utm_source"];
}
if ($input["utm_medium"] != NULL) {
  $invoice["clientUtm"]["utm_medium"] = $input["utm_medium"];
}
if ($input["utm_campaign"] != NULL) {
  $invoice["clientUtm"]["utm_campaign"] = $input["utm_campaign"];
}
if ($input["utm_term"] != NULL) {
  $invoice["clientUtm"]["utm_term"] = $input["utm_term"];
}
if ($input["utm_content"] != NULL) {
  $invoice["clientUtm"]["utm_content"] = $input["utm_content"];
}
if ($input["periodicity"] != NULL && in_array($input["periodicity"], ["ONE_TIME", "MONTHLY", "PERIOD_90_DAYS", "PERIOD_180_DAYS", "PERIOD_YEAR"])) {
  $invoice["periodicity"] = $input["periodicity"];
}

$response = json_decode(send_request("https://gate.lava.top/api/v2/invoice", ["X-Api-Key: " . $lavaToken], "POST", $invoice), true);

// $result["lava"] = [
//   "url" => "https://gate.lava.top/api/v2/invoice",
//   "send" => $invoice,
//   "header" => ["X-Api-Key" => $lavaToken],
//   "response" => $response
// ];

if ($response["id"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "failed create invoice";
  $result["error"]["lava"] = $response;
  http_response_code(422);
  echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

$result["paymentUrl"] = $response["paymentUrl"];
file_put_contents("invoices/invoice-" . $response["id"], json_encode([
  "userId" => $input["userId"],
  "ssToken" => $input["ssToken"],
  "action" => $input["action"],
  "firstAction" => $input["firstAction"],
  "nextAction" => $input["nextAction"],
  "failedAction" => $input["failedAction"],
  "nextFailedAction" => $input["nextFailedAction"],
  "cancelAction" => $input["cancelAction"],
  "paymentUrl" => $response["paymentUrl"],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));


$url = "https://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
$url = explode("?", $url);
$url = $url[0];
if (substr($url, -1) != "/") {
  $url = $url . "/";
}
$result["shortUrl"] = $url . "payment.php?" . http_build_query([
  "identifier" => $response["id"]
]);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
