<?php

declare(strict_types=1);

const STORAGE_DIR = __DIR__ . '/storage';
const SETTINGS_FILE = STORAGE_DIR . '/settings.json';
const LOG_FILE = STORAGE_DIR . '/logs.txt';

const DEFAULT_SETTINGS = [
    'api_key' => '',
    'default_pages' => 1,
    'max_pages' => 5,
    'region' => 'BR',
    'language' => 'pt-BR',
];

const ABSOLUTE_MAX_PAGES = 10;
const YOUTUBE_SEARCH_ENDPOINT = 'https://www.googleapis.com/youtube/v3/search';
