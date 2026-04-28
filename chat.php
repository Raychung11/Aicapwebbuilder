<?php
/**
 * Tenant-scoped product-recommender endpoint for the chat widget.
 *
 *   POST /chat.php   message=<text>
 *   GET  /chat.php   (returns greeting + suggestion chips)
 *
 * Always JSON. CORS is restricted to same-origin (the widget is served
 * by the same tenant page).
 */
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

$company = current_company();
if (!$company) {
    http_response_code(404);
    echo json_encode(['error' => 'No tenant context.']);
    exit;
}
$cid = (int) $company['id'];

// Pre-fetch up to 5 categories for the welcome chips.
$categories = tenant_all(
    'SELECT category, COUNT(*) AS n FROM products
      WHERE company_id = ? AND status = "active"
        AND category IS NOT NULL AND category != ""
      GROUP BY category ORDER BY n DESC LIMIT 5',
    $cid
);
$cat_chips = array_map(fn($r) => 'Show me ' . strtolower($r['category']), $categories);

$message = trim((string) input('message', ''));

// Greeting / no input
if ($message === '') {
    track_event($cid, 'chatbot_open');
    echo json_encode([
        'greeting'    => "Hi! I'm the " . $company['name'] . " product assistant. "
                       . "Tell me what you're looking for and I'll suggest matches.",
        'suggestions' => array_values(array_filter(array_merge([
            "What's featured?",
            "I need a sofa under RM 3000",
        ], $cat_chips))),
    ]);
    exit;
}

track_event($cid, 'chatbot_query', ['entity_type' => 'query']);

// ----- Parse the query -----
$lower = mb_strtolower($message);

// Price extraction: "under RM 3000", "below 2k", "less than 5,000"
$price_max = null;
if (preg_match('/(?:under|below|less than|cheaper than|<\s*)\s*rm?\s*([\d,\.]+)\s*(k)?/iu', $lower, $m)) {
    $val = (float) str_replace(',', '', $m[1]);
    if (!empty($m[2])) $val *= 1000;
    $price_max = $val > 0 ? $val : null;
}

// Featured intent
$wants_featured = preg_match('/\b(featured|popular|best ?seller|top|recommend|highlight)/i', $lower) === 1;

// Strip stop words for token matching
$stop = ['i','a','an','the','for','my','to','show','me','please','some','any','need','want',
         'and','or','of','on','in','with','have','do','you','can','what','your','is','are',
         'rm','myr','under','below','less','than','cheaper','recommend','suggest'];
$tokens = array_values(array_filter(
    preg_split('/[^\p{L}\p{N}]+/u', $lower) ?: [],
    fn($t) => $t !== '' && !in_array($t, $stop, true) && mb_strlen($t) > 1
));

// ----- Build the search -----
$sql = 'SELECT p.id, p.name, p.slug, p.category, p.subcategory,
               p.price_min, p.price_max, p.is_featured,
               (SELECT image_path FROM product_images
                 WHERE product_id = p.id
                 ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
          FROM products p
         WHERE p.company_id = ? AND p.status = "active"';
$params = [$cid];

if ($wants_featured) {
    $sql .= ' AND p.is_featured = 1';
}

if ($tokens) {
    $or = [];
    foreach ($tokens as $t) {
        $or[] = '(p.name LIKE ? OR p.category LIKE ? OR p.subcategory LIKE ? OR p.description LIKE ?)';
        $like = '%' . $t . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $sql .= ' AND (' . implode(' OR ', $or) . ')';
}

if ($price_max !== null) {
    $sql .= ' AND (p.price_min IS NULL OR p.price_min <= ?)';
    $params[] = $price_max;
}

$sql .= ' ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 6';
$rows = db_all($sql, $params);

// Fallback: if zero results, try a relaxed search across name/category only
if (!$rows && $tokens) {
    $sql2 = 'SELECT p.id, p.name, p.slug, p.category, p.subcategory,
                    p.price_min, p.price_max, p.is_featured,
                    (SELECT image_path FROM product_images
                      WHERE product_id = p.id
                      ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
               FROM products p
              WHERE p.company_id = ? AND p.status = "active"
              ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 4';
    $rows = db_all($sql2, [$cid]);
    $fallback = true;
} else {
    $fallback = false;
}

// Format products for the widget
$products = array_map(function ($p) {
    return [
        'id'    => (int) $p['id'],
        'name'  => $p['name'],
        'cat'   => trim(($p['category'] ?? '') . ($p['subcategory'] ? ' › ' . $p['subcategory'] : ''), ' ›'),
        'price' => format_price($p['price_min'], $p['price_max']),
        'img'   => $p['img'],
        'url'   => '/product.php?id=' . (int) $p['id'],
        'wa'    => '/whatsapp-redirect.php?product_id=' . (int) $p['id'],
        'featured' => (bool) $p['is_featured'],
    ];
}, $rows);

// Compose a friendly reply
if ($fallback) {
    $reply = "I couldn't find an exact match for that — here are a few popular pieces you might like:";
} elseif (!$products) {
    $reply = "I couldn't find anything matching that yet. Try a category like \""
           . ($categories ? mb_strtolower($categories[0]['category']) : 'sofa')
           . "\" or message us on WhatsApp for help.";
} elseif ($wants_featured) {
    $reply = "Here are some of our featured pieces:";
} elseif ($price_max !== null) {
    $reply = "Here are options under RM " . number_format($price_max, 0) . ":";
} else {
    $reply = "Here's what I found for you:";
}

echo json_encode([
    'reply'       => $reply,
    'products'    => $products,
    'suggestions' => count($products) >= 3
        ? ['Show me more', 'Under RM 2000', 'What\'s featured?']
        : array_values(array_filter(array_merge(['Show me more', "What's featured?"], $cat_chips))),
]);
