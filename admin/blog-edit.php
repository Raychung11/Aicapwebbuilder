<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/blog.php';
require_once __DIR__ . '/../inc/upload.php';

$id   = (int) input('id', 0);
$mode = (string) input('mode', '');   // ?mode=ai auto-opens the AI panel

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Featured image upload — save to /uploads/blog/
    $featured = $id ? (db_one('SELECT featured_image FROM blog_posts WHERE id = ?', [$id])['featured_image'] ?? null) : null;
    if (!empty($_FILES['featured_image']['name'])) {
        $url = save_upload($_FILES['featured_image'], 0, 'blog');
        if ($url) {
            if ($featured) {
                $abs = __DIR__ . '/..' . $featured;
                if (is_file($abs)) @unlink($abs);
            }
            $featured = $url;
        } else {
            flash_set('error', 'Could not save the image. Use JPG/PNG/WEBP up to 5 MB.');
        }
    } elseif ($id && input('remove_featured') === '1') {
        if ($featured) {
            $abs = __DIR__ . '/..' . $featured;
            if (is_file($abs)) @unlink($abs);
        }
        $featured = null;
    }

    $title    = trim((string) input('title', ''));
    $slug_in  = trim((string) input('slug', ''));
    $slug     = blog_slugify($slug_in !== '' ? $slug_in : $title);
    $slug     = blog_unique_slug('blog_posts', $slug, $id ?: null);
    $language = in_array(input('language'), ['en','zh','ms'], true) ? input('language') : 'en';
    $status   = in_array(input('status'), ['draft','pending_review','published','archived'], true) ? input('status') : 'draft';
    $cat_id   = (int) input('category_id', 0) ?: null;
    $pub_at   = trim((string) input('published_at', '')) ?: null;
    if ($pub_at) {
        $pub_at = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $pub_at)));
    } elseif ($status === 'published') {
        $pub_at = date('Y-m-d H:i:s');
    }

    $data = [
        'title'            => $title,
        'slug'             => $slug,
        'seo_title'        => trim((string) input('seo_title', '')) ?: null,
        'meta_description' => trim((string) input('meta_description', '')) ?: null,
        'excerpt'          => trim((string) input('excerpt', '')) ?: null,
        'content'          => (string) input('content', ''),
        'featured_image'   => $featured,
        'image_prompt'     => trim((string) input('image_prompt', '')) ?: null,
        'facebook_caption' => trim((string) input('facebook_caption', '')) ?: null,
        'linkedin_caption' => trim((string) input('linkedin_caption', '')) ?: null,
        'whatsapp_text'    => trim((string) input('whatsapp_text', '')) ?: null,
        'source_url'       => trim((string) input('source_url', '')) ?: null,
        'source_summary'   => trim((string) input('source_summary', '')) ?: null,
        'language'         => $language,
        'category_id'      => $cat_id,
        'author_id'        => (int) $super['id'],
        'status'           => $status,
        'published_at'     => $pub_at,
    ];

    if ($id) {
        db_exec(
            'UPDATE blog_posts
                SET title=?, slug=?, seo_title=?, meta_description=?, excerpt=?, content=?,
                    featured_image=?, image_prompt=?, facebook_caption=?, linkedin_caption=?,
                    whatsapp_text=?, source_url=?, source_summary=?, language=?, category_id=?,
                    author_id=?, status=?, published_at=?
              WHERE id=?',
            [...array_values($data), $id]
        );
    } else {
        $id = db_insert(
            'INSERT INTO blog_posts
              (title, slug, seo_title, meta_description, excerpt, content, featured_image,
               image_prompt, facebook_caption, linkedin_caption, whatsapp_text, source_url,
               source_summary, language, category_id, author_id, status, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($data)
        );
    }

    // Tags (comma-separated)
    $tag_names = array_filter(array_map('trim', explode(',', (string) input('tags', ''))));
    blog_set_post_tags($id, $tag_names);

    flash_set('success', $status === 'published' ? 'Post published.' : 'Post saved.');
    redirect('/admin/blog-edit.php?id=' . $id);
}

$post = $id ? db_one('SELECT * FROM blog_posts WHERE id = ?', [$id]) : null;
if ($id && !$post) {
    http_response_code(404);
    die('Post not found.');
}
$cats     = blog_all_categories();
$tag_rows = $id ? blog_post_tags($id) : [];
$tag_csv  = implode(', ', array_column($tag_rows, 'name'));

admin_layout_open($post ? 'Edit Post · ' . $post['title'] : 'New Blog Post');
?>

<style>
  .blog-grid { display: grid; gap: 16px; grid-template-columns: 1fr; }
  @media (min-width: 980px) { .blog-grid { grid-template-columns: 2fr 1fr; } }
  .ai-panel {
    border: 1px solid #d1d5db; border-radius: 10px; padding: 14px 16px;
    background: linear-gradient(135deg, #eff6ff, #fef3c7); margin-bottom: 16px;
  }
  .ai-panel h3 { margin: 0 0 6px; font-size: 15px; }
  .ai-panel textarea { min-height: 90px; }
  .ai-log { font-size: 12px; color: #6b7280; margin-top: 6px; }
  .side-card { padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 10px;
               background: #fff; margin-bottom: 12px; }
  .side-card h4 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase;
                  color: #6b7280; letter-spacing: .05em; }
  textarea.autosize { min-height: 80px; }
</style>

<form method="post" enctype="multipart/form-data" id="post-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($post['id'] ?? 0) ?>">

  <div class="ai-panel" id="ai-panel" <?= $mode === 'ai' ? '' : 'style="display:none;background:linear-gradient(135deg,#eff6ff,#fef3c7);"' ?>>
    <h3>🤖 AI Blog Generator</h3>
    <p class="muted" style="margin:0 0 10px;">
      Paste a news URL, a topic idea, or raw notes. Claude will draft the title, SEO tags,
      article body, social captions, and image prompt — you can edit anything after.
    </p>
    <div class="row">
      <div class="col" style="flex:2;min-width:220px;">
        <label>Source URL <span class="muted">(optional)</span></label>
        <input class="input" id="ai_source_url" placeholder="https://…" value="<?= e($post['source_url'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Language</label>
        <select class="input" id="ai_language">
          <option value="en">English</option>
          <option value="zh">中文</option>
          <option value="ms">Bahasa Malaysia</option>
        </select>
      </div>
      <div class="col">
        <label>Category hint</label>
        <select class="input" id="ai_category">
          <option value="">(auto-suggest)</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= e($c['name']) ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Topic / notes</label>
    <textarea class="input" id="ai_input" placeholder="e.g. Malaysia furniture export data 2026, or paste a news excerpt…"></textarea>
    <div class="row" style="margin-top:8px;">
      <div class="col">
        <label>Writing angle <span class="muted">(optional)</span></label>
        <input class="input" id="ai_angle" placeholder="e.g. how BI dashboards help SME exporters">
      </div>
      <div class="col">
        <label>CTA <span class="muted">(optional)</span></label>
        <input class="input" id="ai_cta" placeholder="e.g. Try AiCap Dashboard demo">
      </div>
    </div>
    <div style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <button type="button" class="btn primary" id="ai_go">Generate draft</button>
      <span class="ai-log" id="ai_status">Uses your Claude API key from AI Settings.</span>
    </div>
  </div>

  <div style="margin-bottom:10px;">
    <button type="button" class="btn outline" onclick="var p=document.getElementById('ai-panel');p.style.display=(p.style.display==='none'?'':'none');">
      🤖 <?= $mode === 'ai' ? 'Hide' : 'Show' ?> AI generator
    </button>
  </div>

  <div class="blog-grid">
    <!-- Main content -->
    <div>
      <div class="card">
        <label>Title</label>
        <input class="input" name="title" id="f_title" required value="<?= e($post['title'] ?? '') ?>">

        <label>Slug <span class="muted">(auto from title if blank)</span></label>
        <input class="input" name="slug" id="f_slug" value="<?= e($post['slug'] ?? '') ?>">

        <label>Excerpt <span class="muted">(shown on the listing card)</span></label>
        <textarea class="input autosize" name="excerpt" id="f_excerpt" rows="2"><?= e($post['excerpt'] ?? '') ?></textarea>

        <label>Content <span class="muted">(Markdown or HTML)</span></label>
        <textarea class="input" name="content" id="f_content" rows="18" style="min-height:360px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;"><?= e($post['content'] ?? '') ?></textarea>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px;">🔍 SEO</h3>
        <label>SEO title</label>
        <input class="input" name="seo_title" id="f_seo_title" maxlength="255" value="<?= e($post['seo_title'] ?? '') ?>">

        <label>Meta description</label>
        <textarea class="input autosize" name="meta_description" id="f_meta_description" rows="2" maxlength="320"><?= e($post['meta_description'] ?? '') ?></textarea>

        <label>Image prompt <span class="muted">(for future image generation)</span></label>
        <textarea class="input autosize" name="image_prompt" id="f_image_prompt" rows="2"><?= e($post['image_prompt'] ?? '') ?></textarea>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px;">📣 Social captions</h3>
        <label>Facebook caption</label>
        <textarea class="input autosize" name="facebook_caption" id="f_facebook_caption" rows="3"><?= e($post['facebook_caption'] ?? '') ?></textarea>

        <label>LinkedIn caption</label>
        <textarea class="input autosize" name="linkedin_caption" id="f_linkedin_caption" rows="3"><?= e($post['linkedin_caption'] ?? '') ?></textarea>

        <label>WhatsApp broadcast text</label>
        <textarea class="input autosize" name="whatsapp_text" id="f_whatsapp_text" rows="3"><?= e($post['whatsapp_text'] ?? '') ?></textarea>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px;">📰 Source</h3>
        <label>Source URL <span class="muted">(shown as attribution)</span></label>
        <input class="input" name="source_url" id="f_source_url" value="<?= e($post['source_url'] ?? '') ?>">

        <label>Source summary</label>
        <textarea class="input autosize" name="source_summary" id="f_source_summary" rows="2"><?= e($post['source_summary'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="side-card">
        <h4>Publish</h4>
        <label>Status</label>
        <select class="input" name="status">
          <?php foreach (['draft'=>'Draft','pending_review'=>'Pending review','published'=>'Published','archived'=>'Archived'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($post['status'] ?? 'draft')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>

        <label>Publish date <span class="muted">(optional)</span></label>
        <input class="input" type="datetime-local" name="published_at"
               value="<?= e(!empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '') ?>">

        <button class="btn primary block" type="submit" style="margin-top:14px;width:100%;">Save</button>
        <?php if ($post && $post['status'] === 'published'): ?>
          <a class="btn outline block" target="_blank" rel="noopener" style="margin-top:8px;width:100%;" href="/blog/<?= e($post['slug']) ?>">View public post →</a>
        <?php endif; ?>
      </div>

      <div class="side-card">
        <h4>Category</h4>
        <select class="input" name="category_id" id="f_category_id">
          <option value="0">(none)</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($post['category_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="muted" style="font-size:12px;margin-top:6px;">
          <a href="/admin/blog-categories.php">Manage categories →</a>
        </p>
      </div>

      <div class="side-card">
        <h4>Language</h4>
        <select class="input" name="language">
          <option value="en" <?= ($post['language'] ?? 'en')==='en'?'selected':'' ?>>English</option>
          <option value="zh" <?= ($post['language'] ?? '')==='zh'?'selected':'' ?>>中文</option>
          <option value="ms" <?= ($post['language'] ?? '')==='ms'?'selected':'' ?>>Bahasa Malaysia</option>
        </select>
      </div>

      <div class="side-card">
        <h4>Tags <span class="muted" style="text-transform:none;">(comma-separated)</span></h4>
        <input class="input" name="tags" id="f_tags" placeholder="crm, dealer portal, export"
               value="<?= e($tag_csv) ?>">
      </div>

      <div class="side-card">
        <h4>Featured image</h4>
        <?php if (!empty($post['featured_image'])): ?>
          <img src="<?= e($post['featured_image']) ?>" alt=""
               style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:8px;margin-bottom:8px;background:#eee;">
          <label style="display:flex;align-items:center;gap:6px;margin:0 0 6px;">
            <input type="checkbox" name="remove_featured" value="1"> Remove
          </label>
        <?php endif; ?>
        <input type="file" name="featured_image" accept="image/*">
        <p class="muted" style="font-size:12px;margin-top:6px;">
          Recommended 1600×900. JPG/PNG/WEBP up to 5 MB.
        </p>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  var titleEl = document.getElementById('f_title');
  var slugEl  = document.getElementById('f_slug');
  var slugDirty = slugEl.value.trim() !== '';

  slugEl.addEventListener('input', function () { slugDirty = true; });
  titleEl.addEventListener('input', function () {
    if (slugDirty) return;
    slugEl.value = titleEl.value.toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .substring(0, 190);
  });

  // AI generate
  var goBtn  = document.getElementById('ai_go');
  var status = document.getElementById('ai_status');

  function setField(id, val) {
    var el = document.getElementById(id);
    if (!el || val === undefined || val === null) return;
    el.value = val;
  }
  function pickCategoryByName(name) {
    if (!name) return;
    var sel = document.getElementById('f_category_id');
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].text.trim().toLowerCase() === String(name).trim().toLowerCase()) {
        sel.selectedIndex = i;
        return;
      }
    }
  }
  goBtn.addEventListener('click', function () {
    var input = document.getElementById('ai_input').value.trim();
    var url   = document.getElementById('ai_source_url').value.trim();
    if (!input && !url) { alert('Enter a topic, URL, or notes first.'); return; }
    goBtn.disabled = true;
    status.textContent = 'Calling Claude — this can take 20-40 seconds…';
    var body = new FormData();
    body.append('csrf_token', <?= json_encode(csrf_token()) ?>);
    body.append('input_text',   input);
    body.append('source_url',   url);
    body.append('language',     document.getElementById('ai_language').value);
    body.append('category_hint', document.getElementById('ai_category').value);
    body.append('angle',         document.getElementById('ai_angle').value);
    body.append('cta',           document.getElementById('ai_cta').value);

    fetch('/admin/blog-ai.php', { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { status.textContent = '❌ ' + (j.error || 'AI failed'); return; }
        var d = j.data || {};
        setField('f_title',            d.title);
        setField('f_slug',             d.slug);
        setField('f_seo_title',        d.seo_title);
        setField('f_meta_description', d.meta_description);
        setField('f_excerpt',          d.excerpt);
        setField('f_content',          d.content);
        setField('f_source_summary',   d.source_summary);
        setField('f_facebook_caption', d.facebook_caption);
        setField('f_linkedin_caption', d.linkedin_caption);
        setField('f_whatsapp_text',    d.whatsapp_text);
        setField('f_image_prompt',     d.image_prompt);
        setField('f_source_url',       url);
        if (Array.isArray(d.suggested_tags) && d.suggested_tags.length) {
          setField('f_tags', d.suggested_tags.join(', '));
        }
        pickCategoryByName(d.suggested_category);
        status.textContent = '✓ Draft loaded — review, edit, then save.';
      })
      .catch(function (e) { status.textContent = '❌ Network error'; })
      .finally(function () { goBtn.disabled = false; });
  });
})();
</script>

<?php admin_layout_close(); ?>
