<?php

declare(strict_types=1);

class YouTubeService
{
    private string $apiKey;
    private string $region;
    private string $language;
    private Logger $logger;

    public function __construct(string $apiKey, string $region, string $language, Logger $logger)
    {
        $this->apiKey = $apiKey;
        $this->region = $region;
        $this->language = $language;
        $this->logger = $logger;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function search(string $query, ?string $pageToken = null): array
    {
        $params = [
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => 50,
            'q' => $query,
            'key' => $this->apiKey,
            'regionCode' => $this->region,
            'relevanceLanguage' => $this->language,
        ];

        if ($pageToken !== null && $pageToken !== '') {
            $params['pageToken'] = $pageToken;
        }

        $url = YOUTUBE_SEARCH_ENDPOINT . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->logger->error('Erro no cURL da YouTube API', ['query' => $query, 'error' => $curlError]);
            return ['success' => false, 'error' => 'Erro ao buscar vídeos'];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $this->logger->error('Resposta inválida da YouTube API', ['response' => $response]);
            return ['success' => false, 'error' => 'Erro ao buscar vídeos'];
        }

        if ($httpCode >= 400 || isset($decoded['error'])) {
            $message = 'Erro ao buscar vídeos';
            $apiMessage = (string)($decoded['error']['message'] ?? '');

            if (stripos($apiMessage, 'quota') !== false || stripos($apiMessage, 'rate') !== false) {
                $message = 'Limite de requisições atingido';
            }

            $this->logger->error('Erro de API do YouTube', [
                'http_code' => $httpCode,
                'api_error' => $decoded['error'] ?? null,
                'query' => $query,
            ]);

            return ['success' => false, 'error' => $message];
        }

        return [
            'success' => true,
            'items' => $decoded['items'] ?? [],
            'nextPageToken' => $decoded['nextPageToken'] ?? null,
            'prevPageToken' => $decoded['prevPageToken'] ?? null,
        ];
    }
}
