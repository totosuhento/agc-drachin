<?php
declare(strict_types=1);

/** Pembuat deskripsi unik (opsional). Mendukung Anthropic & semua API OpenAI-compatible. */
final class AI
{
    public static function describe(string $type, string $title, string $description, string $channel): string
    {
        $lang = cfg('site.lang') === 'id' ? 'Indonesian' : 'English';
        $kind = $type === 'series' ? 'a short drama series (playlist)' : 'a short drama video';
        $desc = mb_substr(clean_description($description), 0, 2500);
        $prompt = <<<P
        Write an original page description in $lang for $kind on a website that embeds official YouTube videos.

        Title: $title
        Official channel: $channel
        Original description from the channel:
        """
        $desc
        """

        Rules:
        - 120 to 180 words, 2 short paragraphs, plain text only.
        - Use only facts implied by the title and description. Do not invent names, plot twists or endings.
        - Describe the premise, the mood and the kind of viewer who will enjoy it.
        - No hashtags, no links, no emojis, no "download the app" calls to action, no spoilers.
        - Output only the description.
        P;
        return trim(self::complete($prompt));
    }

    private static function complete(string $prompt): string
    {
        $key = (string)cfg('ai.api_key', '');
        $model = (string)cfg('ai.model', '');
        if ($key === '' || $model === '') {
            throw new RuntimeException('ai.api_key / ai.model belum diisi');
        }
        if (cfg('ai.provider') === 'anthropic') {
            $res = self::post(cfg('ai.endpoint') ?: 'https://api.anthropic.com/v1/messages', [
                'x-api-key: ' . $key, 'anthropic-version: 2023-06-01',
            ], ['model' => $model, 'max_tokens' => 700, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
            return (string)($res['content'][0]['text'] ?? '');
        }
        $res = self::post(cfg('ai.endpoint') ?: 'https://api.openai.com/v1/chat/completions', [
            'Authorization: Bearer ' . $key,
        ], ['model' => $model, 'max_tokens' => 700, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        return (string)($res['choices'][0]['message']['content'] ?? '');
    }

    private static function post(string $url, array $headers, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_HTTPHEADER     => [...$headers, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($body),
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('cURL: ' . $err);
        }
        $json = json_decode((string)$raw, true);
        if ($code >= 400 || !is_array($json)) {
            throw new RuntimeException("AI API $code: " . substr((string)$raw, 0, 300));
        }
        return $json;
    }
}
