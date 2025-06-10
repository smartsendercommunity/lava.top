<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);


$result["state"] = true;
if ($_GET["identifier"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'identifier' is required";
} else if (!file_exists("invoices/invoice-" . $_GET["identifier"])) {
  $result["state"] = false;
  $result["error"]["message"][] = "'identifier' is not found";
} else {
  $invoiceData = json_decode(file_get_contents("invoices/invoice-" . $_GET["identifier"]), true);
  header("Location: " . $invoiceData["paymentUrl"]);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
