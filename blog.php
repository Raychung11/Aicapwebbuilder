<?php
/**
 * Public blog listing at aicap.my/blog.
 * Optional filters: ?category=<slug>, ?language=<en|zh|ms>, ?q=<keyword>, ?page=N.
 */
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/blog.php';

$category = trim((string) input('category', ''));
$language = in_array(input('language'), ['en','zh','ms'], true) ? input('language') : '';
$q        = trim((string) input('q', ''));
$page     = max(1, (int) input('page', 1));
$per_page = 12;

$posts = blog_published_posts([
    'category' => $category ?: null,
    'language' => $language ?: null,
    'q'        => $q ?: null,
    'limit'    => $per_page + 1,
    'offset'   => ($page - 1) * $per_page,
]);
$has_more = count($posts) > $per_page;
if ($has_more) array_pop($posts);

$cats = blog_public_categories();

$page_title = 'Blog | AICAP Furniture BOS';
$page_id    = 'blog';
$page_desc  = 'AiCap Solution\'s furniture industry digital intelligence blog — trends, digitalisation, AI, CRM, WMS, BI dashboards and more for Malaysian furniture businesses.';
require __DIR__ . '/inc/corp_header.php';

$build_url = function (array $overrides = []) use ($category, $language, $q, $page) {
    $qs = array_filter([
        'category' => $overrides['category'] ?? $category,
        'language' => $overrides['language'] ?? $language,
        'q'        => $overrides['q']        ?? $q,
        'page'     => $overrides['page']     ?? null,
    ], fn ($v) => $v !== null && $v !== '');
    return '/blog.php' . ($qs ? ('?' . http_build_query($qs)) : '');
};
?>

<section class="corp-hero" style="padding: clamp(48px,8vw,80px) 0;">
  <div class="container">
    <span class="tag">Furniture Industry Digital Intelligence</span>
    <h1 style="max-width:820px;">The AiCap Blog</h1>
    <p>Trends, digitalisation playbooks, and practical insight for Malaysia's furniture industry — factories, retailers, wholesalers, exporters and SME owners.</p>
  </div>
</section>

<section class="corp">
  <div class="container">
    <form method="get" action="/blog.php" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
      <input class="input" name="q" placeholder="Search articles…" value="<?= e($q) ?>"
             style="flex:1;min-width:200px;padding:12px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;">
      <?php if ($category): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
      <?php if ($language): ?><input type="hidden" name="language" value="<?= e($language) ?>"><?php endif; ?>
      <button class="btn primary" type="submit">Search</button>
      <?php if ($q !== '' || $category || $language): ?>
        <a class="btn outline" href="/blog.php">Clear</a>
      <?php endif; ?>
    </form>

    <?php if ($cats): ?>
      <div style="overflow-x:auto;white-space:nowrap;padding-bottom:6px;margin-bottom:14px;">
        <a class="chip <?= $category === '' ? 'active' : '' ?>" href="<?= e($build_url(['category' => ''])) ?>"
           style="display:inline-block;padding:6px 14px;border-radius:999px;background:<?= $category === '' ? '#0f172a' : '#fff' ?>;color:<?= $category === '' ? '#fff' : '#111' ?>;border:1px solid #d1d5db;text-decoration:none;margin-right:6px;font-size:13px;font-weight:500;">
          All
        </a>
        <?php foreach ($cats as $c): $on = $c['slug'] === $category; ?>
          <a class="chip" href="<?= e($build_url(['category' => $c['slug']])) ?>"
             style="display:inline-block;padding:6px 14px;border-radius:999px;background:<?= $on ? '#0f172a' : '#fff' ?>;color:<?= $on ? '#fff' : '#111' ?>;border:1px solid #d1d5db;text-decoration:none;margin-right:6px;font-size:13px;font-weight:500;">
            <?= e($c['name']) ?> <span style="opacity:.6;font-weight:400;">(<?= (int)$c['n'] ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$posts): ?>
      <div class="box center" style="padding:48px 16px;background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;">
        <h2 style="margin:0 0 6px;font-size:20px;">No posts yet</h2>
        <p class="muted" style="margin:0 0 14px;">Check back soon, or read more about the platform.</p>
        <a class="btn dark" href="/features.php">Explore features →</a>
      </div>
    <?php else: ?>
      <style>
        .blog-grid { display:grid; gap:20px; grid-template-columns: 1fr; }
        @media (min-width: 640px) { .blog-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 980px) { .blog-grid { grid-template-columns: repeat(3, 1fr); gap:24px; } }
        .blog-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px;
                     overflow:hidden; display:flex; flex-direction:column;
                     transition: transform .2s, box-shadow .2s; }
        .blog-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .blog-card .cover { aspect-ratio: 16/9; background: linear-gradient(135deg,#0f172a,#334155); }
        .blog-card .cover img { width:100%; height:100%; object-fit:cover; }
        .blog-card .pad { padding: 16px 18px 18px; display:flex; flex-direction:column; gap:8px; flex:1; }
        .blog-card .cat { color:#f59e0b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
        .blog-card h2 { margin:0; font-size:18px; line-height:1.3; color:#0f172a; }
        .blog-card p.excerpt { color:#374151; margin:0; font-size:14px; line-height:1.5; }
        .blog-card .foot { display:flex; align-items:center; justify-content:space-between;
                           font-size:12px; color:#6b7280; margin-top:auto; padding-top:8px;
                           border-top:1px solid #f1f5f9; }
      </style>

      <div class="blog-grid">
        <?php foreach ($posts as $p):
          $date  = $p['published_at'] ?: $p['created_at'];
          $href  = '/blog/' . rawurlencode($p['slug']);
        ?>
          <a class="blog-card" href="<?= e($href) ?>" style="text-decoration:none;color:inherit;">
            <div class="cover">
              <?php if (!empty($p['featured_image'])): ?>
                <img src="<?= e($p['featured_image']) ?>" alt="" loading="lazy">
              <?php endif; ?>
            </div>
            <div class="pad">
              <?php if (!empty($p['category_name'])): ?>
                <div class="cat"><?= e($p['category_name']) ?></div>
              <?php endif; ?>
              <h2><?= e($p['title']) ?></h2>
              <?php if (!empty($p['excerpt'])): ?>
                <p class="excerpt"><?= e(mb_strimwidth($p['excerpt'], 0, 160, '…')) ?></p>
              <?php endif; ?>
              <div class="foot">
                <span><?= e(date('j M Y', strtotime($date))) ?></span>
                <span style="color:#f59e0b;font-weight:600;">Read more →</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($has_more || $page > 1): ?>
        <div style="display:flex;justify-content:center;gap:10px;margin-top:32px;">
          <?php if ($page > 1): ?>
            <a class="btn outline" href="<?= e($build_url(['page' => $page > 2 ? $page - 1 : null])) ?>">← Previous</a>
          <?php endif; ?>
          <?php if ($has_more): ?>
            <a class="btn dark" href="<?= e($build_url(['page' => $page + 1])) ?>">Next page →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
