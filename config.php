<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

$lavaToken = "";
$lavaCallback = "";
$logUrl = "https://webhook.site/";

if (!file_exists("invoices")) {
  mkdir("invoices", 0777, true);
}

function send_request($url, $header = [], $type = "GET", $param = [], $raw = "json")
{
  $descriptor = curl_init($url);
  if ($type != "GET") {
    if ($raw == "json") {
      curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
      $header[] = "Content-Type: application/json";
    } else if ($raw == "form") {
      curl_setopt($descriptor, CURLOPT_POSTFIELDS, http_build_query($param));
      $header[] = "Content-Type: application/x-www-form-urlencoded";
    } else {
      curl_setopt($descriptor, CURLOPT_POSTFIELDS, $param);
    }
  }
  $header[] = "User-Agent: M-Soft Integration(https://mufiksoft.com)";
  curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($descriptor, CURLOPT_HTTPHEADER, $header);
  curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
  $itog = curl_exec($descriptor);
  curl_close($descriptor);
  return $itog;
}
