<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/ai.php';

$ping = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) input('action', 'save');

    if ($action === 'save') {
        $provider = trim((string) input('provider', '')) ?: null;
        $model    = trim((string) input('model', '')) ?: null;
        $key_in   = trim((string) input('api_key', ''));

        // Only overwrite the key if a new value (not a placeholder) was sent
        if ($key_in !== '' && strpos($key_in, '••••') === false) {
            set_platform_setting('ai_api_key', $key_in);
        }
        set_platform_setting('ai_provider', $provider);
        set_platform_setting('ai_model',    $model);

        flash_set('success', 'AI settings saved.');
        redirect('/admin/ai-settings.php');
    }

    if ($action === 'clear_key') {
        set_platform_setting('ai_api_key', '');
        flash_set('success', 'API key cleared.');
        redirect('/admin/ai-settings.php');
    }

    if ($action === 'test') {
        $ping = ai_ping();
    }
}

$current_provider = (string) platform_setting('ai_provider', '');
$current_model    = (string) platform_setting('ai_model', AI_DEFAULT_MODEL);
$current_key      = (string) platform_setting('ai_api_key', '');
$key_mask         = $current_key !== ''
    ? '••••••••••••' . substr($current_key, max(0, strlen($current_key) - 6))
    : '';
$enabled          = ai_enabled();

admin_layout_open('AI Settings');
?>
<div class="card">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
    <h3 style="margin:0;">Chatbot AI</h3>
    <span class="badge <?= $enabled ? 'green' : 'red' ?>">
      <?= $enabled ? 'Enabled' : 'Disabled (rule-based fallback)' ?>
    </span>
  </div>
  <p class="muted" style="margin:0 0 14px;">
    The tenant chatbot at <code>/chat.php</code> uses Claude when configured.
    On any failure (no key, network error, malformed reply) it silently falls
    back to the keyword/price rule-based recommender so the widget keeps working.
  </p>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <div class="row">
      <div class="col">
        <label>Provider</label>
        <select class="input" name="provider">
          <option value=""           <?= $current_provider === '' ? 'selected' : '' ?>>Off (rule-based only)</option>
          <option value="anthropic"  <?= $current_provider === 'anthropic' ? 'selected' : '' ?>>Anthropic (Claude)</option>
        </select>
      </div>
      <div class="col">
        <label>Model</label>
        <select class="input" name="model">
          <option value="claude-haiku-4-5"     <?= $current_model === 'claude-haiku-4-5' ? 'selected' : '' ?>>claude-haiku-4-5 — fast &amp; cheap (recommended)</option>
          <option value="claude-sonnet-4-6"    <?= $current_model === 'claude-sonnet-4-6' ? 'selected' : '' ?>>claude-sonnet-4-6 — balanced</option>
          <option value="claude-opus-4-7"      <?= $current_model === 'claude-opus-4-7' ? 'selected' : '' ?>>claude-opus-4-7 — most capable</option>
        </select>
      </div>
    </div>

    <label>API key</label>
    <div class="row">
      <div class="col" style="flex:2;">
        <input class="input" name="api_key" type="text" autocomplete="off" spellcheck="false"
               placeholder="sk-ant-api03-..."
               value="<?= e($key_mask) ?>">
        <p class="muted" style="margin-top:6px;font-size:12px;">
          Paste your Anthropic key. We mask it on save — type a new key to replace,
          or leave the placeholder dots alone to keep the existing key.
          Get a key at <a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>.
        </p>
      </div>
      <div class="col" style="flex:0 0 auto;align-self:flex-end;">
        <?php if ($current_key): ?>
          <button class="btn outline" type="submit"
                  onclick="this.form.elements['action'].value='clear_key';return confirm('Remove the stored API key?');">
            Clear key
          </button>
        <?php endif; ?>
      </div>
    </div>

    <p style="margin-top:14px;display:flex;gap:8px;">
      <button class="btn primary" type="submit">Save settings</button>
      <button class="btn outline" type="submit"
              onclick="this.form.elements['action'].value='test';">
        Test connection
      </button>
    </p>
  </form>

  <?php if ($ping): ?>
    <div class="alert <?= $ping['ok'] ? 'success' : 'error' ?>" style="margin-top:14px;">
      <?= $ping['ok'] ? '✅' : '❌' ?> <?= e($ping['message']) ?>
    </div>
  <?php endif; ?>
</div>

<div class="card" style="background:#f9fafb;border:1px dashed #e5e7eb;">
  <h4 style="margin:0 0 8px;">How it works</h4>
  <ol class="muted" style="margin:0;padding-left:20px;font-size:13px;line-height:1.7;">
    <li>Tenant visitor opens the 🤖 widget on their tenant site and sends a message.</li>
    <li><code>/chat.php</code> loads the tenant's catalog (up to 120 products) and asks Claude
        to pick up to 6 matching ids and write a friendly reply.</li>
    <li>The catalog is sent as a <em>cached</em> system prompt (5-min TTL), so subsequent
        queries from the same tenant cost ~10× less in tokens.</li>
    <li>If Claude can't be reached or returns malformed JSON, the request silently
        falls back to the keyword/price rule-based recommender.</li>
    <li>Both paths track <code>chatbot_query</code> in <code>analytics_events</code>.</li>
  </ol>
</div>

<div class="card" style="background:#f9fafb;border:1px dashed #e5e7eb;">
  <h4 style="margin:0 0 8px;">Cost guidance</h4>
  <p class="muted" style="margin:0;font-size:13px;line-height:1.7;">
    <strong>Haiku 4.5</strong> is the right default — well under USD&nbsp;0.001 per chat
    turn for a typical furniture catalog with prompt caching. Move to <strong>Sonnet 4.6</strong>
    if customers ask very nuanced styling questions and you want richer replies.
    <strong>Opus 4.7</strong> is overkill for product recommendations.
  </p>
</div>

<?php admin_layout_close(); ?>
