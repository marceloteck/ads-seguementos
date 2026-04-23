<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/SettingsRepository.php';
require_once __DIR__ . '/../classes/SearchResultNormalizer.php';
require_once __DIR__ . '/../classes/YouTubeService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload inválido']);
    exit;
}

$query = trim((string)($payload['query'] ?? ''));
$pageToken = trim((string)($payload['pageToken'] ?? ''));

$logger = new Logger(LOG_FILE);
$settingsRepo = new SettingsRepository(SETTINGS_FILE, $logger);
$settings = $settingsRepo->load();

if ($query === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Digite um termo de busca']);
    exit;
}

if (trim((string)($settings['api_key'] ?? '')) === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'API não configurada']);
    exit;
}

$maxPages = (int)($settings['max_pages'] ?? DEFAULT_SETTINGS['max_pages']);
$requestedPages = (int)($payload['pages'] ?? ($settings['default_pages'] ?? 1));
$pages = max(1, min($requestedPages, $maxPages));

$service = new YouTubeService(
    (string)$settings['api_key'],
    (string)$settings['region'],
    (string)$settings['language'],
    $logger
);
$normalizer = new SearchResultNormalizer();

$items = [];
$currentToken = $pageToken !== '' ? $pageToken : null;
$nextToken = null;
$prevToken = null;

for ($i = 0; $i < $pages; $i++) {
    $result = $service->search($query, $currentToken);

    if (($result['success'] ?? false) !== true) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Erro ao buscar vídeos']);
        exit;
    }

    $rawItems = $result['items'] ?? [];
    $videoIds = [];

    foreach ($rawItems as $rawItem) {
        $id = (string)($rawItem['id']['videoId'] ?? '');
        if ($id !== '') {
            $videoIds[] = $id;
        }
    }

    $detailsResult = $service->fetchVideoDetails($videoIds);

    if (($detailsResult['success'] ?? false) !== true) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $detailsResult['error'] ?? 'Erro ao buscar vídeos']);
        exit;
    }

    $normalizedItems = $normalizer->normalizeItems($rawItems, $detailsResult['details'] ?? []);
    $items = array_merge($items, $normalizedItems);

    $prevToken = $result['prevPageToken'] ?? $prevToken;
    $nextToken = $result['nextPageToken'] ?? null;

    if ($nextToken === null || $nextToken === '') {
        break;
    }

    $currentToken = $nextToken;
}

if (count($items) === 0) {
    echo json_encode([
        'success' => true,
        'items' => [],
        'next_page_token' => $nextToken,
        'prev_page_token' => $prevToken,
        'message' => 'Nenhum resultado encontrado',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'next_page_token' => $nextToken,
    'prev_page_token' => $prevToken,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
