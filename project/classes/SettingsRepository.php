<?php

declare(strict_types=1);

class SettingsRepository
{
    private string $settingsFile;
    private Logger $logger;

    public function __construct(string $settingsFile, Logger $logger)
    {
        $this->settingsFile = $settingsFile;
        $this->logger = $logger;
    }

    public function load(): array
    {
        if (!file_exists($this->settingsFile)) {
            $this->save(DEFAULT_SETTINGS);
            return DEFAULT_SETTINGS;
        }

        $raw = file_get_contents($this->settingsFile);
        if ($raw === false) {
            $this->logger->error('Falha ao ler settings.json');
            return DEFAULT_SETTINGS;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->logger->error('JSON inválido em settings.json');
            return DEFAULT_SETTINGS;
        }

        return array_merge(DEFAULT_SETTINGS, $decoded);
    }

    public function save(array $settings): bool
    {
        $normalized = $this->normalize($settings);

        $dir = dirname($this->settingsFile);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->logger->error('Falha ao criar diretório de settings', ['dir' => $dir]);
            return false;
        }

        $result = file_put_contents(
            $this->settingsFile,
            json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        if ($result === false) {
            $this->logger->error('Falha ao salvar settings.json');
            return false;
        }

        return true;
    }

    private function normalize(array $settings): array
    {
        $apiKey = trim((string)($settings['api_key'] ?? ''));
        $defaultPages = (int)($settings['default_pages'] ?? DEFAULT_SETTINGS['default_pages']);
        $maxPages = (int)($settings['max_pages'] ?? DEFAULT_SETTINGS['max_pages']);

        $maxPages = max(1, min($maxPages, ABSOLUTE_MAX_PAGES));
        $defaultPages = max(1, min($defaultPages, $maxPages));

        $region = strtoupper(substr(trim((string)($settings['region'] ?? DEFAULT_SETTINGS['region'])), 0, 2));
        $language = trim((string)($settings['language'] ?? DEFAULT_SETTINGS['language']));

        return [
            'api_key' => $apiKey,
            'default_pages' => $defaultPages,
            'max_pages' => $maxPages,
            'region' => $region !== '' ? $region : DEFAULT_SETTINGS['region'],
            'language' => $language !== '' ? $language : DEFAULT_SETTINGS['language'],
        ];
    }
}
