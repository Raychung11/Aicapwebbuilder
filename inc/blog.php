<?php
/**
 * Blog module helpers — shared between admin CRUD, AI generation, and
 * the public /blog.php + /blog-post.php pages.
 *
 * The blog lives at the aicap.my (corporate) level and is NOT
 * tenant-scoped, so there is no company_id column.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Fetch a single published post by slug. Returns null when the row is
 * missing or not yet published. Increments view_count as a side-effect.
 */
function blog_post_by_slug(string $slug): ?array {
    $row = db_one(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
           FROM blog_posts p
      LEFT JOIN blog_categories c ON c.id = p.category_id
          WHERE p.slug = ? AND p.status = "published"
            AND (p.published_at IS NULL OR p.published_at <= NOW())
          LIMIT 1',
        [$slug]
    );
    if (!$row) return null;

    // Best-effort view count bump — don't fail the render if the write fails.
    try {
        db_exec('UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?', [(int) $row['id']]);
    } catch (Throwable $e) { /* ignore */ }

    return $row;
}

/**
 * List published posts, optionally filtered by category slug, language,
 * or a free-text keyword against title / excerpt.
 */
function blog_published_posts(array $opts = []): array {
    $sql = 'SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.language,
                   p.published_at, p.created_at,
                   c.name AS category_name, c.slug AS category_slug
              FROM blog_posts p
         LEFT JOIN blog_categories c ON c.id = p.category_id
             WHERE p.status = "published"
               AND (p.published_at IS NULL OR p.published_at <= NOW())';
    $params = [];
    if (!empty($opts['category'])) {
        $sql .= ' AND c.slug = ?';
        $params[] = $opts['category'];
    }
    if (!empty($opts['language'])) {
        $sql .= ' AND p.language = ?';
        $params[] = $opts['language'];
    }
    if (!empty($opts['q'])) {
        $sql .= ' AND (p.title LIKE ? OR p.excerpt LIKE ?)';
        $params[] = '%' . $opts['q'] . '%';
        $params[] = '%' . $opts['q'] . '%';
    }
    $sql .= ' ORDER BY COALESCE(p.published_at, p.created_at) DESC';
    $limit  = (int) ($opts['limit']  ?? 30);
    $offset = (int) ($opts['offset'] ?? 0);
    $sql .= ' LIMIT ' . max(1, min(100, $limit))
          . ' OFFSET ' . max(0, $offset);
    return db_all($sql, $params);
}

/**
 * Categories that currently have at least one published post.
 * The public listing uses this to avoid empty filter chips.
 */
function blog_public_categories(): array {
    return db_all(
        'SELECT c.id, c.name, c.slug, COUNT(p.id) AS n
           FROM blog_categories c
      LEFT JOIN blog_posts p ON p.category_id = c.id AND p.status = "published"
                            AND (p.published_at IS NULL OR p.published_at <= NOW())
          WHERE c.status = "active"
       GROUP BY c.id, c.name, c.slug
         HAVING n > 0
       ORDER BY c.sort_order, c.name'
    );
}

/**
 * All categories (used by the admin category picker).
 */
function blog_all_categories(): array {
    return db_all(
        'SELECT id, name, slug, description, status
           FROM blog_categories
       ORDER BY sort_order, name'
    );
}

/**
 * Fetch a post's tags as an ordered array of names.
 */
function blog_post_tags(int $post_id): array {
    return db_all(
        'SELECT t.id, t.name, t.slug
           FROM blog_post_tags pt
           JOIN blog_tags t ON t.id = pt.tag_id
          WHERE pt.blog_post_id = ?
       ORDER BY t.name',
        [$post_id]
    );
}

/**
 * Replace the tag set on a post from an array of tag names.
 * Creates any tag that doesn't exist yet.
 */
function blog_set_post_tags(int $post_id, array $tag_names): void {
    db_exec('DELETE FROM blog_post_tags WHERE blog_post_id = ?', [$post_id]);
    foreach ($tag_names as $raw) {
        $name = trim((string) $raw);
        if ($name === '') continue;
        if (mb_strlen($name) > 80) $name = mb_substr($name, 0, 80);
        $slug = blog_slugify($name);
        $tag = db_one('SELECT id FROM blog_tags WHERE slug = ?', [$slug]);
        if (!$tag) {
            $tid = db_insert(
                'INSERT INTO blog_tags (name, slug) VALUES (?, ?)',
                [$name, $slug]
            );
        } else {
            $tid = (int) $tag['id'];
        }
        db_exec(
            'INSERT IGNORE INTO blog_post_tags (blog_post_id, tag_id) VALUES (?, ?)',
            [$post_id, $tid]
        );
    }
}

/**
 * Return `limit` posts in the same category as $post_id, excluding $post_id.
 * Used by the article page's "Related" strip.
 */
function blog_related_posts(int $post_id, ?int $category_id, int $limit = 3): array {
    if (!$category_id) return [];
    return db_all(
        'SELECT id, title, slug, excerpt, featured_image, published_at, created_at
           FROM blog_posts
          WHERE status = "published"
            AND (published_at IS NULL OR published_at <= NOW())
            AND category_id = ? AND id != ?
       ORDER BY COALESCE(published_at, created_at) DESC
          LIMIT ' . max(1, min(6, $limit)),
        [$category_id, $post_id]
    );
}

/**
 * Slugify — reused for post/category/tag slugs. Falls back to
 * "post" when the input yields an empty slug.
 */
function blog_slugify(string $text): string {
    if (function_exists('slugify')) {
        $slug = slugify($text);
        if ($slug !== '') return $slug;
    }
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'post';
}

/**
 * Ensure a slug is unique in a table + column, appending -2/-3/...
 * as needed. Used by both categories and posts.
 */
function blog_unique_slug(string $table, string $base, ?int $ignore_id = null): string {
    $base = blog_slugify($base);
    $slug = $base;
    $i = 1;
    while (true) {
        $params = [$slug];
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        if ($ignore_id) {
            $sql .= ' AND id != ?';
            $params[] = $ignore_id;
        }
        if (!db_one($sql, $params)) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

/**
 * Human-readable language label.
 */
function blog_language_label(string $lang): string {
    return match ($lang) {
        'zh'   => '中文',
        'ms'   => 'Bahasa Malaysia',
        default => 'English',
    };
}

/**
 * Human-readable status badge with color class.
 */
function blog_status_badge(string $status): string {
    $map = [
        'draft'          => ['gray', 'Draft'],
        'pending_review' => ['yellow', 'Pending review'],
        'published'      => ['green', 'Published'],
        'archived'       => ['red', 'Archived'],
    ];
    [$cls, $lbl] = $map[$status] ?? ['gray', $status];
    return '<span class="badge ' . $cls . '">' . e($lbl) . '</span>';
}
