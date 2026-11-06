// THUGSred Dorkify - scope substitution, filtering, highlighting, builder,
// contents scrollspy, copy/export. No network calls; state lives in the URL
// and localStorage only.
(() => {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (_) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (_) { /* private mode */ } },
  };
  const esc = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const googleUrl = (q) => `https://www.google.com/search?q=${encodeURIComponent(q)}&filter=0`;

  // ---------- scope ----------
  const inD = $('#d'), inO = $('#o'), inN = $('#n'), inU = $('#u'), inF = $('#f');
  const globalSearch = $('#global-search');
  const applyGlobal = () => [inD, inO, inN, inU].forEach(el => { el.disabled = globalSearch.checked; });

  function normDomain(v) {
    return v.trim().toLowerCase()
      .replace(/^[a-z]+:\/\//, '')   // scheme
      .replace(/[/?#].*$/, '')       // path, query
      .replace(/:\d+$/, '')          // port
      .replace(/^\*\./, '')
      .replace(/^www\./, '')
      .replace(/^\./, '')
      .replace(/\.$/, '');
  }
  const scope = () => {
    if (globalSearch.checked) return {d:'', o:'', name:'', user:''};
    const d = normDomain(inD.value);
    const o = inO.value.trim().replace(/"/g, '') || (d ? d.split('.')[0] : '');
    const name = inN.value.trim().replace(/"/g, '');
    const user = inU.value.trim().replace(/^@/, '').replace(/["\s]/g, '');
    return { d, o, name, user };
  };

  // Placeholders that can't be filled render as ‹domain› / ‹org› / ‹person› /
  // ‹username› chips, and the dork's search button is disabled.
  function resolve(tpl, globalTpl = '') {
    if (globalSearch.checked) return {q:globalTpl || '‹specific target required›', missing:!globalTpl};
    const { d, o, name, user } = scope();
    let missing = false;
    const out = tpl
      .replace(/\{site\}/g, () => (d ? `site:${d}` : (missing = true, 'site:‹domain›')))
      .replace(/\{d\}/g, () => (d || (missing = true, '‹domain›')))
      .replace(/\{org\}/g, () => (o || (missing = true, '‹org›')))
      .replace(/\{name\}/g, () => (name || (missing = true, '‹person›')))
      .replace(/\{user\}/g, () => (user || (missing = true, '‹username›')));
    return { q: out, missing };
  }

  // ---------- highlighting ----------
  const TOKEN = /(-?[A-Za-z_]+:"[^"]*"?)|(-?[A-Za-z_]+:[^\s()]*)|(-?"[^"]*"?)|([()])|(\s+)|([^\s()]+)/g;

  function highlight(q) {
    let html = '';
    for (const m of q.matchAll(TOKEN)) {
      const [tok, opStr, opVal, quoted, paren, space] = m;
      if (space) { html += tok; continue; }
      if (paren) { html += `<span class="t-paren">${tok}</span>`; continue; }
      if (opStr || opVal) {
        const [, neg, op, val] = tok.match(/^(-?)([A-Za-z_]+:)(.*)$/);
        html += (neg ? '<span class="t-neg">-</span>' : '')
          + `<span class="${neg ? 't-neg' : 't-op'}">${esc(op)}</span>`
          + (val ? `<span class="${val.startsWith('"') ? 't-str' : 't-val'}">${esc(val)}</span>` : '');
        continue;
      }
      if (quoted) {
        html += tok.startsWith('-')
          ? `<span class="t-neg">-</span><span class="t-str">${esc(tok.slice(1))}</span>`
          : `<span class="t-str">${esc(tok)}</span>`;
        continue;
      }
      if (tok === 'OR' || tok === '|' || /^AROUND\(\d+\)$/.test(tok)) { html += `<span class="t-or">${esc(tok)}</span>`; continue; }
      if (tok.length > 1 && tok.startsWith('-')) { html += `<span class="t-neg">${esc(tok)}</span>`; continue; }
      html += esc(tok);
    }
    return html.replace(/‹(\w+)›/g, '<span class="t-ph">$1</span>');
  }

  // Static examples (cheat sheet, patterns, steps) are highlighted once.
  $$('.hl').forEach((el) => { if (!el.closest('.dork')) el.innerHTML = highlight(el.textContent); });

  // ---------- library ----------
  const dorks = $$('.dork');
  const cats = $$('.cat');
  const sevOn = new Set(['crit', 'high', 'med', 'low', 'info']);

  function renderDorks() {
    for (const el of dorks) {
      const { q, missing } = resolve(el.dataset.q, el.dataset.global);
      el._q = missing ? null : q;
      $('.q', el).innerHTML = highlight(q);
      const go = $('.go', el);
      if (missing) {
        go.removeAttribute('href');
        go.setAttribute('aria-disabled', 'true');
        go.title = 'Fill in the scope fields this dork uses first';
      } else {
        go.href = googleUrl(q);
        go.removeAttribute('aria-disabled');
        go.title = 'Open in Google (new tab)';
      }
    }
    // "Beyond Google" links
    const { d, user } = scope();
    for (const a of $$('.tool-link')) {
      const tpl = a.dataset.href;
      const miss = (tpl.includes('{d}') && !d) || (tpl.includes('{user}') && !user);
      const url = tpl.replace(/\{d\}/g, encodeURIComponent(d)).replace(/\{user\}/g, encodeURIComponent(user));
      $('.tool-url', a).textContent = miss ? tpl : url;
      if (miss) { a.removeAttribute('href'); a.setAttribute('aria-disabled', 'true'); }
      else { a.href = url; a.removeAttribute('aria-disabled'); }
    }
  }

  function applyFilter() {
    const terms = inF.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
    let shown = 0;
    for (const el of dorks) {
      const text = el.dataset.text + ' ' + (el._q || '').toLowerCase();
      const ok = (!globalSearch.checked || Boolean(el.dataset.global)) && sevOn.has(el.dataset.sev) && terms.every((t) => text.includes(t));
      el.hidden = !ok;
      if (ok) shown++;
    }
    for (const c of cats) {
      const n = $$('.dork:not([hidden])', c).length;
      c.hidden = n === 0;
      $('.cat-count', c).textContent = n;
      const chip = $(`[data-chip="${c.dataset.cat}"]`);
      if (chip) { chip.classList.toggle('dim', n === 0); $('small', chip).textContent = n; }
    }
    $('#shown').textContent = shown;
    $('#empty').hidden = shown !== 0;
  }

  const SCOPE_FIELDS = { d: inD, o: inO, n: inN, u: inU };

  function saveScope() {
    const url = new URL(location.href);
    store.set('dorkify.global', globalSearch.checked ? '1' : '0');
    globalSearch.checked ? url.searchParams.set('global', '1') : url.searchParams.delete('global');
    for (const [k, el] of Object.entries(SCOPE_FIELDS)) {
      const v = globalSearch.checked ? '' : k === 'd' ? scope().d : el.value.trim();
      store.set(`dorkify.${k}`, el.value.trim());
      v ? url.searchParams.set(k, v) : url.searchParams.delete(k);
    }
    history.replaceState(null, '', url);
  }

  // ---------- clipboard (works on plain-http LAN too) ----------
  const toastEl = $('#toast');
  let toastTimer;
  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove('show'), 1400);
  }
  async function copy(text, what = 'copied') {
    try {
      if (window.isSecureContext && navigator.clipboard) await navigator.clipboard.writeText(text);
      else {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
      }
      toast(what);
    } catch (_) {
      toast('copy failed - select and copy manually');
    }
  }

  function needScope() {
    toast(globalSearch.checked ? 'add a search criterion or turn off global mode' : 'set a domain first');
    inD.focus();
  }

  // ---------- builder ----------
  const qEl = $('#q'), qhl = $('#qhl'), qmeta = $('#qmeta'), qGo = $('#q-go');
  const list = (v) => v.split(',').map((s) => s.trim()).filter(Boolean);
  const quote = (s) => (/[\s:]/.test(s) && !/^".*"$/.test(s) ? `"${s.replace(/"/g, '')}"` : s);
  const group = (items) => (items.length > 1 ? `(${items.join(' OR ')})` : items[0] || '');

  function build() {
    const v = (k) => ($(`[data-b="${k}"]`).value || '').trim();
    const { d } = scope();
    const parts = [];
    const mode = globalSearch.checked ? 'none' : $('input[name="bscope"]:checked').value;
    const dom = d || '‹domain›';
    if (mode === 'domain') parts.push(`site:${dom}`);
    if (mode === 'subs') parts.push(`site:*.${dom} -site:www.${dom}`);
    if (mode === 'else') parts.push(`"${dom}" -site:${dom}`);
    if (v('phrase')) parts.push(`"${v('phrase').replace(/"/g, '')}"`);
    if (v('all')) parts.push(v('all'));
    if (v('any')) parts.push(group(list(v('any')).map(quote)));
    for (const op of ['intitle', 'inurl', 'intext']) {
      if (v(op)) parts.push(group(list(v(op)).map((s) => `${op}:${quote(s)}`)));
    }
    const types = $$('.b-types input:checked').map((c) => `ext:${c.value}`);
    if (types.length) parts.push(group(types));
    list(v('not')).forEach((w) => parts.push(`-${quote(w)}`));
    list(v('notsite')).forEach((s) => parts.push(`-site:${normDomain(s) || s}`));
    if (v('after')) parts.push(`after:${v('after')}`);
    if (v('before')) parts.push(`before:${v('before')}`);
    qEl.value = parts.join(' ');
    renderQuery();
  }

  function renderQuery() {
    const q = qEl.value.trim();
    qhl.innerHTML = q ? highlight(q) : '';
    const words = q ? q.split(/\s+/).length : 0;
    qmeta.textContent = `${words} word${words === 1 ? '' : 's'}${words > 32 ? ' - Google ignores everything after 32' : ''}`;
    qmeta.classList.toggle('over', words > 32);
    const incomplete = !q || q.includes('‹');
    if (incomplete) { qGo.removeAttribute('href'); qGo.setAttribute('aria-disabled', 'true'); }
    else { qGo.href = googleUrl(q); qGo.removeAttribute('aria-disabled'); }
    for (const a of $$('[data-engine]')) {
      if (incomplete) { a.removeAttribute('href'); a.setAttribute('aria-disabled', 'true'); }
      else { a.href = ENGINES[a.dataset.engine](q); a.removeAttribute('aria-disabled'); }
    }
  }

  const ENGINES = {
    bing: (q) => `https://www.bing.com/search?q=${encodeURIComponent(q)}`,
    ddg: (q) => `https://duckduckgo.com/?q=${encodeURIComponent(q)}`,
    brave: (q) => `https://search.brave.com/search?q=${encodeURIComponent(q)}`,
    yandex: (q) => `https://yandex.com/search/?text=${encodeURIComponent(q)}`,
  };

  // Once the box holds hand-edited text (typed, or loaded with "edit"), a
  // domain change no longer rebuilds it; touching the builder fields does.
  let handEdited = false;
  const fromBuilder = event => {
    if (event?.target.name === 'bscope') {
      globalSearch.checked = event.target.value === 'none';
      applyGlobal(); saveScope(); renderDorks(); applyFilter();
    }
    handEdited = false; build();
  };
  $('#bform').addEventListener('submit', (e) => e.preventDefault());
  $('#bform').addEventListener('input', fromBuilder);
  $('#bform').addEventListener('change', fromBuilder);
  qEl.addEventListener('input', () => { handEdited = true; renderQuery(); });
  $('#q-clear').addEventListener('click', () => { $('#bform').reset(); if (globalSearch.checked) $('input[name="bscope"][value="none"]').checked = true; handEdited = false; build(); qEl.focus(); });
  $('#q-copy').addEventListener('click', () => {
    if (!qEl.value.trim()) return;
    if (qEl.value.includes('‹')) return needScope();
    copy(qEl.value.trim(), 'query copied');
  });
  qGo.addEventListener('click', (e) => { if (qGo.getAttribute('aria-disabled')) { e.preventDefault(); if (qEl.value.includes('‹')) needScope(); } });

  // ---------- dork actions ----------
  $('#library').addEventListener('click', (e) => {
    const el = e.target.closest('.dork');
    if (!el) return;
    if (e.target.closest('.go')) {
      if (!el._q) { e.preventDefault(); needScope(); }
      return;
    }
    if (e.target.closest('.copy') || e.target.closest('.q')) {
      if (!el._q) return needScope();
      copy(el._q, 'dork copied');
    } else if (e.target.closest('.edit')) {
      qEl.value = resolve(el.dataset.q, el.dataset.global).q;
      handEdited = true;
      renderQuery();
      $('#builder').scrollIntoView();
      qEl.focus();
    }
  });

  document.addEventListener('click', (e) => {
    const a = e.target.closest('.tool-link[aria-disabled], [data-engine][aria-disabled]');
    if (!a) return;
    e.preventDefault();
    if (a.matches('.tool-link')) a.dataset.href.includes('{user}') ? (toast('set a username first'), inU.focus()) : needScope();
  });

  // ---------- export ----------
  function exportText() {
    const { d, o, name, user } = scope();
    const who = [globalSearch.checked ? 'global search' : d || 'no domain set', o, name, user && `@${user}`].filter(Boolean).join(' / ');
    const lines = [`# THUGSred Dorkify - ${who} - ${new Date().toISOString().slice(0, 10)}`, ''];
    for (const c of cats) {
      const shown = $$('.dork:not([hidden])', c);
      if (!shown.length) continue;
      lines.push(`## ${c.dataset.title}`);
      for (const el of shown) lines.push(`[${el.dataset.sev}] ${$('h3', el).textContent}`, resolve(el.dataset.q, el.dataset.global).q, '');
    }
    return lines.join('\n');
  }
  $('#copy-all').addEventListener('click', () => copy(exportText(), `${$('#shown').textContent} dorks copied`));
  $('#dl-all').addEventListener('click', () => {
    const blob = new Blob([exportText()], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `dorkify-${scope().d || 'template'}.txt`;
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  });

  // ---------- severity toggles ----------
  $$('.sev-toggle').forEach((b) => b.addEventListener('click', () => {
    const on = b.getAttribute('aria-pressed') !== 'true';
    b.setAttribute('aria-pressed', String(on));
    on ? sevOn.add(b.dataset.sev) : sevOn.delete(b.dataset.sev);
    applyFilter();
  }));

  // ---------- contents sidebar: highlight the section being read ----------
  const tocLinks = new Map($$('[data-toc]').map((a) => [a.dataset.toc, a]));
  const pageOrder = [...tocLinks.keys()].filter((id) => document.getElementById(id))
    .sort((a, b) => (document.getElementById(a).compareDocumentPosition(document.getElementById(b)) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
  const visible = new Set();
  const spy = new IntersectionObserver((entries) => {
    for (const en of entries) en.isIntersecting ? visible.add(en.target.id) : visible.delete(en.target.id);
    // first section (in page order) inside the reading band wins
    const current = pageOrder.find((id) => visible.has(id));
    if (!current) return;
    tocLinks.forEach((a, id) => a.classList.toggle('active', id === current));
    const active = tocLinks.get(current);
    const toc = $('#toc');
    if (getComputedStyle(toc).position === 'sticky') {
      const top = active.offsetTop - toc.scrollTop;
      if (top < 0 || top > toc.clientHeight - active.offsetHeight) toc.scrollTop = active.offsetTop - toc.clientHeight / 2;
    }
  }, { rootMargin: '-20% 0px -70% 0px' });
  tocLinks.forEach((_, id) => { const el = document.getElementById(id); if (el) spy.observe(el); });

  // ---------- hero: the mascot's increasingly ridiculous searches ----------
  const SILLY = [
    'site:my-fridge.local intitle:"index of" leftovers',
    'intext:"password" filetype:post-it',
    'inurl:admin intitle:"please do not hack me"',
    '"index of /" "thesis_FINAL_final_v7(2).docx"',
    'ext:env "PASSWORD=hunter2"',
    'ext:sql "INSERT INTO cats" "fluffy"',
    'intitle:"area 51" ext:xlsx "snack budget"',
    '"BEGIN RSA PRIVATE KEY" site:my-diary.blog',
    'cache:yesterday   (RIP, 2024)',
    '"admin" AROUND(3) "admin" -"we fixed it"',
    'site:localhost intitle:"It works!"',
    'filetype:pdf "do not distribute" "secret cookie recipe"',
    'intitle:"index of" ".git" "please ignore"',
    '"how to exit vim" after:1991-01-01',
  ];
  const typerEl = $('#typer');
  if (typerEl) {
    let i = Math.floor(Math.random() * SILLY.length);
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) typerEl.textContent = SILLY[i];
    else {
      let pos = 0, deleting = false;
      const step = () => {
        const text = SILLY[i];
        if (!deleting) {
          typerEl.textContent = text.slice(0, ++pos);
          if (pos === text.length) { deleting = true; return setTimeout(step, 1900); }
          return setTimeout(step, 38 + Math.random() * 60);
        }
        typerEl.textContent = text.slice(0, --pos);
        if (pos === 0) { deleting = false; i = (i + 1) % SILLY.length; return setTimeout(step, 450); }
        setTimeout(step, 14);
      };
      step();
    }
  }

  // ---------- wire up ----------
  const onScope = () => { saveScope(); renderDorks(); applyFilter(); if (!handEdited) build(); };
  Object.values(SCOPE_FIELDS).forEach((el) => el.addEventListener('input', onScope));
  inD.addEventListener('change', () => { inD.value = normDomain(inD.value); onScope(); });
  inF.addEventListener('input', applyFilter);
  globalSearch.addEventListener('change', () => {
    $('input[name="bscope"][value="' + (globalSearch.checked ? 'none' : 'domain') + '"]').checked = true;
    handEdited = false; applyGlobal(); onScope();
  });

  const params = new URLSearchParams(location.search);
  globalSearch.checked = params.has('global') ? params.get('global') === '1' : store.get('dorkify.global') === '1';
  if (globalSearch.checked) $('input[name="bscope"][value="none"]').checked = true;
  applyGlobal();
  for (const [k, el] of Object.entries(SCOPE_FIELDS)) el.value = params.get(k) || store.get(`dorkify.${k}`) || '';
  renderDorks();
  applyFilter();
  build();
})();
