<?php
$entry = __DIR__ . '/app/public/index.php';
if (!is_file($entry)) {
  http_response_code(500);
  echo "App entry not found";
  exit;
}

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/app/public';
require $entry;
