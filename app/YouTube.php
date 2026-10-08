<?php
declare(strict_types=1);

class QuotaExceeded extends RuntimeException {}

/**
 * Klien YouTube Data API v3 (hanya endpoint murah: 1 unit per panggilan).
 * Tidak memakai search.list (100 unit) supaya kuota awet.
 */
class YouTube
{
    private int $units = 0;

    public function __construct(private string $key)
    {
        if ($key === '') {
            throw new RuntimeException('YouTube API key kosong. Isi youtube.api_key di config.php');
        }
    }

    public function units(): int
    {
        return $this->units;
    }

    protected function request(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => 'AGC-Drama-YT/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new RuntimeException('cURL error: ' . $err);
        }
        return [$code, (string)$body];
    }

    protected function get(string $endpoint, array $params): array
    {
        if (quota_used() >= (int)cfg('youtube.daily_quota', 9000)) {
            throw new QuotaExceeded('Batas kuota harian di config tercapai (' . quota_used() . ' unit).');
        }
        $params = array_filter($params, fn($v) => $v !== null && $v !== '');
        $params['key'] = $this->key;
        [$code, $body] = $this->request('https://www.googleapis.com/youtube/v3/' . $endpoint . '?' . http_build_query($params));
        $this->units++;
        quota_add(1);

        $json = json_decode($body, true);
        if ($code >= 400) {
            $reason = $json['error']['errors'][0]['reason'] ?? '';
            $msg = $json['error']['message'] ?? substr($body, 0, 200);
            if (in_array($reason, ['quotaExceeded', 'dailyLimitExceeded', 'rateLimitExceeded'], true)) {
                throw new QuotaExceeded($msg);
            }
            throw new RuntimeException("YouTube API $code ($reason): $msg");
        }
        return is_array($json) ? $json : [];
    }

    public function channel(string $handleOrId): ?array
    {
        $params = ['part' => 'snippet,contentDetails,statistics'];
        if (preg_match('/^UC[\w-]{22}$/', $handleOrId)) {
            $params['id'] = $handleOrId;
        } else {
            $params['forHandle'] = '@' . ltrim($handleOrId, '@');
        }
        return $this->get('channels', $params)['items'][0] ?? null;
    }

    public function playlistItems(string $playlistId, ?string $pageToken = null): array
    {
        return $this->get('playlistItems', [
            'part' => 'snippet,contentDetails', 'playlistId' => $playlistId,
            'maxResults' => 50, 'pageToken' => $pageToken,
        ]);
    }

    public function playlists(string $channelId, ?string $pageToken = null): array
    {
        return $this->get('playlists', [
            'part' => 'snippet,contentDetails', 'channelId' => $channelId,
            'maxResults' => 50, 'pageToken' => $pageToken,
        ]);
    }

    /** @param string[] $ids maks 50 */
    public function videos(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        return $this->get('videos', [
            'part' => 'snippet,contentDetails,statistics,status',
            'id' => implode(',', $ids), 'maxResults' => 50,
        ])['items'] ?? [];
    }
}
