<?php
// Router pentru serverul CLI: servește fișierele reale, altfel index.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
  return false; // lasă serverul să livreze fișierul static
}

require __DIR__ . '/index.php';
