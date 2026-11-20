<?php
/**
 * POST endpoint used by /admin/blog-edit.php to draft a blog with Claude.
 * Returns strict JSON: { ok: bool, data?: {...}, error?: string }.
 *
 * The prompt is the AiCap furniture-industry content-strategist brief.
 * Uses the same API key + model configured on /admin/ai-settings.php.
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/ai.php';
require_once __DIR__ . '/../inc/blog.php';

header('Content-Type: application/json');

function bail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bail('POST required', 405);
csrf_check();

$input      = trim((string) input('input_text', ''));
$source_url = trim((string) input('source_url', ''));
$language   = in_array(input('language'), ['en','zh','ms'], true) ? input('language') : 'en';
$category   = trim((string) input('category_hint', ''));
$angle      = trim((string) input('angle', ''));
$cta        = trim((string) input('cta', ''));

if ($input === '' && $source_url === '') bail('Enter a topic, URL, or notes.');

if (!ai_enabled()) {
    bail('AI is not configured. Add your Claude API key on /admin/ai-settings.php first.');
}

$api_key = trim((string) platform_setting('ai_api_key', ''));
$model   = ai_model();

$lang_label = match ($language) {
    'zh' => 'Chinese (Simplified)',
    'ms' => 'Bahasa Malaysia',
    default => 'English',
};

$system =
    "You are AiCap Solution's furniture industry content strategist.\n"
  . "AiCap Solution is building a Furniture Digital Operating System for Malaysia's furniture "
  . "industry. The system includes BI Dashboard, CRM, WMS, Dealer Portal, Supplier Portal, "
  . "Marketplace, and AI Assistant.\n\n"
  . "Write a professional but easy-to-understand blog article for Malaysian furniture business owners.\n"
  . "Target audience: furniture factory owners, retailers, wholesalers, exporters, SME business owners, "
  . "industry association members.\n\n"
  . "Content angle: Explain why this topic matters to the furniture industry and how digitalisation, AI, "
  . "CRM, WMS, BI dashboard, and marketplace systems can help business owners improve competitiveness.\n\n"
  . "Writing style: clear, business-focused, not too technical, Malaysian SME-friendly, avoid exaggerated "
  . "claims, use practical examples. End with a soft CTA for AiCap Solution.\n\n"
  . "Important: Do not copy news directly. Summarise, interpret, and add business insight.\n\n"
  . "Return STRICT JSON only, no code fences, in this exact shape:\n"
  . '{"title":"","slug":"","seo_title":"","meta_description":"","excerpt":"","content":"",'
  . '"source_summary":"","facebook_caption":"","linkedin_caption":"","whatsapp_text":"",'
  . '"image_prompt":"","suggested_category":"","suggested_tags":[]}' . "\n\n"
  . "Field rules:\n"
  . "- title: clear, business-focused, ≤ 90 chars.\n"
  . "- slug: lowercase, a-z0-9-, ≤ 80 chars.\n"
  . "- seo_title: ≤ 60 chars, keyword-first.\n"
  . "- meta_description: 140-160 chars, benefit-driven.\n"
  . "- excerpt: 1-2 sentences, ≤ 240 chars.\n"
  . "- content: Markdown, 700-1100 words, with H2 sections: Opening, Why It Matters, Industry Pain Point, "
  . "Digital Solution, AiCap Perspective, Conclusion, CTA.\n"
  . "- facebook_caption: 3-6 lines with emojis, hook + value + link tease.\n"
  . "- linkedin_caption: 5-9 lines, professional, industry-insight tone.\n"
  . "- whatsapp_text: ≤ 320 chars, plain, one strong CTA.\n"
  . "- image_prompt: single English sentence describing a professional Malaysian furniture / dashboard "
  . "scene, 16:9, clean corporate, blue+green accents.\n"
  . "- suggested_category: one of the tenant's categories if listed, else a sensible new one.\n"
  . "- suggested_tags: 3-6 short lowercase tags.\n\n"
  . "Write everything (including content) in {$lang_label}. Keep JSON keys and field names in English.";

$user_lines = [];
if ($source_url) $user_lines[] = 'Source URL: ' . $source_url;
if ($category)   $user_lines[] = 'Category hint: ' . $category;
if ($angle)      $user_lines[] = 'Writing angle: ' . $angle;
if ($cta)        $user_lines[] = 'CTA to emphasise: ' . $cta;
if ($input)      $user_lines[] = "Input:\n" . $input;
$user_msg = implode("\n\n", $user_lines);

$payload = [
    'model'      => $model,
    'max_tokens' => 4000,
    'system'     => [
        ['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']],
    ],
    'messages' => [
        ['role' => 'user', 'content' => $user_msg],
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
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false || $code < 200 || $code >= 300) {
    $body = json_decode((string) $resp, true);
    $err_msg = 'AI API error (' . $code . '): '
             . ($body['error']['message'] ?? substr((string) $resp, 0, 300) ?: $err);
    try {
        db_insert(
            'INSERT INTO ai_generation_logs
              (user_id, input_type, input_text, ai_output, status, error_message)
             VALUES (?, ?, ?, ?, "error", ?)',
            [(int) $super['id'], $source_url ? 'news_url' : 'topic',
             $input . ($source_url ? "\n\nSource: {$source_url}" : ''),
             (string) $resp, $err_msg]
        );
    } catch (Throwable $e) { /* ignore log failure */ }
    bail($err_msg, 502);
}

$body = json_decode($resp, true);
$text = $body['content'][0]['text'] ?? '';
$tokens = (int) (($body['usage']['input_tokens'] ?? 0) + ($body['usage']['output_tokens'] ?? 0));

// Extract JSON — Claude sometimes wraps in a code fence.
$parsed = json_decode($text, true);
if (!is_array($parsed) && preg_match('/\{.*\}/s', $text, $m)) {
    $parsed = json_decode($m[0], true);
}
if (!is_array($parsed)) {
    bail('AI returned unparseable output. First 300 chars: ' . substr($text, 0, 300), 502);
}

// Normalize defensively.
$data = [
    'title'            => (string) ($parsed['title']            ?? ''),
    'slug'             => blog_slugify((string) ($parsed['slug'] ?? ($parsed['title'] ?? ''))),
    'seo_title'        => (string) ($parsed['seo_title']        ?? ''),
    'meta_description' => (string) ($parsed['meta_description'] ?? ''),
    'excerpt'          => (string) ($parsed['excerpt']          ?? ''),
    'content'          => (string) ($parsed['content']          ?? ''),
    'source_summary'   => (string) ($parsed['source_summary']   ?? ''),
    'facebook_caption' => (string) ($parsed['facebook_caption'] ?? ''),
    'linkedin_caption' => (string) ($parsed['linkedin_caption'] ?? ''),
    'whatsapp_text'    => (string) ($parsed['whatsapp_text']    ?? ''),
    'image_prompt'     => (string) ($parsed['image_prompt']     ?? ''),
    'suggested_category' => (string) ($parsed['suggested_category'] ?? ''),
    'suggested_tags'   => array_values(array_filter(array_map(
        fn ($t) => trim((string) $t),
        (array) ($parsed['suggested_tags'] ?? [])
    ))),
];

try {
    db_insert(
        'INSERT INTO ai_generation_logs
          (user_id, input_type, input_text, ai_output, token_usage, status)
         VALUES (?, ?, ?, ?, ?, "success")',
        [(int) $super['id'], $source_url ? 'news_url' : 'topic',
         $input . ($source_url ? "\n\nSource: {$source_url}" : ''),
         $text, $tokens]
    );
} catch (Throwable $e) { /* ignore log failure */ }

echo json_encode(['ok' => true, 'data' => $data]);
