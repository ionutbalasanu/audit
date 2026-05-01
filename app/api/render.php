<?php
declare(strict_types=1);

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
echo json_encode([
    'error' => 'legacy_endpoint_disabled',
    'message' => 'Use /app/public/index.php route /api/render instead.',
], JSON_UNESCAPED_UNICODE);
