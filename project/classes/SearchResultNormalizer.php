<?php

declare(strict_types=1);

class SearchResultNormalizer
{
    public function normalizeItems(array $items, array $detailsByVideoId = []): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $videoId = (string)($item['id']['videoId'] ?? '');
            $title = trim((string)($item['snippet']['title'] ?? ''));

            if ($videoId === '' || $title === '') {
                continue;
            }

            $detail = $detailsByVideoId[$videoId] ?? [];
            $channelTitle = trim((string)($detail['channel_title'] ?? $item['snippet']['channelTitle'] ?? ''));
            $viewCount = max(0, (int)($detail['view_count'] ?? 0));

            $normalized[] = [
                'video_id' => $videoId,
                'title' => $title,
                'channel_title' => $channelTitle,
                'view_count' => $viewCount,
                'url' => 'https://www.youtube.com/watch?v=' . $videoId,
            ];
        }

        return $normalized;
    }
}
