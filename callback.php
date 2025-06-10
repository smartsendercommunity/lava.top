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
$headers = getallheaders();
foreach ($headers as $key => $value) {
  if (strtolower($key) == "x-api-key") {
    $authHeader = $value;
  }
}

$log["input"] = [
  "body" => $input,
  "header" => $headers
];

if ($authHeader != $lavaCallback) {
  $result["state"] = false;
  $result["error"]["message"][] = "unauthorized";
  $log["result"] = $result;
  // http_response_code(401);
  $result["log"] = send_request($logUrl, [], "POST", $log);
  echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

if (file_exists("invoices/invoice-" . $input["contractId"])) {
  $invoiceData = json_decode(file_get_contents("invoices/invoice-" . $input["contractId"]), true);
} else {
  $result["state"] = false;
  $result["error"]["message"][] = "this invoice is not found";
  $log["result"] = $result;
  // http_response_code(404);
  $result["log"] = send_request($logUrl, [], "POST", $log);
  echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

if ($input["eventType"] == "payment.success") {
  // Успішна оплата
  if ($input["status"] == "subscription-active" && $invoiceData["firstAction"] != NULL) {
    // Перший платіж по підписці
    $action = $invoiceData["firstAction"];
  } else {
    // Продукт
    $action = $invoiceData["action"];
  }
} else if ($input["eventType"] == "subscription.recurring.payment.success") {
  // Успішне продовження підписки
  if ($input["status"] == "subscription-active" && $invoiceData["nextAction"] != NULL) {
    $action = $invoiceData["nextAction"];
  } else {
    // Продукт
    $action = $invoiceData["action"];
  }
} else if ($input["eventType"] == "subscription.recurring.payment.failed") {
  // Помилка продовження підписки
  if ($input["status"] == "subscription-failed" && $invoiceData["nextFailedAction"] != NULL) {
    $action = $invoiceData["nextFailedAction"];
  } else if ($invoiceData["failedAction"] != NULL) {
    // Помилка оплати
    $action = $invoiceData["failedAction"];
  }
} else if ($input["eventType"] == "subscription.cancelled") {
  // Скасування підписки
  if ($invoiceData["cancelAction"] != NULL) {
    $action = $invoiceData["cancelAction"];
  }
} else if ($input["eventType"] == "payment.failed") {
  // Помилка оплати
  if ($invoiceData["failedAction"] != NULL) {
    $action = $invoiceData["failedAction"];
  }
}

if ($action != NULL) {
  $trigger = json_decode(send_request("https://api.smartsender.com/v1/contacts/" . $invoiceData["userId"] . "/fire", [
    "Authorization: Bearer " . $invoiceData["ssToken"],
  ], "POST", [
    "name" => $action
  ]), true);
  $result["action"] = $action;
  $result["userId"] = $invoiceData["userId"];
  $result["trigger"] = $trigger;
} else {
  $result["action"] = "not trigger";
}

$log["result"] = $result;
$result["log"] = send_request($logUrl, [], "POST", $log);


echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
