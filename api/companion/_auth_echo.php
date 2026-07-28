<?php
header("Content-Type: application/json");
echo json_encode([
  "AUTH_USER" => $_SERVER["PHP_AUTH_USER"] ?? null,
  "AUTH_PW_SET" => isset($_SERVER["PHP_AUTH_PW"]),
  "HTTP_AUTHORIZATION" => $_SERVER["HTTP_AUTHORIZATION"] ?? ($_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? null),
  "X_API_KEY" => $_SERVER["HTTP_X_API_KEY"] ?? null,
], JSON_PRETTY_PRINT);
