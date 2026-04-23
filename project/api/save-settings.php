<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/SettingsRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload inválido']);
    exit;
}

$logger = new Logger(LOG_FILE);
$repository = new SettingsRepository(SETTINGS_FILE, $logger);
$current = $repository->load();

$settings = [
    'api_key' => trim((string)($input['api_key'] ?? $current['api_key'] ?? '')),
    'default_pages' => (int)($input['default_pages'] ?? $current['default_pages'] ?? 1),
    'max_pages' => (int)($input['max_pages'] ?? $current['max_pages'] ?? 5),
    'region' => trim((string)($input['region'] ?? $current['region'] ?? 'BR')),
    'language' => trim((string)($input['language'] ?? $current['language'] ?? 'pt-BR')),
];

if ($settings['max_pages'] < 1 || $settings['max_pages'] > ABSOLUTE_MAX_PAGES) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'max_pages inválido']);
    exit;
}

if ($settings['default_pages'] < 1 || $settings['default_pages'] > $settings['max_pages']) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'default_pages inválido']);
    exit;
}

if (!$repository->save($settings)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar configurações']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);
