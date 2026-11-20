<?php
/**
 * Claude (Anthropic) integration for the tenant chatbot.
 *
 * - Reads provider / API key / model from platform_settings or env vars.
 * - Sends the tenant's catalog as a cached system prompt (cuts cost
 *   on subsequent queries from the same tenant).
 * - Asks Claude to reply with strict JSON: { reply, product_ids[] }.
 * - On any failure, returns null and the caller falls back to the
 *   rule-based recommender in chat.php.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

const AI_DEFAULT_MODEL = 'claude-haiku-4-5';

function ai_provider(): string {
    return strtolower((string) platform_setting('ai_provider', ''));
}

function ai_enabled(): bool {
    return ai_provider() === 'anthropic' && trim((string) platform_setting('ai_api_key', '')) !== '';
}

function ai_model(): string {
    return platform_setting('ai_model', AI_DEFAULT_MODEL) ?: AI_DEFAULT_MODEL;
}

/**
 * Ask Claude for a product recommendation grounded in the given catalog.
 *
 * @param array  $catalog       List of product rows (id, name, category, subcategory, price_min, price_max).
 * @param string $message       The user's message.
 * @param string $company_name  For grounding the assistant's persona.
 * @return array|null           ['reply' => string, 'product_ids' => int[]] or null on failure.
 */
function ai_recommend(array $catalog, string $message, string $company_name): ?array {
    if (!ai_enabled()) return null;

    $api_key = trim((string) platform_setting('ai_api_key', ''));
    $model   = ai_model();

    // Build a compact catalog string. id | name | category › subcategory | price.
    $lines = [];
    foreach ($catalog as $p) {
        $price = '';
        if ($p['price_min'] !== null) {
            $price = 'RM ' . (int) $p['price_min'];
            if ($p['price_max'] !== null && (float) $p['price_max'] != (float) $p['price_min']) {
                $price .= '-' . (int) $p['price_max'];
            }
        }
        $cat = trim(($p['category'] ?? '') . (!empty($p['subcategory']) ? ' › ' . $p['subcategory'] : ''), ' ›');
        $lines[] = sprintf('#%d | %s%s%s', (int) $p['id'], $p['name'], $cat ? ' | ' . $cat : '', $price ? ' | ' . $price : '');
    }
    $catalog_str = implode("\n", $lines) ?: '(empty catalog)';

    $instructions =
        "You are a friendly product assistant for {$company_name}, a furniture brand. "
        . "You help customers choose furniture from this catalog only — never recommend "
        . "items not listed below.\n\n"
        . "Return STRICT JSON in exactly this format and nothing else:\n"
        . '{"reply":"...","product_ids":[ids]}' . "\n\n"
        . "Rules:\n"
        . "- product_ids: up to 6 ids from the catalog, picked by relevance to the user's request.\n"
        . "- If the user asks about pricing, sort cheapest first.\n"
        . "- If nothing fits, set product_ids to [] and explain politely in reply.\n"
        . "- Keep reply under 200 characters, conversational, no markdown.\n"
        . "- Never invent product names, ids, or prices.";

    $payload = [
        'model'      => $model,
        'max_tokens' => 600,
        'system'     => [
            ['type' => 'text', 'text' => $instructions],
            ['type' => 'text',
             'text' => "CATALOG (#id | name | category | price):\n" . $catalog_str,
             'cache_control' => ['type' => 'ephemeral']],
        ],
        'messages' => [
            ['role' => 'user', 'content' => $message],
        ],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 18,
        CURLOPT_CONNECTTIMEOUT => 6,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $code < 200 || $code >= 300) {
        error_log("ai_recommend: HTTP {$code} err={$err} body=" . substr((string) $resp, 0, 400));
        return null;
    }

    $body = json_decode($resp, true);
    if (!is_array($body) || !isset($body['content'][0]['text'])) {
        error_log('ai_recommend: malformed response');
        return null;
    }

    $text   = $body['content'][0]['text'];
    $parsed = json_decode($text, true);
    if (!is_array($parsed) && preg_match('/\{.*\}/s', $text, $m)) {
        $parsed = json_decode($m[0], true);
    }
    if (!is_array($parsed) || !isset($parsed['reply'], $parsed['product_ids']) || !is_array($parsed['product_ids'])) {
        error_log('ai_recommend: unexpected JSON: ' . substr($text, 0, 300));
        return null;
    }

    return [
        'reply'       => substr((string) $parsed['reply'], 0, 500),
        'product_ids' => array_values(array_unique(array_map('intval', $parsed['product_ids']))),
    ];
}

/**
 * Quick connectivity / auth test for the admin AI Settings page.
 * Returns ['ok' => bool, 'message' => string].
 */
function ai_ping(): array {
    if (!ai_enabled()) {
        return ['ok' => false, 'message' => 'AI is not enabled (provider or API key missing).'];
    }
    $api_key = trim((string) platform_setting('ai_api_key', ''));
    $payload = [
        'model'      => ai_model(),
        'max_tokens' => 16,
        'messages'   => [['role' => 'user', 'content' => 'Reply with the single word: OK']],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'message' => 'Network error: ' . $err];
    }
    $body = json_decode($resp, true);
    if ($code === 200 && isset($body['content'][0]['text'])) {
        return ['ok' => true, 'message' => 'Connected — Claude replied: '
            . substr(trim($body['content'][0]['text']), 0, 80)];
    }
    return ['ok' => false, 'message' => 'API error (' . $code . '): '
        . ($body['error']['message'] ?? substr((string) $resp, 0, 200))];
}
