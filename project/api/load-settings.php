<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/SettingsRepository.php';

$logger = new Logger(LOG_FILE);
$repository = new SettingsRepository(SETTINGS_FILE, $logger);

$settings = $repository->load();

unset($settings['api_key']);

echo json_encode([
    'success' => true,
    'settings' => $settings,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
