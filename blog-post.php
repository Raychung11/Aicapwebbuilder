<?php
/**
 * Public blog article at /blog/<slug> (rewritten to /blog-post.php?slug=X).
 *
 * Renders SEO/OG meta, Schema.org Article JSON-LD, the body, tags,
 * a CTA box, related posts, and social share buttons.
 */
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/blog.php';

$slug = trim((string) input('slug', ''));
$post = $slug !== '' ? blog_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found | AICAP Furniture BOS';
    $page_id    = 'blog';
    $page_desc  = 'The article you were looking for could not be found.';
    require __DIR__ . '/inc/corp_header.php';
    ?>
    <section class="corp">
      <div class="container" style="text-align:center;padding:60px 16px;">
        <h1>Post not found</h1>
        <p class="muted">The article may have been moved or unpublished.</p>
        <p><a class="btn primary" href="/blog.php">← Back to the blog</a></p>
      </div>
    </section>
    <?php require __DIR__ . '/inc/corp_footer.php';
    exit;
}

$tags     = blog_post_tags((int) $post['id']);
$related  = blog_related_posts((int) $post['id'], $post['category_id'] ? (int) $post['category_id'] : null, 3);
$scheme   = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'aicap.my';
$canon    = $scheme . '://' . $host . '/blog/' . rawurlencode($post['slug']);
$og_image = !empty($post['featured_image']) ? $scheme . '://' . $host . $post['featured_image'] : '';

$page_title = ($post['seo_title'] ?: $post['title']) . ' | AICAP Furniture BOS';
$page_id    = 'blog';
$page_desc  = $post['meta_description'] ?: ($post['excerpt'] ?: 'AiCap furniture industry insight.');
$page_image = $og_image;
require __DIR__ . '/inc/corp_header.php';

// Body: if content contains block-level HTML tags, render as-is; else run
// a lightweight Markdown-ish transformation for common cases.
function blog_render_content(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    // If it already looks like HTML, trust admin-authored markup.
    if (preg_match('/<(h[1-6]|p|ul|ol|blockquote|figure|table)\b/i', $raw)) {
        return $raw;
    }
    $html = e($raw);
    // Headings — ## Foo, ### Foo
    $html = preg_replace('/^###\s+(.*)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^##\s+(.*)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^#\s+(.*)$/m', '<h2>$1</h2>', $html);
    // Bold **text**
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    // Italic *text*
    $html = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $html);
    // Bullet lists (simple)
    $html = preg_replace_callback('/(?:^[-*]\s+.+(?:\n|$))+/m', function ($m) {
        $items = preg_replace('/^[-*]\s+(.+)$/m', '<li>$1</li>', trim($m[0]));
        return "<ul>{$items}</ul>";
    }, $html);
    // Paragraph split on blank lines
    $paras = preg_split("/\n{2,}/", $html);
    $out = [];
    foreach ($paras as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if (preg_match('/^<(h[1-6]|ul|ol|blockquote|figure|table)/', $p)) {
            $out[] = $p;
        } else {
            $out[] = '<p>' . nl2br($p) . '</p>';
        }
    }
    return implode("\n", $out);
}

// Schema.org Article
$jsonld = [
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    'headline'      => $post['title'],
    'description'   => $page_desc,
    'datePublished' => date('c', strtotime($post['published_at'] ?: $post['created_at'])),
    'dateModified'  => date('c', strtotime($post['updated_at']   ?: $post['created_at'])),
    'author'        => ['@type' => 'Organization', 'name' => 'AiCap Solution'],
    'publisher'     => [
        '@type' => 'Organization',
        'name'  => 'AICAP Furniture BOS',
        'url'   => $scheme . '://' . $host . '/',
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canon],
    'articleSection' => $post['category_name'] ?? null,
    'inLanguage'    => $post['language'],
];
if ($og_image) {
    $jsonld['image'] = [$og_image];
    $jsonld['publisher']['logo'] = [
        '@type'  => 'ImageObject',
        'url'    => $scheme . '://' . $host . '/favicon.ico',
    ];
}
$jsonld = array_filter($jsonld, fn ($v) => $v !== null);

$share_url    = $canon;
$share_title  = $post['title'];
$share_text   = $post['whatsapp_text'] ?: ($post['excerpt'] ?: '');
$wa_msg       = trim(($share_text ? $share_text . "\n\n" : '') . $share_url);
?>
<script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<style>
  .post-hero { background: linear-gradient(135deg, #0f172a, #1e293b); color:#fff;
               padding: clamp(40px,7vw,72px) 0 clamp(30px,5vw,52px); }
  .post-hero .tag {
    display:inline-block; padding: 4px 12px; border-radius:999px;
    background: rgba(245,158,11,.15); color: #f59e0b;
    font-size: 12px; font-weight: 700; letter-spacing:.06em; text-transform: uppercase;
    margin-bottom: 14px;
  }
  .post-hero h1 { font-size: clamp(28px, 4.5vw, 44px); margin: 0 0 12px; line-height: 1.15; max-width: 820px; }
  .post-hero .byline { color:#cbd5e1; font-size:14px; }
  .post-cover { max-width: 1100px; margin: -20px auto 0; padding: 0 20px; }
  .post-cover img { width:100%; aspect-ratio: 16/9; object-fit:cover; border-radius: 14px;
                    background:#e5e7eb; box-shadow: 0 12px 40px rgba(0,0,0,.15); display:block; }
  .post-body { max-width: 760px; margin: 40px auto; padding: 0 20px; font-size: 17px;
               line-height: 1.7; color:#1f2937; }
  .post-body h2 { font-size: clamp(22px,2.8vw,28px); margin: 34px 0 10px; color:#0f172a; }
  .post-body h3 { font-size: clamp(18px,2.4vw,22px); margin: 26px 0 8px; color:#0f172a; }
  .post-body p, .post-body ul, .post-body ol { margin: 0 0 16px; }
  .post-body ul, .post-body ol { padding-left: 22px; }
  .post-body a { color: #0369a1; }
  .post-body img { width:100%; height:auto; border-radius: 10px; margin: 18px 0; display:block; }
  .post-body blockquote { border-left: 4px solid #f59e0b; padding: 8px 16px;
                          background:#fef3c7; color:#78350f; margin: 20px 0; border-radius: 4px; }

  .share-row {
    display:flex; flex-wrap:wrap; gap:10px; margin: 24px 0; padding: 14px 16px;
    background:#f9fafb; border:1px solid #e5e7eb; border-radius: 12px;
  }
  .share-row strong { align-self:center; font-size:14px; color:#374151; }
  .share-row a.share-btn {
    display:inline-flex; align-items:center; gap:6px; padding: 7px 12px; border-radius:999px;
    background:#fff; border:1px solid #d1d5db; color:#111; text-decoration:none;
    font-size:13px; font-weight:600;
  }
  .share-row a.share-btn:hover { background:#0f172a; color:#fff; border-color:#0f172a; }

  .tag-row { display:flex; flex-wrap:wrap; gap:6px; margin: 20px 0; }
  .tag-row span { background:#f3f4f6; color:#374151; padding: 4px 10px; border-radius: 999px; font-size:12px; }

  .cta-box {
    background: linear-gradient(135deg, #0f172a, #1e293b); color:#fff;
    padding: 28px 24px; border-radius: 14px; margin: 32px 0 8px;
  }
  .cta-box h3 { margin: 0 0 8px; color:#fff; font-size:22px; }
  .cta-box p { margin: 0 0 16px; color:#cbd5e1; }
  .related {
    max-width: 1100px; margin: 40px auto; padding: 0 20px;
  }
  .related h3 { font-size: 20px; margin: 0 0 16px; }
  .related-grid { display:grid; gap:16px; grid-template-columns: 1fr; }
  @media (min-width: 720px) { .related-grid { grid-template-columns: repeat(3, 1fr); } }
  .related a { text-decoration:none; color:inherit; background:#fff;
               border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;
               display:flex; flex-direction:column; transition:transform .2s; }
  .related a:hover { transform: translateY(-2px); }
  .related .cover { aspect-ratio: 16/9; background:#e5e7eb; }
  .related .cover img { width:100%; height:100%; object-fit:cover; }
  .related .pad { padding: 12px 14px; }
  .related h4 { margin:0; font-size:15px; line-height:1.3; }
  .related .date { color:#6b7280; font-size:12px; margin-top:6px; }
</style>

<article>
  <section class="post-hero">
    <div class="container">
      <div style="max-width:820px;">
        <?php if (!empty($post['category_name'])): ?>
          <span class="tag"><?= e($post['category_name']) ?></span>
        <?php endif; ?>
        <h1><?= e($post['title']) ?></h1>
        <div class="byline">
          <?= e(date('j M Y', strtotime($post['published_at'] ?: $post['created_at']))) ?>
          &middot; <?= e(blog_language_label($post['language'])) ?>
          <?php if (!empty($post['source_url'])): ?>
            &middot; <a href="<?= e($post['source_url']) ?>" target="_blank" rel="noopener nofollow" style="color:#f59e0b;">Source</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($post['featured_image'])): ?>
    <div class="post-cover">
      <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
    </div>
  <?php endif; ?>

  <div class="post-body">
    <?php if (!empty($post['excerpt'])): ?>
      <p style="font-size:19px;color:#374151;font-weight:500;line-height:1.55;margin-bottom:26px;">
        <?= e($post['excerpt']) ?>
      </p>
    <?php endif; ?>

    <?= blog_render_content((string) $post['content']) ?>

    <?php if ($tags): ?>
      <div class="tag-row">
        <?php foreach ($tags as $t): ?>
          <span>#<?= e($t['name']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="share-row">
      <strong>Share:</strong>
      <a class="share-btn" target="_blank" rel="noopener"
         href="https://wa.me/?text=<?= rawurlencode($wa_msg) ?>">💬 WhatsApp</a>
      <a class="share-btn" target="_blank" rel="noopener"
         href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($share_url) ?>">📘 Facebook</a>
      <a class="share-btn" target="_blank" rel="noopener"
         href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($share_url) ?>">💼 LinkedIn</a>
      <a class="share-btn" target="_blank" rel="noopener"
         href="https://twitter.com/intent/tweet?text=<?= rawurlencode($share_title) ?>&url=<?= rawurlencode($share_url) ?>">🐦 X</a>
      <a class="share-btn" href="#" onclick="navigator.clipboard.writeText('<?= e($share_url) ?>');this.textContent='✓ Copied';return false;">🔗 Copy link</a>
    </div>

    <div class="cta-box">
      <h3>Ready to digitalise your furniture business?</h3>
      <p>AiCap Solution helps furniture companies build BI dashboards, CRM, WMS, dealer portals, supplier portals and AI-powered business systems. Contact us to explore your digital roadmap.</p>
      <div class="btn-row">
        <a class="btn primary" href="/contact.php">Book a discovery call →</a>
        <a class="btn outline-light" href="/features.php">Explore features</a>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
    <div class="related">
      <h3>Related articles</h3>
      <div class="related-grid">
        <?php foreach ($related as $r): ?>
          <a href="/blog/<?= e($r['slug']) ?>">
            <div class="cover">
              <?php if (!empty($r['featured_image'])): ?>
                <img src="<?= e($r['featured_image']) ?>" alt="" loading="lazy">
              <?php endif; ?>
            </div>
            <div class="pad">
              <h4><?= e($r['title']) ?></h4>
              <div class="date"><?= e(date('j M Y', strtotime($r['published_at'] ?: $r['created_at']))) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</article>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
