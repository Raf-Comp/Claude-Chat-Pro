<?php
require_once 'claude-chat-pro.php';

$diagnostics = new ClaudeChatPro\Includes\Diagnostics();

echo "=== Sprawdzanie wymagań systemowych ===\n";
$system_requirements = $diagnostics->check_system_requirements();
print_r($system_requirements);

echo "\n=== Sprawdzanie połączeń API ===\n";
$api_connections = $diagnostics->check_api_connections();
print_r($api_connections);

echo "\n=== Sprawdzanie tabel bazy danych ===\n";
$database_tables = $diagnostics->check_database_tables();
print_r($database_tables);

echo "\n=== Sprawdzanie uprawnień plików ===\n";
$file_permissions = $diagnostics->check_file_permissions();
print_r($file_permissions);

echo "\n=== Pełny raport diagnostyczny ===\n";
$health_check = $diagnostics->health_check();
print_r($health_check); 