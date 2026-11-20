<?php
/**
 * AI product-tour chatbot widget.
 *
 * Injected from inc/footer.php on tenant pages. Self-contained markup,
 * styles and JS — talks to /chat.php on the same host.
 *
 * Expects $company in scope.
 */
$primary = htmlspecialchars($company['theme_color']           ?: '#111827', ENT_QUOTES);
$accent  = htmlspecialchars($company['theme_secondary_color'] ?: '#f59e0b', ENT_QUOTES);
?>
<style>
.cbot-fab {
  position: fixed; left: 16px; bottom: 16px; z-index: 60;
  width: 56px; height: 56px; border-radius: 999px;
  background: <?= $primary ?>; color: <?= $accent ?>;
  border: 0; cursor: pointer;
  box-shadow: 0 6px 18px rgba(0,0,0,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; transition: transform .15s, box-shadow .15s;
}
.cbot-fab:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(0,0,0,.3); }
.cbot-fab .ping {
  position:absolute; top:6px; right:6px; width:10px; height:10px; border-radius:50%;
  background: <?= $accent ?>;
  box-shadow: 0 0 0 0 rgba(245,158,11,.6);
  animation: cbot-ping 2s infinite;
}
@keyframes cbot-ping {
  0%   { box-shadow: 0 0 0 0 rgba(245,158,11,.6); }
  70%  { box-shadow: 0 0 0 10px rgba(245,158,11,0); }
  100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
}

.cbot-panel {
  position: fixed; left: 16px; bottom: 16px; z-index: 65;
  width: 360px; max-width: calc(100vw - 32px);
  height: 540px; max-height: calc(100vh - 32px);
  background: #fff; border-radius: 16px;
  box-shadow: 0 18px 48px rgba(0,0,0,.28);
  display: none; flex-direction: column; overflow: hidden;
  font-family: inherit;
}
.cbot-panel.open { display: flex; }
@media (max-width: 480px) {
  .cbot-panel { left: 8px; right: 8px; width: auto; bottom: 8px; height: calc(100vh - 16px); border-radius: 14px; }
}

.cbot-head {
  padding: 14px 16px; background: <?= $primary ?>; color:#fff;
  display:flex; align-items:center; gap:10px; flex-shrink: 0;
}
.cbot-head .av { width:34px; height:34px; border-radius:10px; background: <?= $accent ?>; color: <?= $primary ?>;
  display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; }
.cbot-head .meta strong { display:block; line-height:1.1; font-size:14px; }
.cbot-head .meta span   { font-size:11px; opacity:.8; }
.cbot-head .meta .dot { display:inline-block; width:6px; height:6px; border-radius:50%; background:#22c55e; margin-right: 4px; }
.cbot-head .x {
  margin-left:auto; background: transparent; color:#fff; border:0; cursor:pointer;
  padding: 6px 10px; font-size: 18px; border-radius: 6px; line-height: 1;
}
.cbot-head .x:hover { background: rgba(255,255,255,.1); }

.cbot-body {
  flex: 1; overflow-y: auto; padding: 14px; background:#f9fafb;
  display:flex; flex-direction:column; gap: 10px;
}

.cbot-msg { max-width: 86%; padding: 10px 12px; border-radius: 12px; font-size: 14px; line-height: 1.45; word-wrap: break-word; }
.cbot-msg.user { align-self: flex-end; background: <?= $primary ?>; color:#fff; border-bottom-right-radius: 4px; }
.cbot-msg.bot  { align-self: flex-start; background: #fff; color:#111; border:1px solid #e5e7eb; border-bottom-left-radius: 4px; }

.cbot-products { display:grid; gap:8px; align-self: stretch; }
.cbot-product {
  display:grid; grid-template-columns: 56px 1fr auto; gap:10px; align-items:center;
  background:#fff; padding:8px; border-radius:10px; border:1px solid #e5e7eb;
  text-decoration:none; color:#111;
}
.cbot-product:hover { border-color: <?= $primary ?>; }
.cbot-product .img { width:56px; height:56px; border-radius:8px; background:#eee; overflow:hidden;
  display:flex; align-items:center; justify-content:center; font-size:20px; color:#9ca3af; }
.cbot-product .img img { width:100%; height:100%; object-fit:cover; }
.cbot-product .info { min-width: 0; }
.cbot-product .info strong { display:block; font-size:13px; line-height:1.2; }
.cbot-product .info span   { display:block; font-size:11px; color:#6b7280; }
.cbot-product .info .price { font-size:13px; color: <?= $primary ?>; font-weight:700; margin-top: 2px; }
.cbot-product .star { font-size: 12px; color: <?= $accent ?>; }

.cbot-suggestions { display:flex; flex-wrap:wrap; gap:6px; margin-top: 6px; }
.cbot-chip {
  background:#fff; border:1px solid #d1d5db; border-radius:999px;
  padding:6px 12px; font-size: 12px; cursor: pointer;
  color:#111; font: inherit;
}
.cbot-chip:hover { background: <?= $primary ?>; color:#fff; border-color: <?= $primary ?>; }

.cbot-foot { padding: 10px 12px; background:#fff; border-top:1px solid #e5e7eb; flex-shrink:0; }
.cbot-input {
  display:flex; gap:8px; align-items:center;
  background:#f3f4f6; border-radius:999px; padding: 4px 4px 4px 14px;
}
.cbot-input input {
  flex: 1; border: 0; background: transparent; font: inherit; padding: 8px 0; min-width: 0;
}
.cbot-input input:focus { outline: 0; }
.cbot-input button {
  background: <?= $primary ?>; color:#fff; border:0; border-radius:999px;
  width:36px; height:36px; cursor:pointer; font-size:16px;
}
.cbot-input button:disabled { opacity: .5; cursor: not-allowed; }

.cbot-typing { display:inline-flex; gap:4px; align-items:center; }
.cbot-typing span {
  width:6px; height:6px; border-radius:50%; background:#9ca3af;
  animation: cbot-bounce 1s infinite ease-in-out;
}
.cbot-typing span:nth-child(2) { animation-delay: .15s; }
.cbot-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes cbot-bounce {
  0%, 100% { transform: translateY(0); opacity: .4; }
  50%      { transform: translateY(-3px); opacity: 1; }
}
</style>

<button type="button" class="cbot-fab" id="cbot-fab" aria-label="Open product assistant">
  🤖<span class="ping"></span>
</button>

<aside class="cbot-panel" id="cbot-panel" role="dialog" aria-label="Product assistant">
  <header class="cbot-head">
    <div class="av">AI</div>
    <div class="meta">
      <strong>Product Assistant</strong>
      <span><span class="dot"></span>Online · powered by AICAP</span>
    </div>
    <button type="button" class="x" id="cbot-close" aria-label="Close">✕</button>
  </header>
  <div class="cbot-body" id="cbot-body" aria-live="polite"></div>
  <div class="cbot-foot">
    <form class="cbot-input" id="cbot-form" autocomplete="off">
      <input type="text" id="cbot-text" placeholder="e.g. sofa under RM 3000" maxlength="200" aria-label="Message">
      <button type="submit" id="cbot-send" aria-label="Send">➤</button>
    </form>
  </div>
</aside>

<script>
(function () {
  var fab    = document.getElementById('cbot-fab');
  var panel  = document.getElementById('cbot-panel');
  var close  = document.getElementById('cbot-close');
  var body   = document.getElementById('cbot-body');
  var form   = document.getElementById('cbot-form');
  var input  = document.getElementById('cbot-text');
  var send   = document.getElementById('cbot-send');
  if (!fab) return;

  var initialized = false;

  function open  () { panel.classList.add('open'); fab.style.display = 'none'; if (!initialized) { initialized = true; greet(); } setTimeout(function(){input.focus();}, 100); }
  function close_() { panel.classList.remove('open'); fab.style.display = 'flex'; }
  fab.addEventListener('click', open);
  close.addEventListener('click', close_);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close_(); });

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];
    });
  }

  function bubble (cls, html) {
    var d = document.createElement('div');
    d.className = 'cbot-msg ' + cls;
    d.innerHTML = html;
    body.appendChild(d);
    body.scrollTop = body.scrollHeight;
    return d;
  }

  function suggestions (chips) {
    if (!chips || !chips.length) return;
    var wrap = document.createElement('div');
    wrap.className = 'cbot-suggestions';
    chips.forEach(function (c) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'cbot-chip';
      b.textContent = c;
      b.addEventListener('click', function () { ask(c); });
      wrap.appendChild(b);
    });
    body.appendChild(wrap);
    body.scrollTop = body.scrollHeight;
  }

  function products (list) {
    if (!list || !list.length) return;
    var wrap = document.createElement('div');
    wrap.className = 'cbot-products';
    list.forEach(function (p) {
      var a = document.createElement('a');
      a.className = 'cbot-product';
      a.href = p.url;
      a.innerHTML =
        '<div class="img">' + (p.img ? '<img src="' + escapeHtml(p.img) + '" alt="" loading="lazy">' : '🛋️') + '</div>' +
        '<div class="info">' +
          '<strong>' + escapeHtml(p.name) + (p.featured ? ' <span class="star">★</span>' : '') + '</strong>' +
          (p.cat   ? '<span>' + escapeHtml(p.cat) + '</span>' : '') +
          (p.price ? '<div class="price">' + escapeHtml(p.price) + '</div>' : '') +
        '</div>' +
        '<span style="color:#9ca3af;font-size:18px;">›</span>';
      wrap.appendChild(a);
    });
    body.appendChild(wrap);
    body.scrollTop = body.scrollHeight;
  }

  function typing (on) {
    var t = document.getElementById('cbot-typing');
    if (on && !t) {
      t = document.createElement('div');
      t.id = 'cbot-typing';
      t.className = 'cbot-msg bot';
      t.innerHTML = '<span class="cbot-typing"><span></span><span></span><span></span></span>';
      body.appendChild(t);
      body.scrollTop = body.scrollHeight;
    } else if (!on && t) {
      t.remove();
    }
  }

  function call (msg, cb) {
    var fd = new FormData();
    if (msg) fd.append('message', msg);
    fetch('/chat.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) { cb(null, d); })
      .catch(function (e) { cb(e); });
  }

  function greet () {
    typing(true);
    call('', function (err, d) {
      typing(false);
      if (err || !d) { bubble('bot', 'Sorry, I\'m having trouble right now.'); return; }
      bubble('bot', escapeHtml(d.greeting || 'Hi there!'));
      suggestions(d.suggestions);
    });
  }

  function ask (msg) {
    var clean = String(msg || '').trim();
    if (!clean) return;
    bubble('user', escapeHtml(clean));
    input.value = '';
    send.disabled = true;
    typing(true);
    call(clean, function (err, d) {
      typing(false);
      send.disabled = false;
      if (err || !d) { bubble('bot', 'Sorry, I\'m having trouble right now.'); return; }
      bubble('bot', escapeHtml(d.reply || ''));
      products(d.products);
      suggestions(d.suggestions);
    });
  }

  form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
})();
</script>
