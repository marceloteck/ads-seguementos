<?php

declare(strict_types=1);

class SearchResultNormalizer
{
    public function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $videoId = (string)($item['id']['videoId'] ?? '');
            $title = trim((string)($item['snippet']['title'] ?? ''));

            if ($videoId === '' || $title === '') {
                continue;
            }

            $normalized[] = [
                'video_id' => $videoId,
                'title' => $title,
                'url' => 'https://www.youtube.com/watch?v=' . $videoId,
            ];
        }

        return $normalized;
    }
}
