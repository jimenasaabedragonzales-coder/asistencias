<?php
$backendRoot = dirname(__DIR__);
$envFile = $backendRoot . DIRECTORY_SEPARATOR . '.env';
$config = parse_ini_file($envFile);

if ($config === false) {
	throw new RuntimeException('No se pudo leer el archivo de configuración: ' . $envFile);
}

define('HOST', $config['DB_HOST'] ?? '');
define('DATABASE', $config['DB_NAME'] ?? '');
define('USERNAME', $config['DB_USER'] ?? '');
define('PASSWORD', $config['DB_PASSWORD'] ?? '');
define('PORT', $config['DB_PORT'] ?? 3306);
$charset = trim((string) ($config['DB_CHARSET'] ?? 'utf8mb4'));
$charset = trim(preg_replace('/^charset=/i', '', $charset));
if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
    throw new RuntimeException('El charset configurado para la base de datos no es válido.');
}
define('CHARSET', $charset);
$sslCa = trim((string) ($config['DB_SSL_CA'] ?? ''), " \t\n\r\0\x0B\"'");

if ($sslCa !== '' && !preg_match('/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\|\/)/', $sslCa)) {
    $sslCa = $backendRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sslCa);
}

define('SSL_CA', $sslCa);
